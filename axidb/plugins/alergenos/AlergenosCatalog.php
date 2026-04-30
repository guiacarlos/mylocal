<?php
namespace AxiDB\Plugins\Alergenos;

class AlergenosCatalog
{
    const ALERGENOS_UE = [
        'gluten', 'crustaceos', 'huevos', 'pescado', 'cacahuetes',
        'soja', 'lacteos', 'frutos_cascara', 'apio', 'mostaza',
        'sesamo', 'sulfitos', 'altramuces', 'moluscos'
    ];

    private $catalogPath;
    private $cache = null;

    public function __construct($storageRoot = null)
    {
        $root = $storageRoot ?: (defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../../STORAGE');
        $this->catalogPath = $root . '/_system/alergenos_catalog.json';
        if (!is_dir(dirname($this->catalogPath))) {
            @mkdir(dirname($this->catalogPath), 0775, true);
        }
        if (!file_exists($this->catalogPath)) {
            $this->seed();
        }
    }

    public function getCatalog()
    {
        if ($this->cache !== null) return $this->cache;
        $raw = @file_get_contents($this->catalogPath);
        $this->cache = json_decode($raw, true) ?: [];
        return $this->cache;
    }

    public function getAlergenosUE()
    {
        return self::ALERGENOS_UE;
    }

    public function lookupIngrediente($nombre)
    {
        $key = $this->normalize($nombre);
        $catalog = $this->getCatalog();
        return $catalog[$key] ?? null;
    }

    public function addIngrediente($nombre, $alergenos)
    {
        $key = $this->normalize($nombre);
        $catalog = $this->getCatalog();
        $valid = array_values(array_intersect($alergenos, self::ALERGENOS_UE));
        $catalog[$key] = $valid;
        $this->cache = $catalog;
        return @file_put_contents($this->catalogPath, json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    private function normalize($s)
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = strtr($s, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'
        ]);
        return preg_replace('/\s+/', ' ', $s);
    }

    private function seed()
    {
        $seed = [
            'harina' => ['gluten'], 'harina de trigo' => ['gluten'], 'pan' => ['gluten'],
            'pan rallado' => ['gluten'], 'pasta' => ['gluten'], 'fideos' => ['gluten'],
            'cuscus' => ['gluten'], 'cebada' => ['gluten'], 'centeno' => ['gluten'],
            'avena' => ['gluten'], 'cerveza' => ['gluten'], 'soja' => ['soja'],
            'salsa de soja' => ['soja', 'gluten'], 'tofu' => ['soja'],
            'leche' => ['lacteos'], 'mantequilla' => ['lacteos'], 'queso' => ['lacteos'],
            'nata' => ['lacteos'], 'yogur' => ['lacteos'], 'requeson' => ['lacteos'],
            'huevo' => ['huevos'], 'huevos' => ['huevos'], 'mayonesa' => ['huevos'],
            'pescado' => ['pescado'], 'salmon' => ['pescado'], 'atun' => ['pescado'],
            'merluza' => ['pescado'], 'bacalao' => ['pescado'], 'anchoas' => ['pescado'],
            'gambas' => ['crustaceos'], 'langostinos' => ['crustaceos'],
            'cigalas' => ['crustaceos'], 'bogavante' => ['crustaceos'],
            'cangrejo' => ['crustaceos'], 'centollo' => ['crustaceos'],
            'mejillon' => ['moluscos'], 'mejillones' => ['moluscos'],
            'almeja' => ['moluscos'], 'almejas' => ['moluscos'],
            'pulpo' => ['moluscos'], 'calamar' => ['moluscos'], 'sepia' => ['moluscos'],
            'cacahuete' => ['cacahuetes'], 'cacahuetes' => ['cacahuetes'],
            'almendra' => ['frutos_cascara'], 'almendras' => ['frutos_cascara'],
            'nuez' => ['frutos_cascara'], 'nueces' => ['frutos_cascara'],
            'avellana' => ['frutos_cascara'], 'avellanas' => ['frutos_cascara'],
            'pistacho' => ['frutos_cascara'], 'pistachos' => ['frutos_cascara'],
            'anacardo' => ['frutos_cascara'], 'pinones' => ['frutos_cascara'],
            'apio' => ['apio'], 'mostaza' => ['mostaza'], 'sesamo' => ['sesamo'],
            'tahini' => ['sesamo'], 'altramuz' => ['altramuces'],
            'altramuces' => ['altramuces'], 'vino' => ['sulfitos'],
            'vinagre' => ['sulfitos'], 'frutos secos' => ['frutos_cascara']
        ];
        @file_put_contents($this->catalogPath, json_encode($seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
