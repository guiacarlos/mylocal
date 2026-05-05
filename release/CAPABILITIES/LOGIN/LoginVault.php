<?php
namespace Login;

/**
 * LoginVault - lectura de usuarios autenticables.
 *
 * Coleccion: users (data/users/u_*.json). Cada doc:
 *   id (slug derivado de email), email, password_hash, role, name, active, ...
 *
 * Stub: usa data_all de lib.php para escanear. La logica final entrara en
 * paso 4 cuando se mueva tambien current_user.
 */
class LoginVault
{
    public static function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') return null;
        if (!function_exists('data_all')) return null;
        foreach (data_all('users') as $u) {
            if (strtolower((string) ($u['email'] ?? '')) === $email) {
                return $u;
            }
        }
        return null;
    }

    public static function findById(string $id): ?array
    {
        if (!function_exists('data_get')) return null;
        return data_get('users', $id);
    }

    /**
     * Crea o reemplaza un usuario y devuelve el doc persistido (sin password_hash).
     */
    public static function upsert(array $user): array
    {
        if (!function_exists('data_put')) {
            throw new \LogicException('LoginVault requiere data_put (lib.php)');
        }
        $persisted = data_put('users', $user['id'], $user, true);
        unset($persisted['password_hash']);
        return $persisted;
    }

    /**
     * Patch parcial (sin _REPLACE_) - util para password_needs_rehash y similares.
     */
    public static function patch(string $id, array $patch): array
    {
        if (!function_exists('data_put')) {
            throw new \LogicException('LoginVault requiere data_put (lib.php)');
        }
        return data_put('users', $id, $patch);
    }

    public static function listAll(): array
    {
        if (!function_exists('data_all')) return [];
        return data_all('users');
    }
}
