<?php
/**
 * DeliveryPublicApi — acción pública de seguimiento sin autenticación.
 *
 * Expone solo el estado y la dirección del pedido — nunca datos de facturación
 * ni información interna del negocio.
 */

declare(strict_types=1);

namespace Delivery;

function handle_delivery_public(string $action, array $req): array
{
    switch ($action) {
        case 'pedido_seguimiento':
            $codigo = strtoupper(trim(s_str($req['codigo'] ?? '', 20)));
            if (!$codigo) throw new \InvalidArgumentException('codigo requerido.');
            $pedido = PedidoModel::getByCode($codigo);
            if (!$pedido) {
                return ['encontrado' => false];
            }
            // Respuesta reducida: sin datos de negocio ni local_id interno
            return [
                'encontrado'  => true,
                'codigo'      => $pedido['codigo_seguimiento'],
                'estado'      => $pedido['estado'],
                'cliente'     => $pedido['cliente'],
                'direccion'   => $pedido['direccion'],
                'notas'       => $pedido['notas'] ?? '',
                'created_at'  => $pedido['created_at'],
            ];

        default:
            throw new \RuntimeException("Acción pública delivery no reconocida: $action");
    }
}
