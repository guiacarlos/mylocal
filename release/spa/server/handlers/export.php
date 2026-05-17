<?php
/**
 * export.php — exportación de datos del local (RGPD art. 20, portabilidad).
 *
 * Acción: export_local_data
 * Requiere: sesión + rol admin/hostelero del local.
 * Devuelve: JSON con local, carta completa, reseñas, posts y citas.
 * Uso: el cliente descarga el JSON como archivo.
 */

declare(strict_types=1);

function handle_export_local_data(array $req, array $user): array
{
    require_once realpath(__DIR__ . '/../../../CAPABILITIES') . '/LOCALES/LocalModel.php';

    $localId = s_id((string)($req['data']['id'] ?? $req['id'] ?? 'l_default'));

    if (!\Locales\LocalModel::userCanAccess($user, $localId)) {
        throw new \RuntimeException('Sin permisos sobre este local');
    }

    // Datos del local
    $local = data_get('locales', $localId) ?? [];

    // Carta jerárquica: cartas → categorías → productos
    $allCartas = data_all('cartas');
    $cartas = array_values(array_filter($allCartas, fn($c) => ($c['local_id'] ?? '') === $localId));
    $cartaIds = array_column($cartas, 'id');

    $allCats = data_all('carta_categorias');
    $categorias = array_values(array_filter($allCats, fn($c) => in_array($c['carta_id'] ?? '', $cartaIds, true)));

    $allProds = data_all('carta_productos');
    $productos = array_values(array_filter($allProds, fn($p) => in_array($p['carta_id'] ?? '', $cartaIds, true)));

    // Contenido publicado (timeline)
    $allPosts = data_all('timeline_posts');
    $posts = array_values(array_filter($allPosts, fn($p) => ($p['local_id'] ?? '') === $localId));

    // Reseñas
    $allReviews = data_all('reviews');
    $reviews = array_values(array_filter($allReviews, fn($r) => ($r['local_id'] ?? '') === $localId));

    // Citas/reservas
    $allCitas = data_all('citas');
    $citas = array_values(array_filter($allCitas, fn($c) => ($c['local_id'] ?? '') === $localId));

    // Quitar campos internos sensibles de cualquier colección
    $sensitive = ['code', 'token', 'secret', 'password_hash', 'ua_hash'];
    $clean = function(array $doc) use ($sensitive): array {
        return array_diff_key($doc, array_flip($sensitive));
    };

    return [
        'exported_at' => date('c'),
        'local_id'    => $localId,
        'format'      => 'mylocal-export-v1',
        'local'       => $clean($local),
        'carta'       => [
            'cartas'     => array_map($clean, $cartas),
            'categorias' => array_map($clean, $categorias),
            'productos'  => array_map($clean, $productos),
        ],
        'posts'   => array_map($clean, $posts),
        'reviews' => array_map($clean, $reviews),
        'citas'   => array_map($clean, $citas),
    ];
}
