<?php
/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL AUTH LOCK - load-bearing                                 ║
   ║ Enruta /acide/* a spa/server/index.php. Si esto falla, no hay    ║
   ║ backend y el login revienta.                                     ║
   ║ Antes de modificar, leer claude/AUTH_LOCK.md y verificar que     ║
   ║ spa/server/tests/test_login.php sigue pasando despues del cambio.║
   ╚══════════════════════════════════════════════════════════════════╝ */
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

// Multi-tenancy: detectar local activo (X-Local-Id header o subdominio)
require_once $root . '/CORE/SubdomainManager.php';
SubdomainManager::detect();

// Seed dinámico por local — devuelve contexto del local activo
if ($path === '/seed/bootstrap.json') {
    $slug = get_current_local_id();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode([
        'local_id'       => $slug,
        'plan'           => 'demo',
        'demo_days_left' => 21,
    ]);
    exit;
}

// Sitemap de la landing corporativa (mylocal.es) — referenciado desde robots.txt
if ($path === '/sitemap.xml') {
    $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
          . '://' . ($_SERVER['HTTP_HOST'] ?? 'mylocal.es');
    header('Content-Type: application/xml; charset=UTF-8');
    header('Cache-Control: public, max-age=86400');
    $today = date('Y-m-d');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ([
        ['/', '1.0', 'weekly'],
        ['/legal/aviso', '0.3', 'monthly'],
        ['/legal/privacidad', '0.3', 'monthly'],
        ['/legal/cookies', '0.3', 'monthly'],
        ['/legal/reembolsos', '0.3', 'monthly'],
    ] as [$loc, $prio, $freq]) {
        echo "  <url><loc>$host$loc</loc><lastmod>$today</lastmod><changefreq>$freq</changefreq><priority>$prio</priority></url>\n";
    }
    echo '</urlset>';
    exit;
}

// SEO: sitemap.xml y llms.txt por local (GET estático, sin sesión)
if ($path === '/carta/sitemap.xml' || $path === '/carta/llms.txt') {
    require_once $root . '/spa/server/lib.php';
    require_once $root . '/CAPABILITIES/SEO/SeoEndpoints.php';
    $localId = get_current_local_id();
    if ($path === '/carta/sitemap.xml') {
        \SEO\SeoEndpoints::sitemap($localId);
    } else {
        \SEO\SeoEndpoints::llmsTxt($localId);
    }
    exit;
}

// 1. API soberana — SPA server (spa/server/index.php)
if (strpos($path, '/acide/') === 0) {
    $spaServer = $root . '/spa/server/index.php';
    if (file_exists($spaServer)) {
        require $spaServer;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'SPA server no encontrado']);
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

