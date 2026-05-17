<?php

/**
 *  ACIDE SOBERANO - GroqConnector v1.0
 * Librería de referencia para la conexión total con la infraestructura de Groq.
 * Implementa Chat, Respuestas (Beta), Audio, Modelos y Batches.
 */

class GroqConnector
{
    private $apiKey;
    private $baseUrl = 'https://api.groq.com/openai/v1';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     *  CHAT COMPLETIONS
     * POST https://api.groq.com/openai/v1/chat/completions
     */
    public function chatCompletion($params)
    {
        return $this->request('POST', '/chat/completions', $params);
    }

    /**
     *  RESPONSES (BETA)
     * POST https://api.groq.com/openai/v1/responses
     */
    public function createResponse($params)
    {
        return $this->request('POST', '/responses', $params);
    }

    /**
     *  AUDIO TRANSCRIPTIONS
     * POST https://api.groq.com/openai/v1/audio/transcriptions
     */
    public function createTranscription($filePath, $model = 'whisper-large-v3', $extraParams = [])
    {
        $data = array_merge([
            'model' => $model,
            'file' => new CURLFile($filePath)
        ], $extraParams);

        return $this->request('POST', '/audio/transcriptions', $data, true);
    }

    /**
     *  AUDIO TRANSLATIONS
     * POST https://api.groq.com/openai/v1/audio/translations
     */
    public function createTranslation($filePath, $model = 'whisper-large-v3', $extraParams = [])
    {
        $data = array_merge([
            'model' => $model,
            'file' => new CURLFile($filePath)
        ], $extraParams);

        return $this->request('POST', '/audio/translations', $data, true);
    }

    /**
     *  LIST MODELS
     * GET https://api.groq.com/openai/v1/models
     */
    public function listModels()
    {
        return $this->request('GET', '/models');
    }

    /**
     *  BATCH OPERATIONS
     */
    public function createBatch($inputFileId, $endpoint = '/v1/chat/completions', $window = '24h')
    {
        return $this->request('POST', '/batches', [
            'input_file_id' => $inputFileId,
            'endpoint' => $endpoint,
            'completion_window' => $window
        ]);
    }

    /**
     *  FILE OPERATIONS
     */
    public function uploadFile($filePath, $purpose = 'batch')
    {
        $data = [
            'file' => new CURLFile($filePath),
            'purpose' => $purpose
        ];
        return $this->request('POST', '/files', $data, true);
    }

    /**
     *  NÚCLEO DE PETICIONES (Engine)
     */
    private function request($method, $endpoint, $data = null, $isMultipart = false)
    {
        if (empty($this->apiKey)) {
            throw new Exception("Groq Error: API Key no suministrada.");
        }

        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        $headers = [
            "Authorization: Bearer " . $this->apiKey
        ];

        if (!$isMultipart) {
            $headers[] = "Content-Type: application/json";
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } else {
            // curl_setopt handle automatically boundary for multipart/form-data
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Groq Connection Error: " . $error);
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = $result['error']['message'] ?? ($result['message'] ?? $response);
            throw new Exception("Groq API Error ($httpCode): " . $msg);
        }

        return $result;
    }
}
