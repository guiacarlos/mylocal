<?php

/**
 * 🛠️ GeminiToolExecutor - Ejecutor de Herramientas MCP
 * 
 * Responsabilidad ÚNICA: Ejecutar herramientas MCP y gestionar límites
 */
class GeminiToolExecutor
{
    private $mcp;
    private $config;
    private $toolsExecuted = 0;
    private $executedTools = [];

    public function __construct($mcp, GeminiConfig $config)
    {
        $this->mcp = $mcp;
        $this->config = $config;
    }

    /**
     * Verificar si se alcanzó el límite de herramientas
     * 
     * @return bool True si se alcanzó el límite
     */
    public function hasReachedLimit(): bool
    {
        return $this->toolsExecuted >= $this->config->getMaxToolsPerTurn();
    }

    /**
     * Verificar si una herramienta ya fue ejecutada (bucle)
     * 
     * @param string $toolName Nombre de la herramienta
     * @return bool True si ya fue ejecutada
     */
    public function isLoop(string $toolName): bool
    {
        return in_array($toolName, $this->executedTools);
    }

    /**
     * Ejecutar una herramienta
     * 
     * @param string $toolName Nombre de la herramienta
     * @param array $args Argumentos
     * @return array Respuesta formateada para Gemini
     */
    public function execute(string $toolName, array $args): array
    {
        try {
            $output = $this->mcp->executeTool($toolName, $args);
            $this->toolsExecuted++;
            $this->executedTools[] = $toolName;

            return [
                'function_response' => [
                    'name' => $toolName,
                    'response' => (object) ['content' => $output]
                ]
            ];
        } catch (Exception $e) {
            error_log("Gemini Tool Error [{$toolName}]: " . $e->getMessage());

            return [
                'function_response' => [
                    'name' => $toolName,
                    'response' => (object) ['content' => "Error: " . $e->getMessage()]
                ]
            ];
        }
    }

    /**
     * Obtener contador de herramientas ejecutadas
     * 
     * @return int Número de herramientas ejecutadas
     */
    public function getExecutedCount(): int
    {
        return $this->toolsExecuted;
    }

    /**
     * Obtener lista de herramientas ejecutadas
     * 
     * @return array Lista de nombres de herramientas
     */
    public function getExecutedTools(): array
    {
        return $this->executedTools;
    }

    /**
     * Resetear contador (para nuevo turno)
     */
    public function reset(): void
    {
        $this->toolsExecuted = 0;
        $this->executedTools = [];
    }
}
