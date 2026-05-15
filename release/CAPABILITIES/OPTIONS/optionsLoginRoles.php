<?php
namespace Options;

/**
 * optionsLoginRoles - whitelist de roles validos.
 *
 * Cualquier rol fuera de esta lista es rechazado por LoginCapability::authenticate
 * y por LoginRoles::isValid. Es la barrera contra escalada de privilegios via
 * inyeccion de roles desconocidos.
 */
class optionsLoginRoles
{
    public const WHITELIST = [
        'superadmin',
        'administrador',
        'admin',
        'editor',
        'maestro',
        'sala',
        'cocina',
        'camarero',
        'estudiante',
        'cliente',
    ];

    public static function isValid(string $role): bool
    {
        return in_array($role, self::WHITELIST, true);
    }

    public static function all(): array
    {
        return self::WHITELIST;
    }
}
