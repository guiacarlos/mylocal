<?php
/**
 * local.php - handler de establecimientos (locales).
 *
 * Modelo: jerarquia preparada para multi-local / multi-user.
 *   - Un usuario puede ser propietario de N locales (owner_user_id)
 *   - Un local puede tener N miembros con distintos roles (members)
 *   - Un local puede tener N cartas activas (default_carta_id apunta a la principal)
 *
 * Persistencia: spa/server/data/locales/<id>.json (AxiDB)
 * IDs: "l_<16 hex>" — ver CAPABILITIES/LOCALES/LocalModel.php
 *
 * Acciones:
 *   get_local        - lee local por id (o el default del usuario)
 *   list_my_locales  - lista locales accesibles por el usuario actual
 *   create_local     - crea nuevo local (propietario = usuario actual)
 *   update_local     - upsert (solo admin/editor del local)
 *   bootstrap_local  - idempotente: si user no tiene local, crea l_default
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib.php';
require_once realpath(__DIR__ . '/../../../CAPABILITIES') . '/LOCALES/LocalModel.php';
require_once realpath(__DIR__ . '/../../../CAPABILITIES') . '/CARTA/CartaModel.php';

function handle_local(string $action, array $req, ?array $user, array $files = []): array
{
    $data = $req['data'] ?? [];

    // get_local es publico (la carta digital publica lo necesita sin sesion).
    // El resto de acciones si exige sesion.
    if ($action === 'get_local') {
        $id = (string) ($data['id'] ?? '');
        if ($id === '') {
            $id = $user ? local_resolve_default_for_user($user) : 'l_default';
        }
        return local_get_or_empty($id);
    }

    if (!$user) throw new RuntimeException('Sesion requerida');

    switch ($action) {

        case 'list_my_locales':
            return ['items' => \Locales\LocalModel::listByUser($user['id'] ?? '')];

        case 'create_local':
            $data['owner_user_id'] = $user['id'] ?? '';
            $r = \Locales\LocalModel::create($data);
            if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'create_local');
            return $r['data'] ?? $r;

        case 'update_local':
            $id = (string) ($data['id'] ?? '');
            if ($id === '') throw new RuntimeException('id requerido');
            if (!\Locales\LocalModel::userCanAccess($user, $id)) {
                throw new RuntimeException('Sin permisos sobre este local');
            }
            $r = \Locales\LocalModel::update($id, $data);
            if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'update_local');
            return $r['data'] ?? $r;

        case 'upload_local_image':
            return local_upload_image($req, $files, $user);

        case 'bootstrap_local':
            return local_bootstrap_for_user($user);

        default:
            throw new RuntimeException("Accion local no reconocida: $action");
    }
}

/**
 * Sube una imagen del local (hero/logo) al directorio MEDIA y persiste la URL
 * relativa en local.imagen_hero.
 *
 * Endpoint multipart: action=upload_local_image, file=<File>, local_id=<id>.
 * Acepta jpg/jpeg/png/webp. Maximo 5 MB.
 */
function local_upload_image(array $req, array $files, array $user): array
{
    $localId = (string) ($req['local_id'] ?? $_POST['local_id'] ?? 'l_default');
    if (!\Locales\LocalModel::userCanAccess($user, $localId)) {
        throw new RuntimeException('Sin permisos sobre este local');
    }

    $f = $files['file'] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se recibio archivo o error de subida');
    }
    if (($f['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Imagen demasiado grande (max 5 MB)');
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException("Formato no permitido: $ext (jpg/png/webp)");
    }

    // MEDIA esta en la raiz del proyecto (regla del proyecto, ver CLAUDE.md)
    $mediaRoot = realpath(__DIR__ . '/../../../MEDIA');
    if (!$mediaRoot) {
        $mediaRoot = __DIR__ . '/../../../MEDIA';
        @mkdir($mediaRoot, 0775, true);
    }
    $localDir = $mediaRoot . '/local/' . preg_replace('/[^a-z0-9_\-]/', '', strtolower($localId));
    if (!is_dir($localDir)) @mkdir($localDir, 0775, true);

    $filename = 'hero_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $localDir . '/' . $filename;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        throw new RuntimeException('Error guardando imagen');
    }

    // URL publica (servida por router.php / vite serveMediaPlugin)
    $url = '/MEDIA/local/' . basename($localDir) . '/' . $filename;

    \Locales\LocalModel::update($localId, ['imagen_hero' => $url]);

    return ['url' => $url, 'local_id' => $localId];
}

/**
 * Devuelve el local resuelto, o un esqueleto vacio para que la UI no rompa.
 * El esqueleto tiene id pero no existe en disco; la UI puede mostrar form vacio.
 */
function local_get_or_empty(string $id): array
{
    $doc = \Locales\LocalModel::read($id);
    if ($doc) return $doc;
    return [
        'id'                => $id,
        'slug'              => '',
        'nombre'            => '',
        'telefono'          => '',
        'direccion'         => '',
        'email'             => '',
        'web'               => '',
        'instagram'         => '',
        'tagline'           => '',
        'owner_user_id'     => '',
        'members'           => [],
        'default_carta_id'  => '',
    ];
}

/**
 * Resuelve el local por defecto para un usuario.
 * Reglas:
 *   1. Si el usuario tiene locales, usa el primero (en futuro: ultimo activo).
 *   2. Si no tiene ninguno, devuelve "l_default" como placeholder.
 *      bootstrap_local lo materializara cuando se llame explicitamente.
 */
function local_resolve_default_for_user(array $user): string
{
    $list = \Locales\LocalModel::listByUser($user['id'] ?? '');
    if (!empty($list)) return $list[0]['id'];
    return 'l_default';
}

/**
 * Idempotente. Si el usuario no tiene ningun local, crea "l_default" + carta
 * principal. Si ya tiene, devuelve el primero.
 *
 * Esto se llama desde la SPA al cargar el dashboard la primera vez.
 */
function local_bootstrap_for_user(array $user): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') throw new RuntimeException('Usuario sin id');

    $list = \Locales\LocalModel::listByUser($userId);
    if (!empty($list)) {
        return ['local' => $list[0], 'created' => false];
    }

    // Crear el local por defecto
    $localRes = \Locales\LocalModel::create([
        'id'             => 'l_default',
        'slug'           => 'mi-local',
        'nombre'         => 'Mi Local',
        'owner_user_id'  => $userId,
    ]);
    if (!($localRes['success'] ?? false)) {
        throw new RuntimeException($localRes['error'] ?? 'No se pudo crear el local');
    }
    $local = $localRes['data'];

    // Crear la carta principal
    $cartaRes = \Carta\CartaModel::create([
        'local_id' => $local['id'],
        'nombre'   => 'Carta principal',
        'tipo'     => 'principal',
    ]);
    if ($cartaRes['success'] ?? false) {
        \Locales\LocalModel::update($local['id'], [
            'default_carta_id' => $cartaRes['data']['id'],
        ]);
        $local['default_carta_id'] = $cartaRes['data']['id'];
    }

    return ['local' => $local, 'created' => true];
}
