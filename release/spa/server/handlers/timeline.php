<?php
/**
 * Timeline handler — publicaciones del dueño (fotos, texto, vídeo).
 *
 * Acciones:
 *   create_post          (auth) crea publicación (texto o con media ya subida)
 *   list_posts           (pública) lista posts del local
 *   delete_post          (auth) elimina post + archivo media
 *   upload_timeline_media (auth) sube imagen/vídeo, devuelve media_url
 *
 * Límite Demo: TimelineModel::DEMO_MAX posts por local.
 */

declare(strict_types=1);

require_once realpath(__DIR__ . '/../../../CAPABILITIES/TIMELINE/TimelineModel.php');

function timeline_seo_invalidate(string $localId): void
{
    if ($localId === '') return;
    require_once realpath(__DIR__ . '/../../../CAPABILITIES/SEO/SeoBuilder.php');
    \SEO\SeoBuilder::invalidateCache($localId);
}

function handle_timeline(string $action, array $req, array $files = []): array
{
    switch ($action) {
        case 'create_post':             return timeline_create_post($req);
        case 'list_posts':              return timeline_list_posts($req);
        case 'delete_post':             return timeline_delete_post($req);
        case 'upload_timeline_media':   return timeline_upload_media($req, $files);
        default: throw new RuntimeException("Acción timeline no reconocida: $action");
    }
}

function timeline_create_post(array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = s_str($data['local_id'] ?? '');
    if ($localId === '') throw new RuntimeException('local_id obligatorio');

    // Límite demo: 50 posts
    $current = \Timeline\TimelineModel::countByLocal($localId);
    if ($current >= \Timeline\TimelineModel::DEMO_MAX) {
        resp(false, ['upgrade_url' => '/dashboard/facturacion'], 'PLAN_LIMIT');
    }

    $r = \Timeline\TimelineModel::create($data);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error create_post');
    timeline_seo_invalidate($localId);
    return $r['data'];
}

function timeline_list_posts(array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = s_str($data['local_id'] ?? '');
    if ($localId === '') throw new RuntimeException('local_id obligatorio');
    $limit  = max(1, min(100, (int) ($data['limit']  ?? 20)));
    $offset = max(0, (int) ($data['offset'] ?? 0));
    return ['items' => \Timeline\TimelineModel::listByLocal($localId, $limit, $offset)];
}

function timeline_delete_post(array $req): array
{
    $data    = $req['data'] ?? $req;
    $id      = s_id($data['id'] ?? '');
    if ($id === '') throw new RuntimeException('id obligatorio');
    $post    = \Timeline\TimelineModel::read($id);
    $localId = (string)(($post ?? [])['local_id'] ?? '');
    if ($post && !empty($post['media_url'])) {
        $mediaRoot = realpath(__DIR__ . '/../../../MEDIA') ?: (__DIR__ . '/../../../MEDIA');
        $file = $mediaRoot . preg_replace('/^\/MEDIA/', '', $post['media_url']);
        if (file_exists($file)) @unlink($file);
    }
    \Timeline\TimelineModel::delete($id);
    timeline_seo_invalidate($localId);
    return ['ok' => true];
}

function timeline_upload_media(array $req, array $files): array
{
    $data    = $req['data'] ?? [];
    $localId = s_str($data['local_id'] ?? $_POST['local_id'] ?? '');
    if ($localId === '') throw new RuntimeException('local_id obligatorio');

    $f = $files['file'] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se recibió archivo o error de subida');
    }
    if (($f['size'] ?? 0) > 20 * 1024 * 1024) {
        throw new RuntimeException('Archivo demasiado grande (max 20 MB)');
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm'];
    $ext     = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException("Formato no permitido: $ext");
    }

    $cleanLocalId = preg_replace('/[^a-z0-9_\-]/', '', strtolower($localId));
    $mediaRoot    = realpath(__DIR__ . '/../../../MEDIA') ?: (__DIR__ . '/../../../MEDIA');
    $dir          = $mediaRoot . '/' . $cleanLocalId . '/timeline';

    $titulo = s_str($data['titulo'] ?? $data['caption'] ?? '', 60);

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        require_once realpath(__DIR__ . '/../../../CORE/MediaUploader.php');
        $filename = \MediaUploader::buildFilename($cleanLocalId, 'post', $titulo, $ext);
        $saved    = \MediaUploader::processAndSave($f['tmp_name'], $dir, $filename);
        $relFile  = basename($saved);
        $finalExt = strtolower(pathinfo($relFile, PATHINFO_EXTENSION));
    } else {
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest     = $dir . '/' . $filename;
        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            throw new RuntimeException('Error guardando archivo');
        }
        $relFile = $filename;
    }

    return ['media_url' => '/MEDIA/' . $cleanLocalId . '/timeline/' . $relFile];
}
