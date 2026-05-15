<?php
namespace Locales;

/**
 * LocalModel - establecimiento (restaurante / bar / cafe).
 *
 * Coleccion AxiDB: locales
 * Persistencia: spa/server/data/locales/<id>.json
 *
 * Schema:
 *   id              string   "l_<16 hex>"
 *   slug            string   "el-rincon-de-ana"  (URL friendly, unico)
 *   nombre          string   "El Rincon de Ana"
 *   telefono        string
 *   direccion       string
 *   email           string
 *   web             string
 *   instagram       string   sin @
 *   tagline         string   subtitulo corto
 *   imagen_hero     string   URL de imagen principal (logo/foto local)
 *   facebook        string   handle/url de Facebook (opcional)
 *   tiktok          string   handle de TikTok sin @ (opcional)
 *   whatsapp        string   numero E.164 sin signos (opcional, ej "34600000000")
 *   web_template    string   plantilla de la carta web: moderna|minimal|premium
 *   web_color       string   tema de color web: claro|oscuro|blanco_roto
 *   copyright       string   texto del footer publico (opcional)
 *   owner_user_id   string   "u_..."  (propietario unico)
 *   members         array    [{user_id, role}]  equipo (admin/editor/sala/cocina/camarero)
 *   default_carta_id string  "c_..."  cual es la principal
 *   created_at      string
 *   updated_at      string
 *
 * Un usuario puede tener varios locales (varios docs con owner_user_id == su id).
 * Un local puede tener varios miembros con distintos roles.
 */
class LocalModel
{
    public const COLLECTION = 'locales';
    public const ID_PREFIX  = 'l_';

    public static function generateId(): string
    {
        return self::ID_PREFIX . bin2hex(random_bytes(8));
    }

    /**
     * Crea un local nuevo. owner_user_id es obligatorio.
     */
    public static function create(array $data): array
    {
        $err = self::validate($data, true);
        if ($err) return ['success' => false, 'error' => $err];

        $id = $data['id'] ?? self::generateId();
        $slug = self::sanitizeSlug($data['slug'] ?? self::slugFromName($data['nombre']));

        $doc = [
            'id'                => $id,
            'slug'              => $slug,
            'nombre'            => trim((string) $data['nombre']),
            'telefono'          => trim((string) ($data['telefono'] ?? '')),
            'direccion'         => trim((string) ($data['direccion'] ?? '')),
            'email'             => trim((string) ($data['email'] ?? '')),
            'web'               => trim((string) ($data['web'] ?? '')),
            'instagram'         => ltrim(trim((string) ($data['instagram'] ?? '')), '@'),
            'tagline'           => trim((string) ($data['tagline'] ?? '')),
            'imagen_hero'       => trim((string) ($data['imagen_hero'] ?? '')),
            'facebook'          => trim((string) ($data['facebook'] ?? '')),
            'tiktok'            => ltrim(trim((string) ($data['tiktok'] ?? '')), '@'),
            'whatsapp'          => preg_replace('/[^0-9+]/', '', (string) ($data['whatsapp'] ?? '')) ?? '',
            'web_template'      => self::sanitizeWebTemplate($data['web_template'] ?? 'moderna'),
            'web_color'         => self::sanitizeWebColor($data['web_color'] ?? 'claro'),
            'copyright'         => trim((string) ($data['copyright'] ?? '')),
            'owner_user_id'     => (string) $data['owner_user_id'],
            'members'           => $data['members'] ?? [
                ['user_id' => $data['owner_user_id'], 'role' => 'admin'],
            ],
            'default_carta_id'  => (string) ($data['default_carta_id'] ?? ''),
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

    public static function findBySlug(string $slug): ?array
    {
        $slug = self::sanitizeSlug($slug);
        foreach (data_all(self::COLLECTION) as $l) {
            if (($l['slug'] ?? '') === $slug) return $l;
        }
        return null;
    }

    public static function listByUser(string $userId): array
    {
        $out = [];
        foreach (data_all(self::COLLECTION) as $l) {
            if (($l['owner_user_id'] ?? '') === $userId) { $out[] = $l; continue; }
            foreach (($l['members'] ?? []) as $m) {
                if (($m['user_id'] ?? '') === $userId) { $out[] = $l; break; }
            }
        }
        return $out;
    }

    public static function update(string $id, array $patch): array
    {
        $existing = self::read($id);
        if (!$existing) return ['success' => false, 'error' => 'Local no encontrado'];

        $allowed = ['nombre', 'telefono', 'direccion', 'email', 'web', 'instagram',
                    'tagline', 'imagen_hero', 'slug', 'default_carta_id', 'members',
                    'facebook', 'tiktok', 'whatsapp',
                    'web_template', 'web_color', 'copyright',
                    // Configuracion ampliada (Ola 3)
                    'idiomas',          // array de codigos ISO: ['es', 'en', ...]
                    'horarios',         // object {lun: [{from, to}], mar: [...], ...}
                    'nif',              // datos fiscales
                    'razon_social',
                    'direccion_fiscal',
                    'tipo_negocio',     // bar / restaurante / cafeteria / pastelería / ...
                    'descripcion'];     // descripcion corta del negocio
        $clean = ['updated_at' => date('c')];
        foreach ($allowed as $f) {
            if (\array_key_exists($f, $patch)) {
                if ($f === 'slug') {
                    $clean[$f] = self::sanitizeSlug($patch[$f]);
                } elseif ($f === 'instagram' || $f === 'tiktok') {
                    $clean[$f] = ltrim(trim((string) $patch[$f]), '@');
                } elseif ($f === 'whatsapp') {
                    $clean[$f] = preg_replace('/[^0-9+]/', '', (string) $patch[$f]) ?? '';
                } elseif ($f === 'web_template') {
                    $clean[$f] = self::sanitizeWebTemplate((string) $patch[$f]);
                } elseif ($f === 'web_color') {
                    $clean[$f] = self::sanitizeWebColor((string) $patch[$f]);
                } elseif ($f === 'members' || $f === 'idiomas') {
                    $clean[$f] = \is_array($patch[$f]) ? $patch[$f] : [];
                } elseif ($f === 'horarios') {
                    // Estructura {dia: [{from, to}, ...], ...}. Aceptamos
                    // tanto array vacio (limpiar) como objeto con tramos.
                    $clean[$f] = \is_array($patch[$f]) ? $patch[$f] : [];
                } else {
                    $clean[$f] = trim((string) $patch[$f]);
                }
            }
        }
        $saved = data_put(self::COLLECTION, $id, $clean);
        return ['success' => true, 'data' => $saved];
    }

    public static function userCanAccess(?array $user, string $localId, string $minRole = 'sala'): bool
    {
        if (!$user) return false;
        if (\in_array(strtolower($user['role'] ?? ''), ['superadmin', 'administrador', 'admin'], true)) {
            return true;
        }
        $local = self::read($localId);
        if (!$local) return false;
        if (($local['owner_user_id'] ?? '') === ($user['id'] ?? '')) return true;
        foreach (($local['members'] ?? []) as $m) {
            if (($m['user_id'] ?? '') === ($user['id'] ?? '')) return true;
        }
        return false;
    }

    public static function sanitizeSlug(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9_\-]/', '-', $s) ?? '';
        $s = preg_replace('/-+/', '-', $s);
        return trim($s, '-');
    }

    public static function slugFromName(string $name): string
    {
        $s = mb_strtolower($name);
        $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'];
        $s = strtr($s, $map);
        return self::sanitizeSlug($s);
    }

    public const WEB_TEMPLATES = ['moderna', 'minimal', 'premium'];
    public const WEB_COLORS    = ['claro', 'oscuro', 'blanco_roto'];

    public static function sanitizeWebTemplate(string $t): string
    {
        $t = strtolower(trim($t));
        return \in_array($t, self::WEB_TEMPLATES, true) ? $t : 'moderna';
    }

    public static function sanitizeWebColor(string $c): string
    {
        $c = strtolower(trim($c));
        return \in_array($c, self::WEB_COLORS, true) ? $c : 'claro';
    }

    private static function validate(array $data, bool $isCreate): ?string
    {
        if ($isCreate) {
            if (empty($data['nombre']) || trim((string) $data['nombre']) === '') {
                return 'nombre obligatorio';
            }
            if (empty($data['owner_user_id'])) {
                return 'owner_user_id obligatorio';
            }
        }
        return null;
    }
}
