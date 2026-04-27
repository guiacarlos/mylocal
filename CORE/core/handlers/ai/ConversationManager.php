<?php

/**
 * ConversationManager - Responsabilidad: Gestionar persistencia de conversaciones
 */
class ConversationManager
{
    private $crud;

    public function __construct($crud)
    {
        $this->crud = $crud;
    }

    public function load($chatId)
    {
        if (!$chatId) {
            return array('messages' => array());
        }

        $chatId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $chatId);
        $data = $this->crud->read('academy_chat_sessions', $chatId);

        return $data ? $data : array('messages' => array());
    }

    public function save($data)
    {
        $chatId = isset($data['chatId']) ? $data['chatId'] : null;

        if (!$chatId) {
            throw new Exception("ID de Chat requerido para persistencia.");
        }

        $chatId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $chatId);

        return $this->crud->update('academy_chat_sessions', $chatId, $data);
    }

    public function listByStudent($studentId)
    {
        if (!$studentId) {
            return array();
        }

        $all = $this->crud->list('academy_chat_sessions');
        $results = array();

        foreach ($all as $session) {
            if (isset($session['studentId']) && $session['studentId'] == $studentId) {
                unset($session['messages']);
                $results[] = $session;
            }
        }

        return $results;
    }
}
