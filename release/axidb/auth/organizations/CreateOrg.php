<?php
/**
 * AxiDB - CreateOrg (legacy scaffold).
 *
 * Subsistema: tests/../auth/organizations
 * Nota: heredado del motor ACIDE; sera absorbido por Op model y StorageDriver en
 *       fases futuras. Cambios no-triviales: hacerlo en la arquitectura nueva.
 */

require_once dirname(__DIR__) . '/Auth.php';

class CreateOrg
{
    private $auth;
    public function __construct()
    {
        $this->auth = new Auth();
    }

    public function execute($orgData, $adminToken)
    {
        if (empty($orgData['name']))
            return ['success' => false, 'error' => 'Nombre requerido'];
        return $this->auth->callAPI('POST', 'data/insert', [
            'collection' => 'tenants',
            'document' => $orgData
        ], $adminToken);
    }
}
