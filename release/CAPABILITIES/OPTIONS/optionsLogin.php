<?php
namespace Options;

/**
 * optionsLogin - defaults NO-SECRETOS de la capability LOGIN.
 *
 * Este archivo es de SOLO LECTURA para el resto del sistema. Los
 * valores aqui declarados son la single source of truth para parametros
 * no-criticos: TTL, costos Argon2, politica de password, tamanos de token.
 *
 * IMPORTANTE: secretos (jwt_secret, claves de firma, etc.) NO viven aqui.
 * Permanecen en spa/server/config/auth.json, que esta web-blocked por
 * .htaccess y excluido del repo (.gitignore). Si se necesita firma
 * adicional en el futuro, se promueve a STORAGE/.vault/login.json.
 *
 * Para cambiar un default: editas la constante. Para overridear en
 * produccion sin tocar codigo: define la clave en STORAGE/.vault/login.json
 * (no implementado todavia, planificado para fase de despliegue).
 */
class optionsLogin
{
    // Sesiones
    public const SESSION_TTL_SECONDS = 86400;        // 24h
    public const TOKEN_BYTES         = 32;           // -> 64 hex chars

    // Argon2id
    public const ARGON2_MEMORY_COST  = 65536;
    public const ARGON2_TIME_COST    = 4;
    public const ARGON2_THREADS      = 1;

    // Password policy
    public const PASSWORD_MIN_LENGTH  = 10;
    public const PASSWORD_MIN_CLASSES = 3;

    // Rate limit (operaciones criticas; otros scopes se pasan por parametro)
    public const RATE_LIMIT_LOGIN_PER_MIN = 5;

    // Bootstrap default - documentado, se cambia en primer login
    public const DEFAULT_BOOTSTRAP_PASSWORD = 'socola2026';
    public const DEFAULT_SUPERADMIN_EMAIL   = 'socola@socola.es';

    public static function get(string $key, $fallback = null)
    {
        $name = 'self::' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '_', $key));
        return defined($name) ? constant($name) : $fallback;
    }
}
