<?php
header('Content-Type: application/json');
require_once __DIR__ . '/tunel.php';

$diag = [
    'status' => 'operational',
    'acide_root' => ACIDE_ROOT,
    'data_root' => DATA_ROOT,
    'data_exists' => is_dir(DATA_ROOT),
    'motor_basepath' => (new Motor())->execute('ls', ['path' => '.']) ? 'verified' : 'error',
    'php_server_root' => $_SERVER['DOCUMENT_ROOT'],
];

echo json_encode($diag, JSON_PRETTY_PRINT);
