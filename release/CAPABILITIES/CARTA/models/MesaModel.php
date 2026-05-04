<?php
namespace CARTA\models;

class MesaModel
{
    private $services;
    private $collection = 'carta_mesas';

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function create($data)
    {
        if (empty($data['local_id'])) {
            return ['success' => false, 'error' => 'local_id es obligatorio'];
        }
        if (empty($data['zona_nombre'])) {
            return ['success' => false, 'error' => 'zona_nombre es obligatorio'];
        }
        if (!isset($data['numero']) || $data['numero'] < 1) {
            return ['success' => false, 'error' => 'numero debe ser positivo'];
        }

        $id = $data['id'] ?? $data['local_id'] . '_' . $this->slugify($data['zona_nombre']) . '_' . $data['numero'];

        $doc = [
            'id' => $id,
            'local_id' => $data['local_id'],
            'zona_nombre' => $data['zona_nombre'],
            'numero' => intval($data['numero']),
            'capacidad' => intval($data['capacidad'] ?? 4),
            'qr_url' => $data['qr_url'] ?? '',
            'activa' => $data['activa'] ?? true,
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

    public function delete($id)
    {
        return $this->services['crud']->delete($this->collection, $id);
    }

    public function listByLocal($localId)
    {
        $all = $this->services['crud']->list($this->collection);
        if (!isset($all['success']) || !$all['success']) {
            return $all;
        }
        $items = array_filter($all['data'] ?? [], function ($item) use ($localId) {
            return ($item['local_id'] ?? '') === $localId;
        });
        usort($items, function ($a, $b) {
            $zoneCompare = strcmp($a['zona_nombre'] ?? '', $b['zona_nombre'] ?? '');
            if ($zoneCompare !== 0) return $zoneCompare;
            return ($a['numero'] ?? 0) - ($b['numero'] ?? 0);
        });
        return ['success' => true, 'data' => array_values($items)];
    }

    public function listByZona($localId, $zonaNombre)
    {
        $result = $this->listByLocal($localId);
        if (!$result['success']) {
            return $result;
        }
        $items = array_filter($result['data'], function ($item) use ($zonaNombre) {
            return ($item['zona_nombre'] ?? '') === $zonaNombre;
        });
        return ['success' => true, 'data' => array_values($items)];
    }

    public function findBySlug($localId, $slug)
    {
        $result = $this->listByLocal($localId);
        if (!$result['success']) {
            return null;
        }
        foreach ($result['data'] as $mesa) {
            $mesaSlug = $this->slugify($mesa['zona_nombre']) . '-' . $mesa['numero'];
            if (strtolower($mesaSlug) === strtolower($slug)) {
                return $mesa;
            }
        }
        return null;
    }

    public function getZonas($localId)
    {
        $result = $this->listByLocal($localId);
        if (!$result['success']) {
            return $result;
        }
        $zonas = [];
        foreach ($result['data'] as $mesa) {
            $zona = $mesa['zona_nombre'] ?? 'General';
            if (!in_array($zona, $zonas)) {
                $zonas[] = $zona;
            }
        }
        return ['success' => true, 'data' => $zonas];
    }

    private function slugify($text)
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
