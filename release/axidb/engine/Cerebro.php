<?php

/**
 *  CEREBRO SOBERANO - El Corazón de ACIDE
 * Responsabilidad: Orquestación, Despacho y Bus de Inteligencia.
 * v1.0 - El Nexo Inteligente
 */

require_once __DIR__ . '/Motor.php';

class Cerebro
{
    private $motor;
    private $services;

    public function __construct($services = [])
    {
        $this->services = $services;
        $this->motor = new Motor();
    }

    /**
     *  Bus de Comandos (El Orquestador Único)
     */
    public function dispatch($action, $args = [])
    {
        //  Dominio Unificado
        // Delegamos todo al núcleo ACIDE para garantizar consistencia en la soberanía de archivos y herramientas.
        if (!class_exists('ACIDE')) {
            require_once __DIR__ . '/ACIDE.php';
        }

        $acide = new ACIDE();
        return $acide->execute(['action' => $action, 'args' => $args]);
    }
}
