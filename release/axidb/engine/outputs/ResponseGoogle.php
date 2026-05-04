<?php

require_once __DIR__ . '/BaseResponseHandler.php';

/**
 *  ResponseGoogle - Especialista en la salida de Google (Gemini)
 */
class ResponseGoogle extends BaseResponseHandler
{
    public function __construct($services)
    {
        parent::__construct($services, 'google');
    }

    protected function format($result)
    {
        // Limpieza de artefactos específicos de Google si fuera necesario
        $content = $result['content'] ?? '';

        // El Arquitecto Gemini a veces devuelve bloques de código markdown innecesarios en el contenido plano
        // Aquí podríamos aplicar filtros quirúrgicos en el futuro.

        return [
            'status' => 'success',
            'provider' => 'google-gemini',
            'content' => $content,
            'output' => $content,
            'timestamp' => date('c')
        ];
    }
}
