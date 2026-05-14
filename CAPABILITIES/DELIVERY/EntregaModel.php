<?php
/**
 * EntregaModel — asignación pedido + vehículo para una fecha.
 *
 * Clave: `en_<uuid>`
 */

declare(strict_types=1);

namespace Delivery;

class EntregaModel
{
    public static function asignar(array $data): array
    {
        $pedidoId   = s_id($data['pedido_id']   ?? '');
        $vehiculoId = s_id($data['vehiculo_id'] ?? '');
        if (!$pedidoId || !$vehiculoId) {
            throw new \InvalidArgumentException('pedido_id y vehiculo_id son requeridos.');
        }
        $fecha = $data['fecha'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new \InvalidArgumentException('Fecha inválida (debe ser YYYY-MM-DD).');
        }

        $id = 'en_' . self::uuid();
        $doc = [
            'id'          => $id,
            'pedido_id'   => $pedidoId,
            'vehiculo_id' => $vehiculoId,
            'fecha'       => $fecha,
            'notas'       => s_str($data['notas'] ?? '', 500),
            'created_at'  => date('c'),
        ];
        // Pasar estado del pedido a en_ruta automáticamente
        PedidoModel::cambiarEstado($pedidoId, 'en_ruta');
        return data_put('entregas', $id, $doc, true);
    }

    public static function listByFecha(string $localId, string $fecha): array
    {
        // Filtramos por fecha y cruzamos con vehículos del local
        $vehiculos = array_column(VehiculoModel::listByLocal($localId), null, 'id');
        $pedidoIds = array_column(PedidoModel::listByLocal($localId), null, 'id');

        return array_values(array_filter(
            data_all('entregas'),
            fn($e) =>
                ($e['fecha'] ?? '') === $fecha &&
                isset($vehiculos[$e['vehiculo_id'] ?? '']) &&
                isset($pedidoIds[$e['pedido_id'] ?? ''])
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
