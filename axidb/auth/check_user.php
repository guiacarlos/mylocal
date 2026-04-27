<?php
/**
 * AxiDB - check_user (legacy scaffold).
 *
 * Subsistema: tests/../auth
 * Nota: heredado del motor ACIDE; sera absorbido por Op model y StorageDriver en
 *       fases futuras. Cambios no-triviales: hacerlo en la arquitectura nueva.
 */

require_once 'UserManager.php';

$um = new UserManager();
$user = $um->getUserByEmail('info@gestasai.com');

if ($user) {
    echo "âœ… Usuario encontrado\n";
    echo "ID: {$user['id']}\n";
    echo "Email: {$user['email']}\n";
    echo "Rol: {$user['role']}\n";
} else {
    echo "âŒ Usuario NO encontrado\n";
}
