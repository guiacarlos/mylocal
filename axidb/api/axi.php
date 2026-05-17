<?php
/**
 * AxiDB - Endpoint unico del modo API (HTTP gateway).
 *
 * Subsistema: api
 * Responsable: leer una peticion JSON POST, instanciar el motor y devolver
 *              la respuesta como JSON. Mantiene el contrato ACIDE legacy
 *              {action, data} -> {success, data, error} mientras el Op model
 *              de Fase 1.3 se introduce por encima.
 * Ubicacion:  axidb/api/axi.php  (__DIR__ esta 2 niveles bajo repo root)
 */

if (!defined('CORE_ROOT')) {
    define('CORE_ROOT', dirname(__DIR__));      // axidb/
}
if (!defined('ACIDE_ROOT')) {
    define('ACIDE_ROOT', CORE_ROOT);
}
if (!defined('AXI_ROOT')) {
    define('AXI_ROOT', dirname(__DIR__));       // axidb/
}

// Deteccion soberana de contexto (Proyecto Activo)
if (!defined('DATA_ROOT')) {
    define('DATA_ROOT', realpath(__DIR__ . '/../../STORAGE'));
}

$activeProjectFile = DATA_ROOT . '/system/active_project.json';
$projectPath = null;
$activeData = null;
if (file_exists($activeProjectFile)) {
    $activeData = json_decode(file_get_contents($activeProjectFile), true);
    if (!empty($activeData['active_project'])) {
        $candidate = realpath(__DIR__ . '/../../PROJECTS/' . $activeData['active_project']);
        if ($candidate && is_dir($candidate)) {
            $projectPath = $candidate;
        }
    }
}

if (!defined('STORAGE_ROOT')) {
    define('STORAGE_ROOT', $projectPath ? realpath($projectPath . '/STORAGE') : DATA_ROOT);
}

if (!defined('STORE_ROOT')) {
    define('STORE_ROOT', realpath(__DIR__ . '/../../CAPABILITIES/STORE'));
}

if (!defined('THEMES_ROOT')) {
    define('THEMES_ROOT',
        realpath(__DIR__ . '/../../THEMES') ?:          // dev: repo/THEMES/
        realpath(__DIR__ . '/../../themes') ?:          // prod: release/themes/
        realpath(__DIR__ . '/../../../themes'));
}

if (!defined('MEDIA_ROOT')) {
    define('MEDIA_ROOT', $projectPath
        ? realpath($projectPath . '/MEDIA')
        : realpath(__DIR__ . '/../../MEDIA'));
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Leer input una sola vez (php://input solo se puede leer una vez)
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: ($_POST ?: $_GET);
$actionCors = strtolower($input['action'] ?? '');

// CORS policy
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$publicActions = [
    'list_products', 'chat_restaurant', 'health_check', 'auth_login',
    'validate_coupon', 'get_payment_settings', 'get_media_formats',
    'process_external_order', 'table_request', 'get_table_order',
    'update_table_cart', 'get_table_requests', 'get_mesa_settings',
    'create_revolut_payment', 'check_revolut_payment',
    'get_carta', 'get_carta_mesa', 'get_producto',
];

if (in_array($actionCors, $publicActions, true) || $actionCors === '') {
    header("Access-Control-Allow-Origin: *");
} elseif ($origin !== '') {
    $parsed     = parse_url($origin);
    $originHost = $parsed['host'] ?? '';
    $serverHost = $_SERVER['HTTP_HOST'] ?? '';
    $isSameServer = $serverHost !== '' && $originHost === $serverHost;
    $isLocalhost  = in_array($originHost, ['localhost', '127.0.0.1', '[::1]'], true);
    if ($isSameServer || $isLocalhost) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Max-Age: 86400");
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");

try {
    // Cargar autoloader + motor AxiDB (maneja Op model, legacy {action}, y delegacion ACIDE).
    require_once __DIR__ . '/../axi.php';

    $op     = $input['op']     ?? null;
    $action = $input['action'] ?? null;

    if ($op !== null || $action !== null) {
        $db = Axi();

        // Headers de auditoria: nombre del op/action antes de ejecutar.
        $opTag = $op ?? $action;
        header("X-Axi-Op: " . $opTag);

        $response = $db->execute($input);

        // Cookie httponly tras login (Op nuevo o legacy).
        $isLogin = $op === 'auth.login' || $action === 'auth_login';
        if ($isLogin && ($response['success'] ?? false) && isset($response['data']['token'])) {
            setcookie('acide_session', $response['data']['token'], [
                'expires'  => time() + 86400,
                'path'     => '/',
                'httponly' => true,
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'samesite' => 'Strict',
            ]);
        }

        // Telemetria de duracion (el Op model la incluye; el legacy no).
        if (isset($response['duration_ms'])) {
            header("X-Axi-Duration-Ms: " . \number_format($response['duration_ms'], 2, '.', ''));
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    } elseif (isset($_GET['health_check'])) {
        echo json_encode(['status' => 'online', 'mode' => 'sovereign']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Peticion malformada: falta "op" o "action".']);
    }
} catch (Throwable $e) {
    error_log("[AxiDB] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del sistema.']);
}
exit;
