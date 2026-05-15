<?php
namespace TPV;

class AnalyticsEngine
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function executeAction($action, $data = [])
    {
        switch ($action) {
            case 'ticket_medio':
                return $this->ticketMedio($data['local_id'] ?? '', $data['desde'] ?? null, $data['hasta'] ?? null);
            case 'rotacion_mesas':
                return $this->rotacionMesas($data['local_id'] ?? '', $data['desde'] ?? null, $data['hasta'] ?? null);
            case 'productos_ranking':
                return $this->productosRanking($data['local_id'] ?? '', $data['desde'] ?? null, $data['hasta'] ?? null);
            case 'franjas_ocupacion':
                return $this->franjasOcupacion($data['local_id'] ?? '');
            case 'resumen_dia':
                return $this->resumenDia($data['local_id'] ?? '');
            default:
                return ['success' => false, 'error' => "Accion no soportada: $action"];
        }
    }

    private function getSesiones($localId, $desde, $hasta)
    {
        $all = $this->services['crud']->list('sesiones_mesa');
        if (!isset($all['success']) || !$all['success']) return [];
        return array_filter($all['data'] ?? [], function ($s) use ($localId, $desde, $hasta) {
            if ($s['local_id'] !== $localId || $s['estado'] !== 'cobrada') return false;
            if ($desde && ($s['cerrada_en'] ?? '') < $desde) return false;
            if ($hasta && ($s['cerrada_en'] ?? '') > $hasta) return false;
            return true;
        });
    }

    private function ticketMedio($localId, $desde, $hasta)
    {
        $sesiones = $this->getSesiones($localId, $desde, $hasta);
        if (empty($sesiones)) return ['success' => true, 'data' => ['ticket_medio' => 0, 'total_sesiones' => 0]];
        $total = array_sum(array_column($sesiones, 'total_bruto'));
        return ['success' => true, 'data' => [
            'ticket_medio' => round($total / count($sesiones), 2),
            'total_sesiones' => count($sesiones),
            'total_facturado' => round($total, 2)
        ]];
    }

    private function rotacionMesas($localId, $desde, $hasta)
    {
        $sesiones = $this->getSesiones($localId, $desde, $hasta);
        if (empty($sesiones)) return ['success' => true, 'data' => ['tiempo_medio_min' => 0]];
        $tiempos = [];
        foreach ($sesiones as $s) {
            if (!empty($s['abierta_en']) && !empty($s['cerrada_en'])) {
                $diff = strtotime($s['cerrada_en']) - strtotime($s['abierta_en']);
                if ($diff > 0) $tiempos[] = $diff / 60;
            }
        }
        $media = empty($tiempos) ? 0 : round(array_sum($tiempos) / count($tiempos), 1);
        return ['success' => true, 'data' => ['tiempo_medio_min' => $media, 'sesiones' => count($tiempos)]];
    }

    private function productosRanking($localId, $desde, $hasta)
    {
        $all = $this->services['crud']->list('lineas_pedido');
        if (!isset($all['success']) || !$all['success']) return ['success' => true, 'data' => []];
        $ranking = [];
        foreach ($all['data'] ?? [] as $l) {
            if ($l['cancelled_at'] !== null) continue;
            $nombre = $l['nombre_producto'] ?? 'Desconocido';
            if (!isset($ranking[$nombre])) $ranking[$nombre] = ['nombre' => $nombre, 'unidades' => 0, 'importe' => 0];
            $ranking[$nombre]['unidades'] += $l['cantidad'] ?? 1;
            $ranking[$nombre]['importe'] += $l['subtotal'] ?? 0;
        }
        usort($ranking, function ($a, $b) { return $b['unidades'] - $a['unidades']; });
        return ['success' => true, 'data' => array_values(array_slice($ranking, 0, 20))];
    }

    private function franjasOcupacion($localId)
    {
        $sesiones = $this->getSesiones($localId, null, null);
        $franjas = array_fill(0, 24, 0);
        foreach ($sesiones as $s) {
            $hora = intval(date('G', strtotime($s['abierta_en'] ?? 'now')));
            $franjas[$hora]++;
        }
        return ['success' => true, 'data' => $franjas];
    }

    private function resumenDia($localId)
    {
        $hoy = date('Y-m-d');
        $sesiones = $this->getSesiones($localId, $hoy . 'T00:00:00', $hoy . 'T23:59:59');
        $total = array_sum(array_column($sesiones, 'total_bruto'));
        $porMetodo = [];
        foreach ($sesiones as $s) {
            $m = $s['metodo_pago'] ?? 'cash';
            $porMetodo[$m] = ($porMetodo[$m] ?? 0) + ($s['total_bruto'] ?? 0);
        }
        return ['success' => true, 'data' => [
            'total_dia' => round($total, 2),
            'sesiones_dia' => count($sesiones),
            'por_metodo' => $porMetodo
        ]];
    }
}
