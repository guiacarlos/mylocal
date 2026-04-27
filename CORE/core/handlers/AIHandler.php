<?php

require_once __DIR__ . '/BaseHandler.php';
require_once __DIR__ . '/ai/ModelLister.php';
require_once __DIR__ . '/ai/PromptSanitizer.php';
require_once __DIR__ . '/ai/ConversationManager.php';
require_once __DIR__ . '/ai/ResponseProcessor.php';
require_once __DIR__ . '/ai/AIOrchestrator.php';

/**
 * AIHandler - Coordinador de componentes de IA (Arquitectura Atómica)
 * Responsabilidad: Despachar acciones a componentes especializados
 */
class AIHandler extends BaseHandler
{
    private $engine;

    public function __construct($services)
    {
        parent::__construct($services);
        $this->engine = isset($this->services['gemini']) ? $this->services['gemini'] : null;
    }

    public function execute($action, $args = array())
    {
        if (!$this->engine) {
            throw new Exception("La capacidad GEMINI no está instalada o activa (Falta carpeta /GEMINI o GeminiEngine.php).");
        }

        try {
            return $this->engine->executeAction($action, $args);
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
