<?php
/**
 * AxiDB - Agents\Llm\GeminiLlm: backend Google Gemini.
 *
 * Subsistema: engine/agents/llm
 * Responsable: hablar con Generative Language API
 *              (https://generativelanguage.googleapis.com/v1beta/models/<m>:generateContent).
 *              Sin SDK; curl directo. Mismo contrato JSON estructurado que
 *              GroqLlm: pedimos respuesta {"content","action","done"}.
 */

namespace Axi\Engine\Agents\Llm;

final class GeminiLlm implements LlmBackend
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gemini-1.5-flash'
    ) {}

    public function name(): string { return 'gemini:' . $this->model; }

    public function complete(array $messages, array $tools = []): array
    {
        if ($this->apiKey === '') {
            return $this->fallback("Gemini sin API key. Configura llm_api_key o AXI_GEMINI_API_KEY.");
        }
        if (!\function_exists('curl_init')) {
            return $this->fallback("GeminiLlm requiere extension curl.");
        }

        // Gemini espera 'contents' (no messages) con role user|model.
        $contents = [];
        $sysHeader = $this->systemHeader($tools);
        // Gemini soporta system_instruction aparte (v1beta) — la enviamos asi.
        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            if ($role === 'system') { continue; }
            $contents[] = [
                'role'  => $role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string) ($m['content'] ?? '')]],
            ];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key=" . \urlencode($this->apiKey);
        $payload = [
            'system_instruction' => ['parts' => [['text' => $sysHeader]]],
            'contents'           => $contents,
            'generationConfig'   => ['temperature' => 0.2, 'maxOutputTokens' => 1024],
        ];

        $ch = \curl_init($url);
        \curl_setopt($ch, CURLOPT_POST, true);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($payload, JSON_UNESCAPED_UNICODE));
        \curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $body = \curl_exec($ch);
        $code = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = \curl_error($ch);
        \curl_close($ch);

        if ($body === false) {
            return $this->fallback("Gemini conexion: {$err}");
        }
        if ($code >= 400) {
            return $this->fallback("Gemini HTTP {$code}: " . \substr((string) $body, 0, 200));
        }
        $data = \json_decode((string) $body, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $tokens = (int) (($data['usageMetadata']['totalTokenCount'] ?? 0));
        return $this->parseStructured($text, $tokens);
    }

    private function systemHeader(array $tools): string
    {
        $list = $tools === [] ? '(any)' : \implode(', ', $tools);
        return "Eres un agente AxiDB. Responde SIEMPRE con un unico objeto JSON valido sin texto adicional, "
             . "con esta forma: {\"content\": \"texto\", \"action\": {\"op\": \"<op>\", ...} | null, \"done\": true|false}. "
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
