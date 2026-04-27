<?php

/**
 * ResponseProcessor - Responsabilidad: Procesar respuestas de proveedores de IA
 */
class ResponseProcessor
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function process($provider, $result)
    {
        $cleanProvider = $this->normalizeProviderName($provider);
        $className = 'Response' . $cleanProvider;
        $handlerFile = dirname(dirname(__DIR__)) . "/outputs/{$className}.php";

        if (file_exists($handlerFile)) {
            require_once $handlerFile;

            if (class_exists($className)) {
                $handler = new $className($this->services);
                return $handler->process($result);
            }
        }

        return array_merge(array(
            'status' => 'success',
            'provider' => $provider,
            'timestamp' => date('c')
        ), $result);
    }

    private function normalizeProviderName($provider)
    {
        if (strpos($provider, '-') !== false) {
            $parts = explode('-', $provider);
            return ucfirst($parts[0]);
        }

        return ucfirst($provider);
    }
}
