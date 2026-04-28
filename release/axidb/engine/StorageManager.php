<?php
/**
 * AxiDB - Gestor de almacenamiento basal sobre JSON.
 *
 * Subsistema: engine
 * Responsable: lectura/escritura de documentos y listado por coleccion.
 *              Respeta la separacion master collections / per-project.
 * Notas:      en Fase 1.4 se introducira StorageDriver como interface
 *              y este manager pasara a ser una impl concreta.
 */

namespace Axi\Engine;

use Exception;

class StorageManager
{
    private $masterCollections = ['users', 'roles', 'projects', 'system_logs', 'vault', 'system'];
    private $dataRoot;
    private $storageRoot;

    public function __construct($dataRoot = null, $storageRoot = null)
    {
        $this->dataRoot = $dataRoot ?: (defined('DATA_ROOT') ? DATA_ROOT : __DIR__ . '/../../storage');
        $this->storageRoot = $storageRoot ?: (defined('STORAGE_ROOT') ? STORAGE_ROOT : $this->dataRoot);
        
        if (!is_dir($this->dataRoot)) mkdir($this->dataRoot, 0755, true);
        if (!is_dir($this->storageRoot)) mkdir($this->storageRoot, 0755, true);

        // Fase 4: Refuerzo de Seguridad Estática
        $this->enforceStaticProtection();
    }

    /**
     * Genera automáticamente archivos de configuración para denegar el acceso HTTP
     * directo a los archivos JSON en Apache e IIS.
     */
    private function enforceStaticProtection()
    {
        $roots = array_unique([$this->dataRoot, $this->storageRoot]);
        foreach ($roots as $root) {
            $absRoot = realpath($root);
            if (!$absRoot) continue;

            // 1. Apache (.htaccess)
            $htaccessPath = $absRoot . '/.htaccess';
            if (!file_exists($htaccessPath)) {
                $content = "# AxiDB: Denegar acceso directo a la base de datos\n";
                $content .= "Require all denied\n";
                $content .= "<Files ~ \"\.(json|lock|bak)$\">\n    Order allow,deny\n    Deny from all\n</Files>\n";
                file_put_contents($htaccessPath, $content);
            }

            // 2. IIS (web.config)
            $webConfigPath = $absRoot . '/web.config';
            if (!file_exists($webConfigPath)) {
                $content = '<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <security>
            <requestFiltering>
                <fileExtensions>
                    <add fileExtension=".json" allowed="false" />
                </fileExtensions>
            </requestFiltering>
        </security>
    </system.webServer>
</configuration>';
                file_put_contents($webConfigPath, $content);
            }

            // 3. Archivo de aviso para Nginx
            $nginxPath = $absRoot . '/nginx_protection.conf';
            if (!file_exists($nginxPath)) {
                $content = "# AxiDB: Configuración para Nginx\n";
                $content .= "# Copie esto en su bloque 'server':\n\n";
                $content .= "location ~ /STORAGE/.*\.json$ {\n    deny all;\n    return 403;\n}\n";
                file_put_contents($nginxPath, $content);
            }
        }
    }

    /**
     * Valida que un identificador sea alfanumérico y seguro.
     */
    private function sanitizeIdentifier($identifier)
    {
        if (!is_string($identifier) || empty($identifier)) {
            throw new Exception("Seguridad: Identificador inválido.");
        }
        if (preg_match('/[^a-zA-Z0-9_\-\.]/', $identifier) || strpos($identifier, '..') !== false) {
            throw new Exception("Seguridad: Identificador malicioso detectado: $identifier");
        }
        return $identifier;
    }

    /**
     * Verifica que una ruta esté dentro de los límites permitidos (Jailing).
     */
    private function validatePath($path)
    {
        $absDataRoot = realpath($this->dataRoot);
        $absStorageRoot = realpath($this->storageRoot);

        if (file_exists($path)) {
            $realPath = realpath($path);
            if (strpos($realPath, $absDataRoot) !== 0 && strpos($realPath, $absStorageRoot) !== 0) {
                throw new Exception("Seguridad: Intento de acceso fuera del área de almacenamiento.");
            }
        } else {
            $parentDir = dirname($path);
            if (is_dir($parentDir)) {
                $realParent = realpath($parentDir);
                if (strpos($realParent, $absDataRoot) !== 0 && strpos($realParent, $absStorageRoot) !== 0) {
                    throw new Exception("Seguridad: Directorio de destino no autorizado.");
                }
            }
        }
        return $path;
    }

    private function getCollectionPath($collection)
    {
        $base = in_array($collection, $this->masterCollections) ? $this->dataRoot : $this->storageRoot;
        return $base . '/' . $collection;
    }

    private function getVersionsPath($collection, $id)
    {
        $base = in_array($collection, $this->masterCollections) ? $this->dataRoot : $this->storageRoot;
        return $base . '/.versions/' . $collection . '/' . $id;
    }

    public function update($collection, $id, $data)
    {
        $collection = $this->sanitizeIdentifier($collection);
        $id = $this->sanitizeIdentifier($id);

        $collectionPath = $this->getCollectionPath($collection);
        $versionsPath = $this->getVersionsPath($collection, $id);

        if (!is_dir($collectionPath)) mkdir($collectionPath, 0755, true);
        if (!is_dir($versionsPath)) mkdir($versionsPath, 0755, true);

        $filePath = $this->validatePath($collectionPath . '/' . $id . '.json');

        $fp = fopen($filePath, 'c+');
        if (!$fp) throw new Exception("No se pudo abrir el archivo: $filePath");

        if (flock($fp, LOCK_EX)) {
            $content = stream_get_contents($fp);
            $existingData = json_decode($content, true);

            if ($existingData) {
                $versionFile = $versionsPath . '/' . time() . '.json';
                $this->validatePath($versionFile);
                file_put_contents($versionFile, $content);
                
                $versions = glob($versionsPath . '/*.json');
                if (count($versions) > 5) {
                    $toDelete = $this->validatePath($versions[0]);
                    unlink($toDelete);
                }
            }

            if (is_array($existingData) && !isset($data['_REPLACE_'])) {
                $data = array_merge($existingData, $data);
            }
            if (isset($data['_REPLACE_'])) unset($data['_REPLACE_']);

            $data['_updatedAt'] = date('c');
            $data['_version'] = (isset($existingData['_version']) ? $existingData['_version'] : 0) + 1;
            if (!isset($data['_createdAt'])) $data['_createdAt'] = date('c');

            ftruncate($fp, 0);
            rewind($fp);
            if (fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
                $this->rebuildIndex($collection);
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

    public function read($collection, $id)
    {
        $collection = $this->sanitizeIdentifier($collection);
        $id = $this->sanitizeIdentifier($id);

        $filePath = $this->validatePath($this->getCollectionPath($collection) . '/' . $id . '.json');
        if (!file_exists($filePath)) return null;
        return json_decode(file_get_contents($filePath), true);
    }

    public function list($collection)
    {
        $collection = $this->sanitizeIdentifier($collection);
        $collectionPath = $this->getCollectionPath($collection);
        $indexPath = $this->validatePath($collectionPath . '/_index.json');
        
        if (file_exists($indexPath)) {
            $data = json_decode(file_get_contents($indexPath), true);
            if (is_array($data)) return $data;
        }

        $results = [];
        if (is_dir($collectionPath)) {
            $files = glob($collectionPath . '/*.json');
            foreach ($files as $file) {
                $file = $this->validatePath($file);
                if (basename($file) === '_index.json' || strpos(basename($file), '_') === 0) continue;
                $data = json_decode(file_get_contents($file), true);
                if ($data) {
                    $data['id'] = basename($file, '.json');
                    $results[] = $data;
                }
            }
        }
        return $results;
    }

    public function delete($collection, $id)
    {
        $collection = $this->sanitizeIdentifier($collection);
        $id = $this->sanitizeIdentifier($id);

        $filePath = $this->validatePath($this->getCollectionPath($collection) . '/' . $id . '.json');
        if (file_exists($filePath)) {
            $result = unlink($filePath);
            $this->rebuildIndex($collection);
            return $result;
        }
        return false;
    }

    public function rebuildIndex($collection)
    {
        $collection = $this->sanitizeIdentifier($collection);
        $collectionPath = $this->getCollectionPath($collection);
        $items = [];
        if (is_dir($collectionPath)) {
            $files = glob($collectionPath . '/*.json');
            foreach ($files as $file) {
                $file = $this->validatePath($file);
                $base = basename($file);
                if ($base === '_index.json' || $base[0] === '_') {
                    continue;
                }
                $data = json_decode(file_get_contents($file), true);
                if ($data) {
                    $data['id'] = basename($file, '.json');
                    $items[] = $data;
                }
            }
        }
        $indexPath = $this->validatePath($collectionPath . '/_index.json');
        file_put_contents(
            $indexPath,
            json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}

// Alias retrocompat (Fase 5): el motor legacy ACIDE y handlers internos
// referencian 'StorageManager' sin namespace. Lo exponemos en el root para
// que `new StorageManager()` desde codigo legacy resuelva al de Axi\Engine.
\class_alias(\Axi\Engine\StorageManager::class, 'StorageManager');

