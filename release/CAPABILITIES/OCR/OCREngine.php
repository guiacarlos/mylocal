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

        $mime = mime_content_type($path) ?: 'image/jpeg';
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
        if (!class_exists('Imagick')) {
            return [
                'success' => false,
                'error' => 'Imagick no disponible: instalar php-imagick o subir las paginas como imagen.'
            ];
        }
        try {
            $im = new \Imagick();
            $im->setResolution(200, 200);
            $im->readImage($path);
            $allText = '';
            $pages = $im->getNumberImages();
            $tmpDir = sys_get_temp_dir();
            foreach ($im as $i => $page) {
                $page->setImageFormat('jpeg');
                $tmp = $tmpDir . '/ocr_pdf_' . uniqid() . "_$i.jpg";
                $page->writeImage($tmp);
                $r = $this->extractFromImage($tmp);
                @unlink($tmp);
                if ($r['success']) $allText .= "\n\n--- PAGINA " . ($i + 1) . " ---\n" . $r['text'];
            }
            return ['success' => true, 'text' => trim($allText), 'pages' => $pages, 'engine' => 'gemini_vision_pdf'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Error PDF: ' . $e->getMessage()];
        }
    }

    private function callGemini($model, $key, $payload)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 60
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($code !== 200) return ['success' => false, 'error' => "HTTP $code $err: " . substr($resp, 0, 200)];
        $data = json_decode($resp, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) return ['success' => false, 'error' => 'Respuesta Gemini vacia'];
        return ['success' => true, 'text' => $text];
    }

    private function loadConfig()
    {
        // Busca el config en multiples ubicaciones por compatibilidad:
        //   1. La que se paso explicitamente al constructor.
        //   2. spa/server/config/gemini.json (flujo SPA activo).
        //   3. STORAGE/config/gemini_settings.json (legacy CORE).
        $candidates = [
            $this->configPath,
            __DIR__ . '/../../spa/server/config/gemini.json',
            __DIR__ . '/../../STORAGE/config/gemini_settings.json',
        ];
        foreach ($candidates as $path) {
            if ($path && file_exists($path)) {
                $cfg = json_decode(@file_get_contents($path), true) ?: [];
                // Normalizar: spa/server/config/gemini.json usa default_model;
                // legacy usa model. Mapear ambos.
                if (!isset($cfg['model']) && isset($cfg['default_model'])) {
                    $cfg['model'] = $cfg['default_model'];
                }
                if (!empty($cfg['api_key'])) return $cfg;
            }
        }
        return [];
    }
}
