<?php
namespace WEBSCRAPER;

require_once __DIR__ . '/RecetaScraper.php';
require_once __DIR__ . '/../RECETAS/models/RecetaModel.php';

class WebScraperCapability
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function importarReceta($url, $autoPublicar = false)
    {
        $scraper = new RecetaScraper();
        $r = $scraper->scrape($url);
        if (!$r['success']) return $r;
        $data = $r['data'];
        $data['publicado'] = $autoPublicar;
        $model = new \RECETAS\models\RecetaModel($this->services);
        return $model->create($data);
    }

    public function importarLote($urls)
    {
        $out = [];
        foreach ((array) $urls as $u) {
            $out[] = ['url' => $u, 'result' => $this->importarReceta($u, false)];
        }
        return ['success' => true, 'data' => $out];
    }
}
