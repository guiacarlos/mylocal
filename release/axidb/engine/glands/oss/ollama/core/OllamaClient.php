<?php

/**
 *  OllamaClient - Cliente HTTP Puro
 * 
 * Responsabilidad ÚNICA: Comunicación HTTP con Ollama API
 */
class OllamaClient
{
    private $config;

    public function __construct(OllamaConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Hacer petición POST a Ollama API
     * 
     * @param string $endpoint Endpoint (ej: /api/chat)
     * @param array $payload Payload JSON
     * @return array Respuesta parseada
     * @throws Exception Si hay error
     */
    public function post(string $endpoint, array $payload): array
    {
        $url = $this->config->getBaseUrl() . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->getTimeout());

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Validar error de conexión
        if ($curlError) {
            throw new Exception("Error de conexión con Ollama: " . $curlError);
        }

        // Validar código HTTP
        if ($httpCode !== 200) {
            throw new Exception("Ollama API Error (HTTP {$httpCode}): " . substr($response, 0, 200));
        }

        // Parsear JSON
        $result = json_decode($response, true);
        if (!$result) {
            throw new Exception("Respuesta de Ollama no es JSON válido");
        }

        return $result;
    }

    /**
     * Hacer petición GET a Ollama API
     * 
     * @param string $endpoint Endpoint
     * @return array Respuesta parseada
     * @throws Exception Si hay error
     */
    public function get(string $endpoint): array
    {
        $url = $this->config->getBaseUrl() . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->getTimeout());

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Ollama API Error (HTTP {$httpCode})");
        }

        $result = json_decode($response, true);
        if (!$result) {
            throw new Exception("Respuesta de Ollama no es JSON válido");
        }

        return $result;
    }
}
