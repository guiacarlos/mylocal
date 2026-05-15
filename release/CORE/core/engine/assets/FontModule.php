<?php

namespace ACIDE\Core\Engine\Assets;

require_once __DIR__ . '/AssetTools.php';

/**
 * 🖋️ FontModule: Responsable de la soberanía tipográfica.
 */
class FontModule
{
    use AssetTools;

    private $outputDir;

    public function __construct(string $outputDir)
    {
        $this->outputDir = $outputDir;
    }

    public function deployVendorFonts(string $sourceDir)
    {
        $fontDst = $this->outputDir . '/fonts';
        if (is_dir($sourceDir)) {
            if (!is_dir($fontDst))
                @mkdir($fontDst, 0755, true);
            $this->copyDir($sourceDir, $fontDst, [], $this->outputDir);
        }
    }
}
