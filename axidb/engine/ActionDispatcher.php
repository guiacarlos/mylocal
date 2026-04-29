<?php
/**
 * AxiDB - ActionDispatcher (legacy scaffold).
 *
 * Subsistema: tests/../engine
 * Nota: heredado del motor ACIDE; sera absorbido por Op model y StorageDriver en
 *       fases futuras. Cambios no-triviales: hacerlo en la arquitectura nueva.
 */

require_once __DIR__ . '/handlers/DataHandler.php';
require_once __DIR__ . '/handlers/ThemeHandler.php';
require_once __DIR__ . '/handlers/CMSHandler.php';
require_once __DIR__ . '/handlers/SystemHandler.php';

class ActionDispatcher
{
    /*
     * [ACIDE DISPATCHER CONTRACT] 
     * - ROL: Embudo Único de Peticiones.
     * - REGLA: Todas las acciones deben estar en el switch($action).
     * - SOBERANÍA: No crear controladores externos. Todo pasa por aquí.
     */
    private $services;
    private $dataHandler;
    private $themeHandler;
    private $cmsHandler;
    private $systemHandler;
    private $projectHandler;
    private $authHandler;
    private $terminalHandler;
    private $aiHandler;
    private $userHandler;
    private $roleHandler;
    private $storeHandler;
    private $academyHandler;
    private $reservasHandler;
    private $qrHandler;
    private $updateHandler;

    public function __construct($services)
    {
        $this->services = $services;
    }

    private function getDataHandler()
    {
        if (!$this->dataHandler) {
            $this->dataHandler = new DataHandler($this->services);
        }
        return $this->dataHandler;
    }

    private function getThemeHandler()
    {
        if (!$this->themeHandler) {
            $this->themeHandler = new ThemeHandler($this->services);
        }
        return $this->themeHandler;
    }

    private function getCMSHandler()
    {
        if (!$this->cmsHandler) {
            $this->cmsHandler = new CMSHandler($this->services);
        }
        return $this->cmsHandler;
    }

    private function getSystemHandler()
    {
        if (!$this->systemHandler) {
            $this->systemHandler = new SystemHandler($this->services);
        }
        return $this->systemHandler;
    }

    private function getAuthHandler()
    {
        if (!$this->authHandler) {
            require_once __DIR__ . '/handlers/AuthHandler.php';
            $this->authHandler = new AuthHandler($this->services);
        }
        return $this->authHandler;
    }

    private function getTerminalHandler()
    {
        if (!$this->terminalHandler) {
            require_once __DIR__ . '/handlers/TerminalHandler.php';
            $this->terminalHandler = new TerminalHandler($this->services);
        }
        return $this->terminalHandler;
    }

    private function getAIHandler()
    {
        if (!$this->aiHandler) {
            require_once __DIR__ . '/handlers/AIHandler.php';
            $this->aiHandler = new AIHandler($this->services);
        }
        return $this->aiHandler;
    }

    private function getUserHandler()
    {
        if (!$this->userHandler) {
            require_once __DIR__ . '/handlers/UserHandler.php';
            $this->userHandler = new UserHandler($this->services);
        }
        return $this->userHandler;
    }

    private function getRoleHandler()
    {
        if (!$this->roleHandler) {
            require_once __DIR__ . '/handlers/RoleHandler.php';
            $this->roleHandler = new RoleHandler($this->services);
        }
        return $this->roleHandler;
    }

    private function getStoreHandler()
    {
        if (!$this->storeHandler) {
            require_once __DIR__ . '/handlers/StoreHandler.php';
            $this->storeHandler = new StoreHandler($this->services);
        }
        return $this->storeHandler;
    }

    private function getAcademyHandler()
    {
        if (!$this->academyHandler) {
            require_once __DIR__ . '/handlers/AcademyHandler.php';
            $this->academyHandler = new AcademyHandler($this->services);
        }
        return $this->academyHandler;
    }

    private function getReservasHandler()
    {
        if (!$this->reservasHandler) {
            require_once __DIR__ . '/handlers/ReservasHandler.php';
            $this->reservasHandler = new ReservasHandler($this->services);
        }
        return $this->reservasHandler;
    }

    private function getQRHandler()
    {
        if (!$this->qrHandler) {
            require_once __DIR__ . '/handlers/QRHandler.php';
            $this->qrHandler = new QRHandler($this->services);
        }
        return $this->qrHandler;
    }

    public function dispatch($action, $request)
    {
        $collection = $request['collection'] ?? ($request['data']['collection'] ?? null);
        $id = $request['id'] ?? ($request['data']['id'] ?? null);

        //  EXTRACCIÓN SOBERANA DE DATOS: 
        // Buscamos en data.data, luego en data, y si no, usamos el request completo (limpio)
        if (isset($request['data']['data'])) {
            $data = $request['data']['data'];
        } else if (isset($request['data']) && is_array($request['data'])) {
            $data = $request['data'];
        } else {
            // Si el JSON viene plano (como en Academy), limpiamos los metadatos de control
            $data = $request;
            unset($data['action'], $data['collection'], $data['id']);
        }

        $params = $request['params'] ?? ($request['data']['params'] ?? []);
        $key = $request['key'] ?? ($request['data']['key'] ?? null);
        $glandAction = $request['gland_action'] ?? ($request['data']['gland_action'] ?? null);

        try {
             $response = (function () use ($action, $request, $collection, $id, $data, $params, $key, $glandAction) {
                switch ($action) {
                    case 'upload':
                        $uploadOptions = [];
                        if (isset($_POST['folder'])) $uploadOptions['folder'] = (string)$_POST['folder'];
                        if (isset($_POST['slug']))   $uploadOptions['slug']   = (string)$_POST['slug'];
                        return $this->getSystemHandler()->upload($_FILES, $uploadOptions);

                    case 'list_media':
                        $folder = isset($data['folder']) ? (string)$data['folder'] : '';
                        return $this->getSystemHandler()->listMedia($folder);

                    case 'delete_media':
                        $url = isset($data['url']) ? (string)$data['url'] : '';
                        return $this->getSystemHandler()->deleteMedia($url);

                    case 'get_media_formats':
                        $formats = \SystemHandler::allowedFormats();
                        return [
                            'success' => true,
                            'data' => [
                                'image' => $formats['image'],
                                'video' => $formats['video'],
                                'all'   => array_merge($formats['image'], $formats['video']),
                            ],
                        ];

                    case 'list_projects':
                    case 'create_project':
                    case 'switch_project':
                    case 'delete_project':
                    case 'get_active_project':
                    case 'export_project':
                    case 'list_blueprints':
                        if (!isset($this->projectHandler)) {
                            require_once __DIR__ . '/handlers/ProjectHandler.php';
                            $this->projectHandler = new ProjectHandler($this->services);
                        }
                        return $this->projectHandler->execute($action, $data);

                    case 'query':
                        return $this->getDataHandler()->query($collection, $params);

                    case 'list_themes':
                        $themes = $this->getThemeHandler()->listThemes();
                        return ['success' => true, 'data' => is_array($themes) ? $themes : []];

                    case 'get_collections':
                        return $this->getDataHandler()->getCollections();

                    case 'get_active_theme_home':
                        return $this->getThemeHandler()->getActiveThemeHome();

                    case 'get_active_theme_id':
                        return $this->getThemeHandler()->getActiveThemeId();

                    case 'activate_theme':
                        $activated = $this->getThemeHandler()->activateTheme(
                            $request['theme_id'] ?? ($request['data']['theme_id'] ?? null)
                        );
                        return ['success' => true, 'data' => $activated];

                    case 'delete_theme':
                        $deleted = $this->getThemeHandler()->deleteTheme(
                            $request['theme_id'] ?? ($request['data']['theme_id'] ?? null)
                        );
                        return ['success' => true, 'data' => $deleted];

                    case 'upload_theme':
                        return $this->getThemeHandler()->uploadTheme($_FILES['theme'] ?? null);

                    case 'set_front_page':
                        return $this->getThemeHandler()->setFrontPage($request['theme_id'] ?? null, $request['page_id'] ?? null);

                    case 'update_theme_colors':
                        $tId = $data['theme_id'] ?? ($request['theme_id'] ?? null);
                        $result = $this->getThemeHandler()->updateThemeColors($tId, $data);
                        return ['success' => true, 'data' => $result];

                    case 'get':
                    case 'read':
                        $readData = $this->getCMSHandler()->handleRead($collection, $id, function () use ($collection, $id) {
                            return $this->getDataHandler()->read($collection, $id);
                        });
                        return ['success' => true, 'data' => $readData];

                    case 'create':
                    case 'update':
                        $updateData = $this->getCMSHandler()->handleWrite($collection, $id, $data, function () use ($collection, $id, $data) {
                            return $this->getDataHandler()->update($collection, $id, $data);
                        });
                        return ['success' => true, 'data' => $updateData];

                    case 'list':
                        $listResults = $this->getDataHandler()->list($collection);
                        $listData = $this->getCMSHandler()->handleList($collection, $listResults);
                        return ['success' => true, 'data' => $listData];

                    case 'delete':
                        $delRes = $this->getDataHandler()->delete($collection, $id);
                        return ['success' => true, 'data' => $delRes];

                    case 'save_theme_part':
                        $tId = $data['theme_id'] ?? ($request['theme_id'] ?? null);
                        $pName = $data['part_name'] ?? ($request['part_name'] ?? null);
                        return $this->getThemeHandler()->savePart($tId, $pName, $data);

                    case 'load_theme_part':
                        $tId = $data['theme_id'] ?? ($request['theme_id'] ?? ($request['params']['theme_id'] ?? null));
                        $pName = $data['part_name'] ?? ($request['part_name'] ?? ($request['params']['part_name'] ?? null));
                        return $this->getThemeHandler()->loadPart($tId, $pName);

                    case 'check_updates':
                    case 'download_update':
                    case 'apply_update':
                    case 'rollback_update':
                    case 'get_update_status':
                    case 'save_update_config':
                        if (!isset($this->updateHandler)) {
                            require_once __DIR__ . '/handlers/UpdateHandler.php';
                            $this->updateHandler = new UpdateHandler($this->services);
                        }
                        return $this->updateHandler->execute($action, $data);

                    case 'build_site':
                        return $this->getSystemHandler()->buildSite();

                    case 'list_glands':
                        if (!isset($this->services['glandManager']))
                            return ['success' => true, 'data' => []];
                        return $this->services['glandManager']->listGlands();

                    case 'health_check':
                        if (!isset($this->services['acide']))
                            return ['success' => true, 'data' => ['status' => 'ok']];
                        return $this->services['acide']->healthCheck();

                    case 'get_gland':
                        if (!isset($this->services['glandManager']))
                            return ['success' => false, 'error' => 'GlandManager not available'];
                        return $this->services['glandManager']->getGland($key);

                    case 'operate_gland':
                        if (!isset($this->services['glandManager']))
                            return ['success' => false, 'error' => 'GlandManager not available'];
                        return $this->services['glandManager']->operate(
                            $key,
                            $glandAction,
                            $params
                        );

                    case 'create_course':
                    case 'create_lesson':
                    case 'update_course':
                    case 'list_courses':
                    case 'delete_course':
                        return $this->getAcademyHandler()->execute($action, $request['args'] ?? ($request['data'] ?? $request));

                    case 'list_reservas':
                    case 'create_reserva':
                    case 'get_availability':
                        return $this->getReservasHandler()->execute($action, $request['args'] ?? ($request['data'] ?? $request));

                    case 'terminal':
                    case 'ls':
                    case 'cat':
                    case 'write':
                    case 'config':
                    case 'execute_command':
                        return $this->getTerminalHandler()->execute($action, $request['args'] ?? ($request['data'] ?? $request));

                    case 'fse_agent_chat':
                    case 'fse_get_patterns':
                    case 'fse_save_pattern':
                    case 'fse_delete_pattern':
                    case 'fse_get_status':
                        if (!isset($this->services['fse'])) {
                            return ['success' => false, 'error' => 'FSE capability no activa'];
                        }
                        return $this->services['fse']->dispatch($action, $data ?? $request);

                    case 'chat':
                    case 'ask':
                        return $this->getAIHandler()->execute('chat', $request);

                    case 'load_conversation':
                        return $this->getAIHandler()->execute('load_conversation', $request);

                    case 'save_conversation':
                    case 'save_chat':
                        return $this->getAIHandler()->execute('save_conversation', $request);

                    case 'list_conversations':
                        return $this->getAIHandler()->execute('list_conversations', $request);

                    case 'list_models':
                    case 'ai:models':
                    case 'models':
                        return $this->getAIHandler()->execute('list_models', $request);

                    case 'generate_sitemap':
                        return $this->getSystemHandler()->generateSitemap($request['base_url'] ?? 'https://example.com');

                    case 'auth_login':
                        $authData = isset($request['email']) ? $request : ($data ?? []);
                        return $this->getAuthHandler()->login(
                            $authData['email'] ?? null,
                            $authData['password'] ?? null,
                            $authData['tenantId'] ?? null
                        );

                    // User Management
                    case 'create_user':
                    case 'read_user':
                    case 'update_user':
                    case 'delete_user':
                    case 'list_users':
                    case 'change_password':
                    case 'update_profile':
                    case 'get_current_user':
                    case 'public_register':
                        return $this->getUserHandler()->execute($action, $data);

                    // Role Management
                    case 'create_role':
                    case 'read_role':
                    case 'update_role':
                    case 'delete_role':
                    case 'list_roles':
                    case 'check_permission':
                        return $this->getRoleHandler()->execute($action, $data);

                    case 'auth_refresh_session':
                        return $this->getAuthHandler()->refreshSession();

                    case 'auth_logout':
                        // Limpia cookie de sesión del dominio
                        setcookie('acide_session', '', time() - 3600, '/');
                        return ['success' => true, 'data' => ['logged_out' => true]];

                    case 'auth_me':
                        // Alias práctico de get_current_user
                        return $this->getUserHandler()->execute('get_current_user', $data);

                    // Store Management
                    case 'list_products':
                    case 'read_product':
                    case 'create_product':
                    case 'update_product':
                    case 'delete_product':
                    case 'update_stock':
                    case 'list_inventory_logs':
                    case 'query_products':
                    case 'list_payment_methods':
                    case 'update_payment_method':
                    case 'list_coupons':
                    case 'create_coupon':
                    case 'update_coupon':
                    case 'delete_coupon':
                    case 'list_shipping_methods':
                    case 'update_shipping_method':
                    case 'list_taxes':
                    case 'update_tax':
                    case 'list_orders':
                    case 'update_order_status':
                    case 'create_sale':
                    case 'get_legal_settings':
                    case 'update_legal_settings':
                    case 'get_company_settings':
                    case 'update_company_settings':
                    case 'delete_tax':
                    case 'bootstrap_store':
                    case 'bootstrap_subscriptions':
                    case 'create_payment_intent':
                    case 'revolut_webhook':
                    case 'validate_coupon':
                    case 'get_payment_settings':
                    case 'update_payment_settings':
                        return $this->getStoreHandler()->execute($action, $data);

                    case 'get_marketplace':
                        require_once __DIR__ . '/MarketplaceManager.php';
                        $mgr = new MarketplaceManager($this->services);
                        return ['success' => true, 'id' => 'marketplace-atomic', 'data' => $mgr->getMarketplace()];

                    // --- Agente Restaurante IA ---
                    case 'chat_restaurant':
                    case 'get_agent_config':
                    case 'update_agent_config':
                    case 'list_agents':
                    case 'get_vault_carta':
                    case 'update_vault_carta':
                    case 'delete_vault_entry':
                    case 'bootstrap_restaurant':
                    case 'search_menu':
                    case 'get_item_details':
                    case 'search_vault':
                        if (!isset($this->services['agente_restaurante'])) {
                            //  INTENTO DE RECUPERACIÓN SOBERANA si no está cargado
                            $acide = $this->services['acide'] ?? null;
                            if ($acide) {
                                // Esto forzará una recarga si es necesario
                            }
                        }
                        if (!isset($this->services['agente_restaurante'])) {
                            throw new Exception("Capacidad AGENTE_RESTAURANTE no disponible en el búnker actual.");
                        }
                        return $this->services['agente_restaurante']->executeAction($action, $data);

                    case 'get_restaurant_zones':
                    case 'update_restaurant_zones':
                        if (!isset($this->services['restaurant_organizer'])) {
                            throw new Exception("Capacidad RESTAURANT_ORGANIZER no forjada.");
                        }
                        return $this->services['restaurant_organizer']->executeAction($action, $data);

                    case 'get_table_order':
                    case 'generate_qr_list':
                    case 'process_external_order':
                    case 'update_table_cart':
                    case 'clear_table':
                    case 'table_request':
                    case 'get_table_requests':
                    case 'acknowledge_request':
                    case 'create_revolut_payment':
                    case 'check_revolut_payment':
                    case 'generate_qr_image':
                    case 'generate_qr_carta':
                    case 'export_qr_zona':
                    case 'export_qr_all':
                        return $this->getQRHandler()->execute($action, $data);

                    case 'get_mesa_settings':
                        // Acción pública: devuelve solo la configuración de mesa que el cliente necesita
                        $crud = $this->services['crud'];
                        $settings = $crud->read('config', 'tpv_settings') ?: [];
                        $mesaPayment = !empty($settings['mesaPayment']);
                        $methods = [];
                        if ($mesaPayment) {
                            $enabledIds = $settings['enabledPaymentMethods'] ?? ['cash', 'card'];
                            $allMethods = [
                                ['id' => 'cash', 'name' => 'Efectivo'],
                                ['id' => 'card', 'name' => 'Tarjeta'],
                                ['id' => 'revolut', 'name' => 'Tarjeta (Revolut)'],
                                ['id' => 'bizum', 'name' => 'Bizum'],
                                ['id' => 'transfer', 'name' => 'Transferencia']
                            ];
                            foreach ($allMethods as $m) {
                                if (in_array($m['id'], $enabledIds)) {
                                    $methods[] = $m;
                                }
                            }
                        }
                        return [
                            'success' => true,
                            'data' => [
                                'mesaPayment' => $mesaPayment,
                                'methods' => $methods,
                                'bizumPhone' => $settings['bizumPhone'] ?? '',
                            ]
                        ];

                    case 'get_carta':
                    case 'get_carta_mesa':
                    case 'get_producto':
                    case 'create_local':
                    case 'update_local':
                    case 'get_local':
                    case 'list_locales':
                    case 'delete_local':
                    case 'create_categoria':
                    case 'update_categoria':
                    case 'get_categoria':
                    case 'list_categorias':
                    case 'delete_categoria':
                    case 'create_producto':
                    case 'update_producto':
                    case 'get_producto_admin':
                    case 'list_productos':
                    case 'list_productos_categoria':
                    case 'delete_producto':
                    case 'create_mesa':
                    case 'update_mesa':
                    case 'get_mesa':
                    case 'list_mesas':
                    case 'list_mesas_zona':
                    case 'get_zonas':
                    case 'delete_mesa':
                        if (!isset($this->services['carta'])) {
                            throw new Exception("Capacidad CARTA no activada.");
                        }
                        return $this->services['carta']->executeAction($action, $data);

                    default:
                        throw new Exception("Acción no reconocida: $action");
                }
            })();

            //  ENVOLTURA SOBERANA: Si la respuesta no tiene el flag success, la envolvemos
            if (is_array($response) && !isset($response['success'])) {
                return ['success' => true, 'data' => $response];
            }

            return $response;
        } catch (Throwable $e) {
            error_log("[Dispatcher] Error en $action: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
