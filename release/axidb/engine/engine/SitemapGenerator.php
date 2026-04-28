<?php

namespace ACIDE\Core\Engine;

/**
 *  SitemapGenerator: El Cartógrafo del Sitio.
 * Responsabilidad Única: Generar el archivo sitemap.xml.
 */
class SitemapGenerator
{
    private $outputDir;
    private $dataDir;

    public function __construct(string $outputDir, string $dataDir)
    {
        $this->outputDir = $outputDir;
        $this->dataDir = $dataDir;
    }

    public function generate(string $baseUrl = 'https://example.com'): string
    {
        $urls = [];
        $urls[] = ['loc' => $baseUrl . '/', 'priority' => '1.0', 'changefreq' => 'daily'];

        //  RUTAS SOBERANAS (Reconocidas oficialmente por ACIDE)
        $urls[] = ['loc' => $baseUrl . '/login', 'priority' => '0.9', 'changefreq' => 'monthly'];
        $urls[] = ['loc' => $baseUrl . '/dashboard', 'priority' => '0.5', 'changefreq' => 'monthly'];
        $urls[] = ['loc' => $baseUrl . '/academy', 'priority' => '0.9', 'changefreq' => 'daily'];

        $pagesDir = $this->dataDir . '/pages';
        if (is_dir($pagesDir)) {
            foreach (glob($pagesDir . '/*.json') as $file) {
                $name = basename($file, '.json');
                if ($name === '_index' || $name === 'home')
                    continue;
                $urls[] = ['loc' => $baseUrl . '/' . $name, 'priority' => '0.8', 'changefreq' => 'weekly'];
            }
        }

        $xml = '<' . '?xml version="1.0" encoding="UTF-8"?' . ">\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$u['loc']}</loc>\n";
            $xml .= "    <priority>{$u['priority']}</priority>\n";
            $xml .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        file_put_contents($this->outputDir . '/sitemap.xml', $xml);
        return ' Generated: sitemap.xml';
    }
}
