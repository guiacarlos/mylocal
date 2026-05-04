<?php
namespace CARTA;

require_once __DIR__ . '/models/LocalModel.php';
require_once __DIR__ . '/models/CategoriaModel.php';
require_once __DIR__ . '/models/ProductoCartaModel.php';
require_once __DIR__ . '/models/MesaModel.php';

use CARTA\models\LocalModel;
use CARTA\models\CategoriaModel;
use CARTA\models\ProductoCartaModel;
use CARTA\models\MesaModel;

class CartaPublicaApi
{
    private $services;
    private $localModel;
    private $categoriaModel;
    private $productoModel;
    private $mesaModel;

    public function __construct($services)
    {
        $this->services = $services;
        $this->localModel = new LocalModel($services);
        $this->categoriaModel = new CategoriaModel($services);
        $this->productoModel = new ProductoCartaModel($services);
        $this->mesaModel = new MesaModel($services);
    }

    public function executeAction($action, $data)
    {
        switch ($action) {
            case 'get_carta':
                return $this->getCarta($data);
            case 'get_carta_mesa':
                return $this->getCartaMesa($data);
            case 'get_producto':
                return $this->getProducto($data);
            default:
                return ['success' => false, 'error' => "Accion '$action' no soportada"];
        }
    }

    private function getCarta($data)
    {
        $slug = $data['slug'] ?? '';
        if (empty($slug)) {
            return ['success' => false, 'error' => 'slug es obligatorio'];
        }

        $local = $this->localModel->findBySlug($slug);
        if (!$local || empty($local['activo'])) {
            return ['success' => false, 'error' => 'Local no encontrado'];
        }

        $categorias = $this->categoriaModel->listAvailableByLocal($local['id']);
        $productos = $this->productoModel->listAvailableByLocal($local['id']);

        $cartaPorCategoria = [];
        $catMap = [];
        foreach (($categorias['data'] ?? []) as $cat) {
            $catMap[$cat['id']] = $cat;
            $cartaPorCategoria[$cat['id']] = [
                'categoria' => $cat,
                'productos' => []
            ];
        }

        foreach (($productos['data'] ?? []) as $prod) {
            $catId = $prod['categoria_id'] ?? '';
            if (isset($cartaPorCategoria[$catId])) {
                $cartaPorCategoria[$catId]['productos'][] = $prod;
            }
        }

        return [
            'success' => true,
            'data' => [
                'local' => [
                    'nombre' => $local['nombre'],
                    'slug' => $local['slug'],
                    'descripcion_corta' => $local['descripcion_corta'] ?? '',
                    'logo_url' => $local['logo_url'] ?? '',
                    'idioma_defecto' => $local['idioma_defecto'] ?? 'es',
                    'idiomas_activos' => $local['idiomas_activos'] ?? ['es']
                ],
                'carta' => array_values($cartaPorCategoria)
            ]
        ];
    }

    private function getCartaMesa($data)
    {
        $slug = $data['slug'] ?? '';
        $mesaSlug = $data['mesa_slug'] ?? '';

        $cartaResult = $this->getCarta(['slug' => $slug]);
        if (!$cartaResult['success']) {
            return $cartaResult;
        }

        $local = $this->localModel->findBySlug($slug);
        $mesa = $this->mesaModel->findBySlug($local['id'], $mesaSlug);

        $cartaResult['data']['mesa'] = $mesa ? [
            'id' => $mesa['id'],
            'zona_nombre' => $mesa['zona_nombre'],
            'numero' => $mesa['numero']
        ] : null;

        return $cartaResult;
    }

    private function getProducto($data)
    {
        $id = $data['id'] ?? '';
        if (empty($id)) {
            return ['success' => false, 'error' => 'id es obligatorio'];
        }

        $producto = $this->productoModel->read($id);
        if (isset($producto['success']) && !$producto['success']) {
            return ['success' => false, 'error' => 'Producto no encontrado'];
        }

        if (empty($producto['disponible'])) {
            return ['success' => false, 'error' => 'Producto no disponible'];
        }

        return ['success' => true, 'data' => $producto];
    }
}
