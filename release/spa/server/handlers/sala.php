<?php
/**
 * sala.php - handler de configuracion de sala (zonas + mesas + QRs).
 *
 * Acciones:
 *   list_zonas              - lista zonas activas del local
 *   create_zona             - crea zona (con icono)
 *   update_zona             - renombra, cambia icono, orden, activa
 *   delete_zona             - soft-delete
 *   create_zonas_preset     - wizard paso 1: presets rapidos
 *   reorder_zonas           - drag & drop entre zonas
 *
 *   list_mesas              - lista mesas activas (filtrable por zona)
 *   create_mesa             - crea una mesa
 *   update_mesa             - cambia numero, capacidad, zona, estado
 *   delete_mesa             - soft-delete
 *   create_mesas_batch      - wizard paso 2: N mesas en una zona
 *   regenerate_mesa_qr      - cambia el qr_token (invalida QR antiguo)
 *
 *   sala_resumen            - {zonas: [...], mesas_total, mesas_por_zona}
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib.php';

define('SALA_CAPS', realpath(__DIR__ . '/../../../CAPABILITIES') ?: '');

require_once SALA_CAPS . '/QR/QrTokenGenerator.php';
require_once SALA_CAPS . '/QR/ZonaModel.php';
require_once SALA_CAPS . '/QR/MesaModel.php';

/* ─────────────────────────────────────────────────────────
   Adaptador CRUD que envuelve data_put/data_get/data_all/data_delete
   para que los modelos QR (que esperan $services['crud']) funcionen
   contra el storage de spa/server sin tocar los modelos.
───────────────────────────────────────────────────────── */
class SalaCrudAdapter
{
    public function create(string $col, array $doc): array
    {
        $id = (string) ($doc['id'] ?? '');
        if ($id === '') return ['success' => false, 'error' => 'id requerido'];
        $saved = data_put($col, $id, $doc, true);
        return ['success' => true, 'data' => $saved];
    }
    public function read(string $col, string $id): array
    {
        $doc = data_get($col, $id);
        if (!$doc) return ['success' => false, 'error' => 'No encontrado'];
        return ['success' => true, 'data' => $doc];
    }
    public function update(string $col, string $id, array $patch): array
    {
        $existing = data_get($col, $id);
        if (!$existing) return ['success' => false, 'error' => 'No encontrado'];
        $merged = array_merge($existing, $patch);
        $saved = data_put($col, $id, $merged, true);
        return ['success' => true, 'data' => $saved];
    }
    public function delete(string $col, string $id): array
    {
        $ok = data_delete($col, $id);
        return ['success' => $ok];
    }
    public function list(string $col): array
    {
        return ['success' => true, 'data' => data_all($col)];
    }
}

function sala_services(): array
{
    static $svc = null;
    if ($svc === null) $svc = ['crud' => new SalaCrudAdapter()];
    return $svc;
}

function sala_zona_model(): \QR\ZonaModel
{
    static $m = null;
    if ($m === null) $m = new \QR\ZonaModel(sala_services());
    return $m;
}

function sala_mesa_model(): \QR\MesaModel
{
    static $m = null;
    if ($m === null) $m = new \QR\MesaModel(sala_services());
    return $m;
}

/* ─────────────────────────────────────────────────────────
   Handler principal
   Contrato: devuelve DATOS PLANOS (no {success,data} envuelto). El
   dispatcher de index.php llama resp(true, handle_sala(...)) que
   construye el envelope final. Si algo falla, lanza RuntimeException
   y el catch global lo convierte en {success:false, error:"..."}.
───────────────────────────────────────────────────────── */
function handle_sala(string $action, array $req, ?array $user): array
{
    if (!$user) throw new RuntimeException('Sesion requerida');
    $localId = (string) ($req['data']['local_id'] ?? $user['local_id'] ?? 'default');
    $data = $req['data'] ?? [];

    $unwrap = function (array $r) use ($action): array {
        if (!($r['success'] ?? false)) {
            throw new RuntimeException($r['error'] ?? "Error en $action");
        }
        return $r['data'] ?? [];
    };

    switch ($action) {
        case 'list_zonas':
            return $unwrap(sala_zona_model()->listByLocal($localId));

        case 'create_zona':
            $data['local_id'] = $localId;
            return $unwrap(sala_zona_model()->create($data));

        case 'update_zona':
            return $unwrap(sala_zona_model()->update((string) ($data['id'] ?? ''), $data));

        case 'delete_zona':
            return $unwrap(sala_zona_model()->delete((string) ($data['id'] ?? '')));

        case 'create_zonas_preset':
            $preset = (string) ($data['preset'] ?? 'salon');
            return $unwrap(sala_zona_model()->createPreset($localId, $preset));

        case 'reorder_zonas':
            $ids = (array) ($data['ordered_ids'] ?? []);
            return $unwrap(sala_zona_model()->reorder($localId, $ids));

        case 'list_mesas':
            $zoneId = (string) ($data['zone_id'] ?? '');
            if ($zoneId !== '') return $unwrap(sala_mesa_model()->listByZona($zoneId));
            return $unwrap(sala_mesa_model()->listByLocal($localId));

        case 'create_mesa':
            $data['local_id'] = $localId;
            return $unwrap(sala_mesa_model()->create($data));

        case 'update_mesa':
            return $unwrap(sala_mesa_model()->update((string) ($data['id'] ?? ''), $data));

        case 'delete_mesa':
            return $unwrap(sala_mesa_model()->delete((string) ($data['id'] ?? '')));

        case 'create_mesas_batch':
            return $unwrap(sala_mesa_model()->createBatch(
                $localId,
                (string) ($data['zone_id'] ?? ''),
                intval($data['cantidad'] ?? 1),
                intval($data['start_numero'] ?? 1),
                intval($data['capacidad'] ?? 4),
            ));

        case 'regenerate_mesa_qr':
            return $unwrap(sala_mesa_model()->regenerateToken((string) ($data['id'] ?? '')));

        case 'sala_resumen':
            return sala_resumen($localId);

        default:
            throw new RuntimeException("Accion sala no reconocida: $action");
    }
}

function sala_resumen(string $localId): array
{
    sala_bootstrap_if_empty($localId);

    $zonas = sala_zona_model()->listByLocal($localId);
    $mesas = sala_mesa_model()->listByLocal($localId);
    $byZone = [];
    foreach (($mesas['data'] ?? []) as $m) {
        $z = $m['zone_id'] ?? 'sin_zona';
        $byZone[$z] = ($byZone[$z] ?? 0) + 1;
    }
    return [
        'zonas' => $zonas['data'] ?? [],
        'mesas_total' => count($mesas['data'] ?? []),
        'mesas_por_zona' => (object) $byZone,
    ];
}

/**
 * Si el local no tiene zonas, crea "Sala" con 1 mesa "1". Idempotente.
 * Punto de partida minimo para que el hostelero arranque sin friccion.
 */
function sala_bootstrap_if_empty(string $localId): void
{
    $existing = sala_zona_model()->listByLocal($localId);
    if (!empty($existing['data'] ?? [])) return;

    $zona = sala_zona_model()->create([
        'local_id' => $localId,
        'nombre'   => 'Sala',
        'icono'    => 'utensils',
        'orden'    => 1,
    ]);
    if (!($zona['success'] ?? false)) return;

    sala_mesa_model()->create([
        'local_id'  => $localId,
        'zone_id'   => $zona['data']['id'],
        'numero'    => '1',
        'capacidad' => 4,
    ]);
}
