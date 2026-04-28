<?php
/**
 * Synaxis server — dispatcher adelgazado + capa de seguridad.
 *
 * Responsabilidades (en orden):
 *   1. Cabeceras de seguridad (complementan las de .htaccess).
 *   2. CORS con whitelist.
 *   3. Emisión de token CSRF (cookie + endpoint).
 *   4. Validación de `action` (debe estar en ALLOWED_ACTIONS).
 *   5. Para acciones no-públicas: validar Bearer/Cookie → current_user().
 *   6. Para acciones state-changing: validar CSRF (double-submit).
 *   7. Delegar al handler.
 *
 * Regla dura: acciones fuera de ALLOWED_ACTIONS → 400 "resolver en cliente".
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store');
header_remove('X-Powered-By');

/* ══════════════════════════════ CORS ══════════════════════════════ */

$corsCfg = @load_config_optional('cors') ?? [
    'allowed_origins' => [],
    'allow_credentials' => true,
    'public_actions' => [],
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = (array) ($corsCfg['allowed_origins'] ?? []);
$isAllowedOrigin = $origin !== '' && in_array($origin, $allowedOrigins, true);

if ($isAllowedOrigin) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
    if ($corsCfg['allow_credentials'] ?? true) {
        header('Access-Control-Allow-Credentials: true');
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    if (!$isAllowedOrigin && $origin !== '') {
        http_response_code(403);
        exit;
    }
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
    header('Access-Control-Max-Age: 86400');
    exit;
}

/* ══════════════════════════ Parse input ══════════════════════════ */

$raw = file_get_contents('php://input') ?: '';
if (strlen($raw) > 2 * 1024 * 1024) {
    http_response_code(413);
    resp(false, null, 'Payload demasiado grande');
}
$req = json_decode($raw, true) ?: [];
$action = is_string($req['action'] ?? null) ? $req['action'] : null;

if (!$action && !empty($_FILES)) $action = 'upload';

/* ═════════════════════ Acciones permitidas ═════════════════════ */

const ALLOWED_ACTIONS = [
    // Auth / sesión
    'auth_login', 'auth_refresh_session', 'get_current_user', 'public_register', 'auth_logout',
    'csrf_token',
    // Pagos
    'create_payment_intent', 'check_revolut_payment', 'create_revolut_payment', 'revolut_webhook',
    // Reservas
    'create_reserva',
    // QR / mesas
    'process_external_order', 'get_table_order', 'update_table_cart',
    'clear_table', 'table_request', 'get_table_requests', 'acknowledge_request',
    // IA
    'chat', 'chat_restaurant', 'ask', 'list_models',
    // Sistema
    'upload', 'synaxis_sync', 'health_check',
    // Público (lectura mínima que el cliente puede cachear)
    'validate_coupon', 'get_payment_settings', 'get_mesa_settings', 'list_products',
];

if (!$action) resp(false, null, 'action requerida');
if (!in_array($action, ALLOWED_ACTIONS, true)) {
    http_response_code(400);
    resp(false, null, "Acción '$action' debe resolverse en el cliente (SynaxisCore).");
}

/* ═════════════════════ CSRF: emisión ═════════════════════ */

if ($action === 'csrf_token') {
    resp(true, ['token' => issue_csrf_token()]);
}
if ($action === 'health_check') {
    resp(true, ['status' => 'healthy', 'mode' => 'synaxis-thin', 'ts' => date('c')]);
}

/* ═════════════════════ Autenticación ═════════════════════ */

$publicActions = array_flip((array) ($corsCfg['public_actions'] ?? []));
$isPublic = isset($publicActions[$action]);

$user = current_user();
if (!$isPublic && !$user) {
    http_response_code(401);
    resp(false, null, "Unauthorized: acción '$action' requiere sesión.");
}

/* ═════════════════════ CSRF: validación ═════════════════════ */

// Protegemos toda acción state-changing salvo:
//   - login/register (aún no hay sesión → CSRF se hace con cookie pre-existente)
//   - revolut_webhook (viene de servidor externo, va con firma HMAC)
//   - process_external_order (QR anónimo → no tiene cookie)
//   - table_request (igual)
$csrfExempt = [
    'auth_login' => true,
    'public_register' => true,
    'revolut_webhook' => true,
    'process_external_order' => true,
    'get_table_order' => true,
    'table_request' => true,
    'chat_restaurant' => true,
    'validate_coupon' => true,
    'get_mesa_settings' => true,
    'get_payment_settings' => true,
    'list_products' => true,
    'csrf_token' => true,
    'health_check' => true,
];
if (!isset($csrfExempt[$action]) && $user) {
    validate_csrf_or_die();
}

/* ═════════════════════ Dispatch ═════════════════════ */

try {
    switch ($action) {
        case 'auth_login':
            require __DIR__ . '/handlers/auth.php';
            resp(true, handle_auth_login($req));

        case 'auth_logout':
            require __DIR__ . '/handlers/auth.php';
            resp(true, handle_auth_logout($user));

        case 'auth_refresh_session':
        case 'get_current_user':
            require __DIR__ . '/handlers/auth.php';
            resp(true, handle_auth_session($user));

        case 'public_register':
            require __DIR__ . '/handlers/auth.php';
            resp(true, handle_public_register($req));

        case 'create_payment_intent':
        case 'check_revolut_payment':
        case 'create_revolut_payment':
        case 'revolut_webhook':
            require __DIR__ . '/handlers/payments.php';
            resp(true, handle_payment($action, $req, $user));

        case 'chat':
        case 'chat_restaurant':
        case 'ask':
        case 'list_models':
            require __DIR__ . '/handlers/ai.php';
            resp(true, handle_ai($action, $req, $user));

        case 'upload':
            require __DIR__ . '/handlers/upload.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            resp(true, handle_upload($_FILES));

        case 'synaxis_sync':
            require __DIR__ . '/handlers/sync.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor', 'sala', 'cocina', 'camarero']);
            resp(true, handle_sync($req, $user));

        case 'process_external_order':
        case 'get_table_order':
        case 'update_table_cart':
        case 'clear_table':
        case 'table_request':
        case 'get_table_requests':
        case 'acknowledge_request':
            require __DIR__ . '/handlers/qr.php';
            // Acciones de gestión (TPV) requieren rol; las anónimas de QR no.
            $tpvOnly = ['update_table_cart', 'clear_table', 'get_table_requests', 'acknowledge_request'];
            if (in_array($action, $tpvOnly, true)) {
                require_role($user, ['superadmin', 'administrador', 'admin', 'sala', 'cocina', 'camarero']);
            }
            resp(true, handle_qr($action, $req));

        case 'create_reserva':
            require __DIR__ . '/handlers/reservas.php';
            resp(true, handle_reserva($req));

        case 'validate_coupon':
        case 'get_payment_settings':
        case 'get_mesa_settings':
        case 'list_products':
            // Acciones públicas de lectura: leen directamente del cache server.
            resp(true, handle_public_read($action));

        default:
            resp(false, null, "Acción no implementada: $action");
    }
} catch (Throwable $e) {
    error_log('[synaxis-server] action=' . $action . ' err=' . $e->getMessage());
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    resp(false, null, 'Error interno');
}

/* ═════════════════════ Helpers públicos ═════════════════════ */

function handle_public_read(string $action): array
{
    switch ($action) {
        case 'list_products':
            return array_values(array_filter(
                data_all('products'),
                fn($p) => ($p['status'] ?? 'draft') === 'publish'
            ));
        case 'validate_coupon':
            // Delegado a store handler si existe; placeholder seguro.
            return ['ok' => false, 'discount' => 0, 'reason' => 'Cupón no encontrado'];
        case 'get_payment_settings':
            $doc = data_get('payment_settings', 'payment_settings') ?: [];
            unset($doc['_internal']);
            return $doc;
        case 'get_mesa_settings':
            $doc = data_get('config', 'tpv_settings') ?: [];
            return [
                'mesaPayment' => (bool) ($doc['mesaPayment'] ?? false),
                'enabledPaymentMethods' => $doc['enabledPaymentMethods'] ?? [],
                'bizumPhone' => $doc['bizumPhone'] ?? '',
            ];
        default:
            return [];
    }
}
