<?php

require_once __DIR__ . '/BaseHandler.php';

class DataHandler extends BaseHandler
{
    public function __construct($services)
    {
        parent::__construct($services);
    }

    /**
     * 🏛️ DESPACHADOR UNIFICADO
     */
    public function execute($action, $args = [])
    {
        $collection = $args['collection'] ?? null;
        $id = $args['id'] ?? null;
        $data = $args['data'] ?? [];
        $params = $args['params'] ?? [];

        switch ($action) {
            case 'query':
                return $this->query($collection, $params);
            case 'read':
                return $this->read($collection, $id);
            case 'list':
                return $this->list($collection);
            case 'update':
                return $this->update($collection, $id, $data);
            case 'delete':
                return $this->delete($collection, $id);
            case 'get_collections':
                return $this->getCollections();
            default:
                throw new Exception("Acción de Datos no reconocida: $action");
        }
    }

    public function query($collection, $params)
    {
        if (!$collection) {
            error_log("[DataHandler] Warning: No collection specified in QUERY.");
            return ['items' => [], 'total' => 0];
        }
        return $this->services['queryEngine']->query($collection, $params);
    }

    public function read($collection, $id)
    {
        if (!$collection || !$id) {
            error_log("[DataHandler] Warning: Insufficient data for READ ($collection:$id)");
            return null;
        }
        return $this->services['crud']->read($collection, $id);
    }

    public function list($collection)
    {
        if (!$collection) {
            error_log("[DataHandler] Warning: No collection specified in LIST.");
            return [];
        }

        // 🏛️ Gestión Especial de Medios Soberanos
        if ($collection === 'media') {
            $mediaRoot = defined('MEDIA_ROOT') ? MEDIA_ROOT : (realpath(__DIR__ . '/../../MEDIA') ?: __DIR__ . '/../../MEDIA');
            if (!is_dir($mediaRoot))
                return [];

            $files = array_filter(glob($mediaRoot . '/*'), 'is_file');
            $results = [];
            foreach ($files as $file) {
                $basename = basename($file);
                $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                $results[] = [
                    'id' => $basename,
                    'filename' => $basename,
                    'url' => '/media/' . $basename,
                    'type' => (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) ? 'image/' . $ext : 'application/octet-stream',
                    'size' => filesize($file)
                ];
            }
            return array_reverse($results); // Mostrar los últimos primero
        }

        return $this->services['crud']->list($collection);
    }

    public function update($collection, $id, $data)
    {
        if (!$collection)
            throw new Exception("Collection is required.");
        if (!$id)
            $id = uniqid();

        $result = $this->services['crud']->update($collection, $id, $data);

        // INTELIGENCIA ACIDE: Reconstruir sitio si cambian datos críticos (Portadas, Ajustes, Partes)
        $criticalCollections = ['pages', 'theme_settings', 'parts', 'posts'];
        if (in_array($collection, $criticalCollections)) {
            try {
                if (isset($this->services['staticGenerator'])) {
                    $this->services['staticGenerator']->buildSite();
                }
            } catch (Exception $e) {
                error_log("Auto-Build failed for $collection: " . $e->getMessage());
            }
        }

        return $result;
    }

    public function delete($collection, $id)
    {
        if (!$collection || !$id) {
            error_log("[DataHandler] Warning: Insufficient data for DELETE ($collection:$id)");
            return false;
        }
        return $this->services['crud']->delete($collection, $id);
    }

    public function getCollections()
    {
        $dataRoot = DATA_ROOT;
        $directories = array_filter(glob($dataRoot . '/*'), 'is_dir');
        $collections = [];

        $systemFolders = [
            'settings',
            'system',
            'media',
            'parts',
            'plugins',
            'users',
            'configs',
            'logs',
            'cache',
            'sessions',
            'theme_settings',
            'academy_courses',
            'academy_lessons',
            'academy_progress',
            'academy_settings',
            'academy_vault',
            'ads',
            'categories',
            'tags' // Estos a veces se quieren mostrar aparte, pero si el usuario quiere "todo dinámico" quizas deba incluirlos.
            // Voy a excluir los puramente técnicos
        ];

        // Definir una blacklist más estricta de cosas que NO deberian aparecer en el menú principal dinámico
        $blacklist = ['settings', 'system', 'media', 'parts', 'plugins', 'configs', 'logs', 'cache', 'sessions', 'theme_settings'];

        foreach ($directories as $dir) {
            $name = basename($dir);
            if (!in_array($name, $blacklist) && strpos($name, '.') !== 0) {
                // Formatear nombre bonito (pages -> Páginas) podría hacerse en frontend
                $collections[] = [
                    'slug' => $name,
                    'path' => '/dashboard/' . $name,
                    'label' => ucfirst($name) // Fallback label
                ];
            }
        }
        return $collections;
    }
}
