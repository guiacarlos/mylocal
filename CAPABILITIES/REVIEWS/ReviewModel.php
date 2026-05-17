<?php
namespace Reviews;

/**
 * ReviewModel - reseñas de clientes sobre el local.
 *
 * Colección AxiDB: reviews
 * Persistencia: spa/server/data/reviews/<id>.json
 *
 * Schema:
 *   id           string  "rev_<16 hex>"
 *   local_id     string  "l_..."
 *   autor        string  nombre del cliente (opcional)
 *   estrellas    int     1-5
 *   comentario   string
 *   respuesta    string  respuesta del dueño (opcional)
 *   verificado   bool    false por defecto (sin sistema de verificación MVP)
 *   fecha        string  ISO 8601
 *   invite_token string  token firmado para el enlace de invitación
 *   created_at   string
 */
class ReviewModel
{
    public const COLLECTION = 'reviews';
    public const ID_PREFIX  = 'rev_';

    public static function generateId(): string
    {
        return self::ID_PREFIX . bin2hex(random_bytes(8));
    }

    /** Genera token de invitación firmado con HMAC-SHA256 */
    public static function generateInviteToken(string $localId): string
    {
        $payload = $localId . '|' . time();
        $secret  = defined('INVITE_SECRET') ? INVITE_SECRET : ($localId . 'mylocal_invite');
        return $localId . '.' . hash_hmac('sha256', $payload, $secret, false);
    }

    /** Valida que el token pertenece a localId (no verifica expiración en MVP) */
    public static function validateInviteToken(string $token, string $localId): bool
    {
        return str_starts_with($token, $localId . '.');
    }

    public static function create(array $data): array
    {
        if (empty($data['local_id'])) return ['success' => false, 'error' => 'local_id obligatorio'];
        $estrellas = max(1, min(5, (int) ($data['estrellas'] ?? 5)));

        $id  = $data['id'] ?? self::generateId();
        $now = date('c');
        $doc = [
            'id'           => $id,
            'local_id'     => (string) $data['local_id'],
            'autor'        => trim((string) ($data['autor'] ?? 'Anónimo')),
            'estrellas'    => $estrellas,
            'comentario'   => trim((string) ($data['comentario'] ?? '')),
            'respuesta'    => '',
            'verificado'   => false,
            'fecha'        => $data['fecha'] ?? $now,
            'invite_token' => (string) ($data['invite_token'] ?? ''),
            'created_at'   => $now,
        ];
        $saved = data_put(self::COLLECTION, $id, $doc, true);
        return ['success' => true, 'data' => $saved];
    }

    public static function read(string $id): ?array
    {
        return data_get(self::COLLECTION, $id);
    }

    /** Respuesta del dueño a una reseña */
    public static function respond(string $id, string $respuesta): array
    {
        $doc = data_get(self::COLLECTION, $id);
        if (!$doc) return ['success' => false, 'error' => 'Reseña no encontrada'];
        $doc['respuesta'] = trim($respuesta);
        $saved = data_put(self::COLLECTION, $id, $doc, false);
        return ['success' => true, 'data' => $saved];
    }

    /** Devuelve reseñas del local ordenadas por fecha desc. */
    public static function listByLocal(string $localId, int $limit = 50, int $offset = 0): array
    {
        $out = [];
        foreach (data_all(self::COLLECTION) as $r) {
            if (($r['local_id'] ?? '') === $localId) $out[] = $r;
        }
        usort($out, fn($a, $b) => strcmp($b['fecha'] ?? '', $a['fecha'] ?? ''));
        return array_values(array_slice($out, $offset, $limit));
    }

    /** Rating agregado: media y distribución de estrellas. */
    public static function aggregate(string $localId): array
    {
        $reviews = self::listByLocal($localId, 1000, 0);
        if (empty($reviews)) return ['count' => 0, 'media' => 0, 'distribucion' => [1=>0,2=>0,3=>0,4=>0,5=>0]];
        $dist  = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $total = 0;
        foreach ($reviews as $r) {
            $s = (int) ($r['estrellas'] ?? 5);
            $dist[$s] = ($dist[$s] ?? 0) + 1;
            $total   += $s;
        }
        return [
            'count'        => count($reviews),
            'media'        => round($total / count($reviews), 1),
            'distribucion' => $dist,
        ];
    }

    public static function delete(string $id): void
    {
        data_delete(self::COLLECTION, $id);
    }
}
