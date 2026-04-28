<?php

/**
 * 🏗️ GeminiPayloadBuilder - Constructor de Payloads
 * 
 * Responsabilidad ÚNICA: Construir payloads para la API de Gemini
 */
class GeminiPayloadBuilder
{
    private $config;

    public function __construct(GeminiConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Construir payload inicial para generateContent
     */
    public function buildInitialPayload(string $userPrompt, ?array $tools = null, array $history = [], bool $noSystem = false): array
    {
        $payload = [
            'contents' => [],
            'generation_config' => $this->config->getGenerationConfig()
        ];

        // 🛡️ SYSTEM INSTRUCTION (Top-level)
        $systemPrompt = trim($this->config->getSystemPrompt());
        if ($systemPrompt !== '' && !$noSystem) {
            $payload['system_instruction'] = [
                'parts' => [['text' => $systemPrompt]]
            ];
        }

        // 📜 HISTORIAL (Filtrado de vacíos)
        foreach ($history as $msg) {
            $content = trim($msg['content'] ?? '');
            if ($content === '')
                continue;

            $role = ($msg['role'] === 'user' || $msg['role'] === 'tool') ? 'user' : 'model';
            $payload['contents'][] = [
                'role' => $role,
                'parts' => [['text' => $content]]
            ];
        }

        // 🎯 ÚLTIMA PETICIÓN (User prompt)
        $userPrompt = trim($userPrompt);
        if ($userPrompt === '') {
            $userPrompt = "Hola"; // Fallback mínimo
        }

        $payload['contents'][] = [
            'role' => 'user',
            'parts' => [['text' => $userPrompt]]
        ];

        // 🛠️ HERRAMIENTAS
        if ($tools !== null && !empty($tools)) {
            $payload['tools'] = [['function_declarations' => $tools]];
        }

        return $payload;
    }

    /**
     * Agregar respuesta del modelo al payload
     */
    public function addModelResponse(array $payload, array $modelResponse): array
    {
        $parts = $modelResponse['parts'] ?? [];
        $cleanedParts = [];

        foreach ($parts as $part) {
            if (isset($part['text']) && trim($part['text']) !== '') {
                $cleanedParts[] = ['text' => $part['text']];
            }

            if (isset($part['function_call'])) {
                $cleanedParts[] = [
                    'function_call' => [
                        'name' => $part['function_call']['name'],
                        'args' => (object) ($part['function_call']['args'] ?? [])
                    ]
                ];
            }
        }

        // 🛡️ BLINDAJE CRÍTICO: Nunca enviar parts vacío
        if (empty($cleanedParts)) {
            $cleanedParts[] = ['text' => '...'];
        }

        $payload['contents'][] = [
            'role' => 'model',
            'parts' => $cleanedParts
        ];

        return $payload;
    }

    /**
     * Agregar respuestas de herramientas al payload
     */
    public function addToolResponses(array $payload, array $toolResponses): array
    {
        if (empty($toolResponses)) {
            return $payload;
        }

        // 🛡️ En Gemini 1.5, las respuestas de funciones van en el rol 'user'
        // pero cada parte DEBE ser un functionResponse.
        $payload['contents'][] = [
            'role' => 'user',
            'parts' => $toolResponses
        ];

        return $payload;
    }

    /**
     * Eliminar herramientas del payload (para forzar solo texto)
     * 
     * @param array $payload Payload actual
     * @return array Payload sin herramientas
     */
    public function removeTools(array $payload): array
    {
        unset($payload['tools']);
        return $payload;
    }
}
