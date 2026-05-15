<?php
/**
 * AxiDB - ReadUser (legacy scaffold).
 *
 * Subsistema: tests/../auth/users
 * Nota: heredado del motor ACIDE; sera absorbido por Op model y StorageDriver en
 *       fases futuras. Cambios no-triviales: hacerlo en la arquitectura nueva.
 */

require_once dirname(__DIR__) . '/Auth.php';

class ReadUser
{
    private $auth;

    public function __construct()
    {
        $this->auth = new Auth();
    }

    public function execute($id, $adminToken)
    {
        $payload = [
            'collection' => 'users',
            'where' => $id ? ['id' => $id] : [],
            'options' => []
        ];
        return $this->auth->callAPI('POST', 'data/query', $payload, $adminToken);
    }
}
