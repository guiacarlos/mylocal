<?php

/**
 *  GeminiConfig - Configuración Centralizada
 * 
 * Responsabilidad ÚNICA: Gestionar configuración de Gemini
 */
class GeminiConfig
{
    private $config;

    // Configuración por defecto
    private const DEFAULTS = [
        'temperature' => 0.7,
        'maxOutputTokens' => 8192,
        'topP' => 0.95,
        'topK' => 40,
        'maxToolsPerTurn' => 3,
        'maxRecursionDepth' => 10,
        'timeout' => 60,
        'defaultModel' => 'gemini-2.0-flash-exp'
    ];

    // Modelos de fallback si la API falla
    private const FALLBACK_MODELS = [
        ['id' => 'gemini-2.0-flash-exp', 'name' => 'Gemini 2.0 Flash (Experimental)'],
        ['id' => 'gemini-2.0-flash', 'name' => 'Gemini 2.0 Flash'],
        ['id' => 'gemini-1.5-flash', 'name' => 'Gemini 1.5 Flash'],
        ['id' => 'gemini-1.5-pro', 'name' => 'Gemini 1.5 Pro']
    ];

    public function __construct(array $systemConfig)
    {
        $this->config = array_merge(self::DEFAULTS, [
            'apiKey' => $this->cleanKey($systemConfig['google_key'] ?? ''),
            'model' => $systemConfig['ai_model'] ?? self::DEFAULTS['defaultModel'],
            'systemPrompt' => $systemConfig['ai_system_prompt'] ?? $this->getDefaultSystemPrompt()
        ]);

        // Validar que el modelo sea de Gemini
        if (strpos($this->config['model'], 'gemini') === false) {
            $this->config['model'] = self::DEFAULTS['defaultModel'];
        }
    }

    public function getApiKey(): string
    {
        return $this->config['apiKey'];
    }

    public function getModel(): string
    {
        return $this->config['model'];
    }

    public function getSystemPrompt(): string
    {
        return $this->config['systemPrompt'];
    }

    public function getGenerationConfig(): array
    {
        return [
            'temperature' => $this->config['temperature'],
            'max_output_tokens' => $this->config['maxOutputTokens'],
            'top_p' => $this->config['topP'],
            'top_k' => $this->config['topK']
        ];
    }

    public function getMaxToolsPerTurn(): int
    {
        return $this->config['maxToolsPerTurn'];
    }

    public function getMaxRecursionDepth(): int
    {
        return $this->config['maxRecursionDepth'];
    }

    public function getTimeout(): int
    {
        return $this->config['timeout'];
    }

    public function getFallbackModels(): array
    {
        return self::FALLBACK_MODELS;
    }

    private function cleanKey(?string $key): string
    {
        if (!$key)
            return '';
        return preg_replace('/\s+/', '', trim($key));
    }

    private function getDefaultSystemPrompt(): string
    {
        return "IDENTIDAD: Eres el 'Arquitecto Soberano' de ACIDE.\n" .
            "EXTRACCIÓN DE CONTEXTO: Tu búnker tiene un manual maestro en '.acide/knowledge_base.md'. Léelo SIEMPRE si tienes dudas de arquitectura.\n" .
            "ENTORNO: Raíz del proyecto (marco-cms). Acceso TOTAL.\n" .
            "ESTRATEGIA: Sé directo. No pidas permiso. Si necesitas información, búscala tú mismo en el disco.";
    }
}
