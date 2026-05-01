<?php
/**
 * router.php — MyLocal dev server
 * Usado por: php -S localhost:8080 -t release release/router.php
 */

// 1. Debug logging para identificar al culpable del bucle
$uri  = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$agent = $_SERVER['HTTP_USER_AGENT'] ?? 'no-agent';
error_log("REQ: $method $uri (Agent: $agent)");

$path = parse_url($uri, PHP_URL_PATH);
$root = __DIR__;

// 1. API soberana
if (strpos($path, '/acide/') === 0) {
    $script = $root . '/CORE/' . substr($path, strlen('/acide/'));
    if (file_exists($script)) {
        require $script;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'endpoint no encontrado']);
    }
    exit;
}

// 2. Archivos fisicos existentes (JS, CSS, imagenes, fonts, etc.)
$file = $root . $path;
if ($path !== '/' && file_exists($file) && is_file($file)) {
    // MIME types para el servidor PHP built-in
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = [
        'js'    => 'application/javascript',
        'mjs'   => 'application/javascript',
        'css'   => 'text/css',
        'html'  => 'text/html',
        'json'  => 'application/json',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'webp'  => 'image/webp',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'map'   => 'application/json',
    ][$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    readfile($file);
    exit;
}

// 3. Todo lo demas → index.html (SPA routing via React Router)
http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
readfile($root . '/index.html');

