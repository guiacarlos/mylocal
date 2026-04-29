<?php
namespace AGENTE_RESTAURANTE;

require_once __DIR__ . '/AlertEngine.php';

class AlertApi
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function executeAction($action, $data = [])
    {
        switch ($action) {
            case 'check_alerts':
                $engine = new AlertEngine($this->services);
                return $engine->checkAlerts($data['local_id'] ?? '');
            default:
                return ['success' => false, 'error' => "Accion no soportada: $action"];
        }
    }
}
