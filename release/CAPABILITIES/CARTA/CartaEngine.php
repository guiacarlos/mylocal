<?php
namespace CARTA;

require_once __DIR__ . '/CartaPublicaApi.php';
require_once __DIR__ . '/CartaAdminApi.php';

class CartaEngine
{
    private $services;
    private $publicaApi;
    private $adminApi;

    private $publicActions = ['get_carta', 'get_carta_mesa', 'get_producto'];

    public function __construct($services)
    {
        $this->services = $services;
        $this->publicaApi = new CartaPublicaApi($services);
        $this->adminApi = new CartaAdminApi($services);
    }

    public function executeAction($action, $data = [])
    {
        if (in_array($action, $this->publicActions)) {
            return $this->publicaApi->executeAction($action, $data);
        }

        return $this->adminApi->executeAction($action, $data);
    }
}
