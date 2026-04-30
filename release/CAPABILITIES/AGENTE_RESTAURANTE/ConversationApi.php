<?php
namespace AGENTE_RESTAURANTE;

require_once __DIR__ . '/ConversationAgent.php';

class ConversationApi
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function executeAction($action, $data = [])
    {
        $agent = new ConversationAgent($this->services);

        switch ($action) {
            case 'chat_hostelero':
                return $agent->chat($data['local_id'] ?? '', $data['pregunta'] ?? '');
            case 'get_conversation_history':
                return $agent->getHistory($data['local_id'] ?? '', intval($data['limit'] ?? 20));
            case 'clear_conversation':
                return $agent->clearHistory($data['local_id'] ?? '');
            default:
                return ['success' => false, 'error' => "Accion no soportada: $action"];
        }
    }
}
