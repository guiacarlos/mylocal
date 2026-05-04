<?php
/**
 * AxiDB - Agents\Manager: fachada de alto nivel del subsistema agentico.
 *
 * Subsistema: engine/agents
 * Responsable: punto de entrada unico para los Op\Ai\*. Orquesta
 *              AgentStore (persistencia), Toolbox (sandbox), Mailbox
 *              (mensajeria) y AgentKernel (loop think/act).
 *
 *              Las Op del namespace Ai delegan aqui en lugar de tocar
 *              tres servicios sueltos. Asi el motor expone un solo
 *              `agents` y los Ops quedan finos.
 */

namespace Axi\Engine\Agents;

use Axi\Engine\AxiException;

final class Manager
{
    public AgentStore $store;
    public Toolbox    $toolbox;
    public Mailbox    $mailbox;
    public AuditLog   $audit;

    public function __construct(string $basePath, object $engine)
    {
        $this->store   = new AgentStore($basePath);
        $this->mailbox = new Mailbox($basePath);
        // Prefijo "_" para que AgentStore::listAll() lo ignore (ya skipea _global.json).
        $this->audit   = new AuditLog($basePath . '/_audit.log');
        $this->toolbox = new Toolbox($engine, $this->audit);
    }

    /** Crea agente primario y lo persiste. Devuelve el Agent. */
    public function createAgent(
        string $name,
        string $role,
        array  $tools  = [],
        array  $budget = [],
        string $llm    = 'noop'
    ): Agent {
        $agent = new Agent($this->store->generateId(), $name, $role);
        if ($tools !== [])  { $agent->tools  = $tools; }
        if ($budget !== []) { $agent->budget = \array_merge($agent->budget, $budget); }
        $agent->llm = $llm;
        $this->store->save($agent);
        return $agent;
    }

    /** Crea microagente hijo con budget acotado. Profundidad maxima 3 (anti-bisnietos). */
    public function createMicroAgent(string $parentId, string $task, int $maxSteps = 10): Agent
    {
        $parent = $this->store->load($parentId);
        if ($parent === null) {
            throw new AxiException(
                "Manager: parent '{$parentId}' no existe.",
                AxiException::DOCUMENT_NOT_FOUND
            );
        }
        // depth(parent) es la distancia del parent al primario. El nuevo hijo
        // estara a depth(parent)+1. Maximo permitido = 3 (primario, hijo, nieto).
        // Bisnieto (depth=3 desde primario, parent en depth=2) queda forbidden.
        if ($this->depth($parent) >= 2) {
            throw new AxiException(
                "Manager: profundidad maxima 3 alcanzada. Un nieto no puede crear bisnietos.",
                AxiException::FORBIDDEN
            );
        }
        $children = $this->store->listAll(null, $parentId);
        $maxChildren = (int) ($parent->budget['max_children'] ?? 3);
        if (\count($children) >= $maxChildren) {
            throw new AxiException(
                "Manager: parent '{$parentId}' alcanzo max_children={$maxChildren}.",
                AxiException::FORBIDDEN
            );
        }

        $agent = new Agent($this->store->generateId(), "micro-{$parent->name}", $task);
        $agent->parentId  = $parentId;
        $agent->ephemeral = true;
        $agent->tools     = $parent->tools;     // hereda sandbox del parent
        $agent->llm       = $parent->llm;
        $agent->budget    = \array_merge($parent->budget, ['max_steps' => $maxSteps]);
        $this->store->save($agent);
        return $agent;
    }

    /**
     * Lanza el kernel sobre un agente. Si es ephemeral y termina en done,
     * se borra del disco automaticamente.
     */
    public function run(string $agentId, ?string $input = null): array
    {
        $agent = $this->store->load($agentId);
        if ($agent === null) {
            throw new AxiException(
                "Manager: agente '{$agentId}' no existe.",
                AxiException::DOCUMENT_NOT_FOUND
            );
        }
        $kernel = new AgentKernel($this->store, $this->toolbox, $this->mailbox);
        $out = $kernel->run($agent, $input);
        if ($agent->ephemeral && $agent->status === Agent::STATUS_DONE) {
            $this->store->delete($agent->id);
            $out['cleaned_up'] = true;
        }
        return $out;
    }

    /**
     * Atajo "ask": si no se pasa $agentId, crea un agente efimero "ask-bot"
     * con tools de solo lectura, ejecuta el prompt, devuelve answer + observations
     * y se autodestruye. Si se pasa $agentId existente, lo usa.
     */
    public function ask(string $prompt, ?string $agentId = null): array
    {
        $autoCreated = false;
        if ($agentId === null || $agentId === '') {
            $agent = $this->createAgent(
                'ask-bot-' . \substr(\bin2hex(\random_bytes(2)), 0, 4),
                'Eres un agente AxiDB de solo lectura. Responde en una frase y, si necesitas datos, ejecuta la Op pertinente del catalogo.',
                Toolbox::readOnlyTools(),
                ['max_steps' => 4]
            );
            $autoCreated = true;
            $agentId = $agent->id;
        }
        $out = $this->run($agentId, $prompt);
        if ($autoCreated && !($out['cleaned_up'] ?? false)) {
            $this->store->delete($agentId);
            $out['cleaned_up'] = true;
        }
        return $out;
    }

    public function kill(string $agentId): bool
    {
        $a = $this->store->load($agentId);
        if ($a === null) { return false; }
        $a->status = Agent::STATUS_KILLED;
        $this->store->save($a);
        return true;
    }

    public function killAll(): int
    {
        return $this->store->killAll();
    }

    public function attach(string $to, string $subject, string $body, ?string $from = null): array
    {
        if ($this->store->load($to) === null) {
            throw new AxiException(
                "Manager: destinatario '{$to}' no existe.",
                AxiException::DOCUMENT_NOT_FOUND
            );
        }
        $msg = [
            'subject'        => $subject,
            'body'           => $body,
            'from'           => $from ?? 'system',
            'correlation_id' => \bin2hex(\random_bytes(8)),
        ];
        $this->mailbox->deliver($to, $msg);
        if ($from !== null) {
            $this->mailbox->logOutbox($from, \array_merge($msg, ['to' => $to]));
        }
        return $msg;
    }

    public function broadcast(string $pattern, string $message, ?string $from = null): int
    {
        $regex = $this->globToRegex($pattern);
        $n = 0;
        foreach ($this->store->listAll() as $agent) {
            if (\preg_match($regex, $agent->role) || \preg_match($regex, $agent->name)) {
                $this->attach($agent->id, 'broadcast', $message, $from);
                $n++;
            }
        }
        return $n;
    }

    /** Devuelve agentes serializables. */
    public function listAll(?string $status = null, ?string $parent = null): array
    {
        $rows = [];
        foreach ($this->store->listAll($status, $parent) as $a) {
            $rows[] = $a->toArray();
        }
        return $rows;
    }

    public function get(string $agentId): ?array
    {
        $a = $this->store->load($agentId);
        return $a === null ? null : $a->toArray();
    }

    private function depth(Agent $a): int
    {
        $d = 0;
        $cur = $a;
        while ($cur->parentId !== null && $d < 10) {
            $cur = $this->store->load($cur->parentId);
            if ($cur === null) { break; }
            $d++;
        }
        return $d;
    }

    private function globToRegex(string $glob): string
    {
        $re = \preg_quote($glob, '#');
        $re = \strtr($re, ['\*' => '.*', '\?' => '.']);
        return '#^' . $re . '$#i';
    }
}
