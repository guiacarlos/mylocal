<?php
/**
 * AxiDB - Op\Ai\ListAgents: vista de agentes vivos.
 *
 * Subsistema: engine/op/ai
 * Responsable: listar agentes con status, role, tools, contadores de budget.
 *              Filtros opcionales por status y parent_id. [Fase 6]
 */

namespace Axi\Engine\Op\Ai;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class ListAgents extends Operation
{
    public const OP_NAME = 'ai.list_agents';

    public function filter(?string $status = null, ?string $parentId = null): self
    {
        if ($status !== null)   { $this->params['status']    = $status; }
        if ($parentId !== null) { $this->params['parent_id'] = $parentId; }
        return $this;
    }

    public function validate(): void
    {
        $allowed = ['idle', 'running', 'waiting', 'errored', 'killed', 'done'];
        if (isset($this->params['status']) && !\in_array($this->params['status'], $allowed, true)) {
            throw new AxiException(
                "ListAgents: status debe ser uno de " . \implode('|', $allowed) . ".",
                AxiException::VALIDATION_FAILED
            );
        }
    }

    public function execute(object $engine): Result
    {
        $manager = $engine->getService('agents');
        if ($manager === null) {
            throw new AxiException("ListAgents: servicio agents no disponible.", AxiException::INTERNAL_ERROR);
        }
        $rows = $manager->listAll(
            $this->params['status']    ?? null,
            $this->params['parent_id'] ?? null
        );
        return Result::ok([
            'agents' => $rows,
            'total'  => \count($rows),
            'kill_switch' => $manager->store->isGlobalKillSwitchActive(),
        ]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'ai.list_agents',
            synopsis:    'Axi\\Op\\Ai\\ListAgents() [->filter(status, parent_id)]',
            description: 'Devuelve la lista de agentes registrados con su estado, tools y contadores de budget. Filtrable por status o parent.',
            params: [
                ['name' => 'status',    'type' => 'string', 'required' => false, 'description' => 'idle|running|waiting|errored|killed|done.'],
                ['name' => 'parent_id', 'type' => 'string', 'required' => false, 'description' => 'Lista solo microagentes de un parent concreto.'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute(new Axi\\Op\\Ai\\ListAgents());"],
                ['lang' => 'json', 'code' => '{"op":"ai.list_agents","status":"running"}'],
                ['lang' => 'cli',  'code' => 'axi ai list-agents [--status running]'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'status no valido.'],
            ],
            related: ['NewAgent', 'KillAgent', 'RunAgent'],
        );
    }
}
