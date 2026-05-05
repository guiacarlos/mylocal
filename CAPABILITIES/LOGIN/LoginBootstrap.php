<?php
namespace Login;

require_once __DIR__ . '/LoginPasswords.php';
require_once __DIR__ . '/LoginVault.php';
require_once __DIR__ . '/../OPTIONS/optionsLogin.php';

/**
 * LoginBootstrap - auto-seed de usuarios default.
 *
 * Si data/users/ esta vacio crea los 4 usuarios del AUTH_LOCK contract:
 *   socola@socola.es     admin
 *   sala@socola.es       sala
 *   cocina@socola.es     cocina
 *   camarero@socola.es   camarero
 *
 * Todos con la password de bootstrap (optionsLogin::DEFAULT_BOOTSTRAP_PASSWORD).
 * El usuario las cambia en su primer login via UI.
 *
 * Idempotente: si un usuario ya existe lo salta sin tocarlo. Es seguro
 * llamarlo en cada arranque sin riesgo de pisar passwords cambiadas.
 */
class LoginBootstrap
{
    public const DEFAULT_USERS = [
        ['email' => 'socola@socola.es',   'name' => 'Socola Admin',    'role' => 'admin'],
        ['email' => 'sala@socola.es',     'name' => 'Editor Sala',     'role' => 'sala'],
        ['email' => 'cocina@socola.es',   'name' => 'Editor Cocina',   'role' => 'cocina'],
        ['email' => 'camarero@socola.es', 'name' => 'Editor Camarero', 'role' => 'camarero'],
    ];

    /**
     * Crea los default users que falten. Devuelve cuantos creo.
     * En SAPI 'cli' imprime trazas; en otro contexto (auto-bootstrap web) calla.
     */
    public static function run(): int
    {
        if (!\function_exists('data_put')) {
            throw new \LogicException('LoginBootstrap requiere lib.php (data_put)');
        }
        $password = \Options\optionsLogin::DEFAULT_BOOTSTRAP_PASSWORD;
        $created = 0;

        foreach (self::DEFAULT_USERS as $u) {
            $email = strtolower(trim($u['email']));
            if (LoginVault::findByEmail($email)) {
                if (\PHP_SAPI === 'cli') echo "   [SKIP] {$email} ya existe\n";
                continue;
            }
            $id = 'u_' . bin2hex(random_bytes(8));
            LoginVault::upsert([
                'id'            => $id,
                'email'         => $email,
                'name'          => $u['name'],
                'role'          => $u['role'],
                'password_hash' => LoginPasswords::hash($password),
            ]);
            if (\PHP_SAPI === 'cli') echo "   [OK]   {$email} ({$u['role']}) creado\n";
            $created++;
        }

        if (\PHP_SAPI === 'cli') echo "\nBootstrap completo. Usuarios nuevos: $created\n";
        return $created;
    }
}
