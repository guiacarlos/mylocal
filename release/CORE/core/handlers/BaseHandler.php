<?php

require_once __DIR__ . '/HandlerInterface.php';

/**
 * 🏛️ BaseHandler - Cimiento de Soberanía para Handlers
 */
abstract class BaseHandler implements HandlerInterface
{
    protected $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    /**
     * Implementación base del despacho.
     * Los hijos deben definir la lógica específica.
     */
    abstract public function execute($action, $args = []);

    /**
     * Acceso rápido a configuración
     */
    protected function getConfig($key = null, $default = null)
    {
        $config = $this->services['crud']->read('system', 'configs') ?: [];
        if ($key === null)
            return $config;
        return $config[$key] ?? $default;
    }
}
