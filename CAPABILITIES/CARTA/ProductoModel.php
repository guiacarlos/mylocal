<?php
namespace Carta;

/**
 * ProductoModel - plato/bebida dentro de una categoria.
 *
 * Coleccion: carta_productos
 * Persistencia: spa/server/data/carta_productos/<id>.json
 *
 * Schema:
 *   id                  string   "prod_<16 hex>"
 *   carta_id            string   "c_..."
 *   categoria_id        string   "cat_..."
 *   local_id            string   "l_..."   desnormalizado
 *   nombre              string
 *   descripcion         string
 *   precio              float
 *   moneda              string   default "EUR"
 *   alergenos           array    14 alergenos UE
 *   imagen_url          string
 *   imagen_mejorada_url string
 *   es_especialidad     bool
 *   texto_promocional   string
 *   ingredientes        array
 *   origen_import       string   "ocr" | "manual" | "scraper"
 *   disponible          bool
 *   orden               int      dentro de la categoria
 *   created_at, updated_at
 */
class ProductoModel
{
    public const COLLECTION = 'carta_productos';
    public const ID_PREFIX  = 'prod_';

    public static function generateId(): string
    {
        return self::ID_PREFIX . bin2hex(random_bytes(8));
    }

    public static function create(array $data): array
    {
        foreach (['carta_id', 'categoria_id', 'local_id', 'nombre'] as $f) {
            if (empty($data[$f])) return ['success' => false, 'error' => "$f obligatorio"];
        }

        $id = $data['id'] ?? self::generateId();
        $doc = [
            'id'                  => $id,
            'carta_id'            => (string) $data['carta_id'],
            'categoria_id'        => (string) $data['categoria_id'],
            'local_id'            => (string) $data['local_id'],
            'nombre'              => trim((string) $data['nombre']),
            'descripcion'         => trim((string) ($data['descripcion'] ?? '')),
            'precio'              => floatval($data['precio'] ?? 0),
            'moneda'              => (string) ($data['moneda'] ?? 'EUR'),
            'alergenos'           => is_array($data['alergenos'] ?? null) ? $data['alergenos'] : [],
            'imagen_url'          => (string) ($data['imagen_url'] ?? ''),
            'imagen_mejorada_url' => (string) ($data['imagen_mejorada_url'] ?? ''),
            'es_especialidad'     => (bool) ($data['es_especialidad'] ?? false),
            'texto_promocional'   => (string) ($data['texto_promocional'] ?? ''),
            'ingredientes'        => is_array($data['ingredientes'] ?? null) ? $data['ingredientes'] : [],
            'origen_import'       => (string) ($data['origen_import'] ?? 'manual'),
            'disponible'          => $data['disponible'] ?? true,
            'orden'               => intval($data['orden'] ?? 0),
            'created_at'          => date('c'),
            'updated_at'          => date('c'),
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
        foreach (data_all(self::COLLECTION) as $p) {
            if (($p['carta_id'] ?? '') !== $cartaId) continue;
            if ($soloDisponibles && empty($p['disponible'])) continue;
            $out[] = $p;
        }
        usort($out, fn($a, $b) => ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0));
        return $out;
    }

    public static function listByCategoria(string $categoriaId, bool $soloDisponibles = false): array
    {
        $out = [];
        foreach (data_all(self::COLLECTION) as $p) {
            if (($p['categoria_id'] ?? '') !== $categoriaId) continue;
            if ($soloDisponibles && empty($p['disponible'])) continue;
            $out[] = $p;
        }
        usort($out, fn($a, $b) => ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0));
        return $out;
    }

    public static function listByLocal(string $localId): array
    {
        $out = [];
        foreach (data_all(self::COLLECTION) as $p) {
            if (($p['local_id'] ?? '') === $localId) $out[] = $p;
        }
        return $out;
    }

    public static function update(string $id, array $patch): array
    {
        $existing = self::read($id);
        if (!$existing) return ['success' => false, 'error' => 'Producto no encontrado'];

        $allowed = ['nombre', 'descripcion', 'precio', 'moneda', 'alergenos',
                    'imagen_url', 'imagen_mejorada_url', 'es_especialidad',
                    'texto_promocional', 'ingredientes', 'disponible', 'orden',
                    'categoria_id'];
        $clean = ['updated_at' => date('c')];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $patch)) {
                if ($f === 'precio') {
                    $clean[$f] = floatval($patch[$f]);
                } elseif ($f === 'alergenos' || $f === 'ingredientes') {
                    $clean[$f] = is_array($patch[$f]) ? $patch[$f] : [];
                } else {
                    $clean[$f] = $patch[$f];
                }
            }
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
