<?php
/**
 * AxiDB - Op\Ai\Ask: pregunta one-shot al agente.
 *
 * Subsistema: engine/op/ai
 * Responsable: la API mas accesible. Si no se pasa agent_id, crea un agente
 *              efimero "ask-bot" con tools de solo lectura, ejecuta el prompt,
 *              recupera respuesta + observaciones y se autodestruye. [Fase 6]
 */

namespace Axi\Engine\Op\Ai;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Ask extends Operation
{
    public const OP_NAME = 'ai.ask';

    public function prompt(string $prompt): self
    {
        $this->params['prompt'] = $prompt;
        return $this;
    }

    public function agent(string $agentId): self
    {
        $this->params['agent_id'] = $agentId;
        return $this;
    }

    public function validate(): void
    {
        if (!isset($this->params['prompt']) || !\is_string($this->params['prompt'])) {
            throw new AxiException(
                "Ai\\Ask: param 'prompt' requerido y debe ser string.",
                AxiException::VALIDATION_FAILED
            );
        }
        if (\trim($this->params['prompt']) === '') {
            throw new AxiException(
                "Ai\\Ask: 'prompt' no puede estar vacio.",
                AxiException::VALIDATION_FAILED
            );
        }
        if (isset($this->params['agent_id']) && !\is_string($this->params['agent_id'])) {
            throw new AxiException(
                "Ai\\Ask: 'agent_id' debe ser string si se proporciona.",
                AxiException::VALIDATION_FAILED
            );
        }
    }

    public function execute(object $engine): Result
    {
        $manager = $engine->getService('agents');
        if ($manager === null) {
            throw new AxiException("Ai\\Ask: servicio agents no disponible.", AxiException::INTERNAL_ERROR);
        }
        $out = $manager->ask(
            (string) $this->params['prompt'],
            isset($this->params['agent_id']) ? (string) $this->params['agent_id'] : null
        );

        // Extrae observation principal (si la hay) para campo 'data' conveniente.
        $observation = null;
        foreach (\array_reverse($out['history'] ?? []) as $turn) {
            if (($turn['role'] ?? '') === 'tool' && isset($turn['observation'])) {
                $observation = $turn['observation'];
                break;
            }
        }

        return Result::ok([
            'answer'      => $out['answer']   ?? '',
            'agent_id'    => $out['agent_id'] ?? null,
            'status'      => $out['status']   ?? null,
            'steps'       => $out['steps']    ?? 0,
            'observation' => $observation,
            'history'     => $out['history']  ?? [],
        ]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'ai.ask',
            synopsis:    'Axi\\Op\\Ai\\Ask() ->prompt("...") [->agent("agent-id")]',
            description: 'Pregunta one-shot. Sin agent_id usa un ask-bot efimero (read-only). Con agent_id reutiliza un agente persistente. Devuelve answer + observation + history.',
            params: [
                ['name' => 'prompt',   'type' => 'string', 'required' => true,  'description' => 'Pregunta o instruccion en lenguaje natural.'],
                ['name' => 'agent_id', 'type' => 'string', 'required' => false, 'description' => 'Id del agente a interrogar. Omitir para ask-bot efimero.'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Ai\\Ask())\n    ->prompt('count products'));"],
                ['lang' => 'json', 'code' => '{"op":"ai.ask","prompt":"count products"}'],
                ['lang' => 'cli',  'code' => 'axi ai ask "count products"'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'prompt ausente o vacio.'],
            ],
            related: ['Ai\\NewAgent', 'Ai\\ListAgents', 'Ai\\RunAgent'],
        );
    }
}
