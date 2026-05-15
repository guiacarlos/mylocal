<?php
/**
 * AxiDB - Op\Ai\Broadcast: deposita mensaje en agentes que matchean glob.
 *
 * Subsistema: engine/op/ai
 * Responsable: matchear el patron contra agent.role y agent.name; deposita
 *              copia del mensaje en el inbox de cada agente coincidente. [Fase 6]
 */

namespace Axi\Engine\Op\Ai;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Broadcast extends Operation
{
    public const OP_NAME = 'ai.broadcast';

    public function send(string $pattern, string $message, ?string $from = null): self
    {
        $this->params['pattern'] = $pattern;
        $this->params['message'] = $message;
        if ($from !== null) { $this->params['from'] = $from; }
        return $this;
    }

    public function validate(): void
    {
        foreach (['pattern', 'message'] as $k) {
            if (empty($this->params[$k]) || !\is_string($this->params[$k])) {
                throw new AxiException("Broadcast: '{$k}' requerido.", AxiException::VALIDATION_FAILED);
            }
        }
        if (isset($this->params['from']) && !\is_string($this->params['from'])) {
            throw new AxiException("Broadcast: 'from' debe ser string.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $manager = $engine->getService('agents');
        if ($manager === null) {
            throw new AxiException("Broadcast: servicio agents no disponible.", AxiException::INTERNAL_ERROR);
        }
        $n = $manager->broadcast(
            (string) $this->params['pattern'],
            (string) $this->params['message'],
            isset($this->params['from']) ? (string) $this->params['from'] : null
        );
        return Result::ok([
            'pattern'   => $this->params['pattern'],
            'delivered' => $n,
        ]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'ai.broadcast',
            synopsis:    'Axi\\Op\\Ai\\Broadcast() ->send(pattern, message, from?)',
            description: 'Deposita una copia del mensaje en el inbox de todos los agentes cuyo role o name matchean el patron glob. Devuelve el contador delivered.',
            params: [
                ['name' => 'pattern', 'type' => 'string', 'required' => true, 'description' => 'Patron glob (ej: "reviewer*"). Matchea role o name.'],
                ['name' => 'message', 'type' => 'string', 'required' => true],
                ['name' => 'from',    'type' => 'string', 'required' => false],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Ai\\Broadcast())\n    ->send('reviewer*', 'Stop current work'));"],
                ['lang' => 'json', 'code' => '{"op":"ai.broadcast","pattern":"reviewer*","message":"Stop current work"}'],
                ['lang' => 'cli',  'code' => 'axi ai broadcast "reviewer*" "Stop current work"'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'pattern o message vacios.'],
            ],
            related: ['Attach', 'ListAgents'],
        );
    }
}
