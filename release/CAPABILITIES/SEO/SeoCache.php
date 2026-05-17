<?php
declare(strict_types=1);
namespace SEO;

/**
 * SeoCache — lectura/escritura del schema SEO pre-generado en AxiDB.
 * TTL de 24h. Cualquier write de contenido (plato, reseña, post) llama
 * a invalidate() para forzar rebuild en el siguiente request.
 */
class SeoCache
{
    private const TTL = 86400; // 24 horas

    public static function get(string $localId): ?string
    {
        $doc = data_get('seo_cache', $localId);
        if (!$doc || empty($doc['schema'])) return null;
        $ts = strtotime((string)($doc['updated_at'] ?? ''));
        if ($ts === false || (time() - $ts) > self::TTL) return null;
        return (string)$doc['schema'];
    }

    public static function set(string $localId, string $schema): void
    {
        data_put('seo_cache', $localId, [
            'schema'     => $schema,
            'updated_at' => date('c'),
        ]);
    }

    public static function invalidate(string $localId): void
    {
        if ($localId !== '') data_delete('seo_cache', $localId);
    }
}
