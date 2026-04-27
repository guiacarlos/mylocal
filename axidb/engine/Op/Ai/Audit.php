<?php
/**
 * AxiDB - Op\Ai\Audit: lee las ultimas N entradas del audit log de agentes.
 *
 * Subsistema: engine/op/ai
 * Responsable: exponer el AuditLog al cliente sin tocar disco directo.
 *              Default: ultimas 50 entradas. Filtrable por agent_id.
 */

namespace Axi\Engine\Op\Ai;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Audit extends Operation
{
    public const OP_NAME = 'ai.audit';

    public function tail(int $n = 50, ?string $agentId = null): self
    {
        $this->params['limit'] = $n;
        if ($agentId !== null) {
            $this->params['agent_id'] = $agentId;
        }
        return $this;
    }

    public function validate(): void
    {
        if (isset($this->params['limit']) && (!\is_int($this->params['limit']) || $this->params['limit'] < 1 || $this->params['limit'] > 1000)) {
            throw new AxiException("Audit: 'limit' debe ser entero 1..1000.", AxiException::VALIDATION_FAILED);
        }
        if (isset($this->params['agent_id']) && !\is_string($this->params['agent_id'])) {
            throw new AxiException("Audit: 'agent_id' debe ser string.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $manager = $engine->getService('agents');
        if ($manager === null) {
            throw new AxiException("Audit: servicio agents no disponible.", AxiException::INTERNAL_ERROR);
        }
        $limit = (int) ($this->params['limit'] ?? 50);
        $rows = $manager->audit->tail($limit);
        if (isset($this->params['agent_id'])) {
            $needle = 'agent:' . $this->params['agent_id'];
            $rows = \array_values(\array_filter($rows, fn($r) => ($r['actor'] ?? '') === $needle));
        }
        return Result::ok([
            'entries' => $rows,
            'count'   => \count($rows),
            'path'    => $manager->audit->path(),
        ]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'ai.audit',
            synopsis:    'Axi\\Op\\Ai\\Audit() ->tail(n, agent_id?)',
            description: 'Lee las ultimas N lineas del audit log NDJSON. Cada linea registra una Op invocada por un agente con actor=agent:<id>, params, success, code y duration_ms.',
            params: [
                ['name' => 'limit',    'type' => 'int',    'required' => false, 'default' => 50, 'description' => 'Numero de entradas (1-1000).'],
                ['name' => 'agent_id', 'type' => 'string', 'required' => false, 'description' => 'Filtra entradas de un agente concreto.'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Ai\\Audit())->tail(20));"],
                ['lang' => 'json', 'code' => '{"op":"ai.audit","limit":20}'],
                ['lang' => 'cli',  'code' => 'axi ai audit --limit 20 [--agent ag_xyz]'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'limit fuera de 1..1000.'],
            ],
            related: ['ListAgents', 'RunAgent'],
        );
    }
}
