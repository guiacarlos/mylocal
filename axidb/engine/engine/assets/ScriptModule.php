<?php

namespace ACIDE\Core\Engine\Assets;

require_once __DIR__ . '/AssetTools.php';

/**
 *  ScriptModule: Responsable del despliegue de motores Javascript.
 */
class ScriptModule
{
    use AssetTools;

    private $outputDir;

    public function __construct(string $outputDir)
    {
        $this->outputDir = $outputDir;
    }

    public function deployModularJS(string $sourceDir)
    {
        $modules = ['acide-mode.js', 'acide-visuals.js', 'acide-core.js'];
        $jsDst = $this->outputDir . '/js';
        if (!is_dir($jsDst))
            @mkdir($jsDst, 0755, true);

        foreach ($modules as $mod) {
            $src = $sourceDir . '/' . $mod;
            if (file_exists($src)) {
                copy($src, $jsDst . '/' . $mod);
            }
        }
    }

    public function deployVendorJS(string $sourceDir)
    {
        $vendorDst = $this->outputDir . '/js/vendor/three';
        if (is_dir($sourceDir)) {
            if (!is_dir($vendorDst))
                @mkdir($vendorDst, 0755, true);
            $this->copyDir($sourceDir, $vendorDst, [], $this->outputDir);
        }
    }
}
