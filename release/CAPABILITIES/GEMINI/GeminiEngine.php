<?php

namespace GEMINI;

/**
 * 🧠 GeminiEngine - Motor de Inteligencia Artificial Soberana.
 * Responsabilidad: Orquestar chats, modelos y procesamiento de IA.
 */
class GeminiEngine
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function executeAction($action, $args = [])
    {
        // Reutilizamos el orquestador existente pero lo aislamos en este motor
        require_once dirname(__DIR__) . '/acide/core/handlers/ai/AIOrchestrator.php';
        require_once dirname(__DIR__) . '/acide/core/handlers/ai/PromptSanitizer.php';
        require_once dirname(__DIR__) . '/acide/core/handlers/ai/ResponseProcessor.php';
        require_once dirname(__DIR__) . '/acide/core/handlers/ai/ConversationManager.php';

        $sanitizer = new \PromptSanitizer();
        $responseProcessor = new \ResponseProcessor($this->services);
        $conversationManager = new \ConversationManager($this->services['crud']);
        $orchestrator = new \AIOrchestrator(
            $this->services['glandManager'] ?? null,
            $sanitizer,
            $responseProcessor
        );

        switch ($action) {
            case 'chat':
            case 'ask':
            case 'generate':
                return $orchestrator->ask($args);
            case 'load_conversation':
                return $conversationManager->load($args['chatId'] ?? null);
            case 'save_conversation':
                return $conversationManager->save($args);
            case 'list_conversations':
                return $conversationManager->listByStudent($args['studentId'] ?? null);
            default:
                throw new \Exception("Acción Gemini no reconocida: $action");
        }
    }
}
