<?php

/**
 *  BaseResponseHandler - Prototipo Atómico de Salida
 */
abstract class BaseResponseHandler
{
    protected $services;
    protected $logPath;

    public function __construct($services, $provider)
    {
        $this->services = $services;
        $this->logPath = dirname(__DIR__, 2) . "/data/logs/response_{$provider}.json";

        if (!is_dir(dirname($this->logPath))) {
            mkdir(dirname($this->logPath), 0777, true);
        }
    }

    /**
     *  PROCESADO UNIVERSAL
     */
    public function process($result)
    {
        // Persistencia para depuración técnica (Búnker Debug)
        file_put_contents($this->logPath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!isset($result['status'])) {
            $result['status'] = 'success';
        }

        if (!isset($result['content'])) {
            $result['content'] = 'Respuesta vacía del motor AI.';
        }

        $formatted = $this->format($result);
        $formatted['log_file'] = basename($this->logPath);

        return $formatted;
    }

    /**
     * Formateo específico por proveedor
     */
    abstract protected function format($result);
}
