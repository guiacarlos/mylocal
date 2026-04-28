<?php
/**
 * AxiDB - Op\Ai\Attach: deposita mensaje en el inbox de un agente.
 *
 * Subsistema: engine/op/ai
 * Responsable: append-only en `_system/agents/<to>/inbox.jsonl`. El agente
 *              destino consumira el mensaje en su proxima ejecucion del
 *              kernel (drain del inbox al inicio del run). [Fase 6]
 */

namespace Axi\Engine\Op\Ai;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Attach extends Operation
{
    public const OP_NAME = 'ai.attach';

    public function message(string $toAgentId, string $subject, string $body, ?string $fromAgentId = null): self
    {
        $this->params['to']      = $toAgentId;
        $this->params['subject'] = $subject;
        $this->params['body']    = $body;
        if ($fromAgentId !== null) {
            $this->params['from'] = $fromAgentId;
        }
        return $this;
    }

    public function validate(): void
    {
        foreach (['to', 'subject', 'body'] as $k) {
            if (empty($this->params[$k]) || !\is_string($this->params[$k])) {
                throw new AxiException("Attach: '{$k}' requerido.", AxiException::VALIDATION_FAILED);
            }
        }
        if (isset($this->params['from']) && !\is_string($this->params['from'])) {
            throw new AxiException("Attach: 'from' debe ser string.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $manager = $engine->getService('agents');
        if ($manager === null) {
            throw new AxiException("Attach: servicio agents no disponible.", AxiException::INTERNAL_ERROR);
        }
        $msg = $manager->attach(
            (string) $this->params['to'],
            (string) $this->params['subject'],
            (string) $this->params['body'],
            isset($this->params['from']) ? (string) $this->params['from'] : null
        );
        return Result::ok($msg);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'ai.attach',
            synopsis:    'Axi\\Op\\Ai\\Attach() ->message(to, subject, body, from?)',
            description: 'Deposita un mensaje {from, to, subject, body, ts, correlation_id} en el inbox.jsonl del agente destino. Append-only.',
            params: [
                ['name' => 'to',      'type' => 'string', 'required' => true],
                ['name' => 'subject', 'type' => 'string', 'required' => true],
                ['name' => 'body',    'type' => 'string', 'required' => true],
                ['name' => 'from',    'type' => 'string', 'required' => false, 'description' => 'Id del agente emisor. Omitir para "system".'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Ai\\Attach())\n    ->message('agent_xyz', 'check', 'Revisa el inventario'));"],
                ['lang' => 'json', 'code' => '{"op":"ai.attach","to":"agent_xyz","subject":"check","body":"..."}'],
                ['lang' => 'cli',  'code' => 'axi ai attach agent_xyz --subject check --body "Revisa..."'],
            ],
            errors: [
                ['code' => AxiException::DOCUMENT_NOT_FOUND, 'when' => 'to no existe.'],
                ['code' => AxiException::VALIDATION_FAILED,  'when' => 'to/subject/body vacios.'],
            ],
            related: ['Broadcast', 'RunAgent'],
        );
    }
}
