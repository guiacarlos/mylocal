<?php

require_once __DIR__ . '/BaseHandler.php';

/**
 * 📱 QRHandler - El Facilitador de Pedidos por Mesa.
 * Responsabilidad: Delegar las acciones de generación de QR y recepción de pedidos externos.
 */
class QRHandler extends BaseHandler
{
    private $engine;

    public function __construct($services)
    {
        parent::__construct($services);
        // El motor QR ya ha sido inyectado en el contenedor de servicios por ACIDE.php
        $this->engine = isset($this->services['qr']) ? $this->services['qr'] : null;
    }

    public function execute($action, $args = array())
    {
        if (!$this->engine) {
            return ['success' => false, 'error' => 'Motor QR no inicializado en ACIDE.'];
        }

        try {
            // Acciones públicas para el portal del cliente (Móvil/QR) y el TPV
            $publicActions = [
                'process_external_order',
                'get_table_order',
                'update_table_cart',
                'clear_table',
                'table_request',
                'get_table_requests',
                'acknowledge_request',
                'create_revolut_payment',
                'check_revolut_payment'
            ];

            // Si la acción no es pública, validamos sesión para admin
            if (!in_array($action, $publicActions) && php_sapi_name() !== 'cli') {
                $currentUser = isset($this->services['auth']) ? $this->services['auth']->validateRequest() : null;
                $currentRole = $currentUser ? $currentUser['role'] : null;

                $this->requirePermission($currentRole, array('superadmin', 'admin', 'administrador', 'super admin'));
            }

            // Delegación al motor QR
            return $this->engine->executeAction($action, $args);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function requirePermission($currentRole, $allowedRoles)
    {
        $role = strtolower(trim($currentRole ?? ''));
        $normalizedPaths = array_map(function ($r) {
            return strtolower($r);
        }, $allowedRoles);

        if (!$role || !in_array($role, $normalizedPaths)) {
            throw new Exception("Soberanía Insuficiente: Se requiere acceso de administrador para gestionar QRs.");
        }
    }
}
