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

function handle_local(string $action, array $req, ?array $user): array
{
    if (!$user) throw new RuntimeException('Sesion requerida');
    $data = $req['data'] ?? [];

    switch ($action) {
        case 'get_local':
            $id = (string) ($data['id'] ?? '');
            if ($id === '') $id = local_resolve_default_for_user($user);
            return local_get_or_empty($id);

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

        case 'bootstrap_local':
            return local_bootstrap_for_user($user);

        default:
            throw new RuntimeException("Accion local no reconocida: $action");
    }
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
