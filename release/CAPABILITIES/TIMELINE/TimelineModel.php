<?php
namespace Timeline;

/**
 * TimelineModel - publicaciones del dueño (fotos, texto, vídeo corto).
 *
 * Colección AxiDB: timeline_posts
 * Persistencia: spa/server/data/timeline_posts/<id>.json
 *
 * Schema:
 *   id           string  "tp_<16 hex>"
 *   local_id     string  "l_..."
 *   tipo         string  foto | texto | video
 *   titulo       string
 *   descripcion  string
 *   media_url    string  ruta relativa en MEDIA/<local_id>/timeline/
 *   publicado_at string  ISO 8601
 *   created_at   string
 *
 * Límite demo: 50 posts (gestionado desde el handler).
 */
class TimelineModel
{
    public const COLLECTION = 'timeline_posts';
    public const ID_PREFIX  = 'tp_';
    public const DEMO_MAX   = 50;

    public static function generateId(): string
    {
        return self::ID_PREFIX . bin2hex(random_bytes(8));
    }

    public static function create(array $data): array
    {
        if (empty($data['local_id'])) return ['success' => false, 'error' => 'local_id obligatorio'];
        $tipo = (string) ($data['tipo'] ?? 'texto');
        if (!in_array($tipo, ['foto', 'texto', 'video'], true)) $tipo = 'texto';

        $id  = $data['id'] ?? self::generateId();
        $now = date('c');
        $doc = [
            'id'           => $id,
            'local_id'     => (string) $data['local_id'],
            'tipo'         => $tipo,
            'titulo'       => trim((string) ($data['titulo'] ?? '')),
            'descripcion'  => trim((string) ($data['descripcion'] ?? '')),
            'media_url'    => (string) ($data['media_url'] ?? ''),
            'publicado_at' => $data['publicado_at'] ?? $now,
            'created_at'   => $now,
        ];
        $saved = data_put(self::COLLECTION, $id, $doc, true);
        return ['success' => true, 'data' => $saved];
    }

    public static function read(string $id): ?array
    {
        return data_get(self::COLLECTION, $id);
    }

    /** Devuelve posts del local ordenados por publicado_at desc (más nuevo primero). */
    public static function listByLocal(string $localId, int $limit = 20, int $offset = 0): array
    {
        $out = [];
        foreach (data_all(self::COLLECTION) as $p) {
            if (($p['local_id'] ?? '') === $localId) $out[] = $p;
        }
        usort($out, fn($a, $b) => strcmp($b['publicado_at'] ?? '', $a['publicado_at'] ?? ''));
        return array_values(array_slice($out, $offset, $limit));
    }

    public static function countByLocal(string $localId): int
    {
        $n = 0;
        foreach (data_all(self::COLLECTION) as $p) {
            if (($p['local_id'] ?? '') === $localId) $n++;
        }
        return $n;
    }

    public static function delete(string $id): void
    {
        data_delete(self::COLLECTION, $id);
    }
}
