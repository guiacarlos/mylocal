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
        // Carta digital publica: el cliente que escanea el QR lee sin sesion
        'get_local', 'list_cartas', 'list_categorias', 'list_productos',
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
    'upload_carta_source', 'ocr_import_carta',
    'ocr_extract', 'ocr_parse',
    'enhance_image_sync',
    'ai_sugerir_alergenos', 'ai_generar_descripcion', 'ai_generar_promocion', 'ai_traducir',
    'importar_carta_estructurada',
    'generate_pdf_carta',
    // Carta CRUD persistente AxiDB (jerarquia local→carta→categoria→producto)
    'list_cartas', 'create_carta', 'update_carta', 'delete_carta',
    'list_categorias', 'create_categoria', 'update_categoria', 'delete_categoria',
    'list_productos', 'create_producto', 'update_producto', 'delete_producto',
    // Sala — zonas + mesas + QRs (Ola 1)
    'list_zonas', 'create_zona', 'update_zona', 'delete_zona',
    'create_zonas_preset', 'reorder_zonas',
    'list_mesas', 'create_mesa', 'update_mesa', 'delete_mesa',
    'create_mesas_batch', 'regenerate_mesa_qr',
    'sala_resumen',
    // Local — datos del establecimiento (multi-local, multi-user)
    'get_local', 'list_my_locales', 'create_local', 'update_local', 'bootstrap_local',
    'upload_local_image',
    // Suscripciones SaaS
    'create_subscription', 'activate_subscription', 'cancel_subscription', 'get_subscription',
    // Sistema
    'upload', 'synaxis_sync', 'health_check',
    // Público (lectura mínima que el cliente puede cachear)
    'validate_coupon', 'get_payment_settings', 'get_mesa_settings', 'list_products',
    // CITAS (agenda y reservas internas)
    'cita_create', 'cita_update', 'cita_list', 'cita_cancel', 'cita_get',
    'recurso_create', 'recurso_update', 'recurso_list', 'recurso_delete',
    'cita_publica_crear',
    // CRM (contactos, interacciones, segmentación)
    'crm_contacto_create', 'crm_contacto_update', 'crm_contacto_get',
    'crm_contacto_list', 'crm_contacto_delete',
    'crm_interaccion_add', 'crm_interaccion_list',
    'crm_segmento_query',
    // NOTIFICACIONES
    'notif_send', 'notif_send_template', 'notif_list',
    'notif_template_list', 'notif_template_save',
    // TAREAS (kanban transversal)
    'tarea_create', 'tarea_list', 'tarea_update', 'tarea_delete',
    // DELIVERY (pedidos, flota, entregas, incidencias)
    'pedido_create', 'pedido_list', 'pedido_get', 'pedido_estado',
    'vehiculo_create', 'vehiculo_list', 'vehiculo_update',
    'entrega_asignar', 'entrega_list_dia',
    'incidencia_add',
    'pedido_seguimiento',
    // OPENCLAUDE (asistente transversal — conector Anthropic)
    'openclaude_status', 'openclaude_complete',
    // OPENCLAW (integración con agente OpenClaw local — skill bidireccional)
    'openclaw_manifest', 'openclaw_call', 'openclaw_status', 'openclaw_event_push',
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
        case 'ocr_import_carta':
            require_once __DIR__ . '/handlers/carta.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            resp(true, handle_carta($action, $req, $_FILES));

        // Carta CRUD — LECTURAS publicas (cliente escanea QR sin sesion)
        case 'list_cartas':
        case 'list_categorias':
        case 'list_productos':
            require_once __DIR__ . '/handlers/carta.php';
            resp(true, handle_carta($action, $req));

        // Carta CRUD — ESCRITURAS solo admin/editor
        case 'ocr_extract':
        case 'ocr_parse':
        case 'enhance_image_sync':
        case 'ai_sugerir_alergenos':
        case 'ai_generar_descripcion':
        case 'ai_generar_promocion':
        case 'ai_traducir':
        case 'importar_carta_estructurada':
        case 'generate_pdf_carta':
        case 'create_carta':
        case 'update_carta':
        case 'delete_carta':
        case 'create_categoria':
        case 'update_categoria':
        case 'delete_categoria':
        case 'create_producto':
        case 'update_producto':
        case 'delete_producto':
            require_once __DIR__ . '/handlers/carta.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            resp(true, handle_carta($action, $req));

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

        case 'get_local':
        case 'list_my_locales':
        case 'bootstrap_local':
            require_once __DIR__ . '/handlers/local.php';
            // lectura permitida a cualquier rol con sesion (lo necesita la SPA)
            resp(true, handle_local($action, $req, $user));

        case 'upload_local_image':
            require_once __DIR__ . '/handlers/local.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            // Multipart: $_POST trae action+local_id, $_FILES trae file
            $req['local_id'] = $req['local_id'] ?? ($_POST['local_id'] ?? '');
            resp(true, handle_local($action, $req, $user, $_FILES));

        case 'create_local':
        case 'update_local':
            require_once __DIR__ . '/handlers/local.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            resp(true, handle_local($action, $req, $user));

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

        // ── CITAS ────────────────────────────────────────────────────────
        case 'cita_create':
        case 'cita_update':
        case 'cita_list':
        case 'cita_cancel':
        case 'cita_get':
        case 'recurso_create':
        case 'recurso_update':
        case 'recurso_list':
        case 'recurso_delete':
            require_once __DIR__ . '/handlers/citas.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            resp(true, \Citas\handle_citas_admin($action, $req, $user));

        case 'cita_publica_crear':
            require_once __DIR__ . '/handlers/citas.php';
            resp(true, \Citas\handle_citas_public($action, $req));

        // ── CRM ──────────────────────────────────────────────────────────
        case 'crm_contacto_create':
        case 'crm_contacto_update':
        case 'crm_contacto_get':
        case 'crm_contacto_list':
        case 'crm_contacto_delete':
        case 'crm_interaccion_add':
        case 'crm_interaccion_list':
        case 'crm_segmento_query':
            require_once __DIR__ . '/handlers/crm.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            resp(true, \Crm\handle_crm($action, $req, $user));

        // ── NOTIFICACIONES ───────────────────────────────────────────────
        case 'notif_send':
        case 'notif_send_template':
        case 'notif_list':
        case 'notif_template_list':
        case 'notif_template_save':
            require_once __DIR__ . '/handlers/notificaciones.php';
            require_role($user, ['superadmin', 'administrador', 'admin']);
            resp(true, \Notificaciones\handle_notificaciones($action, $req, $user));

        // ── TAREAS ───────────────────────────────────────────────────────
        case 'tarea_create':
        case 'tarea_list':
        case 'tarea_update':
        case 'tarea_delete':
            require_once __DIR__ . '/handlers/tareas.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            resp(true, \Tareas\handle_tareas($action, $req, $user));

        // ── DELIVERY ─────────────────────────────────────────────────────
        case 'pedido_create':
        case 'pedido_list':
        case 'pedido_get':
        case 'pedido_estado':
        case 'vehiculo_create':
        case 'vehiculo_list':
        case 'vehiculo_update':
        case 'entrega_asignar':
        case 'entrega_list_dia':
        case 'incidencia_add':
            require_once __DIR__ . '/handlers/delivery.php';
            require_role($user, ['superadmin', 'administrador', 'admin', 'editor']);
            resp(true, \Delivery\handle_delivery_admin($action, $req, $user));

        case 'pedido_seguimiento':
            require_once __DIR__ . '/handlers/delivery.php';
            resp(true, \Delivery\handle_delivery_public($action, $req));

        // ── OPENCLAUDE ───────────────────────────────────────────────────
        case 'openclaude_status':
            require_once __DIR__ . '/handlers/openclaude.php';
            resp(true, \AI\handle_openclaude($action, $req, $user ?? []));

        case 'openclaude_complete':
            require_once __DIR__ . '/handlers/openclaude.php';
            require_role($user, ['superadmin', 'administrador', 'admin']);
            resp(true, \AI\handle_openclaude($action, $req, $user));

        // ── OPENCLAW (skill bidireccional con agente local) ───────────────
        case 'openclaw_manifest':
            require_once __DIR__ . '/handlers/openclaw_skill.php';
            resp(true, \OpenClaw\handle_openclaw_capability($action, $req, [], getallheaders() ?: []));

        case 'openclaw_call':
            require_once __DIR__ . '/handlers/openclaw_skill.php';
            // Auth gestionada internamente por OpenClawSkillExecutor::validateKey()
            resp(true, \OpenClaw\handle_openclaw_capability($action, $req, [], getallheaders() ?: []));

        case 'openclaw_status':
        case 'openclaw_event_push':
            require_once __DIR__ . '/handlers/openclaw_skill.php';
            require_role($user, ['superadmin', 'administrador', 'admin']);
            resp(true, \OpenClaw\handle_openclaw_capability($action, $req, $user, getallheaders() ?: []));

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
