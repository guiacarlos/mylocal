<?php
namespace PAYMENT\drivers;

class CashDriver
{
    public function initiate($data, $pagoId)
    {
        $importe = floatval($data['importe'] ?? 0);
        $entregado = floatval($data['importe_entregado'] ?? 0);
        $cambio = $entregado > 0 ? round($entregado - $importe, 2) : 0;

        return [
            'success' => true,
            'data' => [
                'pago_id' => $pagoId,
                'metodo' => 'cash',
                'importe' => $importe,
                'entregado' => $entregado,
                'cambio' => max(0, $cambio),
                'estado' => 'completado'
            ]
        ];
    }
}
