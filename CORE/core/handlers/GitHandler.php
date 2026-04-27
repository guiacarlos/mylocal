<?php

require_once __DIR__ . '/BaseHandler.php';

/**
 * 🌿 GitHandler - Soberanía de versiones para ACIDE
 * v5.0 - UNIFICADO
 */
class GitHandler extends BaseHandler
{
    private $basePath;

    public function __construct($basePath, $services = null)
    {
        parent::__construct($services);
        $this->basePath = $basePath;
    }

    /**
     * 🏛️ DESPACHADOR UNIFICADO
     */
    public function execute($action, $args = [])
    {
        switch ($action) {
            case 'git:status':
            case 'status':
                return $this->status();
            case 'git:add':
            case 'add':
                return $this->add($args['file'] ?? '.');
            case 'git:commit':
            case 'commit':
                return $this->commit($args['message'] ?? 'Actualización vía ACIDE');
            case 'git:log':
            case 'log':
                return $this->log($args['limit'] ?? 5);
            default:
                throw new Exception("Acción Git no reconocida: $action");
        }
    }

    private function exec($command)
    {
        // En Windows necesitamos /d para cambiar de unidad si es necesario
        $fullCommand = "cd /d " . escapeshellarg($this->basePath) . " && " . $command . " 2>&1";
        $output = [];
        $res = 0;
        exec($fullCommand, $output, $res);
        return [
            'status' => $res === 0 ? 'success' : 'error',
            'output' => implode("\n", $output)
        ];
    }

    public function status()
    {
        return $this->exec("git status");
    }

    public function add($file = '.')
    {
        return $this->exec("git add " . escapeshellarg($file));
    }

    public function commit($message)
    {
        return $this->exec("git commit -m " . escapeshellarg($message));
    }

    public function log($limit = 5)
    {
        return $this->exec("git log -n " . (int) $limit . " --oneline --graph --decorate");
    }
}
