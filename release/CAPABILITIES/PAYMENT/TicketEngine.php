<?php
namespace PAYMENT;

require_once __DIR__ . '/models/SesionMesaModel.php';
require_once __DIR__ . '/models/LineaPedidoModel.php';
require_once __DIR__ . '/models/PagoModel.php';

use PAYMENT\models\SesionMesaModel;
use PAYMENT\models\LineaPedidoModel;
use PAYMENT\models\PagoModel;

class TicketEngine
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function generate($sesionId)
    {
        $sesionModel = new SesionMesaModel($this->services);
        $lineaModel = new LineaPedidoModel($this->services);
        $pagoModel = new PagoModel($this->services);

        $sesion = $sesionModel->read($sesionId);
        if (!isset($sesion['id'])) {
            return ['success' => false, 'error' => 'Sesion no encontrada'];
        }

        $lineas = $lineaModel->listBySesion($sesionId);
        $totales = $lineaModel->calcularTotales($sesionId);
        $pagos = $pagoModel->listBySesion($sesionId);

        $localNombre = $this->getLocalNombre($sesion['local_id'] ?? '');

        $html = $this->buildTicketHtml($localNombre, $sesion, $lineas['data'] ?? [], $totales['data'] ?? [], $pagos['data'] ?? []);

        return [
            'success' => true,
            'data' => [
                'html' => $html,
                'sesion_id' => $sesionId,
                'total' => $totales['data']['total_bruto'] ?? 0
            ]
        ];
    }

    private function buildTicketHtml($localNombre, $sesion, $lineas, $totales, $pagos)
    {
        $h = function ($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); };
        $fecha = date('d/m/Y H:i', strtotime($sesion['abierta_en'] ?? 'now'));

        $html = '<div class="ticket" style="font-family:monospace;max-width:80mm;margin:0 auto;padding:10px;font-size:12px">';
        $html .= '<div style="text-align:center;font-weight:bold;font-size:14px;margin-bottom:8px">' . $h($localNombre) . '</div>';
        $html .= '<div style="text-align:center;margin-bottom:8px">';
        $html .= 'Mesa ' . intval($sesion['numero_mesa']) . ' - ' . $h($sesion['zona_nombre']);
        $html .= '<br>' . $fecha . '</div>';
        $html .= '<div style="border-top:1px dashed #000;margin:8px 0"></div>';

        foreach ($lineas as $l) {
            $sub = number_format($l['subtotal'] ?? 0, 2);
            $html .= '<div style="display:flex;justify-content:space-between">';
            $html .= '<span>' . intval($l['cantidad']) . 'x ' . $h($l['nombre_producto']) . '</span>';
            $html .= '<span>' . $sub . '</span></div>';
        }

        $html .= '<div style="border-top:1px dashed #000;margin:8px 0"></div>';

        $tasas = ['general_21' => 21, 'reducido_10' => 10, 'superreducido_4' => 4, 'exento' => 0];
        $ivaDesglose = [];
        foreach ($lineas as $l) {
            $tipo = $l['iva_tipo'] ?? 'reducido_10';
            $pct = $tasas[$tipo] ?? 10;
            if (!isset($ivaDesglose[$pct])) $ivaDesglose[$pct] = 0;
            $sub = $l['subtotal'] ?? 0;
            $ivaDesglose[$pct] += $sub * $pct / (100 + $pct);
        }

        foreach ($ivaDesglose as $pct => $importe) {
            $html .= '<div style="display:flex;justify-content:space-between;font-size:11px">';
            $html .= '<span>IVA ' . $pct . '%</span><span>' . number_format($importe, 2) . '</span></div>';
        }

        $html .= '<div style="display:flex;justify-content:space-between;font-weight:bold;font-size:14px;margin-top:8px">';
        $html .= '<span>TOTAL</span><span>' . number_format($totales['total_bruto'] ?? 0, 2) . ' EUR</span></div>';

        if (!empty($pagos)) {
            $metodo = $pagos[0]['metodo'] ?? 'cash';
            $nombres = ['cash' => 'Efectivo', 'bizum' => 'Bizum', 'tarjeta' => 'Tarjeta'];
            $html .= '<div style="text-align:center;margin-top:8px">Pago: ' . ($nombres[$metodo] ?? $metodo) . '</div>';
        }

        $html .= '<div style="text-align:center;margin-top:12px;font-size:10px">Gracias por su visita</div>';
        $html .= '</div>';

        return $html;
    }

    private function getLocalNombre($localId)
    {
        $local = $this->services['crud']->read('carta_locales', $localId);
        return $local['nombre'] ?? 'Local';
    }
}
