<?php
namespace PAYMENT\drivers;

class BizumDriver
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function initiate($data, $pagoId)
    {
        $telefono = $this->getBizumPhone($data['local_id'] ?? '');
        if (!$telefono) {
            return ['success' => false, 'error' => 'Bizum no configurado para este local'];
        }

        $importe = floatval($data['importe'] ?? 0);
        $concepto = 'Mesa ' . ($data['numero_mesa'] ?? '') . ' - ' . number_format($importe, 2) . ' EUR';
        $bizumLink = 'bizum://' . $telefono . '?concept=' . urlencode($concepto) . '&amount=' . $importe;

        return [
            'success' => true,
            'data' => [
                'pago_id' => $pagoId,
                'metodo' => 'bizum',
                'bizum_link' => $bizumLink,
                'telefono' => $telefono,
                'importe' => $importe,
                'concepto' => $concepto,
                'estado' => 'pendiente',
                'instrucciones' => 'Pulsa el enlace para abrir Bizum. El camarero confirmara el cobro.'
            ]
        ];
    }

    private function getBizumPhone($localId)
    {
        $settings = $this->services['crud']->read('config', 'tpv_settings');
        return $settings['bizumPhone'] ?? null;
    }
}
