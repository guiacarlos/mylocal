<?php

/**
 * RoleManager - Gestor Soberano de Roles y Permisos
 * Responsabilidad: Definir y gestionar roles del sistema
 */
class RoleManager
{
    private $crud;

    // Roles del sistema (inmutables)
    const SYSTEM_ROLES = [
        'superadmin' => [
            'name' => 'Super Administrador',
            'level' => 1000,
            'permissions' => ['*'], // Acceso total
            'immutable' => true
        ],
        'admin' => [
            'name' => 'Administrador',
            'level' => 900,
            'permissions' => [
                'users.create',
                'users.read',
                'users.update',
                'users.delete',
                'roles.create',
                'roles.read',
                'roles.update',
                'roles.delete',
                'content.create',
                'content.read',
                'content.update',
                'content.delete',
                'themes.manage',
                'plugins.manage',
                'settings.manage'
            ],
            'immutable' => true
        ],
        'editor' => [
            'name' => 'Editor',
            'level' => 700,
            'permissions' => [
                'content.create',
                'content.read',
                'content.update',
                'content.delete',
                'media.upload',
                'media.manage'
            ],
            'immutable' => true
        ],
        'author' => [
            'name' => 'Autor',
            'level' => 500,
            'permissions' => [
                'content.create',
                'content.read',
                'content.update.own',
                'media.upload'
            ],
            'immutable' => true
        ]
    ];

    // Roles de suscripción (configurables)
    const SUBSCRIPTION_ROLES = [
        'platinum' => [
            'name' => 'Platinum',
            'level' => 400,
            'permissions' => [
                'courses.access.all',
                'courses.download',
                'support.priority',
                'features.advanced',
                'storage.unlimited'
            ],
            'subscription_tier' => 'platinum',
            'price_monthly' => 99.99,
            'price_annual' => 999.99
        ],
        'premium' => [
            'name' => 'Premium',
            'level' => 300,
            'permissions' => [
                'courses.access.premium',
                'courses.download',
                'support.standard',
                'features.advanced',
                'storage.50gb'
            ],
            'subscription_tier' => 'premium',
            'price_monthly' => 243.00,
            'price_annual' => 243.00
        ],
        'pro' => [
            'name' => 'Pro',
            'level' => 200,
            'permissions' => [
                'courses.access.pro',
                'support.standard',
                'features.standard',
                'storage.10gb'
            ],
            'subscription_tier' => 'pro',
            'price_monthly' => 54.00,
            'price_annual' => 54.00
        ],
        'standard' => [
            'name' => 'Estándar',
            'level' => 100,
            'permissions' => [
                'courses.access.basic',
                'support.community',
                'features.basic',
                'storage.5gb'
            ],
            'subscription_tier' => 'standard',
            'price_monthly' => 27.00,
            'price_annual' => 27.00
        ],
        'basic' => [
            'name' => 'Básico',
            'level' => 50,
            'permissions' => [
                'courses.access.free',
                'support.community',
                'storage.1gb'
            ],
            'subscription_tier' => 'basic',
            'price_monthly' => 0,
            'price_annual' => 0
        ],
        'student' => [
            'name' => 'Estudiante',
            'level' => 10,
            'permissions' => [
                'courses.access.enrolled',
                'profile.manage'
            ],
            'immutable' => true
        ],
        'guest' => [
            'name' => 'Invitado',
            'level' => 1,
            'permissions' => ['content.read.public'],
            'immutable' => true
        ]
    ];

    public function __construct($crud, $autoInitialize = true)
    {
        $this->crud = $crud;

        if ($autoInitialize && $crud) {
            $this->initializeRoles();
        }
    }

    private function initializeRoles()
    {
        $allRoles = array();

        foreach (self::SYSTEM_ROLES as $id => $data) {
            $allRoles[$id] = $data;
        }

        foreach (self::SUBSCRIPTION_ROLES as $id => $data) {
            $allRoles[$id] = $data;
        }

        foreach ($allRoles as $roleId => $roleData) {
            $existing = $this->crud->read('roles', $roleId);

            if (!$existing) {
                $roleData['id'] = $roleId;
                $roleData['created_at'] = date('c');
                $this->crud->update('roles', $roleId, $roleData);
            }
        }
    }

    public function createRole($data)
    {
        if (!isset($data['id']) || !isset($data['name'])) {
            throw new Exception("ID y nombre son requeridos");
        }

        if (isset(self::SYSTEM_ROLES[$data['id']]) || isset(self::SUBSCRIPTION_ROLES[$data['id']])) {
            throw new Exception("No se puede crear un rol con ID reservado del sistema");
        }

        $roleData = array(
            'id' => $data['id'],
            'name' => $data['name'],
            'level' => isset($data['level']) ? $data['level'] : 50,
            'permissions' => isset($data['permissions']) ? $data['permissions'] : array(),
            'immutable' => false,
            'created_at' => date('c')
        );

        return $this->crud->update('roles', $data['id'], $roleData);
    }

    public function updateRole($roleId, $data)
    {
        $role = $this->crud->read('roles', $roleId);

        if (!$role) {
            throw new Exception("Rol no encontrado");
        }

        unset($data['id']);
        unset($data['immutable']);

        return $this->crud->update('roles', $roleId, $data);
    }

    public function deleteRole($roleId)
    {
        $role = $this->crud->read('roles', $roleId);

        if (!$role) {
            throw new Exception("Rol no encontrado");
        }

        return $this->crud->delete('roles', $roleId);
    }

    public function listRoles($filter = null)
    {
        $roles = $this->crud->list('roles');

        if ($filter === 'subscription') {
            $filtered = array_filter($roles, function ($role) {
                return isset($role['subscription_tier']);
            });
            return array_values($filtered);
        }

        if ($filter === 'system') {
            $filtered = array_filter($roles, function ($role) {
                return isset($role['immutable']) && $role['immutable'];
            });
            return array_values($filtered);
        }

        return array_values($roles);
    }

    public function hasPermission($roleId, $permission)
    {
        $role = $this->crud->read('roles', $roleId);

        if (!$role) {
            return false;
        }

        if (isset($role['permissions']) && in_array('*', $role['permissions'])) {
            return true;
        }

        if (isset($role['permissions']) && in_array($permission, $role['permissions'])) {
            return true;
        }

        $permissionParts = explode('.', $permission);
        $wildcard = $permissionParts[0] . '.*';

        if (isset($role['permissions']) && in_array($wildcard, $role['permissions'])) {
            return true;
        }

        return false;
    }
}
