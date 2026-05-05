<?php
namespace OCR;

/**
 * OCREngine - Conector de OCR.
 *
 * Estrategia: usar Gemini Vision como motor por defecto (mismo proveedor que
 * el resto del stack IA). Si la API key no esta configurada, devuelve un
 * error explicito; nunca inventa texto.
 *
 * Acepta:
 *   - Imagenes: jpg, jpeg, png, webp
 *   - PDFs (se renderiza pagina por pagina con Imagick si esta disponible,
 *     o se rechaza con error claro si no lo esta)
 */
class OCREngine
{
    private $configPath;

    public function __construct($storageRoot = null)
    {
        $root = $storageRoot ?: (defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE');
        $this->configPath = $root . '/config/gemini_settings.json';
    }

    public function extract($filePath)
    {
        if (!is_string($filePath) || !file_exists($filePath)) {
            return ['success' => false, 'error' => 'Archivo no encontrado'];
        }
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return $this->extractFromImage($filePath);
        }
        if ($ext === 'pdf') {
            return $this->extractFromPdf($filePath);
        }
        return ['success' => false, 'error' => "Formato no soportado: $ext"];
    }

    private function extractFromImage($path)
    {
        $cfg = $this->loadConfig();
        $key = $cfg['api_key'] ?? '';
        if (!$key) return ['success' => false, 'error' => 'API key de Gemini no configurada. Edita spa/server/config/gemini.json y anade tu api_key. Ver: https://makersuite.google.com/app/apikey'];

        // mime_content_type requiere ext-fileinfo. Fallback a extension.
        $mime = function_exists('mime_content_type') ? @mime_content_type($path) : false;
        if (!$mime) {
            $extMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
            $mime = $extMap[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'image/jpeg';
        }
        $b64 = base64_encode(file_get_contents($path));
        $model = $cfg['vision_model'] ?? $cfg['model'] ?? 'gemini-1.5-flash';

        $prompt = "Eres un OCR experto en cartas de restaurante en espanol. "
            . "Extrae TODO el texto visible de esta imagen tal cual aparece, "
            . "respetando saltos de linea y orden de columnas. "
            . "Si ves precios (numeros con , o . y simbolo euro) preservalos. "
            . "Devuelve solo el texto extraido, sin comentarios.";

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inline_data' => ['mime_type' => $mime, 'data' => $b64]]
                ]
            ]]
        ];

        $resp = $this->callGemini($model, $key, $payload);
        if (!$resp['success']) return $resp;
        return ['success' => true, 'text' => $resp['text'], 'engine' => 'gemini_vision'];
    }

    private function extractFromPdf($path)
    {
        // Gemini Vision acepta PDFs directamente como inline_data con
        // mime_type=application/pdf. NO necesitamos Imagick para trocear
        // paginas. Una sola llamada procesa el PDF completo.
        // Limite de Gemini para inline: 20 MB. PDFs mas grandes habria
        // que subirlos via Files API (TODO si hace falta).
        $size = filesize($path);
        if ($size === false) {
            return ['success' => false, 'error' => 'No se pudo leer el PDF'];
        }
        if ($size > 20 * 1024 * 1024) {
            return ['success' => false, 'error' => 'PDF demasiado grande (>20 MB). Divide el PDF o subelo como imagen.'];
        }

        $cfg = $this->loadConfig();
        $key = $cfg['api_key'] ?? '';
        if (!$key) return ['success' => false, 'error' => 'API key de Gemini no configurada. Edita CAPABILITIES/OPTIONS o pasa por el panel admin.'];
        $model = $cfg['vision_model'] ?? $cfg['model'] ?? 'gemini-2.5-flash';

        $b64 = base64_encode(file_get_contents($path));
        $prompt = "Eres un OCR experto en cartas de restaurante en espanol. "
            . "Este PDF contiene una o mas paginas de una carta. "
            . "Extrae TODO el texto visible respetando saltos de linea y orden "
            . "de columnas. Si ves precios (numeros con , o . y simbolo euro o EUR) "
            . "preservalos. Marca el inicio de cada pagina con --- PAGINA N --- "
            . "(donde N es el numero). Devuelve solo el texto extraido, sin "
            . "comentarios ni markdown.";

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inline_data' => ['mime_type' => 'application/pdf', 'data' => $b64]]
                ]
            ]]
        ];

        $resp = $this->callGemini($model, $key, $payload);
        if (!$resp['success']) return $resp;
        return ['success' => true, 'text' => $resp['text'], 'engine' => 'gemini_vision_pdf'];
    }

    private function callGemini($model, $key, $payload)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
        $body = json_encode($payload);
        // Soporta tanto curl (preferido) como streams (fallback sin ext-curl).
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $body,
                    'timeout' => 60,
                    'ignore_errors' => true,
                ],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $resp = @file_get_contents($url, false, $ctx);
            $code = 0;
            $err = '';
            foreach (($http_response_header ?? []) as $h) {
                if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $h, $m)) $code = (int) $m[1];
            }
            if ($resp === false) $err = 'stream error';
        }
        if ($code !== 200) return ['success' => false, 'error' => "HTTP $code $err: " . substr((string)$resp, 0, 200)];
        $data = json_decode((string)$resp, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) return ['success' => false, 'error' => 'Respuesta Gemini vacia: ' . substr((string)$resp, 0, 200)];
        return ['success' => true, 'text' => $text];
    }

    private function loadConfig()
    {
        // Source of truth: CAPABILITIES/OPTIONS (el unico sitio de config).
        require_once __DIR__ . '/../OPTIONS/optiosconect.php';
        $opt = mylocal_options();
        $apiKey = $opt->get('ai.api_key', '');
        if (!$apiKey) return [];
        $model = $opt->get('ai.default_model', 'gemini-2.5-flash');
        return [
            'api_key' => $apiKey,
            'model' => $model,
            'vision_model' => $opt->get('ai.vision_model', $model),
        ];
    }
}
