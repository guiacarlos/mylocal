<?php

namespace ACIDE\Core\Engine\Assets;

require_once __DIR__ . '/AssetTools.php';

/**
 *  StorageModule: Responsable de la persistencia de activos generados.
 */
class StorageModule
{
    use AssetTools;

    private $outputDir;
    private $rules;

    public function __construct(string $outputDir, array $rules = [])
    {
        $this->outputDir = $outputDir;
        $this->rules = $rules;
    }

    public function saveCSS(string $css)
    {
        $path = $this->outputDir . '/css/theme.css';
        $sanitized = $this->sanitizeCSS($css);
        file_put_contents($path, $sanitized);
    }

    public function savePage(string $fileName, string $html)
    {
        //  PROTECCIÓN SOBERANA: No permitir que el CMS pise el Dashboard de React
        if (strpos($fileName, 'dashboard/') === 0) {
            error_log("[StorageModule] Bloqueado intento de sobrescribir territorio del Dashboard: $fileName");
            return;
        }
        file_put_contents($this->outputDir . '/' . $fileName, $html);
    }

    public function deployDataAssets(string $dataDir)
    {
        $ignoreDirs = ['.versions', 'logs', 'sessions', 'backups'];
        $items = scandir($dataDir);

        $publicFolders = ['media', 'uploads', 'vault'];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || in_array($item, $ignoreDirs)) {
                continue;
            }

            $src = $dataDir . '/' . $item;
            $dstInternal = $this->outputDir . '/STORAGE/' . $item;

            // 1. Espejo para el Backend (PHP)
            if (is_dir($src)) {
                $this->copyDir($src, $dstInternal, [], $this->outputDir);

                // 2. Exposición para el Frontend (Web) si es carpeta pública
                if (in_array($item, $publicFolders)) {
                    $dstPublic = $this->outputDir . '/' . $item;
                    $this->copyDir($src, $dstPublic, [], $this->outputDir);
                }
            } else {
                @copy($src, $dstInternal);
            }
        }
    }
}
