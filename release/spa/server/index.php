<?php
/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL AUTH LOCK - load-bearing                                 ║
   ║ Este archivo es parte del contrato de auth blindado.             ║
   ║ Antes de modificar, leer claude/AUTH_LOCK.md y verificar que     ║
   ║ spa/server/tests/test_login.php sigue pasando despues del cambio.║
   ╚══════════════════════════════════════════════════════════════════╝ */
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

// Fallback con public_actions razonables si cors.json no existe (dev sin
// configurar). Incluye auth_login y todas las acciones que pre-existen a la
// sesion. Si esto no estuviera, un primer login en una instancia limpia
// devolveria 401 antes de poder crear sesion.
$corsCfg = @load_config_optional('cors') ?? [
    'allowed_origins' => ['http://localhost:5173', 'http://127.0.0.1:5173'],
    'allow_credentials' => true,
    'public_actions' => [
        'health_check', 'csrf_token',
        'auth_login', 'auth_register', 'public_register',
        'chat_restaurant', 'validate_coupon',
        'get_payment_settings', 'get_mesa_settings', 'list_products',
        'process_external_order', 'get_table_order', 'table_request',
        'revolut_webhook',
    ],
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

/* ══════════════ Auto-bootstrap en primer arranque ══════════════ */
$_usersDir = DATA_ROOT . '/users';
if (!is_dir($_usersDir) || count(glob($_usersDir . '/*.json') ?: []) === 0) {
    define('BOOTSTRAP_INTERNAL', true);
    @include_once __DIR__ . '/bin/bootstrap-users.php';
}

/* ══════════════════════════ Parse input ══════════════════════════ */

$raw = file_get_contents('php://input') ?: '';
if (strlen($raw) > 10 * 1024 * 1024) {
    http_response_code(413);
    resp(false, null, 'Payload demasiado grande');
}
$req = json_decode($raw, true) ?: [];
$action = is_string($req['action'] ?? null) ? $req['action'] : null;

// Multipart (file uploads): leer action de $_POST
if (!$action && !empty($_POST['action'])) $action = (string) $_POST['action'];
if (!$action && !empty($_FILES)) $action = 'upload';

/* ═════════════════════ Acciones permitidas ═════════════════════ */

const ALLOWED_ACTIONS = [
    // Auth / sesión — NUNCA eliminar: son el núcleo del sistema de login
    'auth_login', 'auth_logout', 'auth_me', 'auth_refresh_session', 'get_current_user', 'public_register',
    'csrf_token',
    // Pagos
    'create_payment_intent', 'check_revolut_payment', 'create_revolut_payment', 'revolut_webhook',
    // Reservas
    'create_reserva',
    // QR / mesas
    'process_external_order', 'get_table_order', 'update_table_cart',
    'clear_table', 'table_request', 'get_table_requests', 'acknowledge_request',
    // IA general
    'chat', 'chat_restaurant', 'ask', 'list_models',
    // Carta — IA invisible
    'upload_carta_source',
    'ocr_extract', 'ocr_parse',
    'enhance_image_sync',
    'ai_sugerir_alergenos', 'ai_generar_descripcion', 'ai_generar_promocion', 'ai_traducir',
    'importar_carta_estructurada',
    'generate_pdf_carta',
    // Sala — zonas + mesas + QRs (Ola 1)
    'list_zonas', 'create_zona', 'update_zona', 'delete_zona',
    'create_zonas_preset', 'reorder_zonas',
    'list_mesas', 'create_mesa', 'update_mesa', 'delete_mesa',
    'create_mesas_batch', 'regenerate_mesa_qr',
    'sala_resumen',
    // Suscripciones SaaS
    'create_subscription', 'activate_subscription', 'cancel_subscription', 'get_subscription',
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

/* ═════════════════════ Health check ═════════════════════ */

if ($action === 'health_check') {
    resp(true, ['status' => 'healthy', 'mode' => 'bearer-only', 'ts' => date('c')]);
}
// csrf_token sigue respondiendo no-op por compatibilidad: si el cliente
// viejo lo llama, le devolvemos un placeholder y no rompe.
if ($action === 'csrf_token') {
    resp(true, ['token' => 'bearer-mode-no-csrf']);
}

/* ═════════════════════ Autenticación ═════════════════════ */

$publicActions = array_flip((array) ($corsCfg['public_actions'] ?? []));
$isPublic = isset($publicActions[$action]);

$user = current_user();
if (!$isPublic && !$user) {
    http_response_code(401);
    resp(false, null, "Unauthorized: acción '$action' requiere sesión.");
}

/* ═════════════════════ CSRF: no aplica ═════════════════════ */

// Sin cookies httponly no hay riesgo de CSRF cross-site (el atacante no
// puede leer el sessionStorage del navegador, asi que no puede falsificar
// peticiones autenticadas). El token Bearer es la unica credencial.

/* ═════════════════════ Dispatch ═════════════════════ */

try {
    switch ($action) {
        case 'auth_login':
            require_once __DIR__ . '/handlers/auth.php';
            resp(true, handle_auth_login($req));

        case 'auth_logout':
            require_once __DIR__ . '/handlers/auth.php';
            resp(true, handle_auth_logout($user));

        case 'auth_me':             // ← llamado por getCurrentUser() en auth.service.ts
        case 'auth_refresh_session':
        case 'get_current_user':
            require_once __DIR__ . '/handlers/auth.php';
            resp(true, handle_auth_session($user));

        case 'public_register':
            require_once __DIR__ . '/handlers/auth.php';
            resp(true, handle_public_register($req));

        case 'create_payment_intent':
        case 'check_revolut_payment':
        case 'create_revolut_payment':
        case 'revolut_webhook':
            require_once __DIR__ . '/handlers/payments.php';
            resp(true, handle_payment($action, $req, $user));

        case 'chat':
        case 'chat_restaurant':
        case 'ask':
        case 'list_models':
            require_once __DIR__ . '/handlers/ai.php';
            resp(true, handle_ai($action, $req, $user));

        case 'upload':
            require_once __DIR__ . '/handlers/upload.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            resp(true, handle_upload($_FILES));

        case 'synaxis_sync':
            require_once __DIR__ . '/handlers/sync.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor', 'sala', 'cocina', 'camarero']);
            resp(true, handle_sync($req, $user));

        case 'process_external_order':
        case 'get_table_order':
        case 'update_table_cart':
        case 'clear_table':
        case 'table_request':
        case 'get_table_requests':
        case 'acknowledge_request':
            require_once __DIR__ . '/handlers/qr.php';
            // Acciones de gestión (TPV) requieren rol; las anónimas de QR no.
            $tpvOnly = ['update_table_cart', 'clear_table', 'get_table_requests', 'acknowledge_request'];
            if (in_array($action, $tpvOnly, true)) {
                require_role($user, ['superadmin', 'administrador', 'admin', 'sala', 'cocina', 'camarero']);
            }
            resp(true, handle_qr($action, $req));

        case 'create_reserva':
            require_once __DIR__ . '/handlers/reservas.php';
            resp(true, handle_reserva($req));

        case 'upload_carta_source':
            require_once __DIR__ . '/handlers/carta.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            resp(true, handle_carta('upload_carta_source', $req, $_FILES));

        case 'ocr_extract':
        case 'ocr_parse':
        case 'enhance_image_sync':
        case 'ai_sugerir_alergenos':
        case 'ai_generar_descripcion':
        case 'ai_generar_promocion':
        case 'ai_traducir':
        case 'importar_carta_estructurada':
        case 'generate_pdf_carta':
            require_once __DIR__ . '/handlers/carta.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            resp(true, handle_carta($action, $req['data'] ?? $req));

        case 'list_zonas':
        case 'create_zona':
        case 'update_zona':
        case 'delete_zona':
        case 'create_zonas_preset':
        case 'reorder_zonas':
        case 'list_mesas':
        case 'create_mesa':
        case 'update_mesa':
        case 'delete_mesa':
        case 'create_mesas_batch':
        case 'regenerate_mesa_qr':
        case 'sala_resumen':
            require_once __DIR__ . '/handlers/sala.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            resp(true, handle_sala($action, $req, $user));

        case 'create_subscription':
        case 'activate_subscription':
        case 'cancel_subscription':
        case 'get_subscription':
            require_once __DIR__ . '/handlers/subscriptions.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor', 'hostelero']);
            resp(true, handle_subscriptions($action, $req['data'] ?? $req, $user));

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

    // Errores de negocio (RuntimeException, InvalidArgumentException) viajan
    // como HTTP 200 con {success:false, error:"..."}. Esto permite que el
    // cliente lea el body JSON y muestre el mensaje real al usuario.
    // Si devolvieramos 500, el cliente trata el response como `!res.ok` y
    // pierde el envelope, mostrando "HTTP 500: <raw body>".
    if ($e instanceof RuntimeException || $e instanceof InvalidArgumentException) {
        http_response_code(200);
        resp(false, null, $e->getMessage());
    }

    // Errores tecnicos genuinos: 500 con mensaje genericо para no filtrar
    // detalles internos al cliente.
    $code = $e->getCode();
    http_response_code($code >= 400 && $code < 600 ? $code : 500);
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
