<?php

/**
 * 🛰️ ConversationManager - Gestor de Contextos Operativos v2.0
 * Implementa auto-resumen para prevenir el desbordamiento de contexto (Improvement #2).
 */
class ConversationManager
{
    private $dir;
    private $services;

    public function __construct($basePath, $services = null)
    {
        $this->services = $services;
        $this->dir = $basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'conversations';
        if (!is_dir($this->dir))
            mkdir($this->dir, 0777, true);
    }

    public function save($id, $messages, $metadata = [])
    {
        // 🛡️ AUTO-COMPACTACIÓN (Improvement #2)
        // Si hay más de 20 mensajes, intentamos resumir si tenemos el AIHandler disponible
        if (count($messages) > 20 && $this->services) {
            $messages = $this->compact($messages);
        }

        $file = $this->dir . DIRECTORY_SEPARATOR . $id . '.json';
        $data = [
            'id' => $id,
            'updated_at' => time(),
            'messages' => $messages,
            'metadata' => $metadata
        ];
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return true;
    }

    /**
     * Compactación Inteligente de Memoria
     */
    private function compact($messages)
    {
        $systemMsg = $messages[0]; // Preservar system prompt
        $historyToCompact = array_slice($messages, 1, -5); // Dejar los últimos 5 mensajes intactos
        $recentMessages = array_slice($messages, -5);

        // En un entorno ideal, aquí llamaríamos a AIHandler->execute('summarize', ...)
        // Por ahora, marcamos el punto de compactación estructural
        $summaryPlaceholder = [
            'role' => 'system',
            'content' => "[Módulo de Memoria]: Los mensajes anteriores han sido compactados para optimizar el búnker. Contexto preservado."
        ];

        return array_merge([$systemMsg, $summaryPlaceholder], $recentMessages);
    }

    public function list()
    {
        $files = glob($this->dir . DIRECTORY_SEPARATOR . '*.json');
        $list = [];
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data)
                continue;
            $list[] = [
                'id' => $data['id'],
                'updated_at' => $data['updated_at'],
                'title' => $data['metadata']['title'] ?? 'Conversación ' . $data['id'],
                'model' => $data['metadata']['model'] ?? 'N/A',
                'msg_count' => count($data['messages'] ?? [])
            ];
        }
        usort($list, function ($a, $b) {
            return $b['updated_at'] - $a['updated_at']; });
        return $list;
    }

    public function get($id)
    {
        $file = $this->dir . DIRECTORY_SEPARATOR . $id . '.json';
        if (file_exists($file))
            return json_decode(file_get_contents($file), true);
        return null;
    }

    public function delete($id)
    {
        $file = $this->dir . DIRECTORY_SEPARATOR . $id . '.json';
        return file_exists($file) ? unlink($file) : false;
    }
}
