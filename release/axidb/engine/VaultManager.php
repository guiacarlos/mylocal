<?php
/**
 * AxiDB - VaultManager (legacy scaffold).
 *
 * Subsistema: engine
 * Responsable: persistencia de soluciones efectivas con versionado.
 *              En Fase 3 se sustituira por el Vault cifrado AES-256-GCM
 *              por-coleccion descrito en plan v1.
 */
class VaultManager
{
    private $basePath;
    private $vaultDir;
    private $revisionsDir;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
        $this->vaultDir = $basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'vault';
        $this->revisionsDir = $this->vaultDir . DIRECTORY_SEPARATOR . '.revisions';

        if (!is_dir($this->vaultDir))
            mkdir($this->vaultDir, 0777, true);
        if (!is_dir($this->revisionsDir))
            mkdir($this->revisionsDir, 0777, true);
    }

    /**
     * Guarda una solución con control de versiones (Improvement #1)
     */
    public function saveToVault($data)
    {
        $topic = $data['topic'] ?? 'general';
        $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($topic));
        $file = $this->vaultDir . DIRECTORY_SEPARATOR . $slug . '.json';

        //  SISTEMA DE VERSIONADO
        if (file_exists($file)) {
            $oldData = file_get_contents($file);
            $oldChunk = json_decode($oldData, true);
            $version = ($oldChunk['version'] ?? 1);

            // Mover a revisiones
            $revFile = $this->revisionsDir . DIRECTORY_SEPARATOR . $slug . '_v' . $version . '.json';
            file_put_contents($revFile, $oldData);

            $nextVersion = $version + 1;
        } else {
            $nextVersion = 1;
        }

        $id = $slug . '_v' . $nextVersion;

        //  PROTOCOLO DE CONSTRUCCIÓN SEMÁNTICA
        $markdownContent = "##  Wisdom Chunk: " . $topic . " (v{$nextVersion})\n";
        $markdownContent .= "> ID: `{$id}` | Date: " . date('Y-m-d H:i:s') . "\n";
        $markdownContent .= "> Author: " . ($data['author'] ?? 'Arquitecto Soberano') . "\n";
        if (isset($data['description'])) {
            $markdownContent .= "> Description: " . $data['description'] . "\n";
        }
        $markdownContent .= "\n---\n";

        $isCode = (strpos($data['content'], '<?php') !== false || strpos($data['content'], '{') !== false);
        if ($isCode) {
            $markdownContent .= "###  Solución Técnica\n```php\n" . $data['content'] . "\n```\n";
        } else {
            $markdownContent .= "###  Explicación Semántica\n" . $data['content'] . "\n";
        }

        $chunk = [
            'id' => $id,
            'slug' => $slug,
            'version' => $nextVersion,
            'timestamp' => time(),
            'author' => $data['author'] ?? 'Arquitecto Soberano',
            'description' => $data['description'] ?? null,
            'metadata' => [
                'topic' => $topic,
                'tags' => $data['tags'] ?? ['atómico'],
                'file' => $data['file'] ?? null
            ],
            'content' => $markdownContent
        ];

        file_put_contents($file, json_encode($chunk, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $id;
    }

    public function listVault()
    {
        $files = array_filter(glob($this->vaultDir . DIRECTORY_SEPARATOR . '*.json'), 'is_file');
        return array_map(function ($f) {
            return json_decode(file_get_contents($f), true);
        }, $files);
    }

    public function getHistory($slug)
    {
        $revs = glob($this->revisionsDir . DIRECTORY_SEPARATOR . $slug . '_v*.json');
        return array_map(function ($f) {
            return json_decode(file_get_contents($f), true);
        }, $revs);
    }

    public function delete($slug)
    {
        $file = $this->vaultDir . DIRECTORY_SEPARATOR . $slug . '.json';
        if (file_exists($file))
            return unlink($file);
        return false;
    }
}
