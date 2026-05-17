<?php
/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL AUTH LOCK - thin dispatcher                              ║
   ║ La logica vive en CAPABILITIES/LOGIN/. Este archivo solo enruta. ║
   ║ Antes de modificar, leer:                                        ║
   ║   - claude/AUTH_LOCK.md                                          ║
   ║   - CAPABILITIES/LOGIN/README.md                                 ║
   ║ Y verificar que spa/server/tests/test_login.php sigue pasando.   ║
   ╚══════════════════════════════════════════════════════════════════╝ */

declare(strict_types=1);

require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../../../CAPABILITIES/LOGIN/LoginCapability.php';

function handle_auth_login(array $req): array
{
    return \Login\LoginCapability::login($req);
}

function handle_auth_logout(?array $user): array
{
    return \Login\LoginCapability::logout($user);
}

function handle_auth_session(?array $user): array
{
    return \Login\LoginCapability::sessionRefresh($user);
}

function handle_public_register(array $req): array
{
    return \Login\LoginCapability::register($req);
}

/* ════════════════════════ Shims para CLI / bootstrap ════════════════════════
   create-admin.php y bootstrap-users.php llaman estas funciones globales.
   La logica canonica vive en CAPABILITIES/LOGIN/. Estos shims solo existen
   para mantener los scripts CLI sin tener que reescribirlos en este paso.
   bootstrap-users.php se reescribira completo en el paso 6 de la migracion. */

function handle_password_reset(string $action, array $req): array
{
    require_once realpath(__DIR__ . '/../../../CAPABILITIES/LOGIN/LoginPasswordReset.php');
    if ($action === 'auth_forgot_password') {
        return \Login\LoginPasswordReset::requestReset($req);
    }
    return \Login\LoginPasswordReset::resetPassword($req);
}

function handle_auth_change_password(array $req, array $user): array
{
    require_once realpath(__DIR__ . '/../../../CAPABILITIES/LOGIN/LoginVault.php');
    require_once realpath(__DIR__ . '/../../../CAPABILITIES/LOGIN/LoginPasswords.php');

    $current  = (string) ($req['data']['current_password'] ?? '');
    $newPass  = (string) ($req['data']['new_password']     ?? '');
    $confirm  = (string) ($req['data']['confirm_password'] ?? '');

    if ($current === '' || $newPass === '') {
        return ['ok' => false, 'error' => 'Faltan campos requeridos'];
    }
    if ($newPass !== $confirm) {
        return ['ok' => false, 'error' => 'Las contraseñas no coinciden'];
    }

    $stored = \Login\LoginVault::findById($user['id'] ?? '');
    if (!$stored || !password_verify($current, (string)($stored['password_hash'] ?? ''))) {
        return ['ok' => false, 'error' => 'Contraseña actual incorrecta'];
    }

    try {
        \Login\LoginPasswords::assertStrength($newPass);
    } catch (\Exception $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }

    \Login\LoginVault::updateHash($user['id'], password_hash($newPass, PASSWORD_ARGON2ID));
    return ['ok' => true, 'message' => 'Contraseña actualizada correctamente'];
}

function find_user_by_email(string $email): ?array
{
    return \Login\LoginVault::findByEmail($email);
}

function assert_password_strength(string $pw): void
{
    \Login\LoginPasswords::assertStrength($pw);
}
