<?php
namespace PAYMENT\models;

class SesionMesaModel
{
    private $services;
    private $collection = 'sesiones_mesa';

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function create($data)
    {
        if (empty($data['local_id']) || empty($data['mesa_id'])) {
            return ['success' => false, 'error' => 'local_id y mesa_id son obligatorios'];
        }

        $doc = [
            'id' => $data['id'] ?? uniqid('ses_'),
            'local_id' => $data['local_id'],
            'mesa_id' => $data['mesa_id'],
            'zona_nombre' => $data['zona_nombre'] ?? '',
            'numero_mesa' => intval($data['numero_mesa'] ?? 0),
            'abierta_en' => date('c'),
            'cerrada_en' => null,
            'estado' => 'abierta',
            'total_bruto' => 0,
            'total_iva' => 0,
            'total_descuento' => 0,
            'metodo_pago' => null,
            'ticket_id' => null,
            'camarero_id' => $data['camarero_id'] ?? null,
            'created_at' => date('c'),
            'updated_at' => date('c')
        ];

        return $this->services['crud']->create($this->collection, $doc);
    }

    public function read($id)
    {
        return $this->services['crud']->read($this->collection, $id);
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('c');
        return $this->services['crud']->update($this->collection, $id, $data);
    }

    public function cerrar($id, $metodoPago, $ticketId = null)
    {
        return $this->update($id, [
            'estado' => 'cobrada',
            'cerrada_en' => date('c'),
            'metodo_pago' => $metodoPago,
            'ticket_id' => $ticketId
        ]);
    }

    public function cancelar($id)
    {
        return $this->update($id, [
            'estado' => 'cancelada',
            'cerrada_en' => date('c')
        ]);
    }

    public function listAbiertas($localId)
    {
        $all = $this->services['crud']->list($this->collection);
        if (!isset($all['success']) || !$all['success']) return $all;
        $items = array_filter($all['data'] ?? [], function ($s) use ($localId) {
            return $s['local_id'] === $localId && $s['estado'] === 'abierta';
        });
        return ['success' => true, 'data' => array_values($items)];
    }

    public function listByLocal($localId, $estado = null)
    {
        $all = $this->services['crud']->list($this->collection);
        if (!isset($all['success']) || !$all['success']) return $all;
        $items = array_filter($all['data'] ?? [], function ($s) use ($localId, $estado) {
            if ($s['local_id'] !== $localId) return false;
            if ($estado && $s['estado'] !== $estado) return false;
            return true;
        });
        return ['success' => true, 'data' => array_values($items)];
    }

    public function findAbiertaByMesa($localId, $mesaId)
    {
        $result = $this->listAbiertas($localId);
        if (!$result['success']) return null;
        foreach ($result['data'] as $sesion) {
            if ($sesion['mesa_id'] === $mesaId) return $sesion;
        }
        return null;
    }
}
