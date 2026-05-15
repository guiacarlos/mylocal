<?php
namespace WIKI;

class WikiCapability
{
    private $articlesDir;

    public function __construct()
    {
        $this->articlesDir = __DIR__ . '/articles';
    }

    public function listArticles()
    {
        $out = [];
        foreach (glob($this->articlesDir . '/*.md') as $p) {
            $meta = $this->parseFront($p);
            if ($meta) $out[] = $meta;
        }
        usort($out, function ($a, $b) {
            $oa = intval($a['orden'] ?? 999);
            $ob = intval($b['orden'] ?? 999);
            return $oa - $ob;
        });
        return ['success' => true, 'data' => $out];
    }

    public function listSections()
    {
        $r = $this->listArticles();
        if (!$r['success']) return $r;
        $sections = [];
        foreach ($r['data'] as $a) {
            $sec = $a['seccion'] ?? 'General';
            if (!isset($sections[$sec])) $sections[$sec] = [];
            $sections[$sec][] = $a;
        }
        return ['success' => true, 'data' => $sections];
    }

    public function readArticle($slug)
    {
        $slug = preg_replace('/[^a-z0-9_-]/i', '', $slug);
        foreach (glob($this->articlesDir . '/*.md') as $p) {
            $meta = $this->parseFront($p);
            if (!$meta) continue;
            if ($meta['slug'] === $slug) {
                $body = file_get_contents($p);
                $body = preg_replace('/^---.*?---\s*/s', '', $body);
                $meta['markdown'] = $body;
                return ['success' => true, 'data' => $meta];
            }
        }
        return ['success' => false, 'error' => 'Articulo no encontrado'];
    }

    private function parseFront($path)
    {
        $raw = @file_get_contents($path);
        if (!$raw) return null;
        if (!preg_match('/^---\s*(.*?)\s*---/s', $raw, $m)) return null;
        $meta = [];
        foreach (preg_split('/\r?\n/', $m[1]) as $line) {
            if (preg_match('/^\s*([a-z_]+)\s*:\s*(.+)\s*$/i', $line, $kv)) {
                $meta[strtolower($kv[1])] = trim($kv[2]);
            }
        }
        $meta['file'] = basename($path);
        return $meta['slug'] ?? null ? $meta : null;
    }
}
