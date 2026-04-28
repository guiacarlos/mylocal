<?php

require_once __DIR__ . '/BaseHandler.php';

/**
 *  FileHandler - Gestión de archivos atómica para ACIDE
 * v5.0 - UNIFICADO
 */
class FileHandler extends BaseHandler
{
    private $basePath;
    private $history;

    public function __construct($basePath, $history, $services = null)
    {
        parent::__construct($services);
        $this->basePath = $basePath;
        $this->history = $history;
    }

    /**
     *  DESPACHADOR UNIFICADO
     */
    public function execute($action, $args = [])
    {
        switch ($action) {
            case 'ls':
            case 'list':
                return $this->ls($args['path'] ?? '');
            case 'cat':
            case 'read':
                return $this->cat($args['file'] ?? $args['path'] ?? '');
            case 'write':
                return $this->write($args['file'] ?? '', $args['content'] ?? '');
            case 'rm':
            case 'delete':
                return $this->rm($args['file'] ?? '');
            case 'patch':
                return $this->patch($args['file'] ?? '', $args['search'] ?? '', $args['replace'] ?? '');
            case 'mkdir':
                return $this->mkdir($args['path'] ?? '');
            case 'search':
                return $this->search($args['query'] ?? '', $args['path'] ?? '');
            default:
                throw new Exception("Acción de Archivos no reconocida: $action");
        }
    }

    public function ls($path)
    {
        $cleanPath = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $target = realpath($this->basePath . DIRECTORY_SEPARATOR . $cleanPath);

        if (!$target || strpos($target, realpath($this->basePath)) !== 0) {
            throw new Exception("Acceso denegado o ruta inválida: " . $path);
        }

        $items = scandir($target);
        $result = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..')
                continue;
            $fullPath = $target . DIRECTORY_SEPARATOR . $item;
            $relPath = trim(str_replace(realpath($this->basePath), '', $fullPath), DIRECTORY_SEPARATOR);
            $result[] = [
                'name' => $item,
                'path' => str_replace(DIRECTORY_SEPARATOR, '/', $relPath),
                'type' => is_dir($fullPath) ? 'dir' : 'file',
                'size' => is_file($fullPath) ? filesize($fullPath) : 0,
                'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
            ];
        }
        return $result;
    }

    public function cat($file)
    {
        $target = $this->resolve($file);
        if (!is_file($target))
            throw new Exception("Archivo no encontrado: $file");
        return [
            'content' => file_get_contents($target),
            'file' => $file,
            'size' => filesize($target)
        ];
    }

    public function write($file, $content)
    {
        $target = $this->basePath . DIRECTORY_SEPARATOR . trim($file, DIRECTORY_SEPARATOR);
        if (strpos(realpath(dirname($target)) ?: '', realpath($this->basePath)) !== 0) {
            throw new Exception("Acceso denegado fuera del búnker.");
        }

        if (file_exists($target)) {
            $this->history->createSnapshot($file, file_get_contents($target), "Sincronización via FileHandler v5.0");
        }

        if (file_put_contents($target, $content) !== false) {
            return ['status' => 'success', 'message' => "Archivo '$file' persistido."];
        }
        throw new Exception("Error al escribir en disco.");
    }

    public function rm($file)
    {
        $target = $this->resolve($file);
        if (!is_file($target))
            throw new Exception("Archivo no encontrado.");
        if (unlink($target))
            return ['status' => 'success', 'message' => "Archivo eliminado."];
        throw new Exception("Permiso denegado.");
    }

    public function patch($file, $search, $replace)
    {
        $data = $this->cat($file);
        $newContent = str_replace($search, $replace, $data['content'], $count);
        if ($count === 0)
            throw new Exception("Bloque de texto no encontrado.");
        return $this->write($file, $newContent);
    }

    public function mkdir($path)
    {
        $target = $this->basePath . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
        if (is_dir($target))
            return ['status' => 'success'];
        if (mkdir($target, 0777, true))
            return ['status' => 'success'];
        throw new Exception("Fallo al crear directorio.");
    }

    public function search($query, $path = '')
    {
        $cleanPath = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $searchDir = realpath($this->basePath . DIRECTORY_SEPARATOR . $cleanPath) ?: $this->basePath;

        //  Búsqueda nativa rápida (Windows) - Parcheado contra Inyección (Fase 5)
        $cmd = "findstr /S /N /I /C:" . escapeshellarg($query) . " " . escapeshellarg($searchDir . DIRECTORY_SEPARATOR . "*.*");
        $output = shell_exec($cmd);

        if (!$output) {
            return ["status" => "info", "message" => "No se han encontrado coincidencias para: $query", "results" => []];
        }

        $lines = explode("\n", trim($output));
        $results = [];
        foreach ($lines as $line) {
            if (empty(trim($line)))
                continue;
            $results[] = $line;
            if (count($results) >= 50) {
                $results[] = "... (Truncado)";
                break;
            }
        }

        return [
            "status" => "success",
            "query" => $query,
            "results" => $results
        ];
    }

    private function resolve($file)
    {
        $clean = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
        // Intentar resolución absoluta primero si parece serlo, sino relativa al búnker
        if (file_exists($file)) {
            $abs = realpath($file);
            if ($abs && strpos($abs, realpath($this->basePath)) === 0)
                return $abs;
        }

        $target = realpath($this->basePath . DIRECTORY_SEPARATOR . $clean);
        if (!$target || strpos($target, realpath($this->basePath)) !== 0) {
            // Último intento: ver si existe sin realpath (nodos fantasmas en Windows)
            $direct = $this->basePath . DIRECTORY_SEPARATOR . $clean;
            if (file_exists($direct))
                return $direct;
            throw new Exception("Archivo no encontrado o acceso denegado: $file");
        }
        return $target;
    }
}
