<?php
namespace CARTA;

require_once __DIR__ . '/../../axidb/plugins/alergenos/AlergenosCatalog.php';

use AxiDB\Plugins\Alergenos\AlergenosCatalog;

/**
 * MenuEngineer - Motor IA central de la carta.
 *
 * Capa transversal por encima de Gemini y del catalogo de alergenos.
 * Cuatro responsabilidades:
 *   1. sugerirAlergenos(ingredientes, nombre)
 *   2. generarDescripcion(nombre, ingredientes)
 *   3. generarPromocion(nombre, descripcion) -> microcopy de venta
 *   4. traducir(texto, idioma)
 *
 * Todas devuelven {success, data|error}. Nada se inventa: si no hay
 * Gemini configurado, se intenta el algoritmo local (alergenos) o se
 * devuelve error claro (descripcion/promo/traduccion).
 */
class MenuEngineer
{
    private $catalog;
    private $configPath;

    public function __construct($storageRoot = null)
    {
        $root = $storageRoot ?: (defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE');
        $this->configPath = $root . '/config/gemini_settings.json';
        $this->catalog = new AlergenosCatalog($root);
    }

    public function sugerirAlergenos($ingredientes, $nombre = '')
    {
        $found = [];
        $detalle = [];
        foreach ((array) $ingredientes as $ing) {
            $hits = $this->catalog->lookupIngrediente($ing);
            if ($hits) {
                $detalle[$ing] = $hits;
                foreach ($hits as $a) $found[$a] = true;
            }
        }
        if (!$found && $nombre !== '') {
            foreach ($this->expandFromName($nombre) as $tok) {
                $hits = $this->catalog->lookupIngrediente($tok);
                if ($hits) {
                    $detalle[$tok] = $hits;
                    foreach ($hits as $a) $found[$a] = true;
                }
            }
        }
        if ($found) {
            return [
                'success' => true,
                'data' => [
                    'alergenos' => array_keys($found),
                    'detalle' => $detalle,
                    'engine' => 'catalog'
                ]
            ];
        }
        return $this->sugerirAlergenosIA($ingredientes, $nombre);
    }

    private function sugerirAlergenosIA($ingredientes, $nombre)
    {
        $cfg = $this->loadConfig();
        if (empty($cfg['api_key'])) {
            return ['success' => true, 'data' => ['alergenos' => [], 'detalle' => [], 'engine' => 'none']];
        }
        $lista = implode(', ', (array) $ingredientes);
        $codes = implode(', ', AlergenosCatalog::ALERGENOS_UE);
        $prompt = "Plato: {$nombre}. Ingredientes: {$lista}.\n"
            . "Devuelve un JSON {\"alergenos\":[...]} con SOLO los codigos UE de esta lista: {$codes}.\n"
            . "Sin texto fuera del JSON.";
        $r = $this->geminiPrompt($cfg, $prompt);
        if (!$r['success']) return $r;
        $j = json_decode(preg_replace('/^```json\s*|\s*```$/s', '', trim($r['text'])), true);
        $arr = is_array($j['alergenos'] ?? null) ? $j['alergenos'] : [];
        $valid = array_values(array_intersect($arr, AlergenosCatalog::ALERGENOS_UE));
        return ['success' => true, 'data' => ['alergenos' => $valid, 'detalle' => [], 'engine' => 'gemini']];
    }

    public function generarDescripcion($nombre, $ingredientes = [])
    {
        $cfg = $this->loadConfig();
        if (empty($cfg['api_key'])) return ['success' => false, 'error' => 'Gemini no configurado'];
        $lista = is_array($ingredientes) ? implode(', ', $ingredientes) : (string) $ingredientes;
        $prompt = "Eres copywriter gastronomico espanol. Escribe UNA frase corta (max 18 palabras), "
            . "sugerente y apetitosa, sin emojis, sin signos de admiracion, para este plato:\n"
            . "Nombre: {$nombre}\nIngredientes: {$lista}\n"
            . "Devuelve solo la frase, sin comillas.";
        $r = $this->geminiPrompt($cfg, $prompt);
        if (!$r['success']) return $r;
        $text = trim(preg_replace('/^"|"$/', '', trim($r['text'])));
        return ['success' => true, 'data' => ['descripcion' => $text]];
    }

    public function generarPromocion($nombre, $descripcion = '')
    {
        $cfg = $this->loadConfig();
        if (empty($cfg['api_key'])) return ['success' => false, 'error' => 'Gemini no configurado'];
        $prompt = "Eres copywriter de venta gastronomica. Escribe una micro-promocion de 6 a 10 palabras "
            . "para destacar este plato como especialidad de la casa. Sin emojis. Sin signos. Tono cercano.\n"
            . "Plato: {$nombre}\nDescripcion: {$descripcion}";
        $r = $this->geminiPrompt($cfg, $prompt);
        if (!$r['success']) return $r;
        $text = trim(preg_replace('/^"|"$/', '', trim($r['text'])));
        return ['success' => true, 'data' => ['promocion' => $text]];
    }

    public function sugerirCategorias(string $tipoNegocio): array
    {
        $cfg = $this->loadConfig();
        if (empty($cfg['api_key'])) return ['success' => false, 'error' => 'Gemini no configurado'];
        $prompt = "Eres experto en hostelería española. Para un negocio de tipo '{$tipoNegocio}', "
            . "sugiere entre 4 y 7 categorías de carta habituales y concretas. "
            . "Devuelve SOLO un JSON {\"categorias\":[\"Nombre1\",\"Nombre2\",...]} sin texto adicional.";
        $r = $this->geminiPrompt($cfg, $prompt);
        if (!$r['success']) return $r;
        $json = preg_replace('/^```json\s*|\s*```$/s', '', trim($r['text']));
        $data = json_decode($json, true);
        $cats = is_array($data['categorias'] ?? null) ? $data['categorias'] : [];
        return ['success' => true, 'data' => ['categorias' => $cats]];
    }

    public function traducir($texto, $idioma)
    {
        $cfg = $this->loadConfig();
        if (empty($cfg['api_key'])) return ['success' => false, 'error' => 'Gemini no configurado'];
        $mapa = ['en' => 'ingles', 'fr' => 'frances', 'de' => 'aleman', 'es' => 'espanol'];
        $idiomaTxt = $mapa[$idioma] ?? $idioma;
        $prompt = "Traduce al {$idiomaTxt} este texto gastronomico respetando el contexto culinario. "
            . "Devuelve solo la traduccion, sin comillas, sin nada mas:\n{$texto}";
        $r = $this->geminiPrompt($cfg, $prompt);
        if (!$r['success']) return $r;
        return ['success' => true, 'data' => ['texto' => trim($r['text'])]];
    }

    private function expandFromName($nombre)
    {
        $tokens = preg_split('/[\s,]+/', mb_strtolower($nombre, 'UTF-8'));
        return array_filter($tokens, function ($t) { return mb_strlen($t) >= 4; });
    }

    private function geminiPrompt($cfg, $prompt)
    {
        $model = $cfg['model'] ?? 'gemini-2.5-flash';
        $key = $cfg['api_key'];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
        $payload = ['contents' => [['parts' => [['text' => $prompt]]]]];
        $body = json_encode($payload);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $body,
                    'timeout' => 30,
                    'ignore_errors' => true,
                ],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $resp = @file_get_contents($url, false, $ctx);
            $code = 0;
            foreach (($http_response_header ?? []) as $h) {
                if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $h, $m)) $code = (int) $m[1];
            }
        }
        if ($code !== 200) return ['success' => false, 'error' => "HTTP $code"];
        $data = json_decode((string) $resp, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($text === '') return ['success' => false, 'error' => 'Respuesta vacia'];
        return ['success' => true, 'text' => $text];
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
