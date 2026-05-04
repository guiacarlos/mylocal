<?php
namespace FISCAL\models;

class VerifactuRegistroModel
{
    private $services;
    private $collection = 'verifactu_registros';

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function create($data)
    {
        $doc = [
            'id' => $data['id'] ?? uniqid('vrf_'),
            'local_id' => $data['local_id'],
            'sesion_id' => $data['sesion_id'] ?? null,
            'serie' => $data['serie'],
            'numero' => intval($data['numero']),
            'fecha_expedicion' => $data['fecha_expedicion'] ?? date('Y-m-d'),
            'tipo_factura' => 'F2',
            'nif_emisor' => $data['nif_emisor'],
            'nombre_emisor' => $data['nombre_emisor'],
            'base_imponible' => floatval($data['base_imponible'] ?? 0),
            'cuota_iva' => floatval($data['cuota_iva'] ?? 0),
            'total' => floatval($data['total'] ?? 0),
            'desglose_iva' => $data['desglose_iva'] ?? [],
            'huella' => $data['huella'] ?? '',
            'huella_anterior' => $data['huella_anterior'] ?? '',
            'csv' => $data['csv'] ?? '',
            'estado_aeat' => $data['estado_aeat'] ?? 'pendiente',
            'xml' => $data['xml'] ?? '',
            'created_at' => date('c')
        ];

        return $this->services['crud']->create($this->collection, $doc);
    }

    public function read($id)
    {
        return $this->services['crud']->read($this->collection, $id);
    }

    public function update($id, $data)
    {
        return $this->services['crud']->update($this->collection, $id, $data);
    }

    public function getUltimo($localId)
    {
        $all = $this->services['crud']->list($this->collection);
        if (!isset($all['success']) || !$all['success']) return null;
        $filtered = array_filter($all['data'] ?? [], function ($r) use ($localId) {
            return $r['local_id'] === $localId;
        });
        if (empty($filtered)) return null;
        usort($filtered, function ($a, $b) {
            return ($b['numero'] ?? 0) - ($a['numero'] ?? 0);
        });
        return reset($filtered);
    }
}
