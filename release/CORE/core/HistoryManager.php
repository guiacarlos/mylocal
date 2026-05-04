<?php

class HistoryManager
{
    private $basePath;
    private $revisionsPath;
    private $registryFile;

    public function __construct($basePath, $dataRoot = null)
    {
        $this->basePath = $basePath;
        if ($dataRoot) {
            $this->revisionsPath = $dataRoot . DIRECTORY_SEPARATOR . 'revisions';
        } else {
            $this->revisionsPath = $basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'revisions';
        }
        $this->registryFile = $this->revisionsPath . DIRECTORY_SEPARATOR . 'registry.json';

        if (!is_dir($this->revisionsPath)) {
            mkdir($this->revisionsPath, 0777, true);
        }

        if (!file_exists($this->registryFile)) {
            file_put_contents($this->registryFile, json_encode(['revisions' => []]));
        }
    }

    /**
     * Crea un Snapshot de un archivo antes de ser modificado
     */
    public function createSnapshot($filePath, $content, $summary = "Cambio automático ACIDE")
    {
        $fileName = basename($filePath);
        $timestamp = time();
        $revisionId = 'rev_' . $timestamp . '_' . substr(md5($filePath), 0, 6);
        $snapshotFile = $this->revisionsPath . DIRECTORY_SEPARATOR . $revisionId . '.bak';

        // Guardar el contenido
        file_put_contents($snapshotFile, $content);

        // Actualizar registro
        $registry = json_decode(file_get_contents($this->registryFile), true);
        array_unshift($registry['revisions'], [
            'id' => $revisionId,
            'timestamp' => $timestamp,
            'file' => $filePath,
            'name' => $fileName,
            'summary' => $summary,
            'bak_path' => $snapshotFile
        ]);

        // Limitar a los últimos 50 cambios para no llenar el búnker
        $registry['revisions'] = array_slice($registry['revisions'], 0, 50);

        file_put_contents($this->registryFile, json_encode($registry, JSON_PRETTY_PRINT));

        return $revisionId;
    }

    /**
     * Lista el historial de cambios
     */
    public function getHistory()
    {
        return json_decode(file_get_contents($this->registryFile), true)['revisions'];
    }

    /**
     * Restaura una versión específica
     */
    public function restore($revisionId)
    {
        $registry = $this->getHistory();
        foreach ($registry as $rev) {
            if ($rev['id'] === $revisionId) {
                if (file_exists($rev['bak_path'])) {
                    // Resolver ruta absoluta para evitar errores de stream
                    $cleanPath = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $rev['file']), DIRECTORY_SEPARATOR);
                    $target = $this->basePath . DIRECTORY_SEPARATOR . $cleanPath;

                    $content = file_get_contents($rev['bak_path']);
                    file_put_contents($target, $content);
                    return true;
                }
            }
        }
        return false;
    }
}
