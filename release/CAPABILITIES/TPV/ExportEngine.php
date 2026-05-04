<?php
namespace TPV;

class ExportEngine
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function executeAction($action, $data = [])
    {
        switch ($action) {
            case 'export_ventas_csv':
                return $this->exportVentas($data['local_id'] ?? '', $data['desde'] ?? null, $data['hasta'] ?? null);
            case 'export_productos_csv':
                return $this->exportProductos($data['local_id'] ?? '');
            default:
                return ['success' => false, 'error' => "Accion no soportada: $action"];
        }
    }

    private function exportVentas($localId, $desde, $hasta)
    {
        $all = $this->services['crud']->list('sesiones_mesa');
        if (!isset($all['success']) || !$all['success']) return $all;

        $rows = [['ID', 'Mesa', 'Zona', 'Abierta', 'Cerrada', 'Total', 'IVA', 'Metodo', 'Estado']];
        foreach ($all['data'] ?? [] as $s) {
            if ($s['local_id'] !== $localId) continue;
            if ($desde && ($s['cerrada_en'] ?? '') < $desde) continue;
            if ($hasta && ($s['cerrada_en'] ?? '') > $hasta) continue;
            $rows[] = [
                $s['id'], $s['numero_mesa'] ?? '', $s['zona_nombre'] ?? '',
                $s['abierta_en'] ?? '', $s['cerrada_en'] ?? '',
                $s['total_bruto'] ?? 0, $s['total_iva'] ?? 0,
                $s['metodo_pago'] ?? '', $s['estado'] ?? ''
            ];
        }

        return ['success' => true, 'data' => ['csv' => $this->toCsv($rows), 'rows' => count($rows) - 1]];
    }

    private function exportProductos($localId)
    {
        $all = $this->services['crud']->list('lineas_pedido');
        if (!isset($all['success']) || !$all['success']) return $all;

        $rows = [['Producto', 'Precio', 'Cantidad', 'Subtotal', 'IVA', 'Origen', 'Fecha']];
        foreach ($all['data'] ?? [] as $l) {
            if ($l['cancelled_at'] !== null) continue;
            $rows[] = [
                $l['nombre_producto'] ?? '', $l['precio_unitario'] ?? 0,
                $l['cantidad'] ?? 1, $l['subtotal'] ?? 0,
                $l['iva_tipo'] ?? '', $l['origen'] ?? '',
                $l['created_at'] ?? ''
            ];
        }

        return ['success' => true, 'data' => ['csv' => $this->toCsv($rows), 'rows' => count($rows) - 1]];
    }

    private function toCsv($rows)
    {
        $lines = [];
        foreach ($rows as $row) {
            $escaped = array_map(function ($v) {
                $v = str_replace('"', '""', (string) $v);
                return strpos($v, ',') !== false || strpos($v, '"') !== false ? '"' . $v . '"' : $v;
            }, $row);
            $lines[] = implode(',', $escaped);
        }
        return implode("\n", $lines);
    }
}
