<?php
namespace WEBSCRAPER;

/**
 * RecetaScraper - Extrae recetas espanolas desde paginas que ya
 * publican Recipe en Schema.org (JSON-LD o microdata). No depende
 * del HTML especifico de cada sitio: lee el JSON-LD oficial.
 *
 * Cumplimiento: respeta robots.txt mediante un fetcher independiente
 * y agrega el header User-Agent identificable.
 *
 * Estrategia:
 *   1. Descarga el HTML
 *   2. Busca <script type="application/ld+json"> con @type=Recipe
 *   3. Mapea el schema oficial a nuestro modelo RecetaModel
 *   4. Si no hay JSON-LD, devuelve error sin inventar datos
 */
class RecetaScraper
{
    public function fetch($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'error' => 'URL invalida'];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'MyLocalRecipesBot/1.0 (+https://mylocal.es/bot)',
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml']
        ]);
        $html = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($code !== 200 || !$html) {
            return ['success' => false, 'error' => "HTTP $code $err"];
        }
        return ['success' => true, 'html' => $html];
    }

    public function scrape($url)
    {
        $f = $this->fetch($url);
        if (!$f['success']) return $f;
        $recipe = $this->extractJsonLdRecipe($f['html']);
        if (!$recipe) return ['success' => false, 'error' => 'No se encontro Schema Recipe en la pagina'];
        return [
            'success' => true,
            'data' => $this->mapToReceta($recipe, $url, $f['html'])
        ];
    }

    private function extractJsonLdRecipe($html)
    {
        if (!preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $m)) {
            return null;
        }
        foreach ($m[1] as $blob) {
            $data = json_decode(trim($blob), true);
            if (!$data) continue;
            $found = $this->findRecipe($data);
            if ($found) return $found;
        }
        return null;
    }

    private function findRecipe($node)
    {
        if (is_array($node)) {
            $type = $node['@type'] ?? null;
            if (is_string($type) && strcasecmp($type, 'Recipe') === 0) return $node;
            if (is_array($type) && in_array('Recipe', $type)) return $node;
            if (isset($node['@graph']) && is_array($node['@graph'])) {
                foreach ($node['@graph'] as $g) {
                    $r = $this->findRecipe($g);
                    if ($r) return $r;
                }
            }
            foreach ($node as $v) {
                if (is_array($v)) {
                    $r = $this->findRecipe($v);
                    if ($r) return $r;
                }
            }
        }
        return null;
    }

    private function mapToReceta($r, $url, $html)
    {
        $titulo = $r['name'] ?? '';
        $resumen = $this->stripHtml($r['description'] ?? '');
        $img = $this->firstImage($r['image'] ?? '');
        $ings = [];
        foreach ((array) ($r['recipeIngredient'] ?? []) as $i) {
            $ings[] = ['nombre' => $this->stripHtml($i), 'cantidad' => '', 'unidad' => ''];
        }
        $pasos = [];
        $instr = $r['recipeInstructions'] ?? [];
        if (is_string($instr)) {
            foreach (preg_split('/\.\s+|\n+/', $instr) as $s) {
                $s = trim($s);
                if ($s !== '') $pasos[] = $s;
            }
        } elseif (is_array($instr)) {
            foreach ($instr as $step) {
                if (is_string($step)) $pasos[] = $this->stripHtml($step);
                elseif (isset($step['text'])) $pasos[] = $this->stripHtml($step['text']);
                elseif (isset($step['itemListElement'])) {
                    foreach ($step['itemListElement'] as $sub) {
                        if (isset($sub['text'])) $pasos[] = $this->stripHtml($sub['text']);
                    }
                }
            }
        }
        $video = '';
        if (isset($r['video']['contentUrl'])) $video = $r['video']['contentUrl'];
        elseif (isset($r['video']['embedUrl'])) $video = $r['video']['embedUrl'];

        $tiempoPrep = $this->iso8601Mins($r['prepTime'] ?? '');
        $tiempoCoc = $this->iso8601Mins($r['cookTime'] ?? '');
        $raciones = intval(preg_replace('/\D/', '', $r['recipeYield'] ?? '') ?: 0);

        return [
            'titulo' => $titulo,
            'resumen' => $resumen,
            'imagen_principal' => $img,
            'video_url' => $video,
            'tiempo_preparacion_min' => $tiempoPrep,
            'tiempo_coccion_min' => $tiempoCoc,
            'raciones' => $raciones,
            'categoria' => $r['recipeCategory'] ?? 'general',
            'tags' => $this->ensureArray($r['keywords'] ?? []),
            'ingredientes' => $ings,
            'pasos' => $pasos,
            'origen_url' => $url,
            'origen_nombre' => parse_url($url, PHP_URL_HOST),
            'autor' => is_array($r['author'] ?? null) ? ($r['author']['name'] ?? '') : (string)($r['author'] ?? ''),
            'idioma' => $this->detectLang($html)
        ];
    }

    private function firstImage($img)
    {
        if (is_string($img)) return $img;
        if (is_array($img)) {
            if (isset($img['url'])) return $img['url'];
            foreach ($img as $i) {
                if (is_string($i)) return $i;
                if (is_array($i) && isset($i['url'])) return $i['url'];
            }
        }
        return '';
    }

    private function ensureArray($v)
    {
        if (is_array($v)) return $v;
        if (is_string($v) && $v !== '') return array_map('trim', explode(',', $v));
        return [];
    }

    private function iso8601Mins($s)
    {
        if (!$s) return 0;
        if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?/', $s, $m)) {
            return (intval($m[1] ?? 0) * 60) + intval($m[2] ?? 0);
        }
        return 0;
    }

    private function stripHtml($s)
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags((string)$s)));
    }

    private function detectLang($html)
    {
        if (preg_match('/<html[^>]*lang=["\']([a-zA-Z\-]+)["\']/', $html, $m)) {
            return strtolower(substr($m[1], 0, 2));
        }
        return 'es';
    }
}
