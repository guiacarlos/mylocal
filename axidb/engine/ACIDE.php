<?php
/**
 * AxiDB - ACIDE (legacy scaffold).
 *
 * Subsistema: tests/../engine
 * Nota: heredado del motor ACIDE; sera absorbido por Op model y StorageDriver en
 *       fases futuras. Cambios no-triviales: hacerlo en la arquitectura nueva.
 */

require_once __DIR__ . '/CRUDOperations.php';
require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/FileManager.php';
require_once __DIR__ . '/QueryEngine.php';
require_once __DIR__ . '/ThemeManager.php';
require_once __DIR__ . '/ThemeFileManager.php';
require_once __DIR__ . '/StaticGenerator.php';
require_once __DIR__ . '/ElementRenderer.php';
require_once __DIR__ . '/AIContentGenerator.php';
require_once __DIR__ . '/GlandManager.php';
require_once __DIR__ . '/ActionDispatcher.php';
require_once __DIR__ . '/Utils.php';
require_once __DIR__ . '/engine/VisualComposer.php';
require_once __DIR__ . '/engine/AssetOrchestrator.php';
require_once __DIR__ . '/engine/PageRenderer.php';
require_once __DIR__ . '/engine/SitemapGenerator.php';
require_once __DIR__ . '/engine/PageProcessor.php';
require_once __DIR__ . '/engine/StyleComposer.php';
require_once __DIR__ . '/engine/PageLoader.php';

//  MÓDULO COMERCIAL SOBERANO
if (defined('STORE_ROOT') && file_exists(STORE_ROOT . '/StoreEngine.php')) {
    require_once STORE_ROOT . '/StoreEngine.php';
}

/* 
 * [ACIDE CORE CONTRACT] 
 * - ROL: Orquestador Central del Sistema.
 * - ESTRUCTURA: Monolito modular. NO desacoplar en microservicios.
 * - DEPENDENCIAS: Prohibido instalar librerías vía Composer/Vendor aquí.
 * - EXTENSIÓN: Toda nueva funcionalidad debe ir en una clase Handler dedicada.
 * - SEGURIDAD: Solo responde a peticiones validadas por Auth.php.
 */

class ACIDE
{
    private $services = [];
    private $dispatcher;
    private $rootPath;


    public function __construct()
    {
        $this->rootPath = defined('SYNAXIS_ROOT') ? dirname(SYNAXIS_ROOT) : realpath(__DIR__ . '/../../');
        if (!defined('DATA_ROOT')) define('DATA_ROOT', $this->rootPath . '/STORAGE');
        if (!defined('STORAGE_ROOT')) define('STORAGE_ROOT', DATA_ROOT);
        
        $this->services['crud'] = new CRUDOperations();
        $this->services['auth'] = new Auth();
        $this->services['fileManager'] = new FileManager($this->services['crud']);
        $this->services['queryEngine'] = new QueryEngine();
        $this->services['themeManager'] = new ThemeManager($this->services['crud']);
        $this->services['themeFileManager'] = new ThemeFileManager();
        require_once 'McpBridge.php';
        $this->services['mcpBridge'] = new McpBridge(dirname(__DIR__), $this->services);
        $this->services['glandManager'] = new GlandManager($this->services);
        $this->services['acide'] = $this;
        $this->services['staticGenerator'] = new StaticGenerator(
            defined('THEMES_ROOT') ? THEMES_ROOT : $this->rootPath . '/THEMES',
            defined('STORAGE_ROOT') ? STORAGE_ROOT : $this->rootPath . '/STORAGE',
            $this->rootPath . '/release',
            defined('MEDIA_ROOT') ? MEDIA_ROOT : $this->rootPath . '/MEDIA',
            $this->services['crud']
        );

        //  CARGA DINÁMICA DE CAPACIDADES (Engine-First)
        $this->loadCapacities();

        $this->dispatcher = new ActionDispatcher($this->services);
    }

    private function loadCapacities()
    {
        $root = realpath(__DIR__ . '/../../');
        $capabilitiesRoot = $root . '/CAPABILITIES';
        $capacities = ['STORE', 'ACADEMY', 'RESERVAS', 'GEMINI', 'AGENTE_RESTAURANTE', 'RESTAURANT_ORGANIZER', 'QR', 'FSE', 'CARTA'];

        //  RESOLUCIÓN DE ESTADO SOBERANO: Solo cargamos lo que está ACTIVO
        $activeDoc = $this->services['crud']->read('system', 'active_plugins');
        $activeKeys = isset($activeDoc['keys']) ? $activeDoc['keys'] : [];

        foreach ($capacities as $cap) {
            $capPath = $capabilitiesRoot . '/' . $cap;
            $engineFile = $capPath . '/' . ucfirst(strtolower($cap)) . 'Engine.php';
            if (!file_exists($engineFile)) {
                $engineFile = $capPath . '/' . $cap . 'Engine.php';
            }
            $key = strtolower($cap);

            // FSE es una herramienta de sistema: siempre activa para admins
            $alwaysActive = ['fse'];
            $isActive = in_array($key, $activeKeys) || in_array($key, $alwaysActive);

            // ¿Existe la carpeta Y el motor Y está activo en el Marketplace?
            if (is_dir($capPath) && file_exists($engineFile) && $isActive) {
                require_once $engineFile;
                $className = $cap . '\\' . ucfirst(strtolower($cap)) . 'Engine';

                if (class_exists($className)) {
                    $this->services[$key] = new $className($this->services);
                    error_log("[ACIDE] Capacidad '$cap' forjada, activa y en servicio.");
                }
            } else {
                if (is_dir($capPath) && !in_array($key, $activeKeys)) {
                    error_log("[ACIDE] Capacidad '$cap' detectada pero DESACTIVADA por el usuario.");
                }
            }
        }
    }

    public function getServices()
    {
        return $this->services;
    }

    /**
     * Execute the request
     */
    public function execute($request)
    {
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/json; charset=utf-8');
        }

        $action = isset($request['action']) ? $request['action'] : '';
        if (empty($_FILES) && empty($action)) {
            throw new Exception("Action is required.");
        }

        //  PROTOCOLOS PÚBLICOS (Zona Blanca del Búnker)
        $publicActions = [
            'get_active_theme_home',
            'get_active_theme_id',
            'health_check',
            'auth_login',
            'auth_resolve_tenant',
            'public_register', // Permitir registro público
            // 'read', // SE PURGA EN FASE 3
            // 'list', // SE PURGA EN FASE 3
            // 'query', // SE PURGA EN FASE 3
            'chat', // Chat con IA
            'load_conversation', // Cargar historial
            'save_conversation', // Guardar historial
            'validate_coupon', // Validación de cupones en checkout
            'create_payment_intent', // Creación de orden de pago
            'revolut_webhook', // Recepción de señales de pago
            'get_payment_settings', // Consultar métodos de pago activos
            'list_products', // Permitir listado público de productos para la carta
            // 'upload', // SE PURGA EN FASE 3
            'chat_restaurant', // Chat público de la carta
            'process_external_order', // Pedidos desde QR cliente
            'generate_qr_list', // Listado de QRs para imprimir (Dashboard)
            'table_request', // Llamar camarero / pedir cuenta desde QR
            'get_table_order', // Ver pedido activo de la mesa (carta QR)
            'update_table_cart', // Actualizar carrito de mesa desde carta QR
            'get_table_requests', // Ver solicitudes activas de mesa
            'get_mesa_settings', // Configuración pública de la mesa
            'create_revolut_payment', // Pago Revolut desde carta QR
            'check_revolut_payment', // Verificar estado de pago QR
            'get_carta', // Carta publica por slug
            'get_carta_mesa', // Carta publica con contexto de mesa
            'get_producto' // Producto individual publico
        ];

        // Colecciones explícitamente públicas (Fase 3)
        $publicCollections = ['products', 'menu', 'categories', 'restaurant_zones', 'theme_settings'];

        $normalizedAction = strtolower($action);
        $requiresAuth = !in_array($normalizedAction, $publicActions);

        // Si la acción es de lectura legacy, permitir si la colección es pública
        if (in_array($normalizedAction, ['read', 'list', 'query'])) {
            $collection = $request['collection'] ?? '';
            if (!in_array($collection, $publicCollections)) {
                $requiresAuth = true;
            } else {
                $requiresAuth = false;
            }
        }

        if ($requiresAuth && php_sapi_name() !== 'cli') {
            $user = $this->services['auth']->validateRequest();
            if (!$user) {
                error_log("ACIDE_AUTH_FAIL: Action '$action' rejected (401).");
                Utils::sendError("Unauthorized access (Action: $action).", 401);
            }
        }

        // Normalize action for file uploads
        if (!empty($_FILES) && empty($action)) {
            $action = 'upload';
        }

        return $this->dispatcher->dispatch($action, $request);
    }
    public function getDataRoot()
    {
        return DATA_ROOT;
    }

    /**
     *  MONITOR DE SALUD (Point 11)
     */
    public function healthCheck()
    {
        return [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'checks' => [
                'storage_writeable' => is_writable(DATA_ROOT),
                'store_active' => isset($this->services['store']),
                'php_version' => PHP_VERSION,
                'data_integrity' => true,
                'disk_free_space' => disk_free_space(DATA_ROOT)
            ]
        ];
    }
}
