<?php
/**
 * Reviews handler — reseñas de clientes con Schema.org.
 *
 * Acciones:
 *   create_review        (pública, token invite) cliente deja reseña
 *   list_reviews         (pública) lista reseñas del local
 *   get_review_aggregate (pública) media + distribución de estrellas
 *   delete_review        (auth)    dueño borra reseña
 *   respond_review       (auth)    dueño responde reseña
 *   get_invite_link      (auth)    genera enlace firmado para invitación
 */

declare(strict_types=1);

require_once realpath(__DIR__ . '/../../../CAPABILITIES/REVIEWS/ReviewModel.php');

function reviews_seo_invalidate(string $localId): void
{
    if ($localId === '') return;
    require_once realpath(__DIR__ . '/../../../CAPABILITIES/SEO/SeoBuilder.php');
    \SEO\SeoBuilder::invalidateCache($localId);
}

function handle_reviews(string $action, array $req): array
{
    switch ($action) {
        case 'create_review':        return reviews_create($req);
        case 'list_reviews':         return reviews_list($req);
        case 'get_review_aggregate': return reviews_aggregate($req);
        case 'delete_review':        return reviews_delete($req);
        case 'respond_review':       return reviews_respond($req);
        case 'get_invite_link':      return reviews_invite_link($req);
        default: throw new RuntimeException("Acción reviews no reconocida: $action");
    }
}

function reviews_create(array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = s_str($data['local_id'] ?? '');
    if ($localId === '') throw new RuntimeException('local_id obligatorio');

    $estrellas = max(1, min(5, (int) ($data['estrellas'] ?? 5)));
    $comentario = s_str($data['comentario'] ?? '', 1000);
    $autor      = s_str($data['autor'] ?? 'Anónimo', 100);
    $token      = (string) ($data['invite_token'] ?? '');

    // Reseña anónima válida siempre; con token se puede marcar verificado en el futuro
    $r = \Reviews\ReviewModel::create([
        'local_id'     => $localId,
        'autor'        => $autor,
        'estrellas'    => $estrellas,
        'comentario'   => $comentario,
        'invite_token' => $token,
        'verificado'   => false,
    ]);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error create_review');
    reviews_seo_invalidate($localId);
    return $r['data'];
}

function reviews_list(array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = s_str($data['local_id'] ?? '');
    if ($localId === '') throw new RuntimeException('local_id obligatorio');
    $limit  = max(1, min(100, (int) ($data['limit']  ?? 20)));
    $offset = max(0, (int) ($data['offset'] ?? 0));
    return ['items' => \Reviews\ReviewModel::listByLocal($localId, $limit, $offset)];
}

function reviews_aggregate(array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = s_str($data['local_id'] ?? '');
    if ($localId === '') throw new RuntimeException('local_id obligatorio');
    return \Reviews\ReviewModel::aggregate($localId);
}

function reviews_delete(array $req): array
{
    $data    = $req['data'] ?? $req;
    $id      = s_id($data['id'] ?? '');
    if ($id === '') throw new RuntimeException('id obligatorio');
    $review  = \Reviews\ReviewModel::read($id);
    $localId = (string)(($review ?? [])['local_id'] ?? '');
    \Reviews\ReviewModel::delete($id);
    reviews_seo_invalidate($localId);
    return ['ok' => true];
}

function reviews_respond(array $req): array
{
    $data      = $req['data'] ?? $req;
    $id        = s_id($data['id'] ?? '');
    $respuesta = s_str($data['respuesta'] ?? '', 500);
    if ($id === '') throw new RuntimeException('id obligatorio');
    $r = \Reviews\ReviewModel::respond($id, $respuesta);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error respond_review');
    return $r['data'];
}

function reviews_invite_link(array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = s_str($data['local_id'] ?? '');
    if ($localId === '') throw new RuntimeException('local_id obligatorio');
    $token = \Reviews\ReviewModel::generateInviteToken($localId);
    // En dev: localhost. En prod: <slug>.mylocal.es
    $base  = $_SERVER['HTTP_ORIGIN'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'mylocal.es'));
    return ['url' => $base . '/carta?review=' . urlencode($token) . '&local_id=' . urlencode($localId)];
}
