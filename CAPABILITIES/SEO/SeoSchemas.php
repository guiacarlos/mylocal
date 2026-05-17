<?php
declare(strict_types=1);
namespace SEO;

/**
 * SeoSchemas — constructores de nodos Schema.org para menú, reseñas, posts y FAQ.
 * Todos los datos vienen del array de entrada: cero hardcodeos de dominio o contenido.
 */
class SeoSchemas
{
    public static function menuGraph(array $categorias, array $platos): array
    {
        $sections = [];
        foreach ($categorias as $cat) {
            $items = array_values(array_filter($platos, fn($p) => ($p['categoria_id'] ?? '') === ($cat['id'] ?? '')));
            if (empty($items)) continue;
            $sections[] = [
                '@type'       => 'MenuSection',
                'name'        => $cat['nombre'] ?? '',
                'hasMenuItem' => array_map([self::class, 'menuItem'], $items),
            ];
        }
        return ['@type' => 'Menu', 'hasMenuSection' => $sections];
    }

    private static function menuItem(array $p): array
    {
        $item = [
            '@type'  => 'MenuItem',
            'name'   => $p['nombre'] ?? '',
            'offers' => [
                '@type'         => 'Offer',
                'price'         => number_format((float)($p['precio'] ?? 0), 2, '.', ''),
                'priceCurrency' => 'EUR',
            ],
        ];
        if (!empty($p['descripcion']))  $item['description'] = (string)$p['descripcion'];
        if (!empty($p['alt_text']))     $item['image']       = $p['imagen_url'] ?? $p['media_url'] ?? '';
        $diets = self::inferDiets((array)($p['alergenos'] ?? []));
        if ($diets) $item['suitableForDiet'] = count($diets) === 1 ? $diets[0] : $diets;
        return array_filter($item, fn($v) => $v !== '' && $v !== null && $v !== []);
    }

    private static function inferDiets(array $alergenos): array
    {
        $lower = array_map('mb_strtolower', $alergenos);
        $diets = [];
        if (in_array('vegano',      $lower, true)) $diets[] = 'https://schema.org/VeganDiet';
        if (in_array('vegetariano', $lower, true)) $diets[] = 'https://schema.org/VegetarianDiet';
        return $diets;
    }

    /** Máximo 10 reseñas con comentario no vacío — Google ignora más. */
    public static function reviewGraph(array $reviews): array
    {
        $valid = array_filter($reviews, fn($r) => !empty($r['comentario']));
        return array_values(array_map(fn($r) => [
            '@type'         => 'Review',
            'reviewRating'  => ['@type' => 'Rating', 'ratingValue' => (int)($r['estrellas'] ?? 5)],
            'author'        => ['@type' => 'Person', 'name' => $r['autor'] ?? 'Anónimo'],
            'datePublished' => substr($r['fecha'] ?? $r['_createdAt'] ?? date('c'), 0, 10),
            'reviewBody'    => $r['comentario'],
        ], array_slice(array_values($valid), 0, 10)));
    }

    /** Solo posts con media_url — los de solo texto no generan rich result visual. */
    public static function postGraph(array $posts, string $localNombre): array
    {
        $withMedia = array_filter($posts, fn($p) => !empty($p['media_url']));
        return array_values(array_map(fn($p) => [
            '@type'         => 'SocialMediaPosting',
            'headline'      => $p['titulo'] ?? '',
            'text'          => $p['descripcion'] ?? '',
            'image'         => $p['media_url'],
            'datePublished' => substr($p['publicado_at'] ?? $p['_createdAt'] ?? date('c'), 0, 10),
            'author'        => ['@type' => 'Organization', 'name' => $localNombre],
        ], $withMedia));
    }

    public static function faqGraph(array $faqs): array
    {
        return [
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(fn($f) => [
                '@type'          => 'Question',
                'name'           => $f['pregunta'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['respuesta']],
            ], $faqs),
        ];
    }
}
