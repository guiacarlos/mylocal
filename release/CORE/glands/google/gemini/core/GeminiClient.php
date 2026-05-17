<?php

/**
 * ðŸŒ GeminiClient - Cliente HTTP Puro
 * 
 * Responsabilidad ÃšNICA: ComunicaciÃ³n HTTP con la API de Gemini
 */
class GeminiClient
{
    private $config;

    public function __construct(GeminiConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Hacer peticiÃ³n POST a Gemini API
     * 
     * @param string $url URL completa del endpoint
     * @param array $payload Payload JSON
     * @return array Respuesta parseada
     * @throws Exception Si hay error de red o HTTP
     */
    public function post(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->getTimeout());

        $payloadJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents(__DIR__ . '/last_payload.json', $payloadJson);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Validar error de conexiÃ³n
        if ($curlError) {
            throw new Exception("Error de conexiÃ³n con Gemini: " . $curlError);
        }

        // Validar cÃ³digo HTTP
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? $response;
            throw new Exception("Gemini API Error (HTTP {$httpCode}): {$errorMsg}");
        }

        // Parsear JSON
        $result = json_decode($response, true);
        if (!$result) {
            throw new Exception("Respuesta de Gemini no es JSON vÃ¡lido");
        }

        return $result;
    }

    /**
     * Hacer peticiÃ³n GET a Gemini API
     * 
     * @param string $url URL completa del endpoint
     * @return array Respuesta parseada
     * @throws Exception Si hay error
     */
    public function get(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->getTimeout());

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Gemini API Error (HTTP {$httpCode})");
        }

        $result = json_decode($response, true);
        if (!$result) {
            throw new Exception("Respuesta de Gemini no es JSON vÃ¡lido");
        }

        return $result;
    }

    /**
     * Construir URL para generateContent
     * 
     * @param string $model Nombre del modelo
     * @param string $apiVersion VersiÃ³n de la API (v1beta o v1)
     * @return string URL completa
     */
    public function buildGenerateContentUrl(string $model, string $apiVersion = 'v1beta'): string
    {
        $apiKey = $this->config->getApiKey();
        return "https://generativelanguage.googleapis.com/{$apiVersion}/models/{$model}:generateContent?key={$apiKey}";
    }

    /**
     * Construir URL para listar modelos
     * 
     * @param string $apiVersion VersiÃ³n de la API
     * @return string URL completa
     */
    public function buildListModelsUrl(string $apiVersion = 'v1beta'): string
    {
        $apiKey = $this->config->getApiKey();
        return "https://generativelanguage.googleapis.com/{$apiVersion}/models?key={$apiKey}";
    }
}
