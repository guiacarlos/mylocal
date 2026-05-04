<?php

namespace ACIDE\Auth\Users;

/**
 *  UserAuthenticator: El Guardián de Accesos.
 * Responsabilidad: Validación de credenciales y trazabilidad de login usando el motor ACIDE.
 */
class UserAuthenticator
{
    private $finder;
    private $registry;
    private $services;

    public function __construct(UserFinder $finder, UserRegistry $registry, $services = [])
    {
        $this->finder = $finder;
        $this->registry = $registry;
        $this->services = $services;
    }

    public function verify($email, $password)
    {
        $user = $this->finder->getUserByEmail($email);
        if (!$user)
            return ['success' => false, 'error' => 'Usuario no encontrado'];
        if ($user['status'] !== 'active')
            return ['success' => false, 'error' => 'Cuenta inactiva'];

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Contraseña incorrecta'];
        }

        $this->updateLastLogin($user['id']);
        unset($user['password_hash']);
        return ['success' => true, 'user' => $user];
    }

    private function updateLastLogin($userId)
    {
        $user = $this->finder->getUserById($userId);
        if ($user) {
            $user['last_login'] = date('c');
            if (isset($this->services['crud'])) {
                $this->services['crud']->update('.vault/users', $userId, $user);
            } else {
                $file = $this->registry->getUsersDir() . '/' . $userId . '.json';
                file_put_contents($file, json_encode($user, JSON_PRETTY_PRINT));
            }
        }
    }
}
