<?php
namespace Login;

require_once __DIR__ . '/../OPTIONS/optionsLogin.php';

/**
 * LoginSessions - emit / resolve / revoke bearer.
 *
 * Tokens: random_bytes(TOKEN_BYTES) -> bin2hex (64 chars con default 32).
 * Persistidos en spa/server/data/sessions/<token>.json. Schema usado por
 * el flujo historico:
 *   userId, role, email, ua_hash, createdAt (str ISO), expiresAt (str ISO),
 *   ip
 *
 * Mantengo los nombres camelCase + ISO strings para no romper compatibilidad
 * con sesiones ya emitidas. Si en el futuro se quiere consolidar a snake_case,
 * sera un paso aparte con migracion explicita.
 */
class LoginSessions
{
    /**
     * Emite un token nuevo y lo persiste. Devuelve {token, expires_at_iso}.
     */
    public static function issue(array $user): array
    {
        $bytes  = \Options\optionsLogin::TOKEN_BYTES;
        $ttl    = \Options\optionsLogin::SESSION_TTL_SECONDS;
        $token  = bin2hex(random_bytes($bytes));
        $now    = time();
        $expires = $now + $ttl;

        if (!function_exists('data_put')) {
            throw new \LogicException('LoginSessions requiere data_put (lib.php)');
        }
        data_put('sessions', $token, [
            'userId'    => $user['id'] ?? null,
            'role'      => $user['role'] ?? null,
            'email'     => $user['email'] ?? null,
            'ua_hash'   => self::uaHash(),
            'ip'        => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'createdAt' => date('c', $now),
            'expiresAt' => date('c', $expires),
        ], true);

        return ['token' => $token, 'expires_at' => $expires];
    }

    /**
     * Resuelve el bearer del header Authorization a un usuario.
     * Aplica chequeo de expiracion y de UA fingerprint con limpieza perezosa.
     */
    public static function resolve(?string $bearer = null): ?array
    {
        $token = self::extractBearer($bearer);
        if ($token === '') return null;
        if (!function_exists('data_get') || !function_exists('data_delete')) return null;

        $session = data_get('sessions', $token);
        if (!$session) return null;

        if (!empty($session['expiresAt']) && strtotime($session['expiresAt']) < time()) {
            data_delete('sessions', $token);
            return null;
        }

        $uaStored = $session['ua_hash'] ?? null;
        if ($uaStored && $uaStored !== self::uaHash()) {
            data_delete('sessions', $token);
            return null;
        }

        $user = data_get('users', (string) ($session['userId'] ?? ''));
        if (!$user) return null;
        unset($user['password_hash']);
        return $user;
    }

    public static function revoke(string $bearer): void
    {
        $token = self::extractBearer($bearer);
        if ($token === '') return;
        if (function_exists('data_delete')) {
            data_delete('sessions', $token);
        }
    }

    public static function extractBearer(?string $given): string
    {
        if ($given !== null && $given !== '') {
            if (preg_match('/^Bearer\s+([A-Za-z0-9_\-]+)$/', $given, $m)) return $m[1];
            if (preg_match('/^[A-Za-z0-9_\-]{16,}$/', $given)) return $given;
        }
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+([A-Za-z0-9_\-]+)$/', $hdr, $m)) return $m[1];
        return '';
    }

    public static function uaHash(): string
    {
        return hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }
}
