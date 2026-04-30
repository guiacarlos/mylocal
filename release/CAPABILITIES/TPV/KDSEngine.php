<?php
namespace TPV;

class KDSEngine
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function executeAction($action, $data = [])
    {
        switch ($action) {
            case 'get_kitchen_orders':
                return $this->getKitchenOrders($data);
            case 'mark_item_ready':
                return $this->markItemReady($data);
            default:
                return ['success' => false, 'error' => "Accion no soportada: $action"];
        }
    }

    private function getKitchenOrders($data)
    {
        $all = $this->services['crud']->list('lineas_pedido');
        if (!isset($all['success']) || !$all['success']) return $all;

        $items = array_filter($all['data'] ?? [], function ($l) {
            $estado = $l['estado_cocina'] ?? '';
            return in_array($estado, ['pendiente', 'en_cocina']) && $l['cancelled_at'] === null;
        });

        usort($items, function ($a, $b) {
            return strcmp($a['created_at'] ?? '', $b['created_at'] ?? '');
        });

        return ['success' => true, 'data' => array_values($items)];
    }

    private function markItemReady($data)
    {
        $id = $data['id'] ?? '';
        if (empty($id)) return ['success' => false, 'error' => 'id obligatorio'];

        $result = $this->services['crud']->update('lineas_pedido', $id, [
            'estado_cocina' => 'listo'
        ]);

        return $result;
    }
}
