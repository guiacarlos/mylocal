<?php
/**
 * 🏛️ ACIDE SOBERANO - API GATEWAY v9.9
 * Arquitectura Headless Unificada con ACIDE Core.
 */
if (!defined('CORE_ROOT'))
    define('CORE_ROOT', __DIR__);
if (!defined('ACIDE_ROOT'))
    define('ACIDE_ROOT', CORE_ROOT);

// 🛠️ DETECCIÓN SOBERANA DE CONTEXTO (Proyecto Activo)
if (!defined('DATA_ROOT'))
    define('DATA_ROOT', realpath(__DIR__ . '/../STORAGE'));

$activeProjectFile = DATA_ROOT . '/system/active_project.json';
$projectPath = null;
if (file_exists($activeProjectFile)) {
    $activeData = json_decode(file_get_contents($activeProjectFile), true);
    if (!empty($activeData['active_project'])) {
        $candidate = realpath(__DIR__ . '/../PROJECTS/' . $activeData['active_project']);
        if ($candidate && is_dir($candidate)) {
            $projectPath = $candidate;
        }
    }
}

if (!defined('STORAGE_ROOT'))
    define('STORAGE_ROOT', $projectPath ? realpath($projectPath . '/STORAGE') : DATA_ROOT);

if (!defined('STORE_ROOT'))
    define('STORE_ROOT', realpath(__DIR__ . '/../CAPABILITIES/STORE'));

if (!defined('THEMES_ROOT'))
    define('THEMES_ROOT',
        realpath(__DIR__ . '/../THEMES') ?:        // dev: /socola/THEMES/
        realpath(__DIR__ . '/../themes') ?:        // prod: release/themes/
        realpath(__DIR__ . '/../../themes'));

if (!defined('MEDIA_ROOT'))
    define('MEDIA_ROOT', $projectPath ? realpath($projectPath . '/MEDIA') : realpath(__DIR__ . '/../MEDIA'));

error_reporting(E_ALL);
ini_set('display_errors', 0);

// 🔑 Leer input UNA SOLA VEZ (php://input solo se puede leer una vez)
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: ($_POST ?: $_GET);
$actionCors = strtolower($input['action'] ?? '');

// 🛡️ Soberano Shield: CORS Soberano
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$publicActions = ['list_products', 'chat_restaurant', 'health_check', 'auth_login', 'validate_coupon', 'get_payment_settings', 'get_media_formats', 'process_external_order', 'table_request', 'get_table_order', 'update_table_cart', 'get_table_requests', 'get_mesa_settings', 'create_revolut_payment', 'check_revolut_payment'];

if (in_array($actionCors, $publicActions) || empty($actionCors)) {
    // APIs públicas y preflight: cualquier origen puede acceder (la carta es pública)
    header("Access-Control-Allow-Origin: *");
} elseif ($origin) {
    $serverHost = $_SERVER['HTTP_HOST'] ?? '';
    $isSameServer = !empty($serverHost) && strpos($origin, $serverHost) !== false;
    $isLocalhost = strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false;
    if ($isSameServer || $isLocalhost) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Max-Age: 86400");
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-ACIDE-Storage-Root: " . (defined('STORAGE_ROOT') ? STORAGE_ROOT : 'UNDEFINED'));
header("X-ACIDE-Release-Root: " . (defined('RELEASE_ROOT') ? RELEASE_ROOT : 'UNDEFINED'));
header("X-ACIDE-Project: " . ($activeData['active_project'] ?? 'NONE'));

try {
    if (!file_exists(__DIR__ . '/core/ACIDE.php')) {
        throw new Exception("Error Crítico: El motor ACIDE no está presente.");
    }
    require_once __DIR__ . '/core/ACIDE.php';

    $action = $input['action'] ?? null;

    if ($action) {
        $acide = new ACIDE();
        $response = $acide->execute($input);

        if ($action === 'auth_login' && ($response['success'] ?? false) && isset($response['data']['token'])) {
            setcookie('acide_session', $response['data']['token'], [
                'expires' => time() + 86400,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            // Token also sent in JSON so the SPA can store it in localStorage for Authorization: Bearer calls
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    } else if (isset($_GET['health_check'])) {
        echo json_encode(['status' => 'online', 'mode' => 'sovereign']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Petición malformada.']);
    }
} catch (Throwable $e) {
    error_log("[ACIDE SHIELD] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del sistema.']);
}
exit;
