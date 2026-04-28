<?php

/**
 * 📖 GeminiResponseParser - Parser de Respuestas
 * 
 * Responsabilidad ÚNICA: Parsear y validar respuestas de Gemini
 */
class GeminiResponseParser
{
    /**
     * Validar y extraer contenido de la respuesta
     * 
     * @param array $response Respuesta de la API
     * @return array Contenido validado
     * @throws Exception Si la respuesta no es válida
     */
    public function parse(array $response): array
    {
        // Validar estructura básica
        if (!isset($response['candidates']) || empty($response['candidates'])) {
            $errorMsg = $response['error']['message'] ?? 'Sin candidatos en respuesta';
            error_log("Gemini Error: " . $errorMsg);
            error_log("Gemini Response: " . json_encode($response));
            throw new Exception("Gemini: " . $errorMsg);
        }

        $candidate = $response['candidates'][0];

        // Validar contenido del candidato
        if (!isset($candidate['content'])) {
            error_log("Gemini Candidate: " . json_encode($candidate));
            throw new Exception("Gemini: Candidato sin contenido válido");
        }

        return $candidate['content'];
    }

    /**
     * Extraer texto de las partes del contenido
     * 
     * @param array $content Contenido del candidato
     * @return string Texto concatenado
     */
    public function extractText(array $content): string
    {
        $parts = $content['parts'] ?? [];
        $text = '';

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
        }

        return $text ?: 'Sin respuesta de texto.';
    }

    /**
     * Detectar si hay llamadas a funciones
     * 
     * @param array $content Contenido del candidato
     * @return bool True si hay function calls
     */
    public function hasFunctionCalls(array $content): bool
    {
        $parts = $content['parts'] ?? [];

        foreach ($parts as $part) {
            if (isset($part['function_call'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extraer llamadas a funciones
     * 
     * @param array $content Contenido del candidato
     * @return array Lista de function calls
     */
    public function extractFunctionCalls(array $content): array
    {
        $parts = $content['parts'] ?? [];
        $calls = [];

        foreach ($parts as $part) {
            if (isset($part['function_call'])) {
                $calls[] = [
                    'name' => $part['function_call']['name'],
                    'args' => $part['function_call']['args'] ?? []
                ];
            }
        }

        return $calls;
    }
}
