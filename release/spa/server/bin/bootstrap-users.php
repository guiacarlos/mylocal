<?php
/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL AUTH LOCK - load-bearing                                 ║
   ║ Bootstrap auto. DEBE cargar lib.php Y handlers/auth.php.         ║
   ║ Antes de modificar, leer claude/AUTH_LOCK.md y verificar que     ║
   ║ spa/server/tests/test_login.php sigue pasando despues del cambio.║
   ╚══════════════════════════════════════════════════════════════════╝ */
/**
 * bootstrap-users.php — crea los usuarios por defecto de Socolá si no existen.
 * Ejecutar desde CLI: php server/bin/bootstrap-users.php
 * También se llama desde server/index.php en el primer arranque.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli' && !defined('BOOTSTRAP_INTERNAL')) {
    http_response_code(403);
    exit("Forbidden: solo CLI o uso interno.\n");
}

if (!defined('DATA_ROOT')) {
    define('DATA_ROOT', __DIR__ . '/../data');
}
if (!defined('CONFIG_ROOT')) {
    define('CONFIG_ROOT', __DIR__ . '/../config');
}
if (!defined('SERVER_ROOT')) {
    define('SERVER_ROOT', __DIR__ . '/..');
}

require_once __DIR__ . '/../lib.php';
// find_user_by_email vive en handlers/auth.php; cargar para uso CLI e interno.
require_once __DIR__ . '/../handlers/auth.php';

$defaults = [
    ['email' => 'socola@socola.es',   'name' => 'Socola Admin',    'role' => 'admin'],
    ['email' => 'sala@socola.es',     'name' => 'Editor Sala',     'role' => 'sala'],
    ['email' => 'cocina@socola.es',   'name' => 'Editor Cocina',   'role' => 'cocina'],
    ['email' => 'camarero@socola.es', 'name' => 'Editor Camarero', 'role' => 'camarero'],
];

$password = 'socola2026';
$argon2opts = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1];
$created = 0;

foreach ($defaults as $u) {
    $email = strtolower(trim($u['email']));
    if (find_user_by_email($email)) {
        if (PHP_SAPI === 'cli') echo "   [SKIP] {$email} ya existe\n";
        continue;
    }
    $id = 'u_' . bin2hex(random_bytes(8));
    data_put('users', $id, [
        'id'            => $id,
        'email'         => $email,
        'name'          => $u['name'],
        'role'          => $u['role'],
        'password_hash' => password_hash($password, PASSWORD_ARGON2ID, $argon2opts),
    ], true);
    if (PHP_SAPI === 'cli') echo "   [OK]   {$email} ({$u['role']}) creado\n";
    $created++;
}

if (PHP_SAPI === 'cli') echo "\nBootstrap completo. Usuarios nuevos: $created\n";
return $created;
