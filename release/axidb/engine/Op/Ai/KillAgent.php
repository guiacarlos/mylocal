<?php
/**
 * AxiDB - Op\Ai\KillAgent: detiene un agente o todos los agentes.
 *
 * Subsistema: engine/op/ai
 * Responsable: kill switch individual y global. Persiste el cambio de
 *              status a 'killed' y, en modo all=true, levanta el flag
 *              global en `_system/agents/_global.json`. [Fase 6]
 */

namespace Axi\Engine\Op\Ai;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class KillAgent extends Operation
{
    public const OP_NAME = 'ai.kill_agent';

    public function target(string $agentId, bool $all = false): self
    {
        if ($all) {
            $this->params['all'] = true;
        } else {
            $this->params['agent_id'] = $agentId;
        }
        return $this;
    }

    public function validate(): void
    {
        $hasId  = !empty($this->params['agent_id']);
        $hasAll = !empty($this->params['all']);
        if (!$hasId && !$hasAll) {
            throw new AxiException("KillAgent: requiere 'agent_id' o 'all'.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $manager = $engine->getService('agents');
        if ($manager === null) {
            throw new AxiException("KillAgent: servicio agents no disponible.", AxiException::INTERNAL_ERROR);
        }
        if (!empty($this->params['all'])) {
            $n = $manager->killAll();
            return Result::ok(['killed' => $n, 'kill_switch' => true]);
        }
        $ok = $manager->kill((string) $this->params['agent_id']);
        if (!$ok) {
            throw new AxiException(
                "KillAgent: agente '{$this->params['agent_id']}' no existe.",
                AxiException::DOCUMENT_NOT_FOUND
            );
        }
        return Result::ok(['killed' => 1, 'agent_id' => $this->params['agent_id']]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'ai.kill_agent',
            synopsis:    'Axi\\Op\\Ai\\KillAgent() ->target(agent_id) | ->target(\"\", true)',
            description: 'Detiene un agente (status -> killed) y libera budget. Con all=true detiene todos los agentes activos y activa el kill switch global.',
            params: [
                ['name' => 'agent_id', 'type' => 'string', 'required' => false, 'description' => 'Id especifico a matar.'],
                ['name' => 'all',      'type' => 'bool',   'required' => false, 'description' => 'Si true, mata todos los agentes activos.'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Ai\\KillAgent())->target('agent_xyz'));"],
                ['lang' => 'json', 'code' => '{"op":"ai.kill_agent","all":true}'],
                ['lang' => 'cli',  'code' => 'axi ai kill agent_xyz | axi ai kill-all'],
            ],
            errors: [
                ['code' => AxiException::DOCUMENT_NOT_FOUND, 'when' => 'agent_id no existe.'],
                ['code' => AxiException::VALIDATION_FAILED,  'when' => 'ni agent_id ni all.'],
            ],
            related: ['ListAgents', 'RunAgent'],
        );
    }
}
