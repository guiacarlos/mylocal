<?php
/**
 * AxiDB - Op\Ai\RunAgent: dispara el loop receive -> think -> act -> observe.
 *
 * Subsistema: engine/op/ai
 * Responsable: ejecutar el AgentKernel sobre un agente existente. Si el
 *              agente es ephemeral y termina en done, se autodestruye. [Fase 6]
 */

namespace Axi\Engine\Op\Ai;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class RunAgent extends Operation
{
    public const OP_NAME = 'ai.run_agent';

    public function run(string $agentId, ?string $input = null): self
    {
        $this->params['agent_id'] = $agentId;
        if ($input !== null) {
            $this->params['input'] = $input;
        }
        return $this;
    }

    public function validate(): void
    {
        if (empty($this->params['agent_id']) || !\is_string($this->params['agent_id'])) {
            throw new AxiException("RunAgent: 'agent_id' requerido.", AxiException::VALIDATION_FAILED);
        }
        if (isset($this->params['input']) && !\is_string($this->params['input'])) {
            throw new AxiException("RunAgent: 'input' debe ser string.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $manager = $engine->getService('agents');
        if ($manager === null) {
            throw new AxiException("RunAgent: servicio agents no disponible.", AxiException::INTERNAL_ERROR);
        }
        $out = $manager->run(
            (string) $this->params['agent_id'],
            isset($this->params['input']) ? (string) $this->params['input'] : null
        );
        return Result::ok($out);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'ai.run_agent',
            synopsis:    'Axi\\Op\\Ai\\RunAgent() ->run(agent_id, input?)',
            description: 'Dispara el loop del Kernel sobre el agente. Ejecuta receive -> think -> act -> observe hasta que el agente declara done o agota budget. Devuelve answer + history + status final.',
            params: [
                ['name' => 'agent_id', 'type' => 'string', 'required' => true],
                ['name' => 'input',    'type' => 'string', 'required' => false, 'description' => 'Mensaje inicial. Si se omite, procesa solo el inbox.'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Ai\\RunAgent())\n    ->run('agent_xyz', 'Revisa productos sin imagen'));"],
                ['lang' => 'json', 'code' => '{"op":"ai.run_agent","agent_id":"agent_xyz","input":"..."}'],
                ['lang' => 'cli',  'code' => 'axi ai run agent_xyz "Revisa productos sin imagen"'],
            ],
            errors: [
                ['code' => AxiException::DOCUMENT_NOT_FOUND, 'when' => 'agent_id no existe.'],
                ['code' => AxiException::FORBIDDEN,          'when' => 'Kill switch global activo.'],
                ['code' => AxiException::CONFLICT,           'when' => 'Agente ya esta done/killed/errored.'],
                ['code' => AxiException::VALIDATION_FAILED,  'when' => 'agent_id vacio.'],
            ],
            related: ['NewAgent', 'KillAgent', 'Ask', 'ListAgents'],
        );
    }
}
