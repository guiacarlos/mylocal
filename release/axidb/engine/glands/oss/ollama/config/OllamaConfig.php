<?php

/**
 *  OllamaConfig - Configuración Centralizada
 * 
 * Responsabilidad ÚNICA: Gestionar configuración de Ollama
 */
class OllamaConfig
{
    private $config;

    // Configuración por defecto - OPTIMIZADA PARA LOCAL
    private const DEFAULTS = [
        'temperature' => 0.7,
        'num_predict' => 4096, // Más tokens para respuestas completas
        'top_p' => 0.9,
        'top_k' => 40,
        'maxToolsPerTurn' => 30, // SIN RESTRICCIONES - Es local y gratuito
        'maxRecursionDepth' => 30, // Permitir más iteraciones
        'timeout' => 300, // 5 minutos - Sin prisa, es local
        'defaultModel' => 'llama3.2:latest',
        'baseUrl' => 'http://localhost:11434'
    ];

    public function __construct(array $systemConfig)
    {
        $this->config = array_merge(self::DEFAULTS, [
            'baseUrl' => $systemConfig['ollama_url'] ?? self::DEFAULTS['baseUrl'],
            'model' => $systemConfig['ai_model'] ?? self::DEFAULTS['defaultModel'],
            'systemPrompt' => $systemConfig['ai_system_prompt'] ?? $this->getDefaultSystemPrompt()
        ]);

        // Limpiar URL
        $this->config['baseUrl'] = rtrim($this->config['baseUrl'], '/');
    }

    public function getBaseUrl(): string
    {
        return $this->config['baseUrl'];
    }

    public function getModel(): string
    {
        return $this->config['model'];
    }

    public function getSystemPrompt(): string
    {
        return $this->config['systemPrompt'];
    }

    public function getOptions(): array
    {
        return [
            'temperature' => $this->config['temperature'],
            'num_predict' => $this->config['num_predict'],
            'top_p' => $this->config['top_p'],
            'top_k' => $this->config['top_k']
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

    private function getDefaultSystemPrompt(): string
    {
        return "IDENTIDAD: Eres el 'Arquitecto Soberano' de ACIDE.\n" .
            "EXTRACCIÓN DE CONTEXTO: Tu búnker tiene un manual maestro en '.acide/knowledge_base.md'. Léelo SIEMPRE si tienes dudas de arquitectura.\n" .
            "ENTORNO: Raíz del proyecto (marco-cms). Acceso TOTAL.\n" .
            "ESTRATEGIA: Sé directo. No pidas permiso. Si necesitas información, búscala tú mismo en el disco.";
    }
}
