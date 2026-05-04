<?php

/**
 *  OllamaPayloadBuilder - Constructor de Payloads
 * 
 * Responsabilidad ÚNICA: Construir payloads para Ollama API
 */
class OllamaPayloadBuilder
{
    private $config;

    public function __construct(OllamaConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Construir payload inicial para /api/chat
     * 
     * @param string $userPrompt Prompt del usuario
     * @param array|null $tools Definiciones de herramientas MCP
     * @param array $history Historial previo (opcional)
     * @return array Payload completo
     */
    public function buildInitialPayload(string $userPrompt, ?array $tools = null, array $history = []): array
    {
        $systemPrompt = $this->config->getSystemPrompt();

        //  INYECCIÓN DE ARSENAL (Fallback para modelos locales)
        if ($tools !== null && !empty($tools)) {
            $systemPrompt .= "\n\nARSENAL DISPONIBLE:\n";
            foreach ($tools as $tool) {
                $systemPrompt .= "- {$tool['name']}: {$tool['description']}\n";
            }
            $systemPrompt .= "\nSi necesitas operar archivos o ejecutar comandos, usa estas herramientas nativamente.";
        }

        $messages = [];

        //  SYSTEM MESSAGE
        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt
        ];

        //  HISTORIAL (Si existe)
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] ?? 'user',
                'content' => $msg['content'] ?? ''
            ];
        }

        //  ÚLTIMA PETICIÓN
        $messages[] = [
            'role' => 'user',
            'content' => $userPrompt
        ];

        $payload = [
            'model' => $this->config->getModel(),
            'messages' => $messages,
            'stream' => false,
            'options' => $this->config->getOptions()
        ];

        // Agregar herramientas si están disponibles (Formato Ollama: type: function)
        if ($tools !== null && !empty($tools)) {
            $formattedTools = [];
            foreach ($tools as $tool) {
                $formattedTools[] = [
                    'type' => 'function',
                    'function' => [
                        'name' => $tool['name'],
                        'description' => $tool['description'],
                        'parameters' => $tool['parameters']
                    ]
                ];
            }
            $payload['tools'] = $formattedTools;
        }

        return $payload;
    }

    /**
     * Agregar mensaje al payload
     * 
     * @param array $payload Payload actual
     * @param string $role Rol (assistant, user, tool)
     * @param string|null $content Contenido del mensaje
     * @param array|null $toolCalls Llamadas a herramientas
     * @return array Payload actualizado
     */
    public function addMessage(array $payload, string $role, ?string $content = null, ?array $toolCalls = null): array
    {
        $message = ['role' => $role];

        if ($content !== null) {
            $message['content'] = $content;
        }

        if ($toolCalls !== null) {
            $message['tool_calls'] = $toolCalls;
        }

        $payload['messages'][] = $message;

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
