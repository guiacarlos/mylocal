<?php
if (!defined('ACIDE_ROOT')) define('ACIDE_ROOT', __DIR__ . '/CORE');
if (!defined('DATA_ROOT'))  define('DATA_ROOT',  __DIR__ . '/STORAGE');
if (!defined('STORAGE_ROOT')) define('STORAGE_ROOT', DATA_ROOT);

require_once ACIDE_ROOT . '/auth/Auth.php';

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$auth = new Auth();
$user = $auth->validateRequest();
$role = strtolower($user['role'] ?? '');

// /dashboard redirige al TPV
if (strpos($uri, '/dashboard') === 0 || $uri === '/dashboard') {
    header('Location: /sistema/tpv');
    exit;
}

// /admin: panel productos + media, solo roles de gestion
if ($uri === '/admin' || strpos($uri, '/admin/') === 0 || strpos($uri, '/admin?') === 0) {
    $adminRoles = ['superadmin', 'administrador', 'admin', 'maestro', 'editor'];
    if (!$user || !in_array($role, $adminRoles)) {
        header('Location: /login');
        exit;
    }
    $adminFile = __DIR__ . '/admin.html';
    if (file_exists($adminFile)) {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        readfile($adminFile);
        exit;
    }
    http_response_code(404);
    echo 'admin.html no encontrado';
    exit;
}

// Roles autorizados por zona
$allowedRoles = ['superadmin', 'administrador', 'admin', 'maestro', 'editor'];

if (strpos($uri, '/sistema') !== false || strpos($uri, '/editor') !== false) {
    $allowedRoles = array_merge($allowedRoles, ['sala', 'cocina', 'camarero']);
}

if (!$user || !in_array($role, $allowedRoles)) {
    setcookie('acide_session', '', time() - 3600, '/');
    header('Location: /login');
    exit;
}

// Entregar SPA dashboard
$dashEntry = __DIR__ . '/dashboard/index.html';
if (!file_exists($dashEntry)) {
    $dashEntry = __DIR__ . '/dashboard/entry.html';
}

if (!file_exists($dashEntry)) {
    http_response_code(503);
    echo 'Dashboard no disponible';
    exit;
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$html = file_get_contents($dashEntry);
$html = str_replace('./assets/', '/dashboard/assets/', $html);

$extraScripts = '<script src="/js/tpv-admin-link.js" defer></script>' . "\n"
              . '<script src="/js/tpv-media-injector.js" defer></script>';
if (strpos($html, 'tpv-media-injector.js') === false) {
    $html = str_replace('</body>', $extraScripts . "\n</body>", $html);
}

echo $html;
