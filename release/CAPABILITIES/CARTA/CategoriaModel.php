<?php
namespace Carta;

/**
 * CategoriaModel - seccion de una carta (Entrantes, Carnes, Postres, ...).
 *
 * Coleccion: carta_categorias
 * Persistencia: spa/server/data/carta_categorias/<id>.json
 *
 * Schema:
 *   id          string  "cat_<16 hex>"
 *   carta_id    string  "c_..."
 *   local_id    string  "l_..."   desnormalizado para queries rapidas
 *   nombre      string
 *   orden       int     orden visual dentro de la carta
 *   icono       string  emoji o nombre lucide opcional
 *   disponible  bool    soft-hide
 *   created_at, updated_at
 */
class CategoriaModel
{
    public const COLLECTION = 'carta_categorias';
    public const ID_PREFIX  = 'cat_';

    public static function generateId(): string
    {
        return self::ID_PREFIX . bin2hex(random_bytes(8));
    }

    public static function create(array $data): array
    {
        if (empty($data['carta_id'])) return ['success' => false, 'error' => 'carta_id obligatorio'];
        if (empty($data['local_id'])) return ['success' => false, 'error' => 'local_id obligatorio'];
        if (empty($data['nombre']))   return ['success' => false, 'error' => 'nombre obligatorio'];

        $id = $data['id'] ?? self::generateId();
        $doc = [
            'id'          => $id,
            'carta_id'    => (string) $data['carta_id'],
            'local_id'    => (string) $data['local_id'],
            'nombre'      => trim((string) $data['nombre']),
            'orden'       => intval($data['orden'] ?? 0),
            'icono'       => (string) ($data['icono'] ?? ''),
            'disponible'  => $data['disponible'] ?? true,
            'created_at'  => date('c'),
            'updated_at'  => date('c'),
        ];
        $saved = data_put(self::COLLECTION, $id, $doc, true);
        return ['success' => true, 'data' => $saved];
    }

    public static function read(string $id): ?array
    {
        return data_get(self::COLLECTION, $id);
    }

    public static function listByCarta(string $cartaId, bool $soloDisponibles = false): array
    {
        $out = [];
        foreach (data_all(self::COLLECTION) as $c) {
            if (($c['carta_id'] ?? '') !== $cartaId) continue;
            if ($soloDisponibles && empty($c['disponible'])) continue;
            $out[] = $c;
        }
        usort($out, fn($a, $b) => ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0));
        return $out;
    }

    public static function listByLocal(string $localId): array
    {
        $out = [];
        foreach (data_all(self::COLLECTION) as $c) {
            if (($c['local_id'] ?? '') === $localId) $out[] = $c;
        }
        return $out;
    }

    public static function update(string $id, array $patch): array
    {
        $existing = self::read($id);
        if (!$existing) return ['success' => false, 'error' => 'Categoria no encontrada'];

        $allowed = ['nombre', 'orden', 'icono', 'disponible'];
        $clean = ['updated_at' => date('c')];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $patch)) $clean[$f] = $patch[$f];
        }
        return data_put(self::COLLECTION, $id, $clean);
    }

    public static function delete(string $id): array
    {
        return self::update($id, ['disponible' => false]);
    }

    public static function hardDelete(string $id): bool
    {
        return data_delete(self::COLLECTION, $id);
    }
}
