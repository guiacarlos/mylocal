<?php
/**
 * SEO handler — schema JSON-LD por local.
 *
 * Acciones:
 *   get_local_schema (pública) — devuelve el schema @graph completo del local,
 *                                desde caché (24h) o reconstruido al vuelo.
 *
 * Sitemap y llms.txt no pasan por el dispatcher: se sirven como GET desde
 * router.php llamando a CAPABILITIES/SEO/SeoEndpoints.php directamente.
 */

declare(strict_types=1);

require_once realpath(__DIR__ . '/../../../CAPABILITIES/SEO/SeoBuilder.php');

function handle_seo(string $action, array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = s_str($data['local_id'] ?? '');
    if ($localId === '') throw new RuntimeException('local_id obligatorio');

    return match ($action) {
        'get_local_schema' => ['schema' => \SEO\SeoBuilder::getOrBuild($localId)],
        default            => throw new RuntimeException("Acción SEO no reconocida: $action"),
    };
}
