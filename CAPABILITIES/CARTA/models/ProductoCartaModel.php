<?php
namespace CARTA\models;

class ProductoCartaModel
{
    private $services;
    private $collection = 'carta_productos';

    private $alergenosValidos = [
        'gluten', 'crustaceos', 'huevos', 'pescado', 'cacahuetes',
        'soja', 'lacteos', 'frutos_cascara', 'apio', 'mostaza',
        'sesamo', 'sulfitos', 'altramuces', 'moluscos'
    ];

    private $ivaTipos = ['general_21', 'reducido_10', 'superreducido_4', 'exento'];

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function create($data)
    {
        if (empty($data['local_id']) || empty($data['categoria_id']) || empty($data['nombre'])) {
            return ['success' => false, 'error' => 'local_id, categoria_id y nombre son obligatorios'];
        }
        if (!isset($data['precio']) || $data['precio'] < 0) {
            return ['success' => false, 'error' => 'precio debe ser un valor positivo'];
        }

        $ivaTipo = $data['iva_tipo'] ?? 'reducido_10';
        if (!in_array($ivaTipo, $this->ivaTipos)) {
            return ['success' => false, 'error' => 'iva_tipo no valido'];
        }

        $alergenos = $this->validarAlergenos($data['alergenos'] ?? []);

        $doc = [
            'id' => $data['id'] ?? uniqid('prod_'),
            'local_id' => $data['local_id'],
            'categoria_id' => $data['categoria_id'],
            'nombre' => $data['nombre'],
            'nombre_i18n' => [
                'es' => $data['nombre'],
                'en' => $data['nombre_i18n']['en'] ?? '',
                'fr' => $data['nombre_i18n']['fr'] ?? '',
                'de' => $data['nombre_i18n']['de'] ?? ''
            ],
            'descripcion' => $data['descripcion'] ?? '',
            'descripcion_i18n' => [
                'es' => $data['descripcion'] ?? '',
                'en' => $data['descripcion_i18n']['en'] ?? '',
                'fr' => $data['descripcion_i18n']['fr'] ?? '',
                'de' => $data['descripcion_i18n']['de'] ?? ''
            ],
            'precio' => floatval($data['precio']),
            'precio_franja' => [
                'desayuno' => isset($data['precio_franja']['desayuno']) ? floatval($data['precio_franja']['desayuno']) : null,
                'almuerzo' => isset($data['precio_franja']['almuerzo']) ? floatval($data['precio_franja']['almuerzo']) : null,
                'cena' => isset($data['precio_franja']['cena']) ? floatval($data['precio_franja']['cena']) : null
            ],
            'imagen_url' => $data['imagen_url'] ?? '',
            'alergenos' => $alergenos,
            'iva_tipo' => $ivaTipo,
            'disponible' => $data['disponible'] ?? true,
            'orden' => intval($data['orden'] ?? 0),
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
        if (isset($data['alergenos'])) {
            $data['alergenos'] = $this->validarAlergenos($data['alergenos']);
        }
        if (isset($data['iva_tipo']) && !in_array($data['iva_tipo'], $this->ivaTipos)) {
            return ['success' => false, 'error' => 'iva_tipo no valido'];
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

    public function listByCategoria($categoriaId)
    {
        $all = $this->services['crud']->list($this->collection);
        if (!isset($all['success']) || !$all['success']) {
            return $all;
        }
        $items = array_filter($all['data'] ?? [], function ($item) use ($categoriaId) {
            return ($item['categoria_id'] ?? '') === $categoriaId;
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

    private function validarAlergenos($alergenos)
    {
        return array_values(array_intersect((array) $alergenos, $this->alergenosValidos));
    }
}
