<?php

require_once __DIR__ . '/BaseHandler.php';

/**
 * ReservasHandler - Puente hacia el Motor de Reservas SOBERANO.
 */
class ReservasHandler extends BaseHandler
{
    private $engine;

    public function __construct($services)
    {
        parent::__construct($services);
        $this->engine = isset($this->services['reservas']) ? $this->services['reservas'] : null;
    }

    public function execute($action, $args = [])
    {
        if (!$this->engine) {
            throw new Exception("La capacidad RESERVAS no está instalada o activa (Falta carpeta /RESERVAS o ReservasEngine.php).");
        }

        try {
            return $this->engine->executeAction($action, $args);
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
