<?php
/**
 * CitasAdminApi — handler de acciones administrativas de citas.
 * Requiere usuario autenticado (se valida en index.php con require_role).
 */

declare(strict_types=1);

namespace Citas;

function handle_citas_admin(string $action, array $req, array $user): array
{
    $localId = s_id($req['local_id'] ?? ($user['local_id'] ?? ''));

    switch ($action) {
        case 'cita_create':
            return CitasEngine::tryReserve(array_merge($req, ['local_id' => $localId]));

        case 'cita_update':
            $id = s_id($req['id'] ?? '');
            if (!$id) throw new \InvalidArgumentException('id requerido.');
            return CitasModel::update($id, $req);

        case 'cita_cancel':
            $id = s_id($req['id'] ?? '');
            if (!$id) throw new \InvalidArgumentException('id requerido.');
            return CitasEngine::cancel($id);

        case 'cita_get':
            $id = s_id($req['id'] ?? '');
            $doc = CitasModel::get($id);
            if (!$doc) throw new \RuntimeException('Cita no encontrada.');
            return $doc;

        case 'cita_list':
            return CitasModel::listByLocal(
                $localId,
                $req['desde'] ?? null,
                $req['hasta'] ?? null
            );

        case 'recurso_create':
            return RecursosModel::create($localId, $req);

        case 'recurso_update':
            $id = s_id($req['id'] ?? '');
            if (!$id) throw new \InvalidArgumentException('id requerido.');
            return RecursosModel::update($id, $req);

        case 'recurso_list':
            return RecursosModel::listByLocal($localId);

        case 'recurso_delete':
            $id = s_id($req['id'] ?? '');
            if (!$id) throw new \InvalidArgumentException('id requerido.');
            RecursosModel::delete($id);
            return ['deleted' => $id];

        default:
            throw new \RuntimeException("Acción de citas no reconocida: $action");
    }
}
