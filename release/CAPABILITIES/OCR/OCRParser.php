<?php
namespace OCR;

require_once __DIR__ . '/OCRHeuristicParser.php';

/**
 * OCRParser - Convierte texto plano OCR en estructura JSON de carta.
 *
 * Estrategia hibrida:
 *   1. Si Gemini esta disponible (api_key en OPTIONS), parser IA con
 *      generationConfig estricto y prompt que pide carta completa.
 *   2. Si Gemini falla o no esta configurado, fallback al heuristico
 *      (OCRHeuristicParser) que maneja layouts comunes de cartas reales.
 *
 * El campo "engine" en la respuesta indica que motor proceso:
 *   - "gemini_parser" : Gemini estructuro la carta
 *   - "heuristic_v2"  : fallback heuristico
 */
class OCRParser
{
    private $configPath;
    private $heuristic;

    public function __construct($storageRoot = null)
    {
        $root = $storageRoot ?: (defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE');
        $this->configPath = $root . '/config/gemini_settings.json';
        $this->heuristic = new OCRHeuristicParser();
    }

    public function parse($rawText)
    {
        if (!is_string($rawText) || trim($rawText) === '') {
            return ['success' => false, 'error' => 'Texto OCR vacio'];
        }

        // Motor primario: IA local.
        require_once __DIR__ . '/../AI/AIClient.php';
        if (\AI\AIClient::isConfigured()) {
            $r = $this->parseWithLocalAI($rawText, \AI\AIClient::fromOptions());
            if ($r['success']) return $r;
        }

        // Fallback: Gemini.
        $cfg = $this->loadConfig();
        if (!empty($cfg['api_key'])) {
            $smart = $this->parseWithGemini($rawText, $cfg);
            if ($smart['success']) return $smart;
        }

        return $this->heuristic->parse($rawText);
    }

    private function parseWithLocalAI(string $text, \AI\AIClient $client): array
    {
        $instr = "Eres un parser de cartas de restaurante. Recibes texto OCR de una carta "
            . "y devuelves UNICAMENTE un JSON valido con esta forma exacta:\n"
            . '{"categorias":[{"nombre":"...","productos":[{"nombre":"...","descripcion":"...","precio":0.00}]}]}'
            . "\n\nReglas IMPORTANTES:\n"
            . "- Devuelve TODAS las categorias y TODOS los productos. NO resumas. NO omitas nada.\n"
            . "- precio: numero decimal, sin simbolo. Si hay varios precios usa el menor.\n"
            . "- descripcion: ingredientes o explicacion del plato. Vacia si no hay.\n"
            . "- Sin texto fuera del JSON. Sin markdown. Sin ```. Solo JSON puro.\n\n"
            . "Texto OCR:\n" . $text;

        // max_tokens conservador: n_ctx(8192) - estimacion_entrada(~2500) = margen ~5500
        $resp = $client->chat([['role' => 'user', 'content' => $instr]], 5000);
        if (!($resp['success'] ?? false)) {
            error_log('[OCRParser] local_ai fallo: ' . ($resp['error'] ?? ''));
            return ['success' => false, 'error' => $resp['error'] ?? 'Error IA local'];
        }
        $jsonText = $client->extractText($resp) ?? '';
        $jsonText = preg_replace('/^```json\s*|\s*```$/s', '', trim($jsonText));
        $parsed   = json_decode($jsonText, true);
        if (!is_array($parsed) || !isset($parsed['categorias'])) {
            error_log('[OCRParser] JSON invalido: ' . substr($jsonText, 0, 300));
            return ['success' => false, 'error' => 'JSON invalido de IA local: ' . substr($jsonText, 0, 200)];
        }
        return ['success' => true, 'data' => $parsed, 'engine' => 'local_ai_parser'];
    }

    private function parseWithGemini($text, $cfg)
    {
        $model = $cfg['model'] ?? 'gemini-2.5-flash';
        $key = $cfg['api_key'];

        $instr = "Eres un parser de cartas de restaurante. Recibes texto OCR de una carta "
            . "y devuelves UNICAMENTE un JSON valido con esta forma exacta:\n"
            . '{"categorias":[{"nombre":"...","productos":[{"nombre":"...","descripcion":"...","precio":0.00}]}]}'
            . "\n\nReglas IMPORTANTES:\n"
            . "- Devuelve TODAS las categorias y TODOS los productos. NO resumas. NO omitas nada.\n"
            . "- precio: numero decimal, sin simbolo. Si hay varios precios (ej. copa/botella), usa el menor.\n"
            . "- descripcion: si el plato lleva ingredientes o explicacion debajo, ponla aqui. Vacia si no hay.\n"
            . "- categorias: agrupa por cabeceras visibles (ENTRANTES, CARNES, BEBIDAS, etc.) que suelen aparecer en MAYUSCULAS.\n"
            . "- Si un producto tiene precio en linea separada del nombre, parea ambas lineas.\n"
            . "- Ignora pies de pagina, suplementos generales y notas.\n"
            . "- Sin texto fuera del JSON. Sin markdown. Sin ```. Solo JSON puro.\n\n"
            . "Texto OCR:\n" . $text;

        $payload = [
            'contents' => [['parts' => [['text' => $instr]]]],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 16384,
                'responseMimeType' => 'application/json',
            ],
        ];
        $body = json_encode($payload);
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $resp = $this->postJson($url, $body);
        if ($resp['code'] !== 200) {
            return ['success' => false, 'error' => "HTTP {$resp['code']}: " . substr($resp['body'], 0, 200)];
        }
        $data = json_decode($resp['body'], true);
        $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $jsonText = preg_replace('/^```json\s*|\s*```$/s', '', trim($jsonText));
        $parsed = json_decode($jsonText, true);
        if (!is_array($parsed) || !isset($parsed['categorias'])) {
            return ['success' => false, 'error' => 'JSON invalido de Gemini: ' . substr($jsonText, 0, 200)];
        }
        return ['success' => true, 'data' => $parsed, 'engine' => 'gemini_parser'];
    }

    private function postJson(string $url, string $body): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ['code' => $code, 'body' => (string) $resp];
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $body,
                'timeout' => 120,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        $code = 0;
        foreach (($http_response_header ?? []) as $h) {
            if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $h, $m)) $code = (int) $m[1];
        }
        return ['code' => $code, 'body' => (string) $resp];
    }

    private function loadConfig()
    {
        require_once __DIR__ . '/../OPTIONS/optiosconect.php';
        $opt = mylocal_options();
        $apiKey = $opt->get('ai.api_key', '');
        if (!$apiKey) return [];
        return ['api_key' => $apiKey, 'model' => $opt->get('ai.default_model', 'gemini-2.5-flash')];
    }
}
