<?php

/**
 * 🌿 ACIDE SOBERANO - OllamaConnector v1.0
 * Librería de referencia para la conexión programática con Ollama (Local & Cloud).
 * Implementa Chat, Generate, Embeddings y Gestión de Modelos.
 */

class OllamaConnector
{
    private $baseUrl;

    public function __construct($baseUrl = 'http://localhost:11434')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * 💬 CHAT MESSAGES (Protocolo OpenAI-like con Herramientas)
     * POST /api/chat
     */
    public function chat($params)
    {
        // Forzamos stream false para la integración actual del búnker
        $params['stream'] = false;
        return $this->request('POST', '/api/chat', $params);
    }

    /**
     * 📝 GENERATE RESPONSE (Single Prompt)
     * POST /api/generate
     */
    public function generate($params)
    {
        $params['stream'] = false;
        return $this->request('POST', '/api/generate', $params);
    }

    /**
     * 🧠 EMBEDDINGS
     * POST /api/embed
     */
    public function embed($model, $input, $options = [])
    {
        $data = array_merge([
            'model' => $model,
            'input' => $input
        ], $options);
        return $this->request('POST', '/api/embed', $data);
    }

    /**
     * 📋 LIST MODELS (Tags)
     * GET /api/tags
     */
    public function listModels()
    {
        return $this->request('GET', '/api/tags');
    }

    /**
     * 🔍 SHOW MODEL DETAILS
     * POST /api/show
     */
    public function showModel($model)
    {
        return $this->request('POST', '/api/show', ['model' => $model]);
    }

    /**
     * 🛰️ PULL MODEL (Download)
     * POST /api/pull
     */
    public function pullModel($model, $stream = false)
    {
        return $this->request('POST', '/api/pull', ['model' => $model, 'stream' => $stream]);
    }

    /**
     * 🗑️ DELETE MODEL
     * DELETE /api/delete
     */
    public function deleteModel($model)
    {
        return $this->request('DELETE', '/api/delete', ['model' => $model]);
    }

    /**
     * ⚙️ NÚCLEO DE PETICIONES (Engine)
     */
    private function request($method, $endpoint, $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        $headers = [
            'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Mayor timeout para carga de modelos pesados
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Ollama Connection Error: " . $error);
        }

        $result = json_decode($response, true) ?: [];

        if ($httpCode >= 400) {
            $msg = $result['error'] ?? ($result['message'] ?? (is_string($response) ? $response : "Unknown API Error"));
            throw new Exception("Ollama API Error ($httpCode): " . $msg);
        }

        return $result;
    }
}
