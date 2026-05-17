<?php

namespace ACIDE\Auth\Users;

/**
 *  UserEditor: El Maquetador de Perfiles.
 * Responsabilidad: Actualización de datos y gestión de credenciales usando ACIDE Core.
 */
class UserEditor
{
    use UserTools;

    private $registry;
    private $finder;
    private $services;

    public function __construct(UserRegistry $registry, UserFinder $finder, $services = [])
    {
        $this->registry = $registry;
        $this->finder = $finder;
        $this->services = $services;
    }

    public function update($id, $updates)
    {
        $user = $this->finder->getUserById($id);
        if (!$user)
            return ['success' => false, 'error' => 'No encontrado'];

        // Solo estos campos son inmutables desde el exterior — 'role' incluido para prevenir escalación de privilegios
        $protected = ['id', 'password_hash', 'email', 'created_at', 'last_login', 'role'];

        foreach ($updates as $key => $val) {
            if (!in_array($key, $protected)) {
                $user[$key] = is_string($val) ? $this->sanitize($val) : $val;
            }
        }

        $user['updated_at'] = date('c');
        $this->save($id, $user);

        unset($user['password_hash']);
        return ['success' => true, 'user' => $user];
    }

    public function changePassword($id, $newPassword)
    {
        if (strlen($newPassword) < 8)
            return ['success' => false, 'error' => 'Password muy corto'];
        $user = $this->finder->getUserById($id);
        if (!$user)
            return ['success' => false, 'error' => 'No encontrado'];

        $user['password_hash'] = password_hash($newPassword, PASSWORD_ARGON2ID);
        $user['updated_at'] = date('c');
        $this->save($id, $user);

        return ['success' => true, 'message' => 'Contraseña actualizada'];
    }

    /**
     * persistent save using ACIDE Complexity (Versioning, Logging, Indexing)
     */
    private function save($id, $data)
    {
        if (isset($this->services['crud'])) {
            // Utilizamos la potencia de ACIDE: .vault/users como colección soberana
            return $this->services['crud']->update('.vault/users', $id, $data);
        }

        // Fallback Soberano si no hay servicios
        $file = $this->registry->getUsersDir() . '/' . $id . '.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
}
