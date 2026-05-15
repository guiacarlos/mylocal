<?php
/**
 * 🌉 ACIDE SOBERANO - TÚNEL SILENCIOSO v9.0
 * El nexo indisoluble conforme a las Reglas de Oro. 🏛️🌑⚡
 */

// 🛡️ REGLA: Silencio es Oro
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS')
    exit;

if (!defined('ACIDE_ROOT'))
    define('ACIDE_ROOT', __DIR__);
if (!defined('STORAGE_ROOT'))
    define('STORAGE_ROOT', realpath(__DIR__ . '/../STORAGE'));
if (!defined('DATA_ROOT'))
    define('DATA_ROOT', STORAGE_ROOT);

// 🧠 CARGA DEL CEREBRO
require_once ACIDE_ROOT . '/core/Cerebro.php';

try {
    // 📡 CAPTURA DE SEÑAL
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true) ?? $_REQUEST;

    $action = $input['action'] ?? $input['command'] ?? null;
    $args = $input['args'] ?? [];

    if (!$action) {
        echo json_encode([
            'status' => 'silent',
            'identity' => 'ACIDE_BUNKER',
            'version' => '9.5'
        ]);
        exit;
    }

    // 🏛️ EJECUCIÓN SOBERANA VÍA CEREBRO
    $cerebro = new Cerebro();
    $result = $cerebro->dispatch($action, $args);

    // Normalización de respuesta conforme al sistema
    if (isset($result['status']) && $result['status'] === 'error') {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    // 🛡️ REGLA: Protección del Búnker
    http_response_code(200); // Evitamos el 500 para control local
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'type' => 'BUNKER_EXCEPTION'
    ], JSON_UNESCAPED_UNICODE);
}
