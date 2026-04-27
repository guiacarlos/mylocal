<?php
/**
 * AxiDB - Op\Ai\NewAgent: instanciar agente primario.
 *
 * Subsistema: engine/op/ai
 * Responsable: crear un Agent persistente en `_system/agents/<id>/agent.json`.
 *              Delega en el servicio agents (Manager). [Fase 6]
 */

namespace Axi\Engine\Op\Ai;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class NewAgent extends Operation
{
    public const OP_NAME = 'ai.new_agent';

    public function spec(string $name, string $role, array $tools = [], array $budget = [], string $llm = 'noop'): self
    {
        $this->params['name']   = $name;
        $this->params['role']   = $role;
        $this->params['tools']  = $tools;
        $this->params['budget'] = $budget;
        $this->params['llm']    = $llm;
        return $this;
    }

    public function validate(): void
    {
        foreach (['name', 'role'] as $k) {
            if (empty($this->params[$k]) || !\is_string($this->params[$k])) {
                throw new AxiException("NewAgent: '{$k}' requerido.", AxiException::VALIDATION_FAILED);
            }
        }
        if (isset($this->params['tools']) && !\is_array($this->params['tools'])) {
            throw new AxiException("NewAgent: 'tools' debe ser array.", AxiException::VALIDATION_FAILED);
        }
        if (isset($this->params['budget']) && !\is_array($this->params['budget'])) {
            throw new AxiException("NewAgent: 'budget' debe ser objeto.", AxiException::VALIDATION_FAILED);
        }
        if (isset($this->params['llm']) && !\is_string($this->params['llm'])) {
            throw new AxiException("NewAgent: 'llm' debe ser string.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $manager = $engine->getService('agents');
        if ($manager === null) {
            throw new AxiException("NewAgent: servicio agents no disponible.", AxiException::INTERNAL_ERROR);
        }
        $agent = $manager->createAgent(
            (string) $this->params['name'],
            (string) $this->params['role'],
            (array)  ($this->params['tools']  ?? []),
            (array)  ($this->params['budget'] ?? []),
            (string) ($this->params['llm']    ?? 'noop')
        );
        return Result::ok($agent->toArray());
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'ai.new_agent',
            synopsis:    'Axi\\Op\\Ai\\NewAgent() ->spec(name, role, tools?, budget?, llm?)',
            description: 'Crea un agente primario persistente con identidad, role, sandbox de tools, budget y backend LLM. Disponible en Fase 6.',
            params: [
                ['name' => 'name',   'type' => 'string',   'required' => true,  'description' => 'Identificador logico.'],
                ['name' => 'role',   'type' => 'string',   'required' => true,  'description' => 'Prompt de sistema que define comportamiento.'],
                ['name' => 'tools',  'type' => 'string[]', 'required' => false, 'description' => 'Lista de Ops del catalogo permitidas.'],
                ['name' => 'budget', 'type' => 'object',   'required' => false, 'description' => '{max_steps, max_tokens, max_children}.'],
                ['name' => 'llm',    'type' => 'string',   'required' => false, 'default' => 'noop', 'description' => 'noop | groq:<model> | ollama:<model>.'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Ai\\NewAgent())\n    ->spec('reviewer', 'Revisa productos nuevos', ['select','count']));"],
                ['lang' => 'json', 'code' => '{"op":"ai.new_agent","name":"reviewer","role":"...","tools":["select","count"]}'],
                ['lang' => 'cli',  'code' => 'axi ai new-agent reviewer --role "..." --tools select,count'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'name o role vacios.'],
            ],
            related: ['NewMicroAgent', 'RunAgent', 'ListAgents', 'Ask'],
        );
    }
}
