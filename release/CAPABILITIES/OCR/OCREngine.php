<?php
namespace OCR;

/**
 * OCREngine - Extrae texto de imágenes y PDFs de cartas de restaurante.
 *
 * Cascada de motores (en orden de velocidad):
 *   1. Tesseract OCR (local, ~0.3s/pág) — extracción de texto pura y rápida.
 *      Si extrae ≥ 50 caracteres por imagen, se usa directamente.
 *   2. IA local Gemma 4 vision (llama.cpp, ~25s/pág) — cuando Tesseract
 *      no llega al umbral (tipografía decorativa, fondos complejos).
 *   3. Gemini Vision (fallback en la nube) — si los dos locales fallan.
 *
 * Para PDFs: convierte páginas a PNG con Imagick|GhostScript|pdftoppm|WSL,
 * luego aplica la misma cascada sobre cada página.
 *
 * Nunca inventa texto. Siempre devuelve lo que el motor ve.
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
        // Motor 1: Tesseract (local, rápido, sin GPU).
        $text = $this->runTesseract($path);
        if ($text) return ['success' => true, 'text' => $text, 'engine' => 'tesseract'];

        $prompt = 'Lee la imagen y transcribe todo el texto que ves, tal como aparece. Solo el texto.';

        // Motor 2: IA local Gemma 4 vision (llama.cpp).
        $localError = null;
        require_once __DIR__ . '/../AI/AIClient.php';
        if (\AI\AIClient::isConfigured()) {
            $client = \AI\AIClient::fromOptions();
            $resp   = $client->vision($prompt, $path, 1500);
            if ($resp['success'] ?? false) {
                $text = $this->sanitizeOCRText($client->extractText($resp) ?? '');
                if ($text) return ['success' => true, 'text' => $text, 'engine' => 'local_ai'];
            }
            $localError = $resp['error'] ?? 'sin respuesta del modelo local';
            error_log('[OCREngine] IA local fallo (imagen): ' . $localError);
        }

        // Fallback: Gemini Vision.
        $cfg = $this->loadConfig();
        $key = $cfg['api_key'] ?? '';
        if (!$key) {
            return ['success' => false, 'error' => $localError
                ? "IA local no disponible ($localError) y Gemini sin api_key"
                : 'Ningun motor IA configurado'];
        }
        $mime = function_exists('mime_content_type') ? @mime_content_type($path) : false;
        if (!$mime) {
            $extMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
            $mime   = $extMap[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'image/jpeg';
        }
        $b64   = base64_encode(file_get_contents($path));
        $model = $cfg['vision_model'] ?? $cfg['model'] ?? 'gemini-1.5-flash';
        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inline_data' => ['mime_type' => $mime, 'data' => $b64]],
                ],
            ]],
        ];
        $resp = $this->callGemini($model, $key, $payload);
        if (!$resp['success']) {
            return ['success' => false, 'error' => $localError
                ? 'IA local: ' . $localError . ' | Gemini: ' . $resp['error']
                : $resp['error']];
        }
        return ['success' => true, 'text' => $resp['text'], 'engine' => 'gemini_vision'];
    }

    private function extractFromPdf($path)
    {
        $size = filesize($path);
        if ($size === false) return ['success' => false, 'error' => 'No se pudo leer el PDF'];
        if ($size > 20 * 1024 * 1024) {
            return ['success' => false, 'error' => 'PDF demasiado grande (>20 MB). Divide el PDF o subelo como imagen.'];
        }

        // Obtener páginas PNG (compartidas por todos los motores).
        $pages = $this->pdfToImages($path);
        if (empty($pages)) {
            $convError = 'no se pudo convertir el PDF a imagenes (instala poppler-utils en el servidor)';
            error_log('[OCREngine] ' . $convError);
        }

        // Motor 1: Tesseract sobre todas las páginas (rápido, sin GPU).
        if (!empty($pages)) {
            $texts = [];
            foreach ($pages as $i => $imgPath) {
                $t = $this->runTesseract($imgPath);
                if ($t) $texts[] = '--- PAGINA ' . ($i + 1) . " ---\n" . $t;
            }
            if (count($texts) >= max(1, count($pages) / 2)) {
                foreach ($pages as $p) @unlink($p);
                @rmdir(dirname($pages[0]));
                return ['success' => true, 'text' => implode("\n\n", $texts),
                        'engine' => 'tesseract_pdf', 'pages' => count($texts)];
            }
        }

        // Motor 2: IA local Gemma 4 vision por página.
        $localError = $convError ?? null;
        require_once __DIR__ . '/../AI/AIClient.php';
        if (!empty($pages) && \AI\AIClient::isConfigured()) {
            $tmpDir = dirname($pages[0]);
            $client = \AI\AIClient::fromOptions();
            $prompt = 'Lee la imagen y transcribe todo el texto que ves, tal como aparece. Solo el texto.';
            $texts = [];
            foreach ($pages as $i => $imgPath) {
                $resp = $client->vision($prompt, $imgPath, 1500);
                if (($resp['success'] ?? false) && ($t = $this->sanitizeOCRText($client->extractText($resp) ?? ''))) {
                    $texts[] = '--- PAGINA ' . ($i + 1) . " ---\n" . $t;
                } elseif (!($resp['success'] ?? false)) {
                    $localError = $resp['error'] ?? 'sin respuesta en pagina ' . ($i + 1);
                    error_log('[OCREngine] IA local fallo (pdf p.' . ($i + 1) . '): ' . $localError);
                }
                @unlink($imgPath);
            }
            @rmdir($tmpDir);
            if (!empty($texts)) {
                return ['success' => true, 'text' => implode("\n\n", $texts),
                        'engine' => 'local_ai_pdf', 'pages' => count($texts)];
            }
        }

        // Fallback: Gemini (acepta PDF como inline_data nativamente).
        $cfg   = $this->loadConfig();
        $key   = $cfg['api_key'] ?? '';
        if (!$key) return ['success' => false, 'error' => $localError
            ? "IA local: $localError. Gemini sin api_key configurado"
            : 'Ningun motor IA configurado'];
        $model = $cfg['vision_model'] ?? $cfg['model'] ?? 'gemini-2.5-flash';
        $b64   = base64_encode(file_get_contents($path));
        $prompt = "Eres un OCR experto en cartas de restaurante en espanol. "
            . "Este PDF contiene una o mas paginas de una carta. Extrae TODO el texto "
            . "visible respetando orden de columnas. Marca cada pagina con --- PAGINA N ---. "
            . "Devuelve solo el texto, sin comentarios ni markdown.";
        $payload = ['contents' => [['parts' => [
            ['text' => $prompt],
            ['inline_data' => ['mime_type' => 'application/pdf', 'data' => $b64]],
        ]]]];
        $resp = $this->callGemini($model, $key, $payload);
        if (!$resp['success']) {
            return ['success' => false, 'error' => $localError
                ? 'IA local: ' . $localError . ' | Gemini: ' . $resp['error']
                : $resp['error']];
        }
        return ['success' => true, 'text' => $resp['text'], 'engine' => 'gemini_vision_pdf'];
    }

    /**
     * Convierte un PDF en imágenes JPEG temporales (una por página).
     * Prueba Imagick → GhostScript → pdftoppm en ese orden.
     * El llamador es responsable de borrar los archivos con unlink().
     */
    private function pdfToImages(string $pdfPath): array
    {
        $tmpDir = sys_get_temp_dir() . '/ocr_' . bin2hex(random_bytes(6));
        @mkdir($tmpDir, 0775, true);

        if (class_exists('Imagick')) {
            try {
                $im = new \Imagick();
                $im->setResolution(150, 150);
                $im->readImage($pdfPath);
                $im->setImageFormat('png');
                $pages = [];
                foreach ($im as $i => $page) {
                    $out = $tmpDir . '/p' . $i . '.png';
                    $page->writeImage($out);
                    $pages[] = $out;
                }
                $im->clear();
                if (!empty($pages)) return $pages;
            } catch (\Exception $e) { /* sin Imagick funcional */ }
        }

        $gs = $this->findBin(['gs', 'gswin64c', 'gswin32c']);
        if ($gs) {
            @exec(escapeshellcmd($gs) . ' -dBATCH -dNOPAUSE -dSAFER -sDEVICE=png16m -r150'
                . ' -sOutputFile=' . escapeshellarg($tmpDir . '/p%d.png')
                . ' ' . escapeshellarg($pdfPath) . ' 2>/dev/null');
            $files = glob($tmpDir . '/p*.png') ?: [];
            natsort($files);
            if (!empty($files)) return array_values($files);
        }

        $ppm = $this->findBin(['pdftoppm']);
        if ($ppm) {
            @exec(escapeshellcmd($ppm) . ' -png -r 150 ' . escapeshellarg($pdfPath)
                . ' ' . escapeshellarg($tmpDir . '/p') . ' 2>/dev/null');
            $files = glob($tmpDir . '/p*.png') ?: [];
            natsort($files);
            if (!empty($files)) return array_values($files);
        }

        // Fallback WSL: desarrollo Windows con Ubuntu instalado.
        if (PHP_OS_FAMILY === 'Windows') {
            $wslPdf = $this->windowsToWslPath($pdfPath);
            $sep    = DIRECTORY_SEPARATOR;
            $wslOut = $this->windowsToWslPath($tmpDir . $sep . 'p');
            @exec('wsl -d Ubuntu -- pdftoppm -png -r 150 '
                . escapeshellarg($wslPdf) . ' ' . escapeshellarg($wslOut) . ' 2>/dev/null');
            $files = glob($tmpDir . $sep . 'p*.png') ?: [];
            natsort($files);
            if (!empty($files)) return array_values($files);
        }

        @rmdir($tmpDir);
        return [];
    }

    /**
     * Extrae texto de una imagen con Tesseract OCR usando salida TSV para
     * filtrar por confianza por palabra. Si la confianza media es < 70 %
     * (tipografía decorativa, fondo complejo) devuelve cadena vacía para
     * que la cascada pase al siguiente motor (vision).
     */
    private function runTesseract(string $imagePath): string
    {
        $tsv = $this->tesseractCmd($imagePath, 'tsv');
        if ($tsv === '') return '';

        $rows = explode("\n", trim($tsv));
        array_shift($rows); // primera fila es la cabecera de columnas

        $buckets    = [];  // [block-par-line => [words]]
        $totalConf  = 0;
        $wordCount  = 0;

        foreach ($rows as $row) {
            $cols = explode("\t", $row);
            if (count($cols) < 12) continue;
            $level = (int) $cols[0];
            $conf  = (int) $cols[10];
            $word  = rtrim($cols[11] ?? '');
            if ($level !== 5 || $conf < 0 || $word === '') continue;
            $key = $cols[2] . '-' . $cols[3] . '-' . $cols[4]; // bloque-párrafo-línea
            $buckets[$key][] = $word;
            $totalConf += $conf;
            $wordCount++;
        }

        if ($wordCount < 5 || ($totalConf / $wordCount) < 70) return '';

        $text = trim(implode("\n", array_map(
            fn(array $ws) => implode(' ', $ws),
            $buckets
        )));
        return strlen($text) >= 50 ? $text : '';
    }

    private function tesseractCmd(string $imagePath, string $outputType): string
    {
        $suffix = ' stdout -l spa+eng --psm 3 ' . $outputType . ' 2>/dev/null';

        $bin = $this->findBin(['tesseract']);
        if ($bin) {
            return (string) @shell_exec(
                escapeshellcmd($bin) . ' ' . escapeshellarg($imagePath) . $suffix
            );
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $wslImg = $this->windowsToWslPath($imagePath);
            return (string) @shell_exec(
                'wsl -d Ubuntu -- tesseract ' . escapeshellarg($wslImg) . $suffix
            );
        }

        return '';
    }

    private function sanitizeOCRText(string $text): string
    {
        if ($text === '') return '';
        $lines = explode("\n", $text);
        // Patrones que el modelo a veces alucina al inicio (eco del prompt o meta-texto)
        $echoPatterns = [
            'lee la imagen', 'transcri', 'solo el texto', 'eres un ocr',
            'categorias', 'nombres de platos', 'descripciones', 'precios',
        ];
        $result = [];
        $started = false;
        foreach ($lines as $line) {
            if (!$started) {
                $low = strtolower(trim($line));
                $isEcho = false;
                foreach ($echoPatterns as $p) {
                    if (str_contains($low, $p)) { $isEcho = true; break; }
                }
                if ($isEcho || $low === '') continue;
                $started = true;
            }
            $result[] = $line;
        }
        return trim(implode("\n", $result));
    }

    private function windowsToWslPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if (preg_match('/^([A-Za-z]):(.*)/', $path, $m)) {
            return '/mnt/' . strtolower($m[1]) . $m[2];
        }
        return $path;
    }

    private function findBin(array $names): ?string
    {
        foreach ($names as $n) {
            $w = PHP_OS_FAMILY === 'Windows'
                ? @exec('where ' . escapeshellarg($n) . ' 2>NUL')
                : @exec('which ' . escapeshellarg($n) . ' 2>/dev/null');
            if ($w && file_exists(trim((string) $w))) return trim((string) $w);
        }
        return null;
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
