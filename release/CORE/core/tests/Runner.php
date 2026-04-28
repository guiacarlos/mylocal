<?php

/**
 * 🧪 TestRunner - Búnker de Validación Atómica
 */
class TestRunner
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function run()
    {
        $results = [];

        // Test 1: Health Check
        $results['HealthCheck'] = $this->services['acide']->healthCheck()['status'] === 'success';

        // Test 2: Directory Discovery
        require_once dirname(__DIR__) . '/handlers/FileHandler.php';
        $history = $this->services['acide']->getServices()['history'] ?? null;
        $files = new FileHandler(dirname(__DIR__, 2), $history, $this->services);
        $res = $files->ls('.');
        $results['FileHandler_LS'] = is_array($res);

        // Test 3: AI Discovery
        require_once dirname(__DIR__) . '/handlers/AIHandler.php';
        $ai = new AIHandler($this->services);
        $models = $ai->listModels();
        $results['AIHandler_Discovery'] = is_array($models);

        return [
            'status' => 'success',
            'timestamp' => date('c'),
            'passed' => count(array_filter($results)),
            'total' => count($results),
            'details' => $results
        ];
    }
}
