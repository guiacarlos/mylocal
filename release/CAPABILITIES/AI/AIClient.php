<?php
namespace AI;

/**
 * AIClient - Cliente HTTP compatible con la API de OpenAI.
 *
 * Conecta con cualquier servidor que implemente /v1/chat/completions:
 * llama.cpp, vLLM, LocalAI, Ollama, etc.
 *
 * Config via OPTIONS (dotted path):
 *   ai.local_endpoint  — URL base, ej: https://ai.miaplic.com/v1
 *   ai.local_api_key   — Bearer token del servidor
 *   ai.local_model     — Nombre del modelo, ej: gemma-4-e2b
 */
class AIClient
{
    private string $endpoint;
    private string $apiKey;
    private string $model;

    public function __construct(string $endpoint, string $apiKey, string $model)
    {
        $this->endpoint = rtrim($endpoint, '/');
        $this->apiKey   = $apiKey;
        $this->model    = $model;
    }

    /** Crea instancia leyendo config desde OPTIONS. */
    public static function fromOptions(): self
    {
        require_once __DIR__ . '/../OPTIONS/optiosconect.php';
        $opt = mylocal_options();
        return new self(
            (string) $opt->get('ai.local_endpoint', ''),
            (string) $opt->get('ai.local_api_key', ''),
            (string) $opt->get('ai.local_model', 'gemma-4-e2b')
        );
    }

    /** Devuelve true si local_endpoint está configurado en OPTIONS. */
    public static function isConfigured(): bool
    {
        require_once __DIR__ . '/../OPTIONS/optiosconect.php';
        return (bool) mylocal_options()->get('ai.local_endpoint', '');
    }

    /**
     * Chat completion de texto puro.
     *
     * @param array $messages  [{role: user|assistant|system, content: string}]
     * @param int   $maxTokens Límite de tokens en la respuesta
     * @return array {success: bool, choices?: [...], error?: string}
     */
    public function chat(array $messages, int $maxTokens = 4096): array
    {
        $payload = json_encode([
            'model'       => $this->model,
            'messages'    => $messages,
            'max_tokens'  => $maxTokens,
            'temperature' => 0,
        ]);
        return $this->post('/chat/completions', (string) $payload);
    }

    /**
     * Vision: prompt + imagen → texto.
     *
     * Envía la imagen codificada en base64 usando el formato estándar
     * image_url de OpenAI (compatible con llama.cpp + clip model).
     *
     * @param string $prompt    Instrucción de texto para el modelo
     * @param string $imagePath Ruta absoluta a la imagen (jpg, png, webp)
     * @return array {success: bool, choices?: [...], error?: string}
     */
    public function vision(string $prompt, string $imagePath, int $maxTokens = 1500): array
    {
        if (!file_exists($imagePath)) {
            return ['success' => false, 'error' => 'Imagen no encontrada: ' . $imagePath];
        }
        $ext     = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $mimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $mime    = $mimeMap[$ext] ?? 'image/jpeg';
        $b64     = base64_encode((string) file_get_contents($imagePath));

        $messages = [[
            'role'    => 'user',
            'content' => [
                ['type' => 'image_url', 'image_url' => ['url' => "data:{$mime};base64,{$b64}"]],
                ['type' => 'text',      'text'      => $prompt],
            ],
        ]];
        return $this->chat($messages, $maxTokens);
    }

    /**
     * Extrae el texto de la primera choice de una respuesta chat/completions.
     * Devuelve null si la respuesta no tiene contenido.
     */
    public function extractText(array $resp): ?string
    {
        return $resp['choices'][0]['message']['content'] ?? null;
    }

    /* ─── HTTP ─────────────────────────────────────────────────── */

    private function post(string $path, string $body): array
    {
        if (!$this->endpoint) {
            return ['success' => false, 'error' => 'ai.local_endpoint no configurado'];
        }
        $url     = $this->endpoint . $path;
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
        } else {
            $ctx  = stream_context_create([
                'http' => [
                    'method'        => 'POST',
                    'header'        => implode("\r\n", $headers) . "\r\n",
                    'content'       => $body,
                    'timeout'       => 120,
                    'ignore_errors' => true,
                ],
                'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $resp = @file_get_contents($url, false, $ctx);
            $code = 0;
            $err  = '';
            foreach (($http_response_header ?? []) as $h) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $code = (int) $m[1];
            }
            if ($resp === false) $err = 'stream error';
        }

        if ($code !== 200) {
            return ['success' => false, 'error' => "HTTP {$code} {$err}: " . substr((string) $resp, 0, 300)];
        }
        $data = json_decode((string) $resp, true);
        if (!is_array($data)) {
            return ['success' => false, 'error' => 'Respuesta JSON inválida del servidor IA'];
        }
        return array_merge(['success' => true], $data);
    }
}
