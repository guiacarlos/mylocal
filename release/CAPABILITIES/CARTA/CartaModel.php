<?php
namespace Carta;

/**
 * CartaModel - una carta del local (un local puede tener varias).
 *
 * Coleccion AxiDB: cartas
 * Persistencia: spa/server/data/cartas/<id>.json
 *
 * Schema:
 *   id                  string   "c_<16 hex>"
 *   local_id            string   "l_..."
 *   nombre              string   "Carta Principal", "Carta de Verano"
 *   tipo                string   principal | desayuno | menu_dia | cena | bebidas | postres
 *   tema                string   "minimal" | "dark" | "classic" | "premium"
 *   activa              bool     soft-disable
 *   vigencia_desde      string|null  ISO date
 *   vigencia_hasta      string|null  ISO date
 *   categorias_orden    array    [cat_id, ...]  orden de categorias en esta carta
 *   created_at, updated_at
 */
class CartaModel
{
    public const COLLECTION = 'cartas';
    public const ID_PREFIX  = 'c_';

    public const TIPOS_VALIDOS = [
        'principal', 'desayuno', 'menu_dia', 'cena',
        'bebidas', 'postres', 'tapas', 'eventos',
    ];

    public static function generateId(): string
    {
        return self::ID_PREFIX . bin2hex(random_bytes(8));
    }

    public static function create(array $data): array
    {
        if (empty($data['local_id'])) return ['success' => false, 'error' => 'local_id obligatorio'];
        if (empty($data['nombre']))   return ['success' => false, 'error' => 'nombre obligatorio'];

        $id   = $data['id'] ?? self::generateId();
        $tipo = (string) ($data['tipo'] ?? 'principal');
        if (!\in_array($tipo, self::TIPOS_VALIDOS, true)) $tipo = 'principal';

        $doc = [
            'id'                => $id,
            'local_id'          => (string) $data['local_id'],
            'nombre'            => trim((string) $data['nombre']),
            'tipo'              => $tipo,
            'tema'              => (string) ($data['tema'] ?? 'minimal'),
            'activa'            => $data['activa'] ?? true,
            'vigencia_desde'    => $data['vigencia_desde'] ?? null,
            'vigencia_hasta'    => $data['vigencia_hasta'] ?? null,
            'categorias_orden'  => is_array($data['categorias_orden'] ?? null)
                                    ? $data['categorias_orden']
                                    : [],
            'created_at'        => date('c'),
            'updated_at'        => date('c'),
        ];
        $saved = data_put(self::COLLECTION, $id, $doc, true);
        return ['success' => true, 'data' => $saved];
    }

    public static function read(string $id): ?array
    {
        return data_get(self::COLLECTION, $id);
    }

    public static function listByLocal(string $localId, bool $soloActivas = true): array
    {
        $out = [];
        foreach (data_all(self::COLLECTION) as $c) {
            if (($c['local_id'] ?? '') !== $localId) continue;
            if ($soloActivas && empty($c['activa'])) continue;
            $out[] = $c;
        }
        usort($out, fn($a, $b) => strcmp($a['created_at'] ?? '', $b['created_at'] ?? ''));
        return $out;
    }

    public static function update(string $id, array $patch): array
    {
        $existing = self::read($id);
        if (!$existing) return ['success' => false, 'error' => 'Carta no encontrada'];

        $allowed = ['nombre', 'tipo', 'tema', 'activa',
                    'vigencia_desde', 'vigencia_hasta', 'categorias_orden'];
        $clean = ['updated_at' => date('c')];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $patch)) $clean[$f] = $patch[$f];
        }
        if (isset($clean['tipo']) && !\in_array($clean['tipo'], self::TIPOS_VALIDOS, true)) {
            unset($clean['tipo']);
        }
        return data_put(self::COLLECTION, $id, $clean);
    }

    public static function delete(string $id): array
    {
        return self::update($id, ['activa' => false]);
    }

    public static function hardDelete(string $id): bool
    {
        return data_delete(self::COLLECTION, $id);
    }
}
