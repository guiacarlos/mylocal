<?php

/**
 * 📖 OllamaResponseParser - Parser de Respuestas
 * 
 * Responsabilidad ÚNICA: Parsear y validar respuestas de Ollama
 */
class OllamaResponseParser
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
        if (!isset($response['message'])) {
            error_log("Ollama Response: " . json_encode($response));
            throw new Exception("Ollama: Respuesta sin mensaje");
        }

        return $response['message'];
    }

    /**
     * Extraer texto del mensaje
     * 
     * @param array $message Mensaje de Ollama
     * @return string Texto del mensaje
     */
    public function extractText(array $message): string
    {
        return $message['content'] ?? 'Sin respuesta de texto.';
    }

    /**
     * Detectar si hay llamadas a herramientas
     * 
     * @param array $message Mensaje de Ollama
     * @return bool True si hay tool calls
     */
    public function hasToolCalls(array $message): bool
    {
        return isset($message['tool_calls']) && !empty($message['tool_calls']);
    }

    /**
     * Extraer llamadas a herramientas
     * 
     * @param array $message Mensaje de Ollama
     * @return array Lista de tool calls
     */
    public function extractToolCalls(array $message): array
    {
        if (!$this->hasToolCalls($message)) {
            return [];
        }

        $calls = [];
        foreach ($message['tool_calls'] as $call) {
            $calls[] = [
                'name' => $call['function']['name'] ?? '',
                'args' => $call['function']['arguments'] ?? []
            ];
        }

        return $calls;
    }
}
