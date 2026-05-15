<?php
namespace FISCAL\models;

class FiscalConfigModel
{
    private $services;
    private $collection = 'fiscal_config';

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function create($data)
    {
        if (empty($data['local_id']) || empty($data['nif'])) {
            return ['success' => false, 'error' => 'local_id y nif son obligatorios'];
        }

        $doc = [
            'id' => $data['local_id'],
            'local_id' => $data['local_id'],
            'nif' => strtoupper(trim($data['nif'])),
            'nombre_fiscal' => $data['nombre_fiscal'] ?? '',
            'domicilio_fiscal' => $data['domicilio_fiscal'] ?? '',
            'cp' => $data['cp'] ?? '',
            'municipio' => $data['municipio'] ?? '',
            'provincia' => $data['provincia'] ?? '',
            'regimen_iva' => $data['regimen_iva'] ?? 'general',
            'serie_factura' => $data['serie_factura'] ?? 'R',
            'modalidad_fiscal' => $data['modalidad_fiscal'] ?? 'ninguna',
            'territorio_ticketbai' => $data['territorio_ticketbai'] ?? null,
            'certificado_path' => $data['certificado_path'] ?? null,
            'created_at' => date('c'),
            'updated_at' => date('c')
        ];

        return $this->services['crud']->create($this->collection, $doc);
    }

    public function read($localId)
    {
        return $this->services['crud']->read($this->collection, $localId);
    }

    public function update($localId, $data)
    {
        $data['updated_at'] = date('c');
        if (isset($data['nif'])) $data['nif'] = strtoupper(trim($data['nif']));
        return $this->services['crud']->update($this->collection, $localId, $data);
    }
}
