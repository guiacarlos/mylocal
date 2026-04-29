<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store');
echo json_encode([
    'status' => 'ok',
    'version' => '1.0.0',
    'timestamp' => date('c')
]);
