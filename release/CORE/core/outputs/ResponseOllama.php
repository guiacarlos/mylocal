<?php

require_once __DIR__ . '/BaseResponseHandler.php';

/**
 * 🌑 ResponseOllama - Especialista en la salida Local
 */
class ResponseOllama extends BaseResponseHandler
{
    public function __construct($services)
    {
        parent::__construct($services, 'ollama');
    }

    protected function format($result)
    {
        $content = $result['content'] ?? '';

        return [
            'status' => 'success',
            'provider' => 'oss-ollama',
            'content' => $content,
            'output' => $content, // Alias de compatibilidad
            'timestamp' => date('c')
        ];
    }
}
