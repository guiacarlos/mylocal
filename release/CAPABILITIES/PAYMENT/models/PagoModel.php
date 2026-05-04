<?php
namespace PAYMENT\models;

class PagoModel
{
    private $services;
    private $collection = 'pagos';

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function create($data)
    {
        if (empty($data['sesion_id']) || empty($data['local_id'])) {
            return ['success' => false, 'error' => 'sesion_id y local_id son obligatorios'];
        }

        $metodos = ['cash', 'bizum', 'tarjeta'];
        $metodo = $data['metodo'] ?? 'cash';
        if (!in_array($metodo, $metodos)) {
            return ['success' => false, 'error' => 'Metodo de pago no valido'];
        }

        $importe = floatval($data['importe'] ?? 0);
        $takeRate = floatval($data['take_rate_porcentaje'] ?? 0);

        $doc = [
            'id' => $data['id'] ?? uniqid('pag_'),
            'sesion_id' => $data['sesion_id'],
            'local_id' => $data['local_id'],
            'importe' => $importe,
            'metodo' => $metodo,
            'estado' => 'pendiente',
            'referencia_externa' => $data['referencia_externa'] ?? null,
            'take_rate_porcentaje' => $takeRate,
            'take_rate_importe' => round($importe * $takeRate / 100, 2),
            'created_at' => date('c'),
            'completado_en' => null
        ];

        return $this->services['crud']->create($this->collection, $doc);
    }

    public function read($id)
    {
        return $this->services['crud']->read($this->collection, $id);
    }

    public function completar($id)
    {
        return $this->services['crud']->update($this->collection, $id, [
            'estado' => 'completado',
            'completado_en' => date('c')
        ]);
    }

    public function fallar($id)
    {
        return $this->services['crud']->update($this->collection, $id, [
            'estado' => 'fallido'
        ]);
    }

    public function reembolsar($id)
    {
        return $this->services['crud']->update($this->collection, $id, [
            'estado' => 'reembolsado'
        ]);
    }

    public function listBySesion($sesionId)
    {
        $all = $this->services['crud']->list($this->collection);
        if (!isset($all['success']) || !$all['success']) return $all;
        $items = array_filter($all['data'] ?? [], function ($p) use ($sesionId) {
            return $p['sesion_id'] === $sesionId;
        });
        return ['success' => true, 'data' => array_values($items)];
    }

    public function listByLocal($localId, $desde = null, $hasta = null)
    {
        $all = $this->services['crud']->list($this->collection);
        if (!isset($all['success']) || !$all['success']) return $all;
        $items = array_filter($all['data'] ?? [], function ($p) use ($localId, $desde, $hasta) {
            if ($p['local_id'] !== $localId) return false;
            if ($desde && ($p['created_at'] ?? '') < $desde) return false;
            if ($hasta && ($p['created_at'] ?? '') > $hasta) return false;
            return true;
        });
        return ['success' => true, 'data' => array_values($items)];
    }
}
