<?php
/**
 * AxiDB - CreateUser (legacy scaffold).
 *
 * Subsistema: tests/../auth/users
 * Nota: heredado del motor ACIDE; sera absorbido por Op model y StorageDriver en
 *       fases futuras. Cambios no-triviales: hacerlo en la arquitectura nueva.
 */

require_once dirname(__DIR__) . '/Auth.php';

class CreateUser
{
    private $auth;

    public function __construct()
    {
        $this->auth = new Auth();
    }

    public function execute($userData, $adminToken)
    {
        // Validar datos mínimos
        if (empty($userData['email']) || empty($userData['password'])) {
            return ['success' => false, 'error' => 'Email y Password requeridos'];
        }

        // Llamar a la Nave Nodriza (Universal API Protocol)
        return $this->auth->callAPI('POST', 'data/insert', [
            'collection' => 'users',
            'document' => $userData
        ], $adminToken);
    }
}
