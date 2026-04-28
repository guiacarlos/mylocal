<?php

/**
 *  HandlerInterface - Protocolo de Acción Unificada para ACIDE
 */
interface HandlerInterface
{
    /**
     * Punto de entrada para la ejecución de misiones.
     */
    public function execute($action, $args = []);
}
