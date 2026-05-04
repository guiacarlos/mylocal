<?php
namespace LEGAL;

class LegalCapability
{
    private $pagesDir;

    public function __construct()
    {
        $this->pagesDir = __DIR__ . '/pages';
    }

    public function listPages()
    {
        $out = [];
        foreach (glob($this->pagesDir . '/*.md') as $p) {
            $slug = basename($p, '.md');
            $first = '';
            $h = @fopen($p, 'r');
            if ($h) {
                $line = fgets($h);
                fclose($h);
                if ($line && strpos($line, '# ') === 0) $first = trim(substr($line, 2));
            }
            $out[] = ['slug' => $slug, 'titulo' => $first ?: ucfirst($slug)];
        }
        return ['success' => true, 'data' => $out];
    }

    public function readPage($slug)
    {
        $slug = preg_replace('/[^a-z0-9_-]/i', '', $slug);
        $path = $this->pagesDir . '/' . $slug . '.md';
        if (!file_exists($path)) return ['success' => false, 'error' => 'Pagina no encontrada'];
        return ['success' => true, 'data' => [
            'slug' => $slug,
            'markdown' => file_get_contents($path)
        ]];
    }
}
