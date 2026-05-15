<?php
namespace CARTA\models;

class LocalModel
{
    private $services;
    private $collection = 'carta_locales';

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function create($data)
    {
        $required = ['nombre', 'slug'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Campo '$field' es obligatorio"];
            }
        }

        if (!preg_match('/^[a-z0-9\-]+$/', $data['slug'])) {
            return ['success' => false, 'error' => 'Slug solo admite minusculas, numeros y guiones'];
        }

        $existing = $this->findBySlug($data['slug']);
        if ($existing) {
            return ['success' => false, 'error' => 'Ya existe un local con ese slug'];
        }

        $doc = [
            'id' => $data['slug'],
            'slug' => $data['slug'],
            'nombre' => $data['nombre'],
            'descripcion_corta' => $data['descripcion_corta'] ?? '',
            'logo_url' => $data['logo_url'] ?? '',
            'idioma_defecto' => $data['idioma_defecto'] ?? 'es',
            'idiomas_activos' => $data['idiomas_activos'] ?? ['es'],
            'timezone' => $data['timezone'] ?? 'Europe/Madrid',
            'modo_tpv' => $data['modo_tpv'] ?? 'sala',
            'nif' => $data['nif'] ?? '',
            'nombre_fiscal' => $data['nombre_fiscal'] ?? '',
            'domicilio_fiscal' => $data['domicilio_fiscal'] ?? '',
            'activo' => true,
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

    public function findBySlug($slug)
    {
        $doc = $this->services['crud']->read($this->collection, $slug);
        if (isset($doc['success']) && !$doc['success']) {
            return null;
        }
        return $doc;
    }

    public function listAll()
    {
        return $this->services['crud']->list($this->collection);
    }

    public function listActive()
    {
        $all = $this->listAll();
        if (!isset($all['success']) || !$all['success']) {
            return $all;
        }
        $items = array_filter($all['data'] ?? [], function ($item) {
            return !empty($item['activo']);
        });
        return ['success' => true, 'data' => array_values($items)];
    }
}
