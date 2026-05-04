<?php
/**
 * Punto de entrada para el worker via cron o llamada manual.
 * Uso (cron):  * * * * * php /ruta/axidb/plugins/jobs/worker_run.php
 * Uso (web):   POST /acide/index.php  action=jobs.run_batch
 */
require_once __DIR__ . '/../../../CORE/index.php';
require_once __DIR__ . '/JobQueue.php';
require_once __DIR__ . '/JobWorker.php';
require_once __DIR__ . '/../../../CAPABILITIES/OCR/OCREngine.php';
require_once __DIR__ . '/../../../CAPABILITIES/OCR/OCRParser.php';
require_once __DIR__ . '/../../../CAPABILITIES/ENHANCER/ImageEnhancer.php';
require_once __DIR__ . '/../../../CAPABILITIES/CARTA/MenuEngineer.php';

use AxiDB\Plugins\Jobs\JobQueue;
use AxiDB\Plugins\Jobs\JobWorker;

$queue = new JobQueue();
$worker = new JobWorker($queue);

$worker->register('ocr_carta', function ($payload) {
    $eng = new \OCR\OCREngine();
    $raw = $eng->extract($payload['file_path'] ?? '');
    if (!$raw['success']) return $raw;
    $parser = new \OCR\OCRParser();
    return $parser->parse($raw['text'] ?? '');
});

$worker->register('enhance_image', function ($payload) {
    $eng = new \ENHANCER\ImageEnhancer();
    return $eng->enhance($payload['file_path'] ?? '', $payload['out_path'] ?? '');
});

$worker->register('suggest_alergenos', function ($payload) {
    $eng = new \CARTA\MenuEngineer();
    return $eng->sugerirAlergenos($payload['ingredientes'] ?? [], $payload['nombre'] ?? '');
});

$worker->register('generate_descripcion', function ($payload) {
    $eng = new \CARTA\MenuEngineer();
    return $eng->generarDescripcion($payload['nombre'] ?? '', $payload['ingredientes'] ?? []);
});

$worker->register('generate_promocion', function ($payload) {
    $eng = new \CARTA\MenuEngineer();
    return $eng->generarPromocion($payload['nombre'] ?? '', $payload['descripcion'] ?? '');
});

$results = $worker->runBatch(20);
echo json_encode(['ran' => count($results), 'results' => $results], JSON_PRETTY_PRINT);
