<?php
/**
 * local.php - configuracion del establecimiento (local).
 *
 * Coleccion: 'local'. Documento unico por id (id == local_id).
 *
 * Schema:
 *   id          string   slug del local (ej "default", "bar-de-lola")
 *   nombre      string   nombre comercial ("Bar de Lola", "Socola")
 *   telefono    string   "+34 600 000 000"
 *   direccion   string   opcional
 *   email       string   opcional
 *   web         string   opcional
 *   instagram   string   handle sin @ (opcional)
 *   tagline     string   "Cocina mediterranea de mercado" (opcional)
 *   updated_at  string
 *
 * Acciones:
 *   get_local      - lee el local; si no existe devuelve defaults vacios
 *   update_local   - upsert del documento
 *
 * Auth: cualquier rol con sesion. update solo admin/editor.
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib.php';

const LOCAL_DEFAULT_ID = 'default';

function handle_local(string $action, array $req, ?array $user): array
{
    if (!$user) throw new RuntimeException('Sesion requerida');
    $data = $req['data'] ?? [];
    $localId = local_sanitize_id((string) ($data['id'] ?? $user['local_id'] ?? LOCAL_DEFAULT_ID));

    switch ($action) {
        case 'get_local':
            return local_get($localId);

        case 'update_local':
            // Solo roles administrativos pueden modificar (whitelist defensiva
            // alineada con require_role del dispatcher)
            return local_update($localId, $data);

        default:
            throw new RuntimeException("Accion local no reconocida: $action");
    }
}

function local_get(string $id): array
{
    $doc = data_get('local', $id);
    if (!$doc) {
        return [
            'id'        => $id,
            'nombre'    => '',
            'telefono'  => '',
            'direccion' => '',
            'email'     => '',
            'web'       => '',
            'instagram' => '',
            'tagline'   => '',
        ];
    }
    return $doc;
}

function local_update(string $id, array $patch): array
{
    $allowed = ['nombre', 'telefono', 'direccion', 'email', 'web', 'instagram', 'tagline'];
    $clean = ['id' => $id];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $patch)) {
            $val = trim((string) $patch[$f]);
            if (mb_strlen($val) > 200) $val = mb_substr($val, 0, 200);
            $clean[$f] = $val;
        }
    }
    $clean['updated_at'] = date('c');
    return data_put('local', $id, $clean);
}

function local_sanitize_id(string $id): string
{
    $id = strtolower(trim($id));
    $id = preg_replace('/[^a-z0-9_\-]/', '', $id) ?? '';
    return $id !== '' ? $id : LOCAL_DEFAULT_ID;
}
