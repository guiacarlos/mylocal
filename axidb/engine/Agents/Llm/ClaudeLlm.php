<?php
/**
 * AxiDB - Agents\Llm\ClaudeLlm: backend Anthropic Claude.
 *
 * Subsistema: engine/agents/llm
 * Responsable: POST a https://api.anthropic.com/v1/messages
 *              con header `anthropic-version: 2023-06-01`. Espera respuesta
 *              {"content","action","done"} igual que el resto de backends.
 */

namespace Axi\Engine\Agents\Llm;

final class ClaudeLlm implements LlmBackend
{
    public function __construct(
        private string $apiKey,
        private string $model = 'claude-haiku-4-5-20251001'
    ) {}

    public function name(): string { return 'claude:' . $this->model; }

    public function complete(array $messages, array $tools = []): array
    {
        if ($this->apiKey === '') {
            return $this->fallback("Claude sin API key. Configura llm_api_key o AXI_CLAUDE_API_KEY.");
        }
        if (!\function_exists('curl_init')) {
            return $this->fallback("ClaudeLlm requiere extension curl.");
        }

        $sysParts = [];
        $turns    = [];
        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            $text = (string) ($m['content'] ?? '');
            if ($role === 'system') {
                $sysParts[] = $text;
            } elseif ($role === 'assistant') {
                $turns[] = ['role' => 'assistant', 'content' => $text];
            } else {
                $turns[] = ['role' => 'user', 'content' => $text];
            }
        }
        $sysParts[] = $this->systemHeader($tools);
        $system = \implode("\n\n", \array_filter($sysParts));

        $payload = [
            'model'      => $this->model,
            'system'     => $system,
            'messages'   => $turns,
            'max_tokens' => 1024,
            'temperature'=> 0.2,
        ];

        $ch = \curl_init('https://api.anthropic.com/v1/messages');
        \curl_setopt($ch, CURLOPT_POST, true);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01',
        ]);
        \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($payload, JSON_UNESCAPED_UNICODE));
        \curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $body = \curl_exec($ch);
        $code = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = \curl_error($ch);
        \curl_close($ch);

        if ($body === false) {
            return $this->fallback("Claude conexion: {$err}");
        }
        if ($code >= 400) {
            return $this->fallback("Claude HTTP {$code}: " . \substr((string) $body, 0, 200));
        }
        $data = \json_decode((string) $body, true);
        $text = $data['content'][0]['text'] ?? '';
        $tokens = (int) (($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0));
        return $this->parseStructured($text, $tokens);
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
