<?php
/**
 * 🏛️ ACIDE SOBERANO GATEWAY v14.0
 * Orquestador de Acceso Adaptativo y SPA Routing.
 */
if (!defined('ACIDE_ROOT')) define('ACIDE_ROOT', __DIR__ . '/CORE');
if (!defined('DATA_ROOT'))  define('DATA_ROOT',  __DIR__ . '/STORAGE');

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 🛠️ DETECCIÓN SOBERANA DE CONTEXTO (Proyecto Activo) - Similar a CORE/index.php
$activeProjectFile = DATA_ROOT . '/system/active_project.json';
$projectPath = null;
if (file_exists($activeProjectFile)) {
    $activeData = json_decode(file_get_contents($activeProjectFile), true);
    if (!empty($activeData['active_project'])) {
        $candidate = realpath(__DIR__ . '/PROJECTS/' . $activeData['active_project']);
        if ($candidate && is_dir($candidate)) {
            $projectPath = $candidate;
        }
    }
}

if (!defined('STORAGE_ROOT')) {
    define('STORAGE_ROOT', $projectPath ? realpath($projectPath . '/STORAGE') : DATA_ROOT);
}

require_once ACIDE_ROOT . '/core/ACIDE.php';
$acide = new ACIDE();
$auth = $acide->getServices()['auth'];

// 1. VALIDACIÓN DE IDENTIDAD
$user = $auth->validateRequest();
$role = strtolower($user['role'] ?? '');

// 1.5 REDIRECCIÓN FORZADA: /dashboard → /sistema/tpv
// Desde 2026-04-21, el único punto operativo es el TPV. Los ajustes admin viven
// como paneles inline dentro de TPVPos (visibles sólo con authService.isAdmin()).
// Se bypasea la navegación interna del bundle React que por defecto llevaba
// a los admins a /dashboard.
if ($user && (strpos($uri, '/dashboard') === 0 || $uri === '/dashboard')) {
    header("Location: /sistema/tpv");
    exit;
}

// 1.6 ADMIN STANDALONE: /admin sirve admin.html (panel productos + media), solo admin/superadmin
if ($uri === '/admin' || strpos($uri, '/admin/') === 0 || strpos($uri, '/admin?') === 0) {
    $adminRoles = ['superadmin', 'administrador', 'admin', 'maestro', 'editor'];
    if (!$user || !in_array($role, $adminRoles)) {
        header("Location: /login");
        exit;
    }
    $adminFile = __DIR__ . '/admin.html';
    if (file_exists($adminFile)) {
        header("Cache-Control: no-cache, no-store, must-revalidate");
        readfile($adminFile);
        exit;
    }
    http_response_code(404);
    echo "admin.html no encontrado";
    exit;
}

// 2. PERMISOS DINÁMICOS POR ZONA
$allowedRoles = ['superadmin', 'administrador', 'admin', 'maestro', 'editor'];

if (strpos($uri, '/academy') !== false) {
    // Alumnos y clientes solo entran en la zona de academia
    $allowedRoles = array_merge($allowedRoles, ['estudiante', 'cliente', 'client', 'standard', 'pro', 'premium']);
}

if (strpos($uri, '/sistema') !== false || strpos($uri, '/editor') !== false) {
    // Personal de sala/cocina y camareros acceden al TPV y organizador
    $allowedRoles = array_merge($allowedRoles, ['sala', 'cocina', 'camarero']);
}

if (!$user || !in_array($role, $allowedRoles)) {
    setcookie('acide_session', '', time() - 3600, '/');
    $projectRoot = dirname($_SERVER['SCRIPT_NAME']);
    $rootPath = ($projectRoot === DIRECTORY_SEPARATOR || $projectRoot === '/') ? '' : $projectRoot;
    header("Location: " . $rootPath . "/login");
    exit;
}

// 3. ENTREGA DE LA INTERFAZ SPA (dashboard/index.html)
$dashEntryPoint = __DIR__ . '/dashboard/index.html';
if (!file_exists($dashEntryPoint)) {
    $dashEntryPoint = __DIR__ . '/dashboard/entry.html';
}

if (file_exists($dashEntryPoint)) {
    header("X-ACIDE-Identity: Sovereign-Gateway");
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    // Fix relative asset paths: the HTML uses ./assets/ but may be served at /sistema/tpv, /academy, etc.
    // Replace with absolute /dashboard/assets/ so resources resolve correctly from any URL zone.
    $html = file_get_contents($dashEntryPoint);
    $html = str_replace('./assets/', '/dashboard/assets/', $html);
    // Inyectar: enlace flotante al panel admin + media injector del modal producto
    $extraScripts = '<script src="/js/tpv-admin-link.js" defer></script>' . "\n"
                  . '<script src="/js/tpv-media-injector.js" defer></script>';
    if (strpos($html, 'tpv-media-injector.js') === false) {
        $html = str_replace('</body>', $extraScripts . "\n</body>", $html);
    }
    echo $html;
    exit;
}

echo "ACIDE: Tunel obstruido. Revisa la integridad del Dashboard.";