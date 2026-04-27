<?php

/**
 * 🌿 Ollama - Orquestador Principal
 * 
 * Responsabilidad: Orquestar todos los componentes modulares de Ollama
 * 
 * Arquitectura Modular:
 * - OllamaConfig: Configuración
 * - OllamaClient: Cliente HTTP
 * - OllamaPayloadBuilder: Constructor de payloads
 * - OllamaResponseParser: Parser de respuestas
 * - OllamaToolExecutor: Ejecutor de herramientas MCP
 */

require_once __DIR__ . '/config/OllamaConfig.php';
require_once __DIR__ . '/core/OllamaClient.php';
require_once __DIR__ . '/core/OllamaPayloadBuilder.php';
require_once __DIR__ . '/core/OllamaResponseParser.php';
require_once __DIR__ . '/core/OllamaToolExecutor.php';
require_once dirname(__DIR__, 3) . '/core/McpBridge.php';

class Ollama
{
    private $services;
    private $config;
    private $client;
    private $payloadBuilder;
    private $responseParser;
    private $toolExecutor;
    private $mcp;

    public function __construct($services)
    {
        $this->services = $services;

        // Cargar configuración del sistema
        $systemConfig = $this->services['crud']->read('system', 'configs') ?: [];

        // Inicializar componentes modulares
        $this->config = new OllamaConfig($systemConfig);
        $this->client = new OllamaClient($this->config);
        $this->payloadBuilder = new OllamaPayloadBuilder($this->config);
        $this->responseParser = new OllamaResponseParser();

        // Inicializar MCP Bridge
        $acideRoot = dirname(__DIR__, 3);
        $this->mcp = new McpBridge($acideRoot, $this->services);

        // Inicializar ejecutor de herramientas
        $this->toolExecutor = new OllamaToolExecutor($this->mcp, $this->config);
    }

    /**
     * 📋 Listar modelos disponibles
     * 
     * @return array Lista de modelos
     */
    public function listModels(): array
    {
        try {
            $response = $this->client->get('/api/tags');

            $models = [];
            if (isset($response['models'])) {
                foreach ($response['models'] as $model) {
                    $models[] = [
                        'id' => $model['name'],
                        'name' => 'Ollama: ' . $model['name'],
                        'provider' => 'ollama',
                        'size' => $model['size'] ?? 0
                    ];
                }
            }

            return $models;
        } catch (Exception $e) {
            error_log("Ollama listModels Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 🚀 Generar respuesta
     * 
     * @param array $params Parámetros de generación
     * @return array Respuesta formateada
     */
    public function generate(array $params): array
    {
        $prompt = $params['prompt'] ?? '';
        if (empty($prompt)) {
            throw new Exception("Prompt vacío");
        }

        // Construir payload inicial con historia
        $history = $params['conversationHistory'] ?? [];
        $tools = $this->mcp->getToolsDefinition();
        $payload = $this->payloadBuilder->buildInitialPayload($prompt, $tools, $history);

        // Procesar conversación
        return $this->processConversation($payload);
    }

    /**
     * 🔄 Procesar conversación con herramientas
     * 
     * @param array $payload Payload inicial
     * @param int $depth Profundidad de recursión
     * @return array Respuesta final
     */
    private function processConversation(array $payload, int $depth = 0): array
    {
        // Control 1: Límite de recursión
        if ($depth >= $this->config->getMaxRecursionDepth()) {
            error_log("Ollama: Máxima recursión alcanzada (depth={$depth})");
            return [
                'status' => 'success',
                'content' => "Máxima recursión alcanzada. Finalizando."
            ];
        }

        // Control 2: Límite de herramientas
        if ($this->toolExecutor->hasReachedLimit()) {
            error_log("Ollama: Límite de herramientas alcanzado. Forzando respuesta final.");
            return $this->forceFinalResponse($payload);
        }

        // Hacer petición a Ollama
        try {
            $response = $this->client->post('/api/chat', $payload);
        } catch (Exception $e) {
            error_log("Ollama Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'content' => "Error de conexión con Ollama: " . $e->getMessage()
            ];
        }

        // Parsear respuesta
        $message = $this->responseParser->parse($response);

        // Verificar si hay llamadas a herramientas
        if (!$this->responseParser->hasToolCalls($message)) {
            // No hay herramientas, retornar texto
            $text = $this->responseParser->extractText($message);
            return [
                'status' => 'success',
                'content' => $text
            ];
        }

        // Ejecutar herramientas
        $toolCalls = $this->responseParser->extractToolCalls($message);
        $toolTraces = "";

        foreach ($toolCalls as $call) {
            $toolName = $call['name'];

            // Control 3: Detección de bucles
            if ($this->toolExecutor->isLoop($toolName)) {
                error_log("Ollama: Bucle detectado - herramienta '{$toolName}' ya ejecutada");
                $toolTraces .= "⚠️ **Bucle detectado**: [{$toolName}] ya fue ejecutada.\n";
                continue;
            }

            // Ejecutar herramienta
            $toolOutput = $this->toolExecutor->execute($toolName, $call['args']);
            $toolTraces .= "🛠️ **Acción**: [{$toolName}]\n";

            // Agregar resultado de herramienta al payload
            $payload = $this->payloadBuilder->addMessage($payload, 'tool', $toolOutput);
        }

        // Agregar mensaje del asistente al payload
        $payload = $this->payloadBuilder->addMessage(
            $payload,
            'assistant',
            $message['content'] ?? null,
            $message['tool_calls'] ?? null
        );

        // Recursión
        $result = $this->processConversation($payload, $depth + 1);

        // Agregar trazas de herramientas al resultado
        return [
            'status' => 'success',
            'content' => $toolTraces . $result['content']
        ];
    }

    /**
     * 🎯 Forzar respuesta final (sin herramientas)
     * 
     * @param array $payload Payload actual
     * @return array Respuesta final
     */
    private function forceFinalResponse(array $payload): array
    {
        // Eliminar herramientas del payload
        $payload = $this->payloadBuilder->removeTools($payload);

        try {
            $response = $this->client->post('/api/chat', $payload);
            $message = $this->responseParser->parse($response);
            $text = $this->responseParser->extractText($message);

            return [
                'status' => 'success',
                'content' => $text
            ];
        } catch (Exception $e) {
            error_log("Ollama: Error en respuesta final - " . $e->getMessage());
            $count = $this->toolExecutor->getExecutedCount();
            return [
                'status' => 'success',
                'content' => "He ejecutado {$count} acciones. Límite alcanzado."
            ];
        }
    }
}
