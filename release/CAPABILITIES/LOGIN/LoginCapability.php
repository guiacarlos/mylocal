<?php
namespace Login;

require_once __DIR__ . '/LoginSanitize.php';
require_once __DIR__ . '/LoginRateLimit.php';
require_once __DIR__ . '/LoginPasswords.php';
require_once __DIR__ . '/LoginSessions.php';
require_once __DIR__ . '/LoginRoles.php';
require_once __DIR__ . '/LoginVault.php';
require_once __DIR__ . '/LoginBootstrap.php';
require_once __DIR__ . '/../OPTIONS/optionsLogin.php';

/**
 * LoginCapability - fachada publica.
 *
 * Esta es la UNICA superficie que el resto del sistema toca. Cualquier
 * feature que necesite auth importa este archivo y llama a estos metodos.
 *
 * NO se llaman directamente las clases internas (LoginPasswords, etc.)
 * desde fuera de CAPABILITIES/LOGIN/.
 *
 * Contrato (resumen, ver README.md):
 *   ::login($req)              -> ['user'=>..., 'token'=>...]      lanza ante fallo
 *   ::logout($user)            -> ['ok'=>true]
 *   ::sessionRefresh($user)    -> $user (renueva TTL en disco)
 *   ::register($req)           -> $user (rol 'cliente' fijo)
 *   ::resolveUser($bearer?)    -> array|null
 *   ::requireRole($user, [..]) -> void | http_403_die
 *   ::rateLimit($scope, $n)    -> void | http_429_die
 *   ::safeId/Email/Str/Int     -> sanitizadores load-bearing
 */
class LoginCapability
{
    /* ════════════════════════ Flujo principal ════════════════════════ */

    public static function login(array $req): array
    {
        LoginRateLimit::check('login', \Options\optionsLogin::RATE_LIMIT_LOGIN_PER_MIN);

        $email    = LoginSanitize::email($req['data']['email'] ?? $req['email'] ?? null);
        $password = (string) ($req['data']['password'] ?? $req['password'] ?? '');
        if ($password === '') {
            sleep(1);
            throw new \RuntimeException('Credenciales invalidas');
        }

        $user = LoginVault::findByEmail($email);
        $hash = $user['password_hash'] ?? LoginPasswords::dummyHash();

        if (!LoginPasswords::verify($password, $hash) || !$user) {
            sleep(1);
            throw new \RuntimeException('Credenciales invalidas');
        }

        if (LoginPasswords::needsRehash($hash)) {
            LoginVault::patch($user['id'], [
                'password_hash' => LoginPasswords::hash($password),
            ]);
        }

        $sess = LoginSessions::issue($user);
        unset($user['password_hash']);
        return ['user' => $user, 'token' => $sess['token']];
    }

    public static function logout(?array $user): array
    {
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+([A-Za-z0-9_\-]+)$/', $hdr, $m)) {
            LoginSessions::revoke($m[1]);
        }
        if ($user && !empty($user['id'])) {
            error_log('[auth] logout user=' . $user['id']);
        }
        return ['ok' => true];
    }

    /**
     * Rolling refresh: extiende el TTL de la sesion en disco. Token sin cambio.
     */
    public static function sessionRefresh(?array $user): array
    {
        if (!$user) throw new \RuntimeException('Sin sesion');
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+([A-Za-z0-9_\-]+)$/', $hdr, $m) && function_exists('data_put')) {
            $ttl = \Options\optionsLogin::SESSION_TTL_SECONDS;
            data_put('sessions', $m[1], ['expiresAt' => date('c', time() + $ttl)]);
        }
        return $user;
    }

    public static function register(array $req): array
    {
        LoginRateLimit::check('register', 10);

        $email    = LoginSanitize::email($req['data']['email'] ?? null);
        $password = (string) ($req['data']['password'] ?? '');
        $name     = LoginSanitize::str($req['data']['name'] ?? '', 120);

        LoginPasswords::assertStrength($password);

        if (LoginVault::findByEmail($email)) {
            throw new \RuntimeException('Ya existe un usuario con ese email');
        }

        $id = 'u_' . bin2hex(random_bytes(8));
        return LoginVault::upsert([
            'id'            => $id,
            'email'         => $email,
            'name'          => $name,
            'role'          => 'cliente',  // NUNCA aceptar role desde el body
            'password_hash' => LoginPasswords::hash($password),
        ]);
    }

    /* ════════════════════════ Resoluciones / gates ════════════════════════ */

    public static function resolveUser(?string $bearer = null): ?array
    {
        return LoginSessions::resolve($bearer);
    }

    public static function issueSession(array $user): array
    {
        return LoginSessions::issue($user);
    }

    public static function requireRole(?array $user, array $allowed): void
    {
        LoginRoles::require($user, $allowed);
    }

    public static function rateLimit(string $scope, int $perMinute): void
    {
        LoginRateLimit::check($scope, $perMinute);
    }

    /* ════════════════════════ Sanitize wrappers ════════════════════════ */

    public static function safeId(string $v): string             { return LoginSanitize::id($v); }
    public static function safeEmail(?string $v): string         { return LoginSanitize::email($v); }
    public static function safeStr(?string $v, int $max = 2000): string { return LoginSanitize::str($v, $max); }
    public static function safeInt($v, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
    {
        return LoginSanitize::int($v, $min, $max);
    }
}
