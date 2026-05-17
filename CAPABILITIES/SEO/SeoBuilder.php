<?php
declare(strict_types=1);
namespace SEO;

require_once __DIR__ . '/SeoCache.php';
require_once __DIR__ . '/SeoSchemas.php';

/**
 * SeoBuilder — orquestador principal del Schema.org por local.
 *
 * buildRestaurant(): construye el nodo Restaurant desde los datos del local.
 * buildFullPage():   genera el @graph completo y lo guarda en caché.
 * getOrBuild():      sirve desde caché o reconstruye.
 * invalidateCache(): llamado por CARTA, REVIEWS, TIMELINE y LOCAL en cada write.
 *
 * Cero datos hardcodeados: todo viene del array $local de AxiDB.
 */
class SeoBuilder
{
    public static function buildRestaurant(array $local): array
    {
        $schema = [
            '@type'       => ['LocalBusiness', 'FoodEstablishment', 'Restaurant'],
            '@id'         => self::baseUrl() . '/#local',
            'name'        => $local['nombre'] ?? '',
            'url'         => self::baseUrl() . '/carta',
            'description' => $local['descripcion'] ?? '',
        ];

        if (!empty($local['imagen_hero'])) $schema['image']     = $local['imagen_hero'];
        if (!empty($local['telefono']))    $schema['telephone'] = $local['telefono'];
        if (!empty($local['url_maps']))    $schema['hasMap']    = $local['url_maps'];

        if (isset($local['acepta_reservas'])) {
            $schema['acceptsReservations'] = $local['acepta_reservas'] ? 'True' : 'False';
        }

        $cocina = array_values(array_filter((array)($local['tipo_cocina'] ?? [])));
        if ($cocina) $schema['servesCuisine'] = count($cocina) === 1 ? $cocina[0] : $cocina;

        if (!empty($local['precio_medio'])) $schema['priceRange'] = $local['precio_medio'];

        $addr = self::buildAddress($local);
        if ($addr) $schema['address'] = $addr;

        if (!empty($local['lat']) && !empty($local['lng'])) {
            $schema['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float)$local['lat'],
                'longitude' => (float)$local['lng'],
            ];
        }

        if (!empty($local['horario']) && is_array($local['horario'])) {
            $hours = self::buildOpeningHours($local['horario']);
            if ($hours) $schema['openingHoursSpecification'] = $hours;
        }

        $sameAs = array_values(array_filter([
            $local['url_maps']   ?? '',
            $local['instagram']  ?? '',
        ]));
        if ($sameAs) $schema['sameAs'] = $sameAs;

        return $schema;
    }

    private static function buildAddress(array $local): ?array
    {
        $dir = $local['direccion'] ?? '';
        if (is_array($dir) && !empty($dir)) {
            $street = trim(($dir['calle'] ?? '') . ' ' . ($dir['numero'] ?? ''));
            return array_filter([
                '@type'           => 'PostalAddress',
                'streetAddress'   => $street,
                'addressLocality' => $dir['ciudad'] ?? '',
                'postalCode'      => $dir['cp'] ?? '',
                'addressRegion'   => $dir['provincia'] ?? '',
                'addressCountry'  => $dir['pais'] ?? 'ES',
            ], fn($v) => $v !== '');
        }
        if (is_string($dir) && $dir !== '') {
            return ['@type' => 'PostalAddress', 'streetAddress' => $dir, 'addressCountry' => 'ES'];
        }
        return null;
    }

    private static function buildOpeningHours(array $horario): array
    {
        static $map = [
            'Lu'=>'Monday','Ma'=>'Tuesday','Mi'=>'Wednesday','Ju'=>'Thursday',
            'Vi'=>'Friday','Sa'=>'Saturday','Do'=>'Sunday',
            'Mo'=>'Monday','Tu'=>'Tuesday','We'=>'Wednesday',
            'Th'=>'Thursday','Fr'=>'Friday','Su'=>'Sunday',
        ];
        $specs = [];
        foreach ($horario as $d) {
            if (!empty($d['cerrado'])) continue;
            $day = $map[$d['dia'] ?? ''] ?? null;
            if (!$day || empty($d['abre']) || empty($d['cierra'])) continue;
            $specs[] = [
                '@type'      => 'OpeningHoursSpecification',
                'dayOfWeek'  => 'https://schema.org/' . $day,
                'opens'      => $d['abre'],
                'closes'     => $d['cierra'],
            ];
        }
        return $specs;
    }

    public static function buildFullPage(string $localId): string
    {
        self::loadModels();
        $local     = data_get('locales', $localId) ?? [];
        $categorias = \Carta\CategoriaModel::listByLocal($localId);
        $platos    = \Carta\ProductoModel::listByLocal($localId);
        $reviews   = \Reviews\ReviewModel::listByLocal($localId, 10);
        $agg       = \Reviews\ReviewModel::aggregate($localId);
        $posts     = \Timeline\TimelineModel::listByLocal($localId, 6);

        $restaurant = self::buildRestaurant($local);

        if ($agg['count'] > 0) {
            $restaurant['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => round((float)$agg['media'], 1),
                'reviewCount' => (int)$agg['count'],
            ];
        }

        $menu = SeoSchemas::menuGraph($categorias, $platos);
        if (!empty($menu['hasMenuSection'])) $restaurant['hasMenu'] = $menu;

        $graph = [$restaurant];
        foreach (SeoSchemas::reviewGraph($reviews) as $r) $graph[] = $r;
        foreach (SeoSchemas::postGraph($posts, $local['nombre'] ?? '') as $p) $graph[] = $p;

        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        SeoCache::set($localId, $json);
        return $json;
    }

    public static function getOrBuild(string $localId): string
    {
        return SeoCache::get($localId) ?? self::buildFullPage($localId);
    }

    public static function invalidateCache(string $localId): void
    {
        SeoCache::invalidate($localId);
    }

    private static function loadModels(): void
    {
        $cap = realpath(__DIR__ . '/../../CAPABILITIES') ?: (__DIR__ . '/../../CAPABILITIES');
        if (!class_exists('\\Carta\\CategoriaModel'))
            @require_once $cap . '/CARTA/CategoriaModel.php';
        if (!class_exists('\\Carta\\ProductoModel'))
            @require_once $cap . '/CARTA/ProductoModel.php';
        if (!class_exists('\\Reviews\\ReviewModel'))
            @require_once $cap . '/REVIEWS/ReviewModel.php';
        if (!class_exists('\\Timeline\\TimelineModel'))
            @require_once $cap . '/TIMELINE/TimelineModel.php';
    }

    private static function baseUrl(): string
    {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'mylocal.es');
    }
}
