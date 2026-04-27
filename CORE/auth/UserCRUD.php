<?php

require_once __DIR__ . '/RoleManager.php';

/**
 * UserCRUD - Gestor Soberano de Usuarios
 * Responsabilidad: CRUD completo de usuarios con seguridad militar
 */
class UserCRUD
{
    private $crud;
    private $roleManager;

    public function __construct($crud)
    {
        $this->crud = $crud;
        $this->roleManager = new RoleManager($crud);
    }

    public function create($data, $creatorRole = null)
    {
        // Validaciones
        if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email válido es requerido");
        }

        if (!isset($data['password']) || strlen($data['password']) < 8) {
            throw new Exception("La contraseña debe tener al menos 8 caracteres");
        }

        // Verificar que el email no exista
        $existing = $this->findByEmail($data['email']);
        if ($existing) {
            throw new Exception("El email ya está registrado");
        }

        // Asignar rol por defecto si no se especifica
        $role = isset($data['role']) ? $data['role'] : 'student';

        // Verificar permisos del creador
        if ($creatorRole && !in_array($creatorRole, array('superadmin', 'admin'))) {
            throw new Exception("No tiene permisos para crear usuarios");
        }

        // Generar ID único
        $userId = 'user_' . uniqid() . '_' . time();

        // Preparar datos del usuario
        $userData = array(
            'id' => $userId,
            'email' => strtolower(trim($data['email'])),
            'password' => password_hash($data['password'], PASSWORD_BCRYPT, array('cost' => 12)),
            'name' => isset($data['name']) ? $data['name'] : '',
            'role' => $role,
            'status' => 'active',
            'phone' => isset($data['phone']) ? $data['phone'] : '',
            'address' => isset($data['address']) ? $data['address'] : '',
            'billing_info' => isset($data['billing_info']) ? $data['billing_info'] : array(),
            'subscription' => array(
                'tier' => $role,
                'status' => 'active',
                'started_at' => date('c'),
                'expires_at' => null
            ),
            'profile' => array(
                'avatar' => isset($data['avatar']) ? $data['avatar'] : '',
                'bio' => isset($data['bio']) ? $data['bio'] : '',
                'preferences' => isset($data['preferences']) ? $data['preferences'] : array()
            ),
            'security' => array(
                'two_factor_enabled' => false,
                'last_password_change' => date('c'),
                'failed_login_attempts' => 0,
                'locked_until' => null
            ),
            'metadata' => array(
                'created_at' => date('c'),
                'created_by' => $creatorRole ? $creatorRole : 'system',
                'last_login' => null,
                'login_count' => 0,
                'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
            )
        );

        // Guardar usuario
        $result = $this->crud->update('users', $userId, $userData);

        // No devolver contraseña
        unset($result['password']);

        return $result;
    }

    public function read($userId)
    {
        $user = $this->crud->read('users', $userId);

        if ($user) {
            unset($user['password']);
        }

        return $user;
    }

    public function update($userId, $data, $updaterRole = null)
    {
        $user = $this->crud->read('users', $userId);

        if (!$user) {
            throw new Exception("Usuario no encontrado");
        }

        // Campos que no se pueden actualizar directamente
        $protected = array('id', 'password', 'created_at', 'created_by');

        foreach ($protected as $field) {
            unset($data[$field]);
        }

        // Validar email si se está cambiando
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email inválido");
            }

            $existing = $this->findByEmail($data['email']);
            if ($existing && $existing['id'] !== $userId) {
                throw new Exception("El email ya está en uso");
            }

            $data['email'] = strtolower(trim($data['email']));
        }

        // Verificar permisos para cambiar rol
        if (isset($data['role']) && $data['role'] !== $user['role']) {
            if (!$updaterRole || !in_array($updaterRole, array('superadmin', 'admin'))) {
                throw new Exception("No tiene permisos para cambiar roles");
            }
        }

        $data['metadata']['updated_at'] = date('c');
        $data['metadata']['updated_by'] = $updaterRole ? $updaterRole : 'system';

        $result = $this->crud->update('users', $userId, $data);
        unset($result['password']);

        return $result;
    }

    public function delete($userId, $deleterRole = null)
    {
        if (!$deleterRole || !in_array($deleterRole, array('superadmin', 'admin'))) {
            throw new Exception("No tiene permisos para eliminar usuarios");
        }

        $user = $this->crud->read('users', $userId);

        if (!$user) {
            throw new Exception("Usuario no encontrado");
        }

        // No permitir eliminar superadmin
        if ($user['role'] === 'superadmin') {
            throw new Exception("No se puede eliminar un superadministrador");
        }

        return $this->crud->delete('users', $userId);
    }

    public function list($filter = null)
    {
        $users = $this->crud->list('users');

        // Remover contraseñas
        $users = array_map(function ($user) {
            unset($user['password']);
            return $user;
        }, $users);

        // Aplicar filtros
        if ($filter) {
            if (isset($filter['role'])) {
                $users = array_filter($users, function ($user) use ($filter) {
                    return $user['role'] === $filter['role'];
                });
            }

            if (isset($filter['status'])) {
                $users = array_filter($users, function ($user) use ($filter) {
                    return $user['status'] === $filter['status'];
                });
            }
        }

        return array_values($users);
    }

    public function changePassword($userId, $currentPassword, $newPassword)
    {
        $user = $this->crud->read('users', $userId);

        if (!$user) {
            throw new Exception("Usuario no encontrado");
        }

        if (!password_verify($currentPassword, $user['password'])) {
            throw new Exception("Contraseña actual incorrecta");
        }

        if (strlen($newPassword) < 8) {
            throw new Exception("La nueva contraseña debe tener al menos 8 caracteres");
        }

        $data = array(
            'password' => password_hash($newPassword, PASSWORD_BCRYPT, array('cost' => 12)),
            'security' => array_merge($user['security'], array(
                'last_password_change' => date('c')
            ))
        );

        return $this->crud->update('users', $userId, $data);
    }

    public function updateProfile($userId, $profileData)
    {
        $user = $this->crud->read('users', $userId);

        if (!$user) {
            throw new Exception("Usuario no encontrado");
        }

        $allowedFields = array('name', 'phone', 'address', 'profile', 'billing_info');
        $updateData = array();

        foreach ($allowedFields as $field) {
            if (isset($profileData[$field])) {
                $updateData[$field] = $profileData[$field];
            }
        }

        return $this->crud->update('users', $userId, $updateData);
    }

    private function findByEmail($email)
    {
        $users = $this->crud->list('users');

        foreach ($users as $user) {
            if (strtolower($user['email']) === strtolower($email)) {
                return $user;
            }
        }

        return null;
    }
}
