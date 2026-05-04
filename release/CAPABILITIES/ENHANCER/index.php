<?php
namespace ENHANCER;

require_once __DIR__ . '/ImageEnhancer.php';
require_once __DIR__ . '/PaletteExtractor.php';

class EnhancerCapability
{
    private $enhancer;
    private $palette;

    public function __construct()
    {
        $this->enhancer = new ImageEnhancer();
        $this->palette = new PaletteExtractor();
    }

    public function varitaMagica($srcPath, $destPath = null)
    {
        return $this->enhancer->enhance($srcPath, $destPath);
    }

    public function paletaDesdeLogo($logoPath)
    {
        return $this->palette->extract($logoPath);
    }
}
