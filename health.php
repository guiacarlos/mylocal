<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store');

// Versión desde manifest.json (mismo directorio — release/) o fallback
$manifestFile = __DIR__ . '/manifest.json';
$version = '1.0.0';
if (file_exists($manifestFile)) {
    $m = json_decode(file_get_contents($manifestFile), true);
    if (!empty($m['version'])) $version = $m['version'];
}

// Check de escritura en STORAGE (requisito operativo en hosting compartido)
$storageOk = false;
$storageDir = __DIR__ . '/STORAGE';
if (is_dir($storageDir) && is_writable($storageDir)) {
    $probe = $storageDir . '/.health_probe';
    $storageOk = @file_put_contents($probe, '1') !== false;
    if ($storageOk) @unlink($probe);
}

$status = $storageOk ? 'ok' : 'degraded';
if (!$storageOk) http_response_code(503);

echo json_encode([
    'status'    => $status,
    'version'   => $version,
    'php'       => PHP_VERSION,
    'storage'   => $storageOk ? 'writable' : 'not_writable',
    'timestamp' => date('c'),
], JSON_UNESCAPED_UNICODE);
