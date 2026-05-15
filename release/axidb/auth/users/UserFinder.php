<?php

namespace ACIDE\Auth\Users;

/**
 *  UserFinder: El Rastreador de Identidades.
 * Responsabilidad: Localizar y cargar perfiles de usuario.
 */
class UserFinder
{
    private $registry;

    public function __construct(UserRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function getUserById($id)
    {
        $file = $this->registry->getUsersDir() . '/' . $id . '.json';
        if (!file_exists($file))
            return null;
        return json_decode(file_get_contents($file), true);
    }

    public function getUserByEmail($email)
    {
        $index = $this->registry->getIndex();
        $email = strtolower(trim($email));
        if (!isset($index[$email]))
            return null;
        return $this->getUserById($index[$email]);
    }
}
