<?php

require_once __DIR__ . '/BaseHandler.php';

/**
 * 💻 TerminalHandler - Orquestador Atómico de ACIDE v5.0
 * Unificación total de la arquitectura de despacho. 🏛️🌑📡🦾⚡
 */
class TerminalHandler extends BaseHandler
{
    private $basePath;
    private $history;
    private $handlers = [];

    public function __construct($services)
    {
        parent::__construct($services);

        // Resolución de Búnker Objetivo
        $config = $this->getConfig();
        $target = $config['target_project'] ?? './';
        $root = dirname(__DIR__, 2);
        $fullPath = $root . DIRECTORY_SEPARATOR . $target;
        $this->basePath = realpath($fullPath) ?: $root;

        // Persistencia
        $dataRoot = defined('DATA_ROOT') ? DATA_ROOT : $root . DIRECTORY_SEPARATOR . 'data';
        require_once dirname(__DIR__) . '/HistoryManager.php';
        $this->history = new HistoryManager($this->basePath, $dataRoot);

        // Carga de Especialistas (Improvement #3: UNIFICADOS)
        require_once __DIR__ . '/FileHandler.php';
        require_once __DIR__ . '/SystemHandler.php';
        require_once __DIR__ . '/GitHandler.php';
        require_once __DIR__ . '/AIHandler.php';
        require_once __DIR__ . '/AcademyHandler.php';

        $this->handlers['files'] = new FileHandler($this->basePath, $this->history, $this->services);
        $this->handlers['system'] = new SystemHandler($this->services, $this->basePath);
        $this->handlers['git'] = new GitHandler($this->basePath, $this->services);
        $this->handlers['ai'] = new AIHandler($this->services);
        $this->handlers['academy'] = new AcademyHandler($this->services);
    }

    /**
     * 🏛️ DESPACHADOR MAESTRO (Protocolo Unificado)
     */
    public function execute($command, $args = [])
    {
        try {
            // 🗺️ MAPEO TÁCTICO DE OBLIGACIONES
            $routing = [
                // Archivos
                'ls' => 'files',
                'list_files' => 'files',
                'cat' => 'files',
                'read_file' => 'files',
                'write' => 'files',
                'write_file' => 'files',
                'echo' => 'files',
                'rm' => 'files',
                'delete_file' => 'files',
                'patch' => 'files',
                'edit_file' => 'files',
                'mkdir' => 'files',
                'create_directory' => 'files',
                'search' => 'files',

                // Sistema
                'health' => 'system',
                'monitor' => 'system',
                'config' => 'system',
                'help' => 'system',
                'run_command' => 'system',

                // Inteligencia
                'ask' => 'ai',
                'as' => 'ai',
                'ai:models' => 'ai',
                'summarize' => 'ai',
                'dame' => 'ai',
                'analiza' => 'ai',
                'evalua' => 'ai',
                'Haz' => 'ai',
                'Quiero' => 'ai',
                'Ayuda' => 'ai',
                'Chat' => 'ai',

                // Academia
                'create_course' => 'academy',
                'update_course' => 'academy',
                'delete_course' => 'academy',
                'list_courses' => 'academy',
                'create_lesson' => 'academy'
            ];

            $handlerKey = $routing[$command] ?? null;

            if ($handlerKey && isset($this->handlers[$handlerKey])) {
                return $this->handlers[$handlerKey]->execute($command, $args);
            }

            // Fallback: Si no está en el mapa, intentar dispatch genérico vía ACIDE Core
            return $this->services['acide']->execute(['action' => $command] + $args);

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
