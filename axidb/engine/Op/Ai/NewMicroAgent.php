<?php
/**
 * AxiDB - Op\Ai\NewMicroAgent: crea microagente efimero hijo.
 *
 * Subsistema: engine/op/ai
 * Responsable: spawn de un Agent ephemeral con parent_id != null. Hereda
 *              tools/llm del parent. Profundidad maxima 3, max_children
 *              acotado por el budget del parent. [Fase 6]
 */

namespace Axi\Engine\Op\Ai;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class NewMicroAgent extends Operation
{
    public const OP_NAME = 'ai.new_micro_agent';

    public function spawn(string $parentId, string $task, int $maxSteps = 10): self
    {
        $this->params['parent_id'] = $parentId;
        $this->params['task']      = $task;
        $this->params['max_steps'] = $maxSteps;
        return $this;
    }

    public function validate(): void
    {
        foreach (['parent_id', 'task'] as $k) {
            if (empty($this->params[$k]) || !\is_string($this->params[$k])) {
                throw new AxiException("NewMicroAgent: '{$k}' requerido.", AxiException::VALIDATION_FAILED);
            }
        }
        if (isset($this->params['max_steps']) && (!\is_int($this->params['max_steps']) || $this->params['max_steps'] < 1)) {
            throw new AxiException("NewMicroAgent: 'max_steps' debe ser entero >= 1.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $manager = $engine->getService('agents');
        if ($manager === null) {
            throw new AxiException("NewMicroAgent: servicio agents no disponible.", AxiException::INTERNAL_ERROR);
        }
        $agent = $manager->createMicroAgent(
            (string) $this->params['parent_id'],
            (string) $this->params['task'],
            (int)    ($this->params['max_steps'] ?? 10)
        );
        return Result::ok($agent->toArray());
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'ai.new_micro_agent',
            synopsis:    'Axi\\Op\\Ai\\NewMicroAgent() ->spawn(parent_id, task, max_steps?)',
            description: 'Crea un microagente efimero hijo de un agente primario. Se autodestruye al completar (status=done) o agotar max_steps. Profundidad maxima 3.',
            params: [
                ['name' => 'parent_id', 'type' => 'string', 'required' => true,  'description' => 'Id del agente primario que lo crea.'],
                ['name' => 'task',      'type' => 'string', 'required' => true,  'description' => 'Descripcion de la tarea acotada (rol del micro).'],
                ['name' => 'max_steps', 'type' => 'int',    'required' => false, 'default' => 10],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Ai\\NewMicroAgent())\n    ->spawn('agent_xyz', 'Indexar documentos sin tag', 50));"],
                ['lang' => 'json', 'code' => '{"op":"ai.new_micro_agent","parent_id":"agent_xyz","task":"...","max_steps":50}'],
                ['lang' => 'cli',  'code' => 'axi ai spawn agent_xyz "Indexar documentos" --max-steps 50'],
            ],
            errors: [
                ['code' => AxiException::DOCUMENT_NOT_FOUND, 'when' => 'parent_id no existe.'],
                ['code' => AxiException::FORBIDDEN,          'when' => 'Profundidad >= 3 o max_children agotado.'],
                ['code' => AxiException::VALIDATION_FAILED,  'when' => 'parent_id o task vacios.'],
            ],
            related: ['NewAgent', 'KillAgent', 'RunAgent'],
        );
    }
}
