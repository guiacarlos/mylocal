<?php

require_once __DIR__ . '/BaseHandler.php';
require_once __DIR__ . '/../../auth/RoleManager.php';

/**
 * RoleHandler - Manejador de operaciones de roles
 * Responsabilidad: Exponer CRUD de roles a través del túnel ACIDE
 */
class RoleHandler extends BaseHandler
{
    private $roleManager;

    public function __construct($services)
    {
        parent::__construct($services);
        $this->roleManager = new RoleManager($services['crud']);
    }

    public function execute($action, $args = array())
    {
        // Obtener usuario actual de la sesión (vía Auth service)
        $currentUser = isset($this->services['auth']) ? $this->services['auth']->validateRequest() : null;
        $currentRole = $currentUser ? $currentUser['role'] : null;

        switch ($action) {
            case 'create_role':
                $this->requirePermission($currentRole, array('superadmin', 'admin'));
                return $this->roleManager->createRole($args);

            case 'read_role':
                $roleId = isset($args['id']) ? $args['id'] : null;
                return $this->services['crud']->read('roles', $roleId);

            case 'update_role':
                $this->requirePermission($currentRole, array('superadmin', 'admin'));
                $roleId = isset($args['id']) ? $args['id'] : null;
                return $this->roleManager->updateRole($roleId, $args);

            case 'delete_role':
                $this->requirePermission($currentRole, array('superadmin', 'admin'));
                $roleId = isset($args['id']) ? $args['id'] : null;
                return $this->roleManager->deleteRole($roleId);

            case 'list_roles':
                $filter = isset($args['filter']) ? $args['filter'] : null;
                return ['success' => true, 'data' => $this->roleManager->listRoles($filter)];

            case 'check_permission':
                $roleId = isset($args['role']) ? $args['role'] : null;
                $permission = isset($args['permission']) ? $args['permission'] : null;
                return array(
                    'has_permission' => $this->roleManager->hasPermission($roleId, $permission)
                );

            default:
                throw new Exception("Acción de rol no reconocida: " . $action);
        }
    }

    private function requirePermission($currentRole, $allowedRoles)
    {
        if (!$currentRole || !in_array($currentRole, $allowedRoles)) {
            throw new Exception("Permisos insuficientes");
        }
    }
}
