<?php

/**
 *  Gemini - Orquestador Principal
 * 
 * Responsabilidad: Orquestar todos los componentes modulares de Gemini
 * 
 * Arquitectura Modular:
 * - GeminiConfig: Configuración
 * - GeminiClient: Cliente HTTP
 * - GeminiPayloadBuilder: Constructor de payloads
 * - GeminiResponseParser: Parser de respuestas
 * - GeminiToolExecutor: Ejecutor de herramientas MCP
 */

require_once __DIR__ . '/config/GeminiConfig.php';
require_once __DIR__ . '/core/GeminiClient.php';
require_once __DIR__ . '/core/GeminiPayloadBuilder.php';
require_once __DIR__ . '/core/GeminiResponseParser.php';
require_once __DIR__ . '/core/GeminiToolExecutor.php';
require_once dirname(__DIR__, 3) . '/core/McpBridge.php';

class Gemini
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

        //  Compatibilidad con Academia: Fusionar con academy_settings si existen
        $academySettings = $this->services['crud']->read('academy_settings', 'current') ?: [];
        $mergedConfig = $systemConfig;

        // Priorizar llaves de academia para el contexto de aprendizaje
        if (!empty($academySettings['gemini_api_key'])) {
            $mergedConfig['google_key'] = $academySettings['gemini_api_key'];
        }
        if (!empty($academySettings['gemini_model'])) {
            $mergedConfig['ai_model'] = $academySettings['gemini_model'];
        }
        if (!empty($academySettings['default_system_prompt'])) {
            $mergedConfig['ai_system_prompt'] = $academySettings['default_system_prompt'];
        }

        // Inicializar componentes modulares
        $this->config = new GeminiConfig($mergedConfig);
        $this->client = new GeminiClient($this->config);
        $this->payloadBuilder = new GeminiPayloadBuilder($this->config);
        $this->responseParser = new GeminiResponseParser();

        // Inicializar MCP Bridge
        $acideRoot = dirname(__DIR__, 3);
        $this->mcp = new McpBridge($acideRoot, $this->services);

        // Inicializar ejecutor de herramientas
        $this->toolExecutor = new GeminiToolExecutor($this->mcp, $this->config);
    }

    /**
     *  Listar modelos disponibles
     * 
     * @return array Lista de modelos
     */
    public function listModels(): array
    {
        if (!$this->config->getApiKey()) {
            return $this->config->getFallbackModels();
        }

        try {
            // Intentar v1beta primero
            $url = $this->client->buildListModelsUrl('v1beta');
            $response = $this->client->get($url);

            return $this->parseModelsList($response);
        } catch (Exception $e) {
            // Fallback a v1
            try {
                $url = $this->client->buildListModelsUrl('v1');
                $response = $this->client->get($url);

                return $this->parseModelsList($response);
            } catch (Exception $e2) {
                error_log("Gemini listModels Error: " . $e2->getMessage());
                return $this->config->getFallbackModels();
            }
        }
    }

    /**
     *  Generar respuesta
     * 
     * @param array $params Parámetros de generación
     * @return array Respuesta formateada
     */
    public function generate(array $params): array
    {
        if (!$this->config->getApiKey()) {
            throw new Exception("Google API Key no configurada");
        }

        $prompt = $params['prompt'] ?? '';
        error_log("[Gemini DEBUG] Model: " . ($params['model'] ?? $this->config->getModel()) . " | Key: " . substr($this->config->getApiKey(), 0, 5) . "...");
        if (empty($prompt)) {
            throw new Exception("Prompt vacío");
        }

        $lessonId = $params['lesson_id'] ?? null;

        //  VAULT CHECK: Soberanía de Datos y Velocidad
        if ($lessonId) {
            $cachedResponse = $this->checkVault($prompt, $lessonId);
            if ($cachedResponse) {
                return [
                    'status' => 'success',
                    'content' => $cachedResponse . "\n\n*(Respuesta recuperada del Vault Soberano)*",
                    'vault_hit' => true
                ];
            }
        }

        // Usar modelo del parámetro o el configurado
        $model = $params['model'] ?? $this->config->getModel();
        $noToolsRequested = $params['no_tools'] ?? false;

        // Construir payload inicial con historia
        $rawHistory = $params['history'] ?? ($params['conversationHistory'] ?? []);
        // Normalizar historial: acepta tanto {role,content} como formato Gemini {role,parts:[{text}]}
        $history = array_map(function($msg) {
            if (isset($msg['parts']) && !isset($msg['content'])) {
                $msg['content'] = $msg['parts'][0]['text'] ?? '';
            }
            return $msg;
        }, $rawHistory);

        $noSystemRequested = $params['no_system'] ?? false;
        $tools = ($noToolsRequested || !empty($params['no_tools'])) ? null : $this->mcp->getToolsDefinition();
        $payload = $this->payloadBuilder->buildInitialPayload($prompt, $tools, $history, $noSystemRequested);

        //  Sobrescribir system_instruction si se pasa 'system' explícitamente en params
        if (!empty($params['system']) && !$noSystemRequested) {
            $payload['system_instruction'] = [
                'parts' => [['text' => trim($params['system'])]]
            ];
        }

        // Estrategia de reintentos escalonados: v1beta -> v1 -> Fallback sin extras
        try {
            $url = $this->client->buildGenerateContentUrl($model, 'v1beta');
            try {
                $result = $this->processConversation($url, $payload);
            } catch (Exception $e) {
                // Si falla por esquema o parámetros desconocidos (400), intentar sin extras
                if (strpos($e->getMessage(), '400') !== false || strpos($e->getMessage(), 'tools') !== false || strpos($e->getMessage(), 'system_instruction') !== false) {
                    unset($payload['tools']);
                    unset($payload['system_instruction']);
                    $result = $this->processConversation($url, $payload);
                } else {
                    throw $e;
                }
            }
        } catch (Exception $e) {
            // Si v1beta falla por completo (ej: no disponible), intentar v1
            try {
                $url = $this->client->buildGenerateContentUrl($model, 'v1');
                $result = $this->processConversation($url, $payload);
            } catch (Exception $e2) {
                throw new Exception("Error tras agotar reintentos técnicos: " . $e2->getMessage());
            }
        }

        //  GUARDAR EN VAULT: Asegurar el conocimiento para el futuro
        if ($lessonId && isset($result['content'])) {
            $this->saveToVault($prompt, $result['content'], $lessonId);
        }

        return $result;
    }

    /**
     *  Consultar el Vault para evitar llamadas duplicadas
     */
    private function checkVault($query, $lessonId)
    {
        $entries = $this->services['crud']->list('academy_vault');
        if (!$entries)
            return null;

        foreach ($entries as $entry) {
            if (($entry['lesson_id'] ?? '') !== $lessonId)
                continue;

            $similarity = Utils::calculateSimilarity($query, $entry['query'] ?? '');
            if ($similarity >= 0.8) { // Límite del 80% solicitado
                return $entry['response'] ?? null;
            }
        }

        return null;
    }

    /**
     *  Persistir respuesta en el Vault
     */
    private function saveToVault($query, $response, $lessonId)
    {
        if (empty($response))
            return;

        // Evitar guardar errores o respuestas negativas genéricas
        $negatives = ['lo siento', 'no puedo', 'no tengo información', 'error'];
        foreach ($negatives as $neg) {
            if (stripos($response, $neg) !== false)
                return;
        }

        $id = 'v-' . time() . '-' . rand(1000, 9999);
        $data = [
            'id' => $id,
            'lesson_id' => $lessonId,
            'query' => $query,
            'response' => $response,
            'created_at' => date('c')
        ];

        $this->services['crud']->update('academy_vault', $id, $data);
    }

    /**
     *  Procesar conversación con herramientas
     * 
     * @param string $url URL del endpoint
     * @param array $payload Payload inicial
     * @param int $depth Profundidad de recursión
     * @return array Respuesta final
     */
    private function processConversation(string $url, array $payload, int $depth = 0): array
    {
        // Control 1: Límite de recursión
        if ($depth >= $this->config->getMaxRecursionDepth()) {
            error_log("Gemini: Máxima recursión alcanzada (depth={$depth})");
            return [
                'status' => 'success',
                'content' => "Máxima recursión alcanzada. Finalizando."
            ];
        }

        // Control 2: Límite de herramientas
        if ($this->toolExecutor->hasReachedLimit()) {
            error_log("Gemini: Límite de herramientas alcanzado. Forzando respuesta final.");
            return $this->forceFinalResponse($url, $payload);
        }

        // Hacer petición a Gemini
        $response = $this->client->post($url, $payload);

        // Parsear respuesta
        $content = $this->responseParser->parse($response);

        // Verificar si hay llamadas a funciones
        if (!$this->responseParser->hasFunctionCalls($content)) {
            // No hay herramientas, retornar texto
            $text = $this->responseParser->extractText($content);
            return [
                'status' => 'success',
                'content' => $text
            ];
        }

        // Ejecutar herramientas
        $functionCalls = $this->responseParser->extractFunctionCalls($content);
        $toolResponses = [];
        $toolTraces = "";

        foreach ($functionCalls as $call) {
            $toolName = $call['name'];

            // Control 3: Detección de bucles
            if ($this->toolExecutor->isLoop($toolName)) {
                error_log("Gemini: Bucle detectado - herramienta '{$toolName}' ya ejecutada");
                $toolTraces .= " **Bucle detectado**: [{$toolName}] ya fue ejecutada.\n";
                continue;
            }

            // Ejecutar herramienta
            $toolResponse = $this->toolExecutor->execute($toolName, $call['args']);
            $toolResponses[] = $toolResponse;
            $toolTraces .= " **Acción**: [{$toolName}]\n";
        }

        // Agregar respuesta del modelo y respuestas de herramientas al payload
        $payload = $this->payloadBuilder->addModelResponse($payload, $content);
        $payload = $this->payloadBuilder->addToolResponses($payload, $toolResponses);

        // Recursión
        $result = $this->processConversation($url, $payload, $depth + 1);

        // Agregar trazas de herramientas al resultado
        return [
            'status' => 'success',
            'content' => $toolTraces . $result['content']
        ];
    }

    /**
     *  Forzar respuesta final (sin herramientas)
     * 
     * @param string $url URL del endpoint
     * @param array $payload Payload actual
     * @return array Respuesta final
     */
    private function forceFinalResponse(string $url, array $payload): array
    {
        // Eliminar herramientas del payload
        $payload = $this->payloadBuilder->removeTools($payload);

        try {
            $response = $this->client->post($url, $payload);
            $content = $this->responseParser->parse($response);
            $text = $this->responseParser->extractText($content);

            return [
                'status' => 'success',
                'content' => $text
            ];
        } catch (Exception $e) {
            error_log("Gemini: Error en respuesta final - " . $e->getMessage());
            $count = $this->toolExecutor->getExecutedCount();
            return [
                'status' => 'success',
                'content' => "He ejecutado {$count} acciones. Límite alcanzado."
            ];
        }
    }

    /**
     * Parsear lista de modelos
     * 
     * @param array $response Respuesta de la API
     * @return array Lista de modelos formateada
     */
    private function parseModelsList(array $response): array
    {
        $models = [];

        if (isset($response['models'])) {
            foreach ($response['models'] as $model) {
                if (in_array('generateContent', $model['supportedGenerationMethods'] ?? [])) {
                    $models[] = [
                        'id' => str_replace('models/', '', $model['name']),
                        'name' => $model['displayName'],
                        'provider' => 'google',
                        'description' => $model['description'] ?? ''
                    ];
                }
            }
        }

        return !empty($models) ? $models : $this->config->getFallbackModels();
    }
}
