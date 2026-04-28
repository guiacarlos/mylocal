<?php

/**
 * ⚡ ATOMIC REPO: Groq (High-Speed AI)
 * Responsabilidad: Inferencia ultra-rápida vía LPU utilizando el GroqConnector.
 */
require_once dirname(__DIR__, 3) . '/core/McpBridge.php';
require_once dirname(__DIR__, 3) . '/core/connectors/GroqConnector.php';

class Groq
{
    private $services;
    private $connector;

    public function __construct($services)
    {
        $this->services = $services;
        $config = $this->services['crud']->read('system', 'configs') ?: [];
        $apiKey = isset($config['groq_key']) ? trim($config['groq_key']) : '';
        $this->connector = new GroqConnector($apiKey);
    }

    /**
     * 📋 LISTAR MODELOS DISPONIBLES
     */
    public function listModels()
    {
        try {
            $data = $this->connector->listModels();
            $models = [];
            if (isset($data['data'])) {
                foreach ($data['data'] as $m) {
                    // Incluir TODOS los modelos disponibles
                    if (isset($m['id'])) {
                        $models[] = [
                            'id' => $m['id'],
                            'name' => "Groq: " . $m['id'],
                            'provider' => 'groq'
                        ];
                    }
                }
            }
            return $models;
        } catch (Exception $e) {
            error_log("Groq ListModels Error: " . $e->getMessage());
            return [];
        }
    }

    public function generate($params)
    {
        $config = $this->services['crud']->read('system', 'configs') ?: [];
        $model = $params['model'] ?? ($config['ai_model'] ?? 'llama-3.3-70b-versatile');

        $acideRoot = dirname(__DIR__, 3);
        $mcp = new McpBridge($acideRoot, $this->services);

        $systemPrompt = $config['ai_system_prompt'] ?? "IDENTIDAD: Eres el 'Arquitecto Soberano' de ACIDE.";
        $mcpConfig = $config['mcpServers'] ?? [];
        $mcpContext = !empty($mcpConfig) ? "\nSERVIDORES MCP ACTIVOS:\n" . json_encode($mcpConfig, JSON_PRETTY_PRINT) : "";

        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt . $mcpContext];

        // 📜 HISTORIAL (Si existe)
        $history = $params['conversationHistory'] ?? [];
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] ?? 'user',
                'content' => $msg['content'] ?? ''
            ];
        }

        // 🎯 ÚLTIMA PETICIÓN
        $messages[] = ['role' => 'user', 'content' => $params['prompt'] ?? ''];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.6,
            'tools' => $this->formatTools($mcp->getToolsDefinition())
        ];

        return $this->processAgenticTurn($payload, $mcp);
    }

    private function processAgenticTurn($payload, $mcp, $depth = 0)
    {
        if ($depth >= 15)
            return ['status' => 'success', 'content' => "Máxima recursión Groq."];

        $result = $this->connector->chatCompletion($payload);
        $message = $result['choices'][0]['message'] ?? null;

        if (!$message)
            throw new Exception("Error en respuesta de Groq.");

        $payload['messages'][] = $message;
        $hasToolCall = false;
        $toolTraces = "";

        if (isset($message['tool_calls'])) {
            $hasToolCall = true;
            foreach ($message['tool_calls'] as $call) {
                $toolName = $call['function']['name'];
                $toolArgs = json_decode($call['function']['arguments'], true) ?: [];

                $output = $mcp->executeTool($toolName, $toolArgs);

                // 🛡️ FIX GROQ v6.6: Garantizar string para OpenAI compatibility
                $toolContent = is_string($output) ? $output : json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (is_bool($output))
                    $toolContent = $output ? 'true' : 'false';

                $payload['messages'][] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'],
                    'content' => (string) $toolContent
                ];
                $toolTraces .= "🛠️ **Acción Groq:** [{$toolName}]\n";
            }
        }

        if ($hasToolCall) {
            $res = $this->processAgenticTurn($payload, $mcp, $depth + 1);
            return ['status' => 'success', 'content' => $toolTraces . $res['content']];
        }

        return ['status' => 'success', 'content' => $toolTraces . ($message['content'] ?? 'Operación finalizada.')];
    }

    private function formatTools($definitions)
    {
        $tools = [];
        foreach ($definitions as $def) {
            $tools[] = ['type' => 'function', 'function' => $def];
        }
        return $tools;
    }
}
