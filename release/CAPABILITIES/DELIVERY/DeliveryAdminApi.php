<?php
/**
 * DeliveryAdminApi — handler de acciones administrativas de delivery.
 * Requiere usuario autenticado (validado en index.php con require_role).
 */

declare(strict_types=1);

namespace Delivery;

function handle_delivery_admin(string $action, array $req, array $user): array
{
    $localId = s_id($req['local_id'] ?? ($user['local_id'] ?? ''));

    switch ($action) {
        case 'pedido_create':
            return PedidoModel::create(array_merge($req, ['local_id' => $localId]));

        case 'pedido_list':
            return PedidoModel::listByLocal($localId, $req['estado'] ?? null);

        case 'pedido_get':
            $id = s_id($req['id'] ?? '');
            if (!$id) throw new \InvalidArgumentException('id requerido.');
            $doc = PedidoModel::get($id);
            if (!$doc) throw new \RuntimeException('Pedido no encontrado.');
            return $doc;

        case 'pedido_estado':
            $id = s_id($req['id'] ?? '');
            $estado = s_str($req['estado'] ?? '', 20);
            if (!$id || !$estado) throw new \InvalidArgumentException('id y estado son requeridos.');
            return PedidoModel::cambiarEstado($id, $estado);

        case 'vehiculo_create':
            return VehiculoModel::create($localId, $req);

        case 'vehiculo_list':
            return VehiculoModel::listByLocal($localId);

        case 'vehiculo_update':
            $id = s_id($req['id'] ?? '');
            if (!$id) throw new \InvalidArgumentException('id requerido.');
            return VehiculoModel::update($id, $req);

        case 'entrega_asignar':
            return EntregaModel::asignar($req);

        case 'entrega_list_dia':
            $fecha = $req['fecha'] ?? date('Y-m-d');
            return EntregaModel::listByFecha($localId, $fecha);

        case 'incidencia_add':
            return IncidenciaModel::add($req);

        default:
            throw new \RuntimeException("Acción delivery no reconocida: $action");
    }
}
