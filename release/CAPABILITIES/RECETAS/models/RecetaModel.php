<?php
namespace RECETAS\models;

class RecetaModel
{
    private $services;
    private $collection = 'recetas';

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function create($data)
    {
        if (empty($data['titulo'])) {
            return ['success' => false, 'error' => 'titulo obligatorio'];
        }
        $slug = $data['slug'] ?? $this->slugify($data['titulo']);
        $doc = [
            'id' => $data['id'] ?? uniqid('rec_'),
            'slug' => $slug,
            'titulo' => $data['titulo'],
            'resumen' => $data['resumen'] ?? '',
            'imagen_principal' => $data['imagen_principal'] ?? '',
            'video_url' => $data['video_url'] ?? '',
            'tiempo_preparacion_min' => intval($data['tiempo_preparacion_min'] ?? 0),
            'tiempo_coccion_min' => intval($data['tiempo_coccion_min'] ?? 0),
            'raciones' => intval($data['raciones'] ?? 0),
            'dificultad' => $this->normalizarDificultad($data['dificultad'] ?? ''),
            'categoria' => $data['categoria'] ?? 'general',
            'tags' => is_array($data['tags'] ?? null) ? $data['tags'] : [],
            'ingredientes' => $this->normalizarIngredientes($data['ingredientes'] ?? []),
            'pasos' => $this->normalizarPasos($data['pasos'] ?? []),
            'origen_url' => $data['origen_url'] ?? '',
            'origen_nombre' => $data['origen_nombre'] ?? '',
            'autor' => $data['autor'] ?? 'MyLocal',
            'idioma' => $data['idioma'] ?? 'es',
            'seo' => [
                'meta_title' => $data['seo']['meta_title'] ?? $data['titulo'],
                'meta_description' => $data['seo']['meta_description'] ?? ($data['resumen'] ?? ''),
                'meta_keywords' => $data['seo']['meta_keywords'] ?? ''
            ],
            'publicado' => !empty($data['publicado']),
            'created_at' => date('c'),
            'updated_at' => date('c')
        ];
        return $this->services['crud']->create($this->collection, $doc);
    }

    public function read($id)
    {
        return $this->services['crud']->read($this->collection, $id);
    }

    public function readBySlug($slug)
    {
        $all = $this->services['crud']->list($this->collection);
        if (!($all['success'] ?? false)) return $all;
        foreach ($all['data'] ?? [] as $item) {
            if (($item['slug'] ?? '') === $slug) return ['success' => true, 'data' => $item];
        }
        return ['success' => false, 'error' => 'No encontrada'];
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('c');
        if (isset($data['ingredientes'])) {
            $data['ingredientes'] = $this->normalizarIngredientes($data['ingredientes']);
        }
        if (isset($data['pasos'])) {
            $data['pasos'] = $this->normalizarPasos($data['pasos']);
        }
        if (isset($data['dificultad'])) {
            $data['dificultad'] = $this->normalizarDificultad($data['dificultad']);
        }
        return $this->services['crud']->update($this->collection, $id, $data);
    }

    public function delete($id)
    {
        return $this->services['crud']->delete($this->collection, $id);
    }

    public function listPublicas($limit = 50)
    {
        $all = $this->services['crud']->list($this->collection);
        if (!($all['success'] ?? false)) return $all;
        $items = array_filter($all['data'] ?? [], function ($r) { return !empty($r['publicado']); });
        usort($items, function ($a, $b) {
            return strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? '');
        });
        return ['success' => true, 'data' => array_slice(array_values($items), 0, $limit)];
    }

    public function listByCategoria($cat, $limit = 50)
    {
        $r = $this->listPublicas(500);
        if (!$r['success']) return $r;
        $items = array_filter($r['data'], function ($x) use ($cat) {
            return ($x['categoria'] ?? '') === $cat;
        });
        return ['success' => true, 'data' => array_slice(array_values($items), 0, $limit)];
    }

    private function normalizarIngredientes($arr)
    {
        if (!is_array($arr)) return [];
        $out = [];
        foreach ($arr as $i) {
            if (is_string($i)) { $out[] = ['nombre' => trim($i), 'cantidad' => '', 'unidad' => '']; continue; }
            if (!is_array($i)) continue;
            $out[] = [
                'nombre' => trim($i['nombre'] ?? ''),
                'cantidad' => trim((string)($i['cantidad'] ?? '')),
                'unidad' => trim($i['unidad'] ?? '')
            ];
        }
        return array_values(array_filter($out, function ($x) { return $x['nombre'] !== ''; }));
    }

    private function normalizarPasos($arr)
    {
        if (!is_array($arr)) return [];
        $out = [];
        foreach ($arr as $p) {
            if (is_string($p)) { $out[] = trim($p); }
            elseif (is_array($p) && isset($p['texto'])) { $out[] = trim($p['texto']); }
        }
        return array_values(array_filter($out, function ($x) { return $x !== ''; }));
    }

    private function normalizarDificultad($d)
    {
        $valid = ['facil', 'media', 'dificil'];
        $d = mb_strtolower(trim((string)$d), 'UTF-8');
        return in_array($d, $valid) ? $d : 'media';
    }

    private function slugify($s)
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim($s, '-');
    }
}
