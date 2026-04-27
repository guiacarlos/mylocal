<?php
/**
 * AxiDB - Agents\Llm\GroqLlm: backend Groq (Llama, Mixtral...).
 *
 * Subsistema: engine/agents/llm
 * Responsable: envolver el GroqConnector legacy en el contrato LlmBackend
 *              y traducir el JSON OpenAI-like a {content, action, done}.
 *
 * El protocolo entre kernel y LLM es JSON estructurado: pedimos al modelo
 * que responda SIEMPRE con un objeto:
 *   {"content": "...", "action": {"op": "...", ...} | null, "done": bool}
 *
 * Si el modelo devuelve texto libre, intentamos extraer el JSON; si no se
 * puede, devolvemos content=texto y done=true.
 */

namespace Axi\Engine\Agents\Llm;

final class GroqLlm implements LlmBackend
{
    public function __construct(
        private string $apiKey,
        private string $model = 'llama-3.1-8b-instant'
    ) {
        // El connector legacy esta fuera de namespaces; lo cargamos perezosamente.
        $path = \dirname(__DIR__, 2) . '/connectors/GroqConnector.php';
        if (!\class_exists('GroqConnector', false) && \is_file($path)) {
            require_once $path;
        }
    }

    public function name(): string { return 'groq:' . $this->model; }

    public function complete(array $messages, array $tools = []): array
    {
        if (!\class_exists('GroqConnector', false)) {
            return $this->fallback("GroqConnector no disponible (connectors/GroqConnector.php no encontrado).");
        }
        if ($this->apiKey === '') {
            return $this->fallback("Groq sin API key. Configura llm_api_key en el agente.");
        }

        $sysHeader = $this->systemHeader($tools);
        $payloadMessages = \array_merge(
            [['role' => 'system', 'content' => $sysHeader]],
            $messages
        );

        try {
            $client = new \GroqConnector($this->apiKey);
            $resp = $client->chatCompletion([
                'model'    => $this->model,
                'messages' => $payloadMessages,
                'temperature' => 0.2,
            ]);
            $text = $resp['choices'][0]['message']['content'] ?? '';
            $tokens = (int) ($resp['usage']['total_tokens'] ?? 0);
            return $this->parseStructured($text, $tokens);
        } catch (\Throwable $e) {
            return $this->fallback("Groq error: " . $e->getMessage());
        }
    }

    private function systemHeader(array $tools): string
    {
        $list = $tools === [] ? '(any)' : \implode(', ', $tools);
        return "Eres un agente AxiDB. Responde SIEMPRE con un unico objeto JSON valido sin texto adicional, "
             . "con esta forma estricta:\n"
             . "{\"content\": \"texto para el usuario\", \"action\": {\"op\": \"<op-name>\", ...params...} | null, \"done\": true|false}\n"
             . "Ops permitidos: {$list}. Si la peticion ya esta resuelta, pon \"action\": null y \"done\": true. "
             . "Nunca inventes Ops fuera de la lista.";
    }

    private function parseStructured(string $text, int $tokens): array
    {
        $text = \trim($text);
        // Algunos modelos envuelven en ```json ... ```
        if (\preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $text, $m)) {
            $text = $m[1];
        }
        $obj = \json_decode($text, true);
        if (!\is_array($obj)) {
            return [
                'content' => $text,
                'action'  => null,
                'done'    => true,
                'tokens'  => $tokens,
            ];
        }
        return [
            'content' => (string) ($obj['content'] ?? ''),
            'action'  => \is_array($obj['action'] ?? null) ? $obj['action'] : null,
            'done'    => (bool) ($obj['done'] ?? true),
            'tokens'  => $tokens,
        ];
    }

    private function fallback(string $msg): array
    {
        return ['content' => $msg, 'action' => null, 'done' => true, 'tokens' => 0];
    }
}
