<?php
/**
 * AxiDB - UpdateUser (legacy scaffold).
 *
 * Subsistema: tests/../auth/users
 * Nota: heredado del motor ACIDE; sera absorbido por Op model y StorageDriver en
 *       fases futuras. Cambios no-triviales: hacerlo en la arquitectura nueva.
 */

require_once dirname(__DIR__) . '/Auth.php';

class UpdateUser
{
    private $auth;

    public function __construct()
    {
        $this->auth = new Auth();
    }

    public function execute($id, $userData, $adminToken)
    {
        if (!$id)
            return ['success' => false, 'error' => 'ID requerido'];

        return $this->auth->callAPI('POST', 'data/update', [
            'collection' => 'users',
            'where' => ['id' => $id],
            'updates' => $userData
        ], $adminToken);
    }
}
