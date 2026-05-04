<?php
namespace PAYMENT\models;

class LineaPedidoModel
{
    private $services;
    private $collection = 'lineas_pedido';

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function create($data)
    {
        if (empty($data['sesion_id']) || empty($data['producto_id'])) {
            return ['success' => false, 'error' => 'sesion_id y producto_id son obligatorios'];
        }

        $precio = floatval($data['precio_unitario'] ?? 0);
        $cantidad = intval($data['cantidad'] ?? 1);

        $doc = [
            'id' => $data['id'] ?? uniqid('lin_'),
            'sesion_id' => $data['sesion_id'],
            'producto_id' => $data['producto_id'],
            'nombre_producto' => $data['nombre_producto'] ?? '',
            'precio_unitario' => $precio,
            'cantidad' => $cantidad,
            'iva_tipo' => $data['iva_tipo'] ?? 'reducido_10',
            'subtotal' => $precio * $cantidad,
            'nota' => $data['nota'] ?? '',
            'estado_cocina' => $data['estado_cocina'] ?? 'pendiente',
            'ronda' => intval($data['ronda'] ?? 1),
            'origen' => $data['origen'] ?? 'TPV',
            'created_at' => date('c'),
            'cancelled_at' => null
        ];

        return $this->services['crud']->create($this->collection, $doc);
    }

    public function read($id)
    {
        return $this->services['crud']->read($this->collection, $id);
    }

    public function update($id, $data)
    {
        if (isset($data['precio_unitario']) && isset($data['cantidad'])) {
            $data['subtotal'] = floatval($data['precio_unitario']) * intval($data['cantidad']);
        }
        return $this->services['crud']->update($this->collection, $id, $data);
    }

    public function cancel($id)
    {
        return $this->update($id, ['cancelled_at' => date('c')]);
    }

    public function updateEstadoCocina($id, $estado)
    {
        $validos = ['pendiente', 'en_cocina', 'listo', 'servido'];
        if (!in_array($estado, $validos)) {
            return ['success' => false, 'error' => 'Estado cocina no valido'];
        }
        return $this->update($id, ['estado_cocina' => $estado]);
    }

    public function listBySesion($sesionId)
    {
        $all = $this->services['crud']->list($this->collection);
        if (!isset($all['success']) || !$all['success']) return $all;
        $items = array_filter($all['data'] ?? [], function ($l) use ($sesionId) {
            return $l['sesion_id'] === $sesionId && $l['cancelled_at'] === null;
        });
        return ['success' => true, 'data' => array_values($items)];
    }

    public function calcularTotales($sesionId)
    {
        $result = $this->listBySesion($sesionId);
        if (!$result['success']) return $result;

        $bruto = 0;
        $iva = 0;
        $tasas = ['general_21' => 0.21, 'reducido_10' => 0.10, 'superreducido_4' => 0.04, 'exento' => 0];

        foreach ($result['data'] as $linea) {
            $sub = $linea['subtotal'] ?? 0;
            $bruto += $sub;
            $tasa = $tasas[$linea['iva_tipo'] ?? 'reducido_10'] ?? 0.10;
            $iva += $sub * $tasa / (1 + $tasa);
        }

        return [
            'success' => true,
            'data' => [
                'total_bruto' => round($bruto, 2),
                'total_iva' => round($iva, 2),
                'total_neto' => round($bruto - $iva, 2),
                'num_items' => count($result['data'])
            ]
        ];
    }
}
