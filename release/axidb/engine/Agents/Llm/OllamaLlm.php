<?php
/**
 * AxiDB - Agents\Llm\OllamaLlm: backend local via Ollama.
 *
 * Subsistema: engine/agents/llm
 * Responsable: envolver el OllamaConnector legacy. Mismo contrato JSON
 *              estructurado que GroqLlm.
 */

namespace Axi\Engine\Agents\Llm;

final class OllamaLlm implements LlmBackend
{
    public function __construct(
        private string $model   = 'llama3.1',
        private string $baseUrl = 'http://localhost:11434'
    ) {
        $path = \dirname(__DIR__, 2) . '/connectors/OllamaConnector.php';
        if (!\class_exists('OllamaConnector', false) && \is_file($path)) {
            require_once $path;
        }
    }

    public function name(): string { return 'ollama:' . $this->model; }

    public function complete(array $messages, array $tools = []): array
    {
        if (!\class_exists('OllamaConnector', false)) {
            return $this->fallback("OllamaConnector no disponible.");
        }
        try {
            $client = new \OllamaConnector($this->baseUrl);
            $resp = $client->chat([
                'model'    => $this->model,
                'messages' => \array_merge(
                    [['role' => 'system', 'content' => $this->systemHeader($tools)]],
                    $messages
                ),
                'options'  => ['temperature' => 0.2],
            ]);
            $text = $resp['message']['content'] ?? '';
            $tokens = (int) (($resp['eval_count'] ?? 0) + ($resp['prompt_eval_count'] ?? 0));
            return $this->parseStructured($text, $tokens);
        } catch (\Throwable $e) {
            return $this->fallback("Ollama error: " . $e->getMessage());
        }
    }

    private function systemHeader(array $tools): string
    {
        $list = $tools === [] ? '(any)' : \implode(', ', $tools);
        return "Eres un agente AxiDB. Responde SIEMPRE con un unico objeto JSON valido sin texto adicional: "
             . "{\"content\": \"...\", \"action\": {\"op\": \"<op>\", ...} | null, \"done\": true|false}. "
             . "Ops permitidos: {$list}.";
    }

    private function parseStructured(string $text, int $tokens): array
    {
        $text = \trim($text);
        if (\preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $text, $m)) {
            $text = $m[1];
        }
        $obj = \json_decode($text, true);
        if (!\is_array($obj)) {
            return ['content' => $text, 'action' => null, 'done' => true, 'tokens' => $tokens];
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
