<?php
/**
 * AxiDB - UpdateOrg (legacy scaffold).
 *
 * Subsistema: tests/../auth/organizations
 * Nota: heredado del motor ACIDE; sera absorbido por Op model y StorageDriver en
 *       fases futuras. Cambios no-triviales: hacerlo en la arquitectura nueva.
 */

require_once dirname(__DIR__) . '/Auth.php';

class UpdateOrg
{
    private $auth;
    public function __construct()
    {
        $this->auth = new Auth();
    }

    public function execute($id, $data, $adminToken)
    {
        if (!$id)
            return ['success' => false, 'error' => 'ID requerido'];
        return $this->auth->callAPI('POST', 'data/update', [
            'collection' => 'tenants',
            'where' => ['id' => $id],
            'updates' => $data
        ], $adminToken);
    }
}
