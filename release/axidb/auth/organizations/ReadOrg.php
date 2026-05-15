<?php
/**
 * AxiDB - ReadOrg (legacy scaffold).
 *
 * Subsistema: tests/../auth/organizations
 * Nota: heredado del motor ACIDE; sera absorbido por Op model y StorageDriver en
 *       fases futuras. Cambios no-triviales: hacerlo en la arquitectura nueva.
 */

require_once dirname(__DIR__) . '/Auth.php';

class ReadOrg
{
    private $auth;
    public function __construct()
    {
        $this->auth = new Auth();
    }

    public function execute($id, $adminToken)
    {
        return $this->auth->callAPI('POST', 'data/query', [
            'collection' => 'tenants',
            'where' => $id ? ['id' => $id] : [],
            'options' => []
        ], $adminToken);
    }
}
