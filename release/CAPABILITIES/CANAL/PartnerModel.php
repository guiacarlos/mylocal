<?php
namespace CANAL;

class PartnerModel
{
    private $storagePath;

    public function __construct()
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $this->storagePath = $root . '/canal/partners';
        if (!is_dir($this->storagePath)) mkdir($this->storagePath, 0777, true);
    }

    public function create($data)
    {
        $id = $data['id'] ?? uniqid('ptr_');
        $doc = [
            'id' => $id,
            'nombre_empresa' => $data['nombre_empresa'] ?? '',
            'contacto' => $data['contacto'] ?? '',
            'locales_asignados' => $data['locales_asignados'] ?? [],
            'comision_pct' => floatval($data['comision_pct'] ?? 0),
            'fecha_acuerdo' => $data['fecha_acuerdo'] ?? date('Y-m-d'),
            'activo' => true,
            'created_at' => date('c')
        ];
        file_put_contents($this->storagePath . '/' . $id . '.json', json_encode($doc, JSON_PRETTY_PRINT));
        return ['success' => true, 'data' => $doc];
    }

    public function read($id)
    {
        $path = $this->storagePath . '/' . $id . '.json';
        if (!file_exists($path)) return ['success' => false, 'error' => 'Partner no encontrado'];
        return ['success' => true, 'data' => json_decode(file_get_contents($path), true)];
    }

    public function listAll()
    {
        $partners = [];
        foreach (glob($this->storagePath . '/*.json') as $file) {
            $partners[] = json_decode(file_get_contents($file), true);
        }
        return ['success' => true, 'data' => $partners];
    }

    public function update($id, $data)
    {
        $existing = $this->read($id);
        if (!$existing['success']) return $existing;
        $doc = array_merge($existing['data'], $data, ['updated_at' => date('c')]);
        file_put_contents($this->storagePath . '/' . $id . '.json', json_encode($doc, JSON_PRETTY_PRINT));
        return ['success' => true, 'data' => $doc];
    }
}
