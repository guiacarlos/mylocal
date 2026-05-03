/**
 * Catálogo de acciones del contrato ACIDE / Synaxis.
 *
 * Replica el dispatcher PHP actual (CORE/core/ActionDispatcher.php). Cada
 * acción se etiqueta con:
 *   - scope: `local` (SynaxisCore la resuelve en el navegador sin tocar red)
 *            `server` (obliga a ir al HTTP, la SPA no puede resolverla)
 *            `hybrid` (prefiere local; sincroniza contra server cuando hay).
 *   - domain: categoría de negocio.
 *
 * Regla: las acciones `server` son las que requieren secretos (API keys,
 * hashes), acceso a disco del servidor, o coordinación multi-dispositivo
 * en tiempo real (webhooks, colas de pedidos).
 */

export type ActionScope = 'local' | 'server' | 'hybrid';

export interface ActionMeta {
    action: string;
    scope: ActionScope;
    domain: string;
    description?: string;
}

export const ACTION_CATALOG: readonly ActionMeta[] = [
    // ── CRUD soberano ────────────────────────────────────────
    { action: 'read', scope: 'hybrid', domain: 'crud' },
    { action: 'get', scope: 'hybrid', domain: 'crud' },
    { action: 'list', scope: 'hybrid', domain: 'crud' },
    { action: 'query', scope: 'hybrid', domain: 'crud' },
    { action: 'create', scope: 'hybrid', domain: 'crud' },
    { action: 'update', scope: 'hybrid', domain: 'crud' },
    { action: 'delete', scope: 'hybrid', domain: 'crud' },

    // ── Autenticación ────────────────────────────────────────
    { action: 'auth_login', scope: 'server', domain: 'auth', description: 'Hash bcrypt/Argon2 en el servidor' },
    { action: 'auth_refresh_session', scope: 'server', domain: 'auth' },
    { action: 'auth_resolve_tenant', scope: 'server', domain: 'auth' },
    { action: 'auth_me', scope: 'server', domain: 'auth' },
    { action: 'get_current_user', scope: 'server', domain: 'auth' },
    { action: 'public_register', scope: 'server', domain: 'auth' },

    // ── Temas y FSE ──────────────────────────────────────────
    { action: 'list_themes', scope: 'local', domain: 'theme' },
    { action: 'activate_theme', scope: 'local', domain: 'theme' },
    { action: 'get_active_theme_home', scope: 'local', domain: 'theme' },
    { action: 'get_active_theme_id', scope: 'local', domain: 'theme' },
    { action: 'save_theme_part', scope: 'local', domain: 'theme' },
    { action: 'load_theme_part', scope: 'local', domain: 'theme' },
    { action: 'update_theme_colors', scope: 'local', domain: 'theme' },

    // ── Store / Tienda ───────────────────────────────────────
    { action: 'list_products', scope: 'hybrid', domain: 'store' },
    { action: 'read_product', scope: 'hybrid', domain: 'store' },
    { action: 'create_product', scope: 'local', domain: 'store' },
    { action: 'update_product', scope: 'local', domain: 'store' },
    { action: 'delete_product', scope: 'local', domain: 'store' },
    { action: 'query_products', scope: 'hybrid', domain: 'store' },
    { action: 'update_stock', scope: 'hybrid', domain: 'store' },
    { action: 'list_inventory_logs', scope: 'local', domain: 'store' },
    { action: 'list_payment_methods', scope: 'hybrid', domain: 'store' },
    { action: 'update_payment_method', scope: 'local', domain: 'store' },
    { action: 'list_coupons', scope: 'local', domain: 'store' },
    { action: 'create_coupon', scope: 'local', domain: 'store' },
    { action: 'update_coupon', scope: 'local', domain: 'store' },
    { action: 'delete_coupon', scope: 'local', domain: 'store' },
    { action: 'validate_coupon', scope: 'hybrid', domain: 'store' },
    { action: 'list_orders', scope: 'hybrid', domain: 'store' },
    { action: 'update_order_status', scope: 'hybrid', domain: 'store' },
    { action: 'create_sale', scope: 'hybrid', domain: 'store' },
    { action: 'get_company_settings', scope: 'hybrid', domain: 'store' },
    { action: 'update_company_settings', scope: 'local', domain: 'store' },
    { action: 'get_legal_settings', scope: 'hybrid', domain: 'store' },
    { action: 'update_legal_settings', scope: 'local', domain: 'store' },
    { action: 'get_payment_settings', scope: 'hybrid', domain: 'store' },
    { action: 'update_payment_settings', scope: 'local', domain: 'store' },

    // ── Pagos (OBLIGATORIO servidor) ─────────────────────────
    { action: 'create_payment_intent', scope: 'server', domain: 'payments', description: 'Claves Revolut en servidor' },
    { action: 'revolut_webhook', scope: 'server', domain: 'payments', description: 'Webhook → URL pública HTTP' },
    { action: 'create_revolut_payment', scope: 'server', domain: 'payments' },
    { action: 'check_revolut_payment', scope: 'server', domain: 'payments' },

    // ── QR / mesas (multi-dispositivo) ───────────────────────
    { action: 'get_table_order', scope: 'server', domain: 'qr' },
    { action: 'process_external_order', scope: 'server', domain: 'qr' },
    { action: 'update_table_cart', scope: 'server', domain: 'qr' },
    { action: 'clear_table', scope: 'server', domain: 'qr' },
    { action: 'table_request', scope: 'server', domain: 'qr' },
    { action: 'get_table_requests', scope: 'server', domain: 'qr' },
    { action: 'acknowledge_request', scope: 'server', domain: 'qr' },
    { action: 'generate_qr_list', scope: 'local', domain: 'qr' },
    { action: 'get_mesa_settings', scope: 'hybrid', domain: 'qr' },

    // ── Reservas ─────────────────────────────────────────────
    { action: 'list_reservas', scope: 'hybrid', domain: 'reservas' },
    { action: 'create_reserva', scope: 'server', domain: 'reservas' },
    { action: 'get_availability', scope: 'hybrid', domain: 'reservas' },

    // ── Academia / Gemini ────────────────────────────────────
    { action: 'list_courses', scope: 'hybrid', domain: 'academy' },
    { action: 'create_course', scope: 'local', domain: 'academy' },
    { action: 'create_lesson', scope: 'local', domain: 'academy' },
    { action: 'update_course', scope: 'local', domain: 'academy' },
    { action: 'delete_course', scope: 'local', domain: 'academy' },
    { action: 'chat', scope: 'server', domain: 'ai', description: 'API key Gemini en servidor' },
    { action: 'ask', scope: 'server', domain: 'ai' },
    { action: 'chat_restaurant', scope: 'server', domain: 'ai' },
    { action: 'load_conversation', scope: 'local', domain: 'ai' },
    { action: 'save_conversation', scope: 'local', domain: 'ai' },
    { action: 'list_conversations', scope: 'local', domain: 'ai' },
    { action: 'list_models', scope: 'server', domain: 'ai' },

    // ── Carta hostelera (CRUD local + IA servidor) ───────────
    // CRUD: usa las acciones genéricas (list/create/update/delete)
    // con collection: 'carta_categorias' | 'carta_productos' | 'carta_mesas'

    // IA invisible — obligatorio servidor (API keys, procesado binario)
    { action: 'upload_carta_source', scope: 'server', domain: 'carta', description: 'Sube PDF/imagen para OCR' },
    { action: 'ocr_extract', scope: 'server', domain: 'carta', description: 'OCR via Gemini Vision' },
    { action: 'ocr_parse', scope: 'server', domain: 'carta', description: 'Estructura carta desde texto OCR' },
    { action: 'enhance_image_sync', scope: 'server', domain: 'carta', description: 'Varita mágica Imagick/GD' },
    { action: 'ai_sugerir_alergenos', scope: 'server', domain: 'carta', description: 'Alérgenos UE desde ingredientes' },
    { action: 'ai_generar_descripcion', scope: 'server', domain: 'carta', description: 'Copywriting gastronómico' },
    { action: 'ai_generar_promocion', scope: 'server', domain: 'carta', description: 'Micro-promo para especialidad' },
    { action: 'ai_traducir', scope: 'server', domain: 'carta', description: 'Traducción culinaria' },
    { action: 'importar_carta_estructurada', scope: 'server', domain: 'carta', description: 'Importa carta en lote desde OCR' },
    { action: 'generate_pdf_carta', scope: 'server', domain: 'carta', description: 'Genera PDF físico con plantilla' },

    // ── Suscripciones SaaS (Revolut) ─────────────────────────
    { action: 'create_subscription',   scope: 'server', domain: 'billing', description: 'Crea orden Revolut + guarda pending' },
    { action: 'activate_subscription', scope: 'server', domain: 'billing', description: 'Verifica pago Revolut + activa plan' },
    { action: 'cancel_subscription',   scope: 'server', domain: 'billing', description: 'Desactiva auto-renovación' },
    { action: 'get_subscription',      scope: 'server', domain: 'billing', description: 'Estado actual del plan' },

    // ── Sistema ──────────────────────────────────────────────
    { action: 'health_check', scope: 'local', domain: 'system' },
    { action: 'build_site', scope: 'server', domain: 'system' },
    { action: 'generate_sitemap', scope: 'server', domain: 'system' },
    { action: 'upload', scope: 'server', domain: 'system', description: 'Binarios van a hosting con URL pública' },
    { action: 'synaxis_sync', scope: 'server', domain: 'system', description: 'Push/pull de oplog (Fase 3)' },
    { action: 'list_glands', scope: 'local', domain: 'system' },
    { action: 'get_marketplace', scope: 'hybrid', domain: 'system' },
] as const;

export type ActionName = (typeof ACTION_CATALOG)[number]['action'];

const ACTION_SCOPE_MAP = new Map(ACTION_CATALOG.map((m) => [m.action, m.scope]));

export function getActionScope(action: string): ActionScope {
    return ACTION_SCOPE_MAP.get(action) ?? 'hybrid';
}

export function isLocalOnly(action: string): boolean {
    return getActionScope(action) === 'local';
}

export function requiresServer(action: string): boolean {
    return getActionScope(action) === 'server';
}
