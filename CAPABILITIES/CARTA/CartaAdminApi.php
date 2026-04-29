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

class CartaAdminApi
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
            case 'create_local':
                return $this->localModel->create($data);
            case 'update_local':
                return $this->localModel->update($data['id'] ?? '', $data);
            case 'get_local':
                return ['success' => true, 'data' => $this->localModel->read($data['id'] ?? '')];
            case 'list_locales':
                return $this->localModel->listAll();
            case 'delete_local':
                return $this->localModel->delete($data['id'] ?? '');

            case 'create_categoria':
                return $this->categoriaModel->create($data);
            case 'update_categoria':
                return $this->categoriaModel->update($data['id'] ?? '', $data);
            case 'get_categoria':
                return ['success' => true, 'data' => $this->categoriaModel->read($data['id'] ?? '')];
            case 'list_categorias':
                return $this->categoriaModel->listByLocal($data['local_id'] ?? '');
            case 'delete_categoria':
                return $this->categoriaModel->delete($data['id'] ?? '');

            case 'create_producto':
                return $this->productoModel->create($data);
            case 'update_producto':
                return $this->productoModel->update($data['id'] ?? '', $data);
            case 'get_producto':
                return ['success' => true, 'data' => $this->productoModel->read($data['id'] ?? '')];
            case 'list_productos':
                return $this->productoModel->listByLocal($data['local_id'] ?? '');
            case 'list_productos_categoria':
                return $this->productoModel->listByCategoria($data['categoria_id'] ?? '');
            case 'delete_producto':
                return $this->productoModel->delete($data['id'] ?? '');

            case 'create_mesa':
                return $this->mesaModel->create($data);
            case 'update_mesa':
                return $this->mesaModel->update($data['id'] ?? '', $data);
            case 'get_mesa':
                return ['success' => true, 'data' => $this->mesaModel->read($data['id'] ?? '')];
            case 'list_mesas':
                return $this->mesaModel->listByLocal($data['local_id'] ?? '');
            case 'list_mesas_zona':
                return $this->mesaModel->listByZona($data['local_id'] ?? '', $data['zona_nombre'] ?? '');
            case 'get_zonas':
                return $this->mesaModel->getZonas($data['local_id'] ?? '');
            case 'delete_mesa':
                return $this->mesaModel->delete($data['id'] ?? '');

            default:
                return ['success' => false, 'error' => "Accion '$action' no soportada"];
        }
    }
}
