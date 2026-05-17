<?php
declare(strict_types=1);
namespace SEO;

require_once __DIR__ . '/SeoBuilder.php';

/**
 * SeoEndpoints — endpoints GET para sitemap.xml y llms.txt por local.
 * Llamado desde router.php cuando detecta /carta/sitemap.xml o /carta/llms.txt.
 * Requiere que lib.php y SubdomainManager estén cargados por router.php antes.
 */
class SeoEndpoints
{
    public static function sitemap(string $localId): void
    {
        self::loadModels();
        $platos = \Carta\ProductoModel::listByLocal($localId);
        $posts  = \Timeline\TimelineModel::listByLocal($localId, 50);
        $base   = self::base();

        $urls = [
            ['loc' => "$base/carta", 'changefreq' => 'weekly', 'priority' => '1.0', 'lastmod' => date('Y-m-d')],
        ];

        // lastmod del plato más reciente
        if ($platos) {
            $last = max(array_map(fn($p) => strtotime($p['_updatedAt'] ?? $p['_createdAt'] ?? '0') ?: 0, $platos));
            if ($last > 0) $urls[0]['lastmod'] = date('Y-m-d', $last);
        }

        foreach ($posts as $p) {
            if (empty($p['media_url'])) continue;
            $urls[] = [
                'loc'        => "$base/carta",
                'changefreq' => 'daily',
                'priority'   => '0.8',
                'lastmod'    => substr($p['publicado_at'] ?? $p['_createdAt'] ?? date('c'), 0, 10),
            ];
            break;
        }

        foreach (['/legal/privacidad', '/legal/aviso', '/legal/cookies'] as $legal) {
            $urls[] = ['loc' => $base . $legal, 'changefreq' => 'yearly', 'priority' => '0.3'];
        }

        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo "  <url>\n    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            if (!empty($u['lastmod']))    echo "    <lastmod>{$u['lastmod']}</lastmod>\n";
            if (!empty($u['changefreq'])) echo "    <changefreq>{$u['changefreq']}</changefreq>\n";
            if (!empty($u['priority']))   echo "    <priority>{$u['priority']}</priority>\n";
            echo "  </url>\n";
        }
        echo '</urlset>';
    }

    public static function llmsTxt(string $localId): void
    {
        self::loadModels();
        $local  = data_get('locales', $localId) ?? [];
        $cats   = \Carta\CategoriaModel::listByLocal($localId);
        $platos = \Carta\ProductoModel::listByLocal($localId);
        $agg    = \Reviews\ReviewModel::aggregate($localId);
        $posts  = \Timeline\TimelineModel::listByLocal($localId, 5);
        $base   = self::base();

        $nombre   = $local['nombre'] ?? $localId;
        $desc     = $local['descripcion'] ?? '';
        $tel      = $local['telefono'] ?? '';
        $precio   = $local['precio_medio'] ?? '';
        $cocina   = implode(', ', (array)($local['tipo_cocina'] ?? []));
        $dir      = $local['direccion'] ?? [];
        $ciudad   = is_array($dir) ? ($dir['ciudad'] ?? '') : '';
        $dirStr   = is_array($dir)
            ? trim(($dir['calle'] ?? '') . ' ' . ($dir['numero'] ?? '') . ', ' . ($dir['cp'] ?? '') . ' ' . $ciudad)
            : (is_string($dir) ? $dir : '');
        $horarioStr = self::horarioResumen((array)($local['horario'] ?? []));

        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');

        $lines = ["# $nombre"];
        if ($desc) $lines[] = "> $desc";
        $lines[] = '';
        $lines[] = '## Información';
        if ($dirStr)     $lines[] = "Dirección: $dirStr";
        if ($tel)        $lines[] = "Teléfono: $tel";
        if ($horarioStr) $lines[] = "Horario: $horarioStr";
        if ($precio)     $lines[] = "Precio medio: $precio";
        if ($cocina)     $lines[] = "Tipo de cocina: $cocina";
        $lines[] = '';
        $lines[] = '## Carta';
        $lines[] = "$base/carta — Carta completa con precios y alérgenos";
        $nP = count($platos); $nC = count($cats);
        $lines[] = "$nP platos en $nC categorías.";
        $catNames = implode(', ', array_column($cats, 'nombre'));
        if ($catNames) $lines[] = "Categorías: $catNames";
        $lines[] = '';
        $lines[] = '## Reseñas';
        $lines[] = $agg['count'] > 0
            ? round((float)$agg['media'], 1) . ' sobre 5 — ' . $agg['count'] . ' valoraciones verificadas.'
            : 'Sin reseñas aún.';
        if ($posts) {
            $lines[] = '';
            $lines[] = '## Últimas novedades';
            foreach ($posts as $p) {
                $fecha = substr($p['publicado_at'] ?? $p['_createdAt'] ?? '', 0, 10);
                $lines[] = '- ' . ($p['titulo'] ?? '') . ($fecha ? " ($fecha)" : '');
            }
        }
        $reservas = !empty($local['acepta_reservas']);
        $lines[] = '';
        $lines[] = '## Reservas';
        $lines[] = $reservas ? "Acepta reservas. Contactar en $tel." : 'Sin reservas. Servicio directo.';
        echo implode("\n", $lines);
    }

    private static function horarioResumen(array $horario): string
    {
        $open = [];
        foreach ($horario as $d) {
            if (!empty($d['cerrado'])) continue;
            $open[] = ($d['dia'] ?? '') . ' ' . ($d['abre'] ?? '') . '–' . ($d['cierra'] ?? '');
        }
        return implode(', ', $open);
    }

    private static function base(): string
    {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'mylocal.es');
    }

    private static function loadModels(): void
    {
        $cap = realpath(__DIR__ . '/../../CAPABILITIES') ?: (__DIR__ . '/../../CAPABILITIES');
        if (!class_exists('\\Carta\\CategoriaModel'))   @require_once $cap . '/CARTA/CategoriaModel.php';
        if (!class_exists('\\Carta\\ProductoModel'))    @require_once $cap . '/CARTA/ProductoModel.php';
        if (!class_exists('\\Reviews\\ReviewModel'))    @require_once $cap . '/REVIEWS/ReviewModel.php';
        if (!class_exists('\\Timeline\\TimelineModel')) @require_once $cap . '/TIMELINE/TimelineModel.php';
    }
}
