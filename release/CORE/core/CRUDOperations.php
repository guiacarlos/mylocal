<?php

require_once __DIR__ . '/Utils.php';

class CRUDOperations
{
    /**
     * @var array Master Collections: Always read from DATA_ROOT (Master Storage)
     * all other collections read from STORAGE_ROOT (which might be per project)
     */
    private $masterCollections = ['users', 'roles', 'projects', 'system_logs'];

    /**
     * Security: Validates that an identifier only contains safe characters.
     * Blocks path traversal attempts like '../etc/passwd' or null bytes.
     */
    private function sanitizeIdentifier($identifier)
    {
        if (!is_string($identifier) || empty($identifier)) {
            throw new Exception("[Security] Identificador invalido o vacio.");
        }
        if (strpos($identifier, '..') !== false || strpos($identifier, "\0") !== false) {
            throw new Exception("[Security] Path traversal detectado: $identifier");
        }
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $identifier)) {
            throw new Exception("[Security] Identificador con caracteres no permitidos: $identifier");
        }
        return $identifier;
    }

    /**
     * Security: Verifies that a resolved path stays within the allowed storage roots.
     * Prevents symlink-based and realpath-based escapes.
     */
    private function validatePath($path)
    {
        $dataRoot   = defined('DATA_ROOT')    ? realpath(DATA_ROOT)    : null;
        $storageRoot = defined('STORAGE_ROOT') ? realpath(STORAGE_ROOT) : null;

        if (file_exists($path)) {
            $real = realpath($path);
            if ($dataRoot && strpos($real, $dataRoot) === 0)    return $path;
            if ($storageRoot && strpos($real, $storageRoot) === 0) return $path;
            throw new Exception("[Security] Acceso denegado: ruta fuera del area de almacenamiento.");
        }

        // Archivo aun no creado: verificar directorio padre
        $parentDir = dirname($path);
        if (is_dir($parentDir)) {
            $realParent = realpath($parentDir);
            if ($dataRoot && strpos($realParent, $dataRoot) === 0)    return $path;
            if ($storageRoot && strpos($realParent, $storageRoot) === 0) return $path;
            throw new Exception("[Security] Directorio de destino no autorizado.");
        }

        return $path;
    }

    private function getCollectionPath($collection)
    {
        $base = (defined('DATA_ROOT') && in_array($collection, $this->masterCollections)) ? DATA_ROOT : (defined('STORAGE_ROOT') ? STORAGE_ROOT : (defined('DATA_ROOT') ? DATA_ROOT : __DIR__ . '/../../STORAGE'));
        return $base . '/' . $collection;
    }

    private function getVersionsPath($collection, $id)
    {
        $base = (defined('DATA_ROOT') && in_array($collection, $this->masterCollections)) ? DATA_ROOT : (defined('STORAGE_ROOT') ? STORAGE_ROOT : (defined('DATA_ROOT') ? DATA_ROOT : __DIR__ . '/../../STORAGE'));
        return $base . '/.versions/' . $collection . '/' . $id;
    }

    private function getLogPath()
    {
        return (defined('DATA_ROOT')) ? DATA_ROOT . '/logs' : ((defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE') . '/logs');
    }

    private function rebuildIndex($collection)
    {
        $collectionPath = $this->getCollectionPath($collection);
        if (!is_dir($collectionPath))
            return;

        $files = glob($collectionPath . '/*.json');
        $indexData = [];

        foreach ($files as $file) {
            $filename = basename($file);
            if ($filename === 'index.json' || $filename === '_index.json' || strpos($filename, '_') === 0) {
                continue;
            }

            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if ($data) {
                $id = basename($file, '.json');
                if (!isset($data['id'])) {
                    $data['id'] = $id;
                }
                $indexData[] = $data;
            }
        }

        file_put_contents($collectionPath . '/_index.json', json_encode($indexData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function update($collection, $id, $data)
    {
        $collection = $this->sanitizeIdentifier($collection);
        $id         = $this->sanitizeIdentifier($id);

        $collectionPath = $this->getCollectionPath($collection);
        $versionsPath = $this->getVersionsPath($collection, $id);

        if (!is_dir($collectionPath))
            mkdir($collectionPath, 0755, true);
        if (!is_dir($versionsPath))
            mkdir($versionsPath, 0755, true);

        $filePath = $collectionPath . '/' . $id . '.json';

        $fp = fopen($filePath, 'c+');
        if (!$fp)
            throw new Exception("No se pudo abrir el archivo: $filePath");

        if (flock($fp, LOCK_EX)) {
            $content = stream_get_contents($fp);
            $existingData = json_decode($content, true);

            if ($existingData) {
                $versionFile = $versionsPath . '/' . time() . '.json';
                file_put_contents($versionFile, $content);

                $versions = glob($versionsPath . '/*.json');
                if (count($versions) > 5) {
                    unlink($versions[0]);
                }
            }

            // 🛡️ SOBERANÍA DE DATOS: Si el flag _REPLACE_ está presente, no mezclamos con lo antiguo.
            // Esto permite limpiezas atómicas y eliminaciones de claves en documentos JSON únicos.
            if (is_array($existingData) && !isset($data['_REPLACE_'])) {
                $data = array_merge($existingData, $data);
            }
            if (isset($data['_REPLACE_']))
                unset($data['_REPLACE_']);

            $data['_updatedAt'] = date('c');
            $data['_version'] = (isset($existingData['_version']) ? $existingData['_version'] : 0) + 1;
            if (!isset($data['_createdAt'])) {
                $data['_createdAt'] = date('c');
            }

            ftruncate($fp, 0);
            rewind($fp);
            if (fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);

                $this->rebuildIndex($collection);
                $this->log("UPDATE", "$collection/$id", "Version " . $data['_version']);

                return $data;
            } else {
                flock($fp, LOCK_UN);
                fclose($fp);
                throw new Exception("Error al escribir: $filePath");
            }
        } else {
            fclose($fp);
            throw new Exception("No se pudo bloquear: $filePath");
        }
    }

    private function log($action, $target, $details = "")
    {
        $logPath = $this->getLogPath();
        if (!is_dir($logPath))
            mkdir($logPath, 0755, true);
        $logFile = $logPath . '/' . date('Y-m-d') . '.log';
        $entry = "[" . date('H:i:s') . "] $action | $target | $details\n";
        file_put_contents($logFile, $entry, FILE_APPEND);
    }

    public function read($collection, $id)
    {
        $collection = $this->sanitizeIdentifier($collection);
        $id         = $this->sanitizeIdentifier($id);
        $filePath   = $this->validatePath($this->getCollectionPath($collection) . '/' . $id . '.json');

        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if ($data === null) {
            throw new Exception("Error decoding JSON from: $filePath");
        }

        return $data;
    }

    public function list($collection)
    {
        $collection = $this->sanitizeIdentifier($collection);
        $collectionPath = $this->validatePath($this->getCollectionPath($collection));

        $indexPath = $collectionPath . '/_index.json';
        if (file_exists($indexPath)) {
            $content = file_get_contents($indexPath);
            $data = json_decode($content, true);
            if (is_array($data))
                return $data;
        }

        $results = [];
        if (is_dir($collectionPath)) {
            $files = glob($collectionPath . '/*.json');
            foreach ($files as $file) {
                $filename = basename($file);
                if ($filename === '_index.json' || strpos($filename, '_') === 0)
                    continue;

                $content = file_get_contents($file);
                $data = json_decode($content, true);
                if ($data) {
                    $id = basename($file, '.json');
                    $data['id'] = $id;
                    $results[] = $data;
                }
            }
        }

        return $results;
    }

    public function delete($collection, $id)
    {
        $collection = $this->sanitizeIdentifier($collection);
        $id         = $this->sanitizeIdentifier($id);
        $filePath   = $this->validatePath($this->getCollectionPath($collection) . '/' . $id . '.json');

        if (file_exists($filePath)) {
            $result = unlink($filePath);
            if ($result) {
                $this->rebuildIndex($collection);
            }
            return $result;
        }

        return false;
    }
}
