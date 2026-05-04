<?php

require_once __DIR__ . '/BaseHandler.php';

/**
 *  AcademyHandler - Gestión Atómica de Academia para IAs
 */
class AcademyHandler extends BaseHandler
{
    private $engine;

    public function __construct($services)
    {
        parent::__construct($services);
        $this->engine = isset($this->services['academy']) ? $this->services['academy'] : null;
    }

    public function execute($command, $args = [])
    {
        if (!$this->engine) {
            throw new Exception("La capacidad ACADEMIA no está instalada o activa (Falta carpeta /ACADEMY o AcademyEngine.php).");
        }

        try {
            return $this->engine->executeAction($command, $args);
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
