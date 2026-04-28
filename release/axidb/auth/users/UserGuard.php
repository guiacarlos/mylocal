<?php

namespace ACIDE\Auth\Users;

/**
 *  UserGuard: El Centinela del Búnker.
 * Responsabilidad: Limpieza de identidades y reportes de usuarios usando ACIDE Core.
 */
class UserGuard
{
    private $registry;
    private $finder;
    private $services;

    public function __construct(UserRegistry $registry, UserFinder $finder, $services = [])
    {
        $this->registry = $registry;
        $this->finder = $finder;
        $this->services = $services;
    }

    public function delete($id)
    {
        $user = $this->finder->getUserById($id);
        if (!$user)
            return ['success' => false, 'error' => 'No encontrado'];

        // Eliminar via ACIDE CRUD (esto puede disparar logs de auditoría)
        if (isset($this->services['crud'])) {
            $this->services['crud']->delete('.vault/users', $id);
        } else {
            $file = $this->registry->getUsersDir() . '/' . $id . '.json';
            if (file_exists($file))
                unlink($file);
        }

        $this->registry->removeFromIndex($user['email']);

        return ['success' => true, 'message' => 'Eliminado'];
    }

    public function listAll()
    {
        $index = $this->registry->getIndex();
        $users = [];
        foreach ($index as $email => $id) {
            $user = $this->finder->getUserById($id);
            if ($user) {
                unset($user['password_hash']);
                $users[] = $user;
            }
        }
        return ['success' => true, 'users' => $users];
    }
}
