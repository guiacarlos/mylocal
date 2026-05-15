<?php
namespace Login;

require_once __DIR__ . '/../OPTIONS/optionsLoginRoles.php';
require_once __DIR__ . '/../OPTIONS/optionsLoginPermissions.php';

/**
 * LoginRoles - gate de permisos server-side.
 *
 * require() es la unica fuente de verdad para "este request puede continuar?".
 * El rol que viene del cliente (IndexedDB) es decorativo y NUNCA se confia
 * para decisiones de seguridad.
 *
 * Reglas:
 *  - Si $user es null  -> HTTP 401 + die.
 *  - Si rol no en allowed -> HTTP 403 + die.
 *  - Si rol no esta en optionsLoginRoles::WHITELIST -> HTTP 403 + die.
 */
class LoginRoles
{
    public static function require(?array $user, array $allowed): void
    {
        if (!$user) {
            http_response_code(401);
            if (function_exists('resp')) resp(false, null, 'Unauthorized');
            exit;
        }
        $role = strtolower((string) ($user['role'] ?? ''));
        if (!\Options\optionsLoginRoles::isValid($role)) {
            http_response_code(403);
            if (function_exists('resp')) resp(false, null, 'Forbidden: rol desconocido');
            exit;
        }
        $allowedLower = \array_map('strtolower', $allowed);
        if (!\in_array($role, $allowedLower, true)) {
            http_response_code(403);
            if (function_exists('resp')) resp(false, null, 'Forbidden: rol insuficiente');
            exit;
        }
    }

    public static function isValid(string $role): bool
    {
        return \Options\optionsLoginRoles::isValid($role);
    }

    public static function allows(string $role, string $action): bool
    {
        return \Options\optionsLoginPermissions::allow($role, $action);
    }
}
