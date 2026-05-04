<?php
namespace AGENTE_RESTAURANTE;

require_once __DIR__ . '/MenuEngineer.php';

class MenuEngineerApi
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function executeAction($action, $data = [])
    {
        switch ($action) {
            case 'analyze_menu':
                $engineer = new MenuEngineer($this->services);
                return $engineer->analyze($data['local_id'] ?? '');
            default:
                return ['success' => false, 'error' => "Accion no soportada: $action"];
        }
    }
}
