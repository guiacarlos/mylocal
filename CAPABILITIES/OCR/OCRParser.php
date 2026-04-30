<?php
namespace OCR;

/**
 * OCRParser - Convierte texto plano OCR en estructura JSON de carta.
 *
 * Estrategia hibrida:
 *   1. Primer paso heuristico: detecta lineas con precio y agrupa por
 *      bloques (categoria = linea sin precio precedida de hueco).
 *   2. Si Gemini esta disponible, hace una segunda pasada para refinar
 *      y devolver la estructura final.
 */
class OCRParser
{
    private $configPath;

    public function __construct($storageRoot = null)
    {
        $root = $storageRoot ?: (defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE');
        $this->configPath = $root . '/config/gemini_settings.json';
    }

    public function parse($rawText)
    {
        if (!is_string($rawText) || trim($rawText) === '') {
            return ['success' => false, 'error' => 'Texto OCR vacio'];
        }
        $cfg = $this->loadConfig();
        if (!empty($cfg['api_key'])) {
            $smart = $this->parseWithGemini($rawText, $cfg);
            if ($smart['success']) return $smart;
        }
        return $this->parseHeuristic($rawText);
    }

    private function parseHeuristic($text)
    {
        $lines = preg_split('/\r?\n/', $text);
        $categorias = [];
        $current = null;
        $priceRegex = '/(\d{1,3}(?:[\.,]\d{1,2})?)\s*(?:€|eur|EUR)?\s*$/u';

        foreach ($lines as $raw) {
            $line = trim($raw);
            if ($line === '' || preg_match('/^---/', $line)) continue;

            if (preg_match($priceRegex, $line, $m)) {
                $precio = floatval(str_replace(',', '.', $m[1]));
                $nombre = trim(preg_replace($priceRegex, '', $line));
                $nombre = trim($nombre, " .-:_\t");
                if ($nombre === '') continue;
                if ($current === null) {
                    $current = ['nombre' => 'Carta', 'productos' => []];
                    $categorias[] =& $current;
                }
                $current['productos'][] = [
                    'nombre' => $nombre,
                    'precio' => $precio,
                    'descripcion' => ''
                ];
            } else {
                if (mb_strlen($line) <= 60 && !preg_match('/[.!?]$/', $line)) {
                    unset($current);
                    $current = ['nombre' => $this->cleanCategoryName($line), 'productos' => []];
                    $categorias[] =& $current;
                } elseif ($current !== null && !empty($current['productos'])) {
                    $idx = count($current['productos']) - 1;
                    $current['productos'][$idx]['descripcion'] = trim(
                        ($current['productos'][$idx]['descripcion'] . ' ' . $line)
                    );
                }
            }
        }
        unset($current);

        $categorias = array_values(array_filter($categorias, function ($c) {
            return !empty($c['productos']);
        }));

        return ['success' => true, 'data' => ['categorias' => $categorias], 'engine' => 'heuristic'];
    }

    private function parseWithGemini($text, $cfg)
    {
        $model = $cfg['model'] ?? 'gemini-1.5-flash';
        $key = $cfg['api_key'];

        $instr = "Eres un parser de cartas de restaurante. Recibes texto OCR de una carta "
            . "y devuelves UNICAMENTE un JSON valido con esta forma exacta:\n"
            . '{"categorias":[{"nombre":"...","productos":[{"nombre":"...","descripcion":"...","precio":0.00}]}]}'
            . "\n\nReglas:\n"
            . "- precio: numero decimal, sin simbolo. Si no hay precio, usa 0.\n"
            . "- descripcion: cadena vacia si no hay descripcion clara.\n"
            . "- categorias: agrupa por las cabeceras visibles (ENTRANTES, CARNES, etc).\n"
            . "- Sin texto fuera del JSON. Sin markdown. Solo JSON puro.\n\n"
            . "Texto OCR:\n" . $text;

        $payload = ['contents' => [['parts' => [['text' => $instr]]]]];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 60
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) return ['success' => false, 'error' => "HTTP $code"];
        $data = json_decode($resp, true);
        $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $jsonText = preg_replace('/^```json\s*|\s*```$/s', '', trim($jsonText));
        $parsed = json_decode($jsonText, true);
        if (!is_array($parsed) || !isset($parsed['categorias'])) {
            return ['success' => false, 'error' => 'JSON invalido de Gemini'];
        }
        return ['success' => true, 'data' => $parsed, 'engine' => 'gemini_parser'];
    }

    private function cleanCategoryName($s)
    {
        $s = trim($s, " .-:_\t");
        if (mb_strtoupper($s, 'UTF-8') === $s) {
            return mb_convert_case(mb_strtolower($s, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }
        return $s;
    }

    private function loadConfig()
    {
        if (!file_exists($this->configPath)) return [];
        return json_decode(@file_get_contents($this->configPath), true) ?: [];
    }
}
