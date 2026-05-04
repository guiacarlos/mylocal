<?php

require_once __DIR__ . '/BaseResponseHandler.php';

/**
 * 🚀 ResponseGroq - Especialista en la salida LPU (Speed)
 */
class ResponseGroq extends BaseResponseHandler
{
    public function __construct($services)
    {
        parent::__construct($services, 'groq');
    }

    protected function format($result)
    {
        $content = $result['content'] ?? '';

        return [
            'status' => 'success',
            'provider' => 'oss-groq',
            'content' => $content,
            'output' => $content,
            'timestamp' => date('c')
        ];
    }
}
