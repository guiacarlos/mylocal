<?php

namespace ACIDE\Core\Engine\Assets;

require_once __DIR__ . '/AssetTools.php';

/**
 * 🧹 CleanerModule: Responsable de la purificación del entorno de release.
 */
class CleanerModule
{
    use AssetTools;

    private $outputDir;

    public function __construct(string $outputDir)
    {
        $this->outputDir = $outputDir;
    }

    public function clearRelease()
    {
        // 🛡️ PROTECCIÓN SOBERANA
        if (empty($this->outputDir) || $this->outputDir === '/' || (strpos($this->outputDir, 'headless') !== false && strlen($this->outputDir) < 30)) {
            error_log("[CRITICAL] CleanerModule: Bloqueado intento de borrar directorio raíz: " . $this->outputDir);
            return;
        }

        if (is_dir($this->outputDir)) {
            $keep = [$this->outputDir . '/acide/data/sessions'];
            $this->deleteDir($this->outputDir, $keep);
        }

        if (!is_dir($this->outputDir))
            @mkdir($this->outputDir, 0755, true);
        @mkdir($this->outputDir . '/css', 0755, true);
        @mkdir($this->outputDir . '/js', 0755, true);
        @mkdir($this->outputDir . '/fonts', 0755, true);
    }
}
