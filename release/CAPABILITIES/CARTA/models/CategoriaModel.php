<?php
namespace CARTA\models;

class CategoriaModel
{
    private $services;
    private $collection = 'carta_categorias';

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function create($data)
    {
        if (empty($data['local_id'])) {
            return ['success' => false, 'error' => 'local_id es obligatorio'];
        }
        if (empty($data['nombre'])) {
            return ['success' => false, 'error' => 'nombre es obligatorio'];
        }

        $doc = [
            'id' => $data['id'] ?? uniqid('cat_'),
            'local_id' => $data['local_id'],
            'nombre' => $data['nombre'],
            'nombre_i18n' => [
                'es' => $data['nombre'],
                'en' => $data['nombre_i18n']['en'] ?? '',
                'fr' => $data['nombre_i18n']['fr'] ?? '',
                'de' => $data['nombre_i18n']['de'] ?? ''
            ],
            'icono_texto' => mb_substr($data['icono_texto'] ?? '', 0, 2),
            'orden' => intval($data['orden'] ?? 0),
            'disponible' => $data['disponible'] ?? true,
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
        if (isset($data['icono_texto'])) {
            $data['icono_texto'] = mb_substr($data['icono_texto'], 0, 2);
        }
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
            return ($a['orden'] ?? 0) - ($b['orden'] ?? 0);
        });
        return ['success' => true, 'data' => array_values($items)];
    }

    public function listAvailableByLocal($localId)
    {
        $result = $this->listByLocal($localId);
        if (!$result['success']) {
            return $result;
        }
        $items = array_filter($result['data'], function ($item) {
            return !empty($item['disponible']);
        });
        return ['success' => true, 'data' => array_values($items)];
    }
}
