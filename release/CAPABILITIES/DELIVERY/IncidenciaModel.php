<?php
/**
 * IncidenciaModel — registro de incidencias sobre pedidos.
 *
 * Clave: `inc_<uuid>`
 */

declare(strict_types=1);

namespace Delivery;

class IncidenciaModel
{
    const TIPOS = ['daño', 'retraso', 'dirección_incorrecta', 'cliente_ausente', 'devolucion', 'otro'];

    public static function add(array $data): array
    {
        $pedidoId = s_id($data['pedido_id'] ?? '');
        if (!$pedidoId) throw new \InvalidArgumentException('pedido_id requerido.');

        $tipo = $data['tipo'] ?? 'otro';
        if (!in_array($tipo, self::TIPOS, true)) $tipo = 'otro';

        $id = 'inc_' . self::uuid();
        $doc = [
            'id'         => $id,
            'pedido_id'  => $pedidoId,
            'tipo'       => $tipo,
            'descripcion' => s_str($data['descripcion'] ?? '', 1000),
            'resuelto'   => false,
            'created_at' => date('c'),
        ];
        // Marcar pedido como incidencia
        PedidoModel::cambiarEstado($pedidoId, 'incidencia');
        return data_put('incidencias', $id, $doc, true);
    }

    public static function listByPedido(string $pedidoId): array
    {
        return array_values(array_filter(
            data_all('incidencias'),
            fn($i) => ($i['pedido_id'] ?? '') === $pedidoId
        ));
    }

    public static function listByLocal(string $localId): array
    {
        $pedidoIds = array_column(PedidoModel::listByLocal($localId), null, 'id');
        return array_values(array_filter(
            data_all('incidencias'),
            fn($i) => isset($pedidoIds[$i['pedido_id'] ?? ''])
        ));
    }

    private static function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%012x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0x4000, 0x4fff),
            random_int(0x8000, 0xbfff),
            random_int(0, 0xffffffffffff)
        );
    }
}
