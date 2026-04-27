<?php

/**
 * ✅ ATOMIC REPO: Google Tasks
 * Responsabilidad: Gestión de listas de tareas y tareas individuales.
 * Referencia: Google Tasks API v1
 */
class Tasks
{
    private $services;
    private $endpoint = '/api/google/tasks/v1';

    public function __construct($services)
    {
        $this->services = $services;
    }

    /**
     * Listar todas las listas de tareas del usuario
     */
    public function listTaskLists()
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'GET',
            'path' => $this->endpoint . '/users/@me/lists'
        ];
    }

    /**
     * Listar tareas de una lista específica
     */
    public function listTasks($taskListId, $params = [])
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'GET',
            'path' => $this->endpoint . "/lists/$taskListId/tasks",
            'params' => [
                'showCompleted' => $params['showCompleted'] ?? true,
                'showDeleted' => false,
                'maxResults' => $params['limit'] ?? 50
            ]
        ];
    }

    /**
     * Crear una nueva tarea
     */
    public function insertTask($taskListId, $params)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => $this->endpoint . "/lists/$taskListId/tasks",
            'data' => [
                'title' => $params['title'],
                'notes' => $params['notes'] ?? '',
                'due' => $params['due'] ?? null // Formato RFC 3339
            ]
        ];
    }

    /**
     * Marcar tarea como completada o actualizar contenido
     */
    public function updateTask($taskListId, $taskId, $updates)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'PATCH',
            'path' => $this->endpoint . "/lists/$taskListId/tasks/$taskId",
            'data' => $updates
        ];
    }

    /**
     * Eliminar todas las tareas completadas de una lista
     */
    public function clearCompleted($taskListId)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => $this->endpoint . "/lists/$taskListId/clear"
        ];
    }
}
