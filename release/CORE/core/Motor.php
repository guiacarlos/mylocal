<?php

/**
 * ⚙️ MOTOR SOBERANO - El Brazo Ejecutor
 * Responsabilidad: Persistencia física atómica (Búnker).
 * v1.0 - Pureza Estructural ACIDE
 */
class Motor
{
    private $basePath;

    public function __construct()
    {
        // En la arquitectura independiente, el búnker gestionado es un hermano: ../../marco-cms/
        $this->basePath = realpath(__DIR__ . '/../../marco-cms/');

        if (!$this->basePath) {
            // Intento alternativo para estructuras personalizadas
            $this->basePath = realpath(dirname(ACIDE_ROOT) . '/marco-cms/');
        }

        if (!$this->basePath) {
            throw new Exception("Motor: Búnker de destino no localizado. ACIDE requiere acceso al sistema de archivos objetivo.");
        }
    }

    public function execute($action, $args = [])
    {
        switch ($action) {
            case 'ls':
            case 'list_files':
                return $this->ls($args['path'] ?? '.');
            case 'cat':
            case 'read_file':
                return $this->cat($args['file'] ?? $args['path'] ?? '');
            case 'write':
            case 'write_file':
                return $this->write($args['file'] ?? $args['path'] ?? '', $args['content'] ?? '');
            case 'mkdir':
                return $this->mkdir($args['path'] ?? '');
            default:
                throw new Exception("Motor: Acción física desconocida: $action");
        }
    }

    private function ls($path)
    {
        $target = $this->resolve($path);
        if (!is_dir($target))
            throw new Exception("Ruta inválida: $path");

        $items = scandir($target);
        $result = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..')
                continue;
            $full = $target . DIRECTORY_SEPARATOR . $item;
            $rel = ltrim(str_replace($this->basePath, '', $full), DIRECTORY_SEPARATOR);
            $result[] = [
                'name' => $item,
                'path' => str_replace(DIRECTORY_SEPARATOR, '/', $rel),
                'type' => is_dir($full) ? 'dir' : 'file',
                'size' => is_file($full) ? filesize($full) : 0
            ];
        }
        return $result;
    }

    private function cat($file)
    {
        $target = $this->resolve($file);
        if (!is_file($target))
            throw new Exception("Archivo ausente: $file");
        return [
            'content' => file_get_contents($target),
            'file' => $file,
            'size' => filesize($target)
        ];
    }

    private function write($file, $content)
    {
        $target = $this->basePath . DIRECTORY_SEPARATOR . trim($file, DIRECTORY_SEPARATOR);
        $dir = dirname($target);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);
        if (file_put_contents($target, $content) !== false) {
            return ['status' => 'success', 'message' => "Matriz persistida en $file"];
        }
        throw new Exception("Fallo de escritura física.");
    }

    private function mkdir($path)
    {
        $target = $this->basePath . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
        if (is_dir($target))
            return ['status' => 'success'];
        if (mkdir($target, 0755, true))
            return ['status' => 'success'];
        throw new Exception("Fallo al crear cámara.");
    }

    private function resolve($path)
    {
        $clean = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        if ($clean === '.')
            return $this->basePath;
        $target = realpath($this->basePath . DIRECTORY_SEPARATOR . $clean);
        if (!$target || strpos($target, $this->basePath) !== 0) {
            // Permitir resolución si el directorio padre es válido (para nuevos archivos)
            $parent = realpath(dirname($this->basePath . DIRECTORY_SEPARATOR . $clean));
            if (!$parent || strpos($parent, $this->basePath) !== 0) {
                throw new Exception("Soberanía violada: Ruta fuera del búnker.");
            }
            return $this->basePath . DIRECTORY_SEPARATOR . $clean;
        }
        return $target;
    }
}
