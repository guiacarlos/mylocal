<?php
/**
 * AxiDB - Agents\Llm\NoopLlm: backend deterministico sin red.
 *
 * Subsistema: engine/agents/llm
 * Responsable: dar una experiencia agentica usable sin API keys ni red.
 *              Reconoce un puñado de comandos en lenguaje natural y los
 *              traduce a Ops del catalogo. Si el prompt no matchea, devuelve
 *              un mensaje claro pidiendo formato concreto.
 *
 * Patrones soportados (case-insensitive, ES/EN):
 *   "ping"                       -> ping
 *   "describe" / "list collections" / "schema"  -> describe / schema
 *   "help [op]"                  -> help target=...
 *   "count <coleccion>"          -> count collection=...
 *   "list <coleccion>"           -> select collection=... limit=5
 *   "exists <coleccion> <id>"    -> exists collection=... id=...
 *   "stop" / "done" / "fin"      -> done=true
 *
 * Cualquier otra cosa: devuelve content explicativo + done=true.
 *
 * El plan a futuro es que existan implementaciones reales (GroqLlm,
 * OllamaLlm, GeminiLlm, ClaudeLlm) y NoopLlm quede como fallback offline
 * y banco de pruebas determinista.
 */

namespace Axi\Engine\Agents\Llm;

final class NoopLlm implements LlmBackend
{
    public function name(): string { return 'noop'; }

    public function complete(array $messages, array $tools = []): array
    {
        $prompt = $this->lastUserPrompt($messages);
        // Si viene del Mailbox como "[from] subject: body", quedate con el body.
        if (\preg_match('/^\[[^\]]+\]\s+[^:]+:\s+(.+)$/s', $prompt, $m)) {
            $prompt = $m[1];
        }
        $norm   = \strtolower(\trim($prompt));

        if ($norm === '' || \in_array($norm, ['stop', 'done', 'fin', 'exit', 'quit'], true)) {
            return $this->reply('Ok, cierro la tarea.', null, true, $prompt);
        }

        if ($norm === 'ping') {
            return $this->reply('Hago ping al motor.', ['op' => 'ping'], true, $prompt);
        }

        if (\in_array($norm, ['describe', 'list collections', 'colecciones', 'lista colecciones'], true)) {
            return $this->reply('Listo las colecciones.', ['op' => 'describe'], true, $prompt);
        }

        if ($norm === 'schema') {
            return $this->reply('Pido el schema global.', ['op' => 'schema'], true, $prompt);
        }

        if (\preg_match('/^help(?:\s+([a-z][\w\.\-]*))?$/i', $prompt, $m)) {
            $target = $m[1] ?? '';
            $params = ['op' => 'help'];
            if ($target !== '') { $params['target'] = $target; }
            return $this->reply("Ayuda" . ($target ? " de '{$target}'" : ' general'), $params, true, $prompt);
        }

        if (\preg_match('/^(?:count|cuant[oa]s?(?:\s+hay\s+en)?)\s+([a-z0-9_\-]+)/i', $prompt, $m)) {
            return $this->reply(
                "Cuento documentos de '{$m[1]}'.",
                ['op' => 'count', 'collection' => $m[1]],
                true,
                $prompt
            );
        }

        if (\preg_match('/^(?:list|listar?|muestra)\s+([a-z0-9_\-]+)(?:\s+(?:limit|top)\s+(\d+))?/i', $prompt, $m)) {
            $limit = isset($m[2]) ? (int) $m[2] : 5;
            return $this->reply(
                "Listo {$limit} de '{$m[1]}'.",
                ['op' => 'select', 'collection' => $m[1], 'limit' => $limit],
                true,
                $prompt
            );
        }

        if (\preg_match('/^exists?\s+([a-z0-9_\-]+)\s+([A-Za-z0-9_\-]+)/i', $prompt, $m)) {
            return $this->reply(
                "Compruebo si '{$m[2]}' existe en '{$m[1]}'.",
                ['op' => 'exists', 'collection' => $m[1], 'id' => $m[2]],
                true,
                $prompt
            );
        }

        return $this->reply(
            "NoopLlm sin red: no se interpretar libremente. Probables: 'ping', 'describe', 'help [op]', 'count <coleccion>', 'list <coleccion> [limit N]', 'exists <coleccion> <id>'.",
            null,
            true,
            $prompt
        );
    }

    private function reply(string $content, ?array $action, bool $done, string $promptForTokens): array
    {
        return [
            'content' => $content,
            'action'  => $action,
            'done'    => $done,
            'tokens'  => (int) \ceil(\strlen($promptForTokens) / 4) + (int) \ceil(\strlen($content) / 4),
        ];
    }

    private function lastUserPrompt(array $messages): string
    {
        for ($i = \count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                return (string) ($messages[$i]['content'] ?? '');
            }
        }
        return '';
    }
}
