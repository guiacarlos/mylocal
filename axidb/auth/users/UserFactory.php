<?php

namespace ACIDE\Auth\Users;

/**
 *  UserFactory: El Forjador de Identidades.
 * Responsabilidad: Creación y registro de nuevos usuarios soberanos usando el Motor ACIDE.
 */
class UserFactory
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

    public function create($email, $password, $name, $role = 'viewer')
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return ['success' => false, 'error' => 'Email inválido'];
        if (strlen($password) < 8)
            return ['success' => false, 'error' => 'Password demasiado corto'];
        if ($this->finder->getUserByEmail($email))
            return ['success' => false, 'error' => 'Email ya registrado'];

        $user = [
            'id' => $this->generateUUID(),
            'email' => strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'name' => $this->sanitize($name),
            'role' => $role,
            'status' => 'active',
            'metadata' => new \stdClass(),
            'created_at' => date('c'),
            'updated_at' => date('c'),
            'last_login' => null
        ];

        // Guardar via ACIDE para obtener versionado y logs
        $this->save($user['id'], $user);

        // Registrar en el índice email -> ID
        $this->registry->addToIndex($email, $user['id']);

        unset($user['password_hash']);
        return ['success' => true, 'user' => $user];
    }

    private function save($id, $data)
    {
        if (isset($this->services['crud'])) {
            return $this->services['crud']->update('.vault/users', $id, $data);
        }

        $file = $this->registry->getUsersDir() . '/' . $id . '.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
}
