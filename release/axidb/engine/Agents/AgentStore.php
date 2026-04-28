<?php
/**
 * AxiDB - Agents\AgentStore: persistencia de agentes en _system/agents/.
 *
 * Subsistema: engine/agents
 * Responsable: CRUD de Agents en disco. Cada agente vive en su propio
 *              directorio `_system/agents/<id>/agent.json`. La raiz
 *              `_system/agents/_global.json` mantiene la kill-switch flag.
 */

namespace Axi\Engine\Agents;

use Axi\Engine\AxiException;

final class AgentStore
{
    public function __construct(private string $basePath)
    {
        if (!\is_dir($basePath)) {
            @\mkdir($basePath, 0700, true);
        }
    }

    public function save(Agent $agent): void
    {
        $dir = $this->dirFor($agent->id);
        if (!\is_dir($dir)) {
            \mkdir($dir, 0700, true);
        }
        $agent->updatedAt = \date('c');
        $tmp = $dir . '/agent.json.tmp.' . \bin2hex(\random_bytes(4));
        \file_put_contents($tmp, \json_encode($agent->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        @\chmod($tmp, 0600);
        \rename($tmp, $dir . '/agent.json');
    }

    public function load(string $id): ?Agent
    {
        $path = $this->dirFor($id) . '/agent.json';
        if (!\is_file($path)) {
            return null;
        }
        $data = \json_decode(\file_get_contents($path), true);
        return \is_array($data) ? Agent::fromArray($data) : null;
    }

    public function exists(string $id): bool
    {
        return \is_file($this->dirFor($id) . '/agent.json');
    }

    /** @return Agent[] ordenados por updated_at desc */
    public function listAll(?string $statusFilter = null, ?string $parentFilter = null): array
    {
        if (!\is_dir($this->basePath)) { return []; }
        $agents = [];
        foreach (\scandir($this->basePath) as $entry) {
            if ($entry === '.' || $entry === '..' || $entry[0] === '_') { continue; }
            $a = $this->load($entry);
            if ($a === null) { continue; }
            if ($statusFilter !== null && $a->status !== $statusFilter) { continue; }
            if ($parentFilter !== null && $a->parentId !== $parentFilter) { continue; }
            $agents[] = $a;
        }
        \usort($agents, fn($x, $y) => \strcmp($y->updatedAt, $x->updatedAt));
        return $agents;
    }

    public function delete(string $id): bool
    {
        $dir = $this->dirFor($id);
        if (!\is_dir($dir)) { return false; }
        foreach (\scandir($dir) as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                @\unlink($dir . '/' . $entry);
            }
        }
        return @\rmdir($dir);
    }

    public function killAll(): int
    {
        $n = 0;
        foreach ($this->listAll() as $a) {
            if ($a->isAlive()) {
                $a->status = Agent::STATUS_KILLED;
                $this->save($a);
                $n++;
            }
        }
        $this->setGlobalKillSwitch(true);
        return $n;
    }

    public function setGlobalKillSwitch(bool $on): void
    {
        \file_put_contents(
            $this->basePath . '/_global.json',
            \json_encode(['kill_switch' => $on, 'updated_at' => \date('c')], JSON_PRETTY_PRINT)
        );
    }

    public function isGlobalKillSwitchActive(): bool
    {
        $path = $this->basePath . '/_global.json';
        if (!\is_file($path)) { return false; }
        $data = \json_decode(\file_get_contents($path), true);
        return (bool) ($data['kill_switch'] ?? false);
    }

    public function generateId(): string
    {
        return 'ag_' . \date('YmdHis') . \bin2hex(\random_bytes(3));
    }

    public function dirFor(string $id): string
    {
        $this->validateId($id);
        return $this->basePath . '/' . $id;
    }

    private function validateId(string $id): void
    {
        if (!\preg_match('/^[A-Za-z0-9][A-Za-z0-9_\-]{0,80}$/', $id)) {
            throw new AxiException(
                "AgentStore: id invalido '{$id}' (solo [A-Za-z0-9_-]).",
                AxiException::VALIDATION_FAILED
            );
        }
    }
}
