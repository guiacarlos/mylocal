<?php
/**
 * OpenClaudeClient — cliente HTTP para la API de Anthropic (Claude).
 *
 * Config via OPTIONS namespace "openclaude":
 *   openclaude.api_key  — Bearer token de la API (sk-ant-...)
 *   openclaude.model    — ID del modelo (default: claude-haiku-4-5-20251001)
 *   openclaude.timeout  — segundos de timeout (default: 1)
 *
 * Si api_key no está configurada → isEnabled() devuelve false.
 * Si el servicio tarda más que timeout → error, la app NO se cae.
 */

declare(strict_types=1);

namespace AI;

class OpenClaudeClient
{
    private const API_URL  = 'https://api.anthropic.com/v1/messages';
    private const API_VER  = '2023-06-01';
    private const DEF_MODEL = 'claude-haiku-4-5-20251001';
    private const DEF_TIMEOUT = 1;

    private string $apiKey;
    private string $model;
    private int    $timeout;

    public function __construct(string $apiKey, string $model, int $timeout)
    {
        $this->apiKey  = $apiKey;
        $this->model   = $model ?: self::DEF_MODEL;
        $this->timeout = max(1, $timeout);
    }

    public static function fromOptions(): self
    {
        require_once __DIR__ . '/../OPTIONS/optiosconect.php';
        $opt = mylocal_options();
        return new self(
            (string) $opt->get('openclaude.api_key', ''),
            (string) $opt->get('openclaude.model', self::DEF_MODEL),
            (int)    $opt->get('openclaude.timeout', self::DEF_TIMEOUT)
        );
    }

    public static function isEnabled(): bool
    {
        require_once __DIR__ . '/../OPTIONS/optiosconect.php';
        return (bool) mylocal_options()->get('openclaude.api_key', '');
    }

    /**
     * Envía un mensaje y devuelve la respuesta del modelo.
     *
     * @param string      $prompt  Texto del usuario
     * @param string|null $system  Instrucción de sistema opcional
     * @param int         $maxTokens Límite de tokens en la respuesta
     * @return array {success: bool, content?: string, model?: string, error?: string}
     */
    public function complete(string $prompt, ?string $system = null, int $maxTokens = 1000): array
    {
        if (!$this->apiKey) {
            return ['success' => false, 'error' => 'openclaude.api_key no configurada'];
        }

        $payload = [
            'model'      => $this->model,
            'max_tokens' => $maxTokens,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ];
        if ($system !== null) $payload['system'] = $system;

        $body    = (string) json_encode($payload);
        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: ' . self::API_VER,
        ];

        return $this->post($body, $headers);
    }

    /** Extrae el texto de la primera respuesta. */
    public function extractText(array $resp): ?string
    {
        return $resp['content'][0]['text'] ?? null;
    }

    /* ─── HTTP ───────────────────────────────────────────────── */

    private function post(string $body, array $headers): array
    {
        if (function_exists('curl_init')) {
            return $this->postCurl($body, $headers);
        }
        return $this->postStream($body, $headers);
    }

    private function postCurl(string $body, array $headers): array
    {
        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) return ['success' => false, 'error' => 'curl: ' . $err];
        return $this->parseResponse((string) $resp, $code);
    }

    private function postStream(string $body, array $headers): array
    {
        $ctx  = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", $headers),
                'content'       => $body,
                'timeout'       => $this->timeout,
                'ignore_errors' => true,
            ],
        ]);
        $resp = @file_get_contents(self::API_URL, false, $ctx);
        $code = 0;
        foreach (($http_response_header ?? []) as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $code = (int) $m[1];
        }
        if ($resp === false) return ['success' => false, 'error' => 'stream timeout o error de red'];
        return $this->parseResponse($resp, $code);
    }

    private function parseResponse(string $resp, int $code): array
    {
        $data = json_decode($resp, true);
        if (!is_array($data)) {
            return ['success' => false, 'error' => "HTTP {$code}: respuesta no JSON"];
        }
        if ($code !== 200) {
            $msg = $data['error']['message'] ?? "HTTP {$code}";
            return ['success' => false, 'error' => $msg];
        }
        return [
            'success' => true,
            'content' => $data['content'] ?? [],
            'model'   => $data['model'] ?? $this->model,
            'usage'   => $data['usage'] ?? [],
        ];
    }
}
