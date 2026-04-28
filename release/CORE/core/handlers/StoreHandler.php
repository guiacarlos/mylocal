<?php

require_once __DIR__ . '/BaseHandler.php';

/**
 * 🛒 StoreHandler - El Embajador Comercial de ACIDE.
 * Responsabilidad: Orquestar la comunicación entre el túnel y el StoreEngine.
 */
class StoreHandler extends BaseHandler
{
    private $engine;

    public function __construct($services)
    {
        parent::__construct($services);
        // El motor STORE ya ha sido inyectado en el contenedor de servicios por ACIDE.php
        $this->engine = isset($this->services['store']) ? $this->services['store'] : null;
    }

    public function execute($action, $args = array())
    {
        if (!$this->engine) {
            return ['success' => false, 'error' => 'Motor comercial (STORE) no inicializado en ACIDE.'];
        }

        $currentUser = isset($this->services['auth']) ? $this->services['auth']->validateRequest() : null;
        $currentRole = $currentUser ? $currentUser['role'] : null;

        // Seguridad: Solo admin/superadmin gestionan la tienda profesionalmente
        $protectedActions = [
            'create_product',
            'update_product',
            'delete_product',
            'update_stock',
            'update_payment_method',
            'create_coupon',
            'update_coupon',
            'delete_coupon',
            'update_shipping_method',
            'update_tax',
            'update_order_status',
            'update_legal_settings',
            'update_company_settings',
            'delete_tax',
            'bootstrap_store'
        ];

        if (in_array($action, $protectedActions) && php_sapi_name() !== 'cli') {
            $this->requirePermission($currentRole, array('superadmin', 'admin', 'administrador', 'super admin'));
        }

        // Acciones públicas (permitidas sin ser admin)
        $publicActions = ['create_payment_intent', 'list_payment_methods', 'revolut_webhook', 'validate_coupon'];
        // list_payment_methods ya es pública de facto si no está en protected, 
        // pero podemos ser explícitos si queremos.


        try {
            // Delegación atómica al motor STORE inyectado
            $result = $this->engine->executeAction($action, $args);

            // 🔒 Filtro de visibilidad: la categoría 'subscription' sólo la ve 'superadmin'.
            // Se aplica sobre el listado y sobre la lectura individual para que ni el TPV
            // ni el panel Admin vean estos productos a menos que el rol exacto sea superadmin.
            $isSuperadmin = strtolower(trim($currentRole ?? '')) === 'superadmin';
            if (!$isSuperadmin) {
                if ($action === 'list_products' && isset($result['data']) && is_array($result['data'])) {
                    $result['data'] = array_values(array_filter($result['data'], function ($p) {
                        return strtolower($p['category'] ?? '') !== 'subscription';
                    }));
                } elseif ($action === 'read_product' && isset($result['data']['category'])) {
                    if (strtolower($result['data']['category']) === 'subscription') {
                        return ['success' => false, 'error' => 'Producto no disponible.'];
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function requirePermission($currentRole, $allowedRoles)
    {
        $role = strtolower(trim($currentRole ?? ''));
        // Normalización para soportar roles con espacios como "super admin"
        $normalizedPaths = array_map(function ($r) {
            return strtolower($r);
        }, $allowedRoles);

        if (!$role || !in_array($role, $normalizedPaths)) {
            throw new Exception("Soberanía Insuficiente: Se requiere acceso de gestión comercial.");
        }
    }
}
