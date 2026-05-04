<?php
namespace RECETAS;

require_once __DIR__ . '/models/RecetaModel.php';

class RecetasCapability
{
    private $services;
    private $model;

    public function __construct($services)
    {
        $this->services = $services;
        $this->model = new \RECETAS\models\RecetaModel($services);
    }

    public function model()
    {
        return $this->model;
    }
}
