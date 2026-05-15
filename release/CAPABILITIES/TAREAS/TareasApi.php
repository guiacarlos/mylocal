<?php
/**
 * TareasApi — handler de acciones del tablero kanban.
 * Requiere usuario autenticado.
 */

declare(strict_types=1);

namespace Tareas;

function handle_tareas(string $action, array $req, array $user): array
{
    $localId = s_id($req['local_id'] ?? ($user['local_id'] ?? ''));

    switch ($action) {
        case 'tarea_create':
            return TareaModel::create(array_merge($req, ['local_id' => $localId]));

        case 'tarea_list':
            return TareaModel::listByLocal($localId, $req['estado'] ?? null);

        case 'tarea_update':
            $id = s_id($req['id'] ?? '');
            if (!$id) throw new \InvalidArgumentException('id requerido.');
            return TareaModel::update($id, $req);

        case 'tarea_delete':
            $id = s_id($req['id'] ?? '');
            if (!$id) throw new \InvalidArgumentException('id requerido.');
            TareaModel::delete($id);
            return ['deleted' => $id];

        default:
            throw new \RuntimeException("Acción de tareas no reconocida: $action");
    }
}
