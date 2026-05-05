<?php
namespace Options;

/**
 * optionsLoginPermissions - mapa role -> [acciones permitidas].
 *
 * Soporta wildcards con asterisco. Ejemplos:
 *   '*'           -> permite todo (solo admin/superadmin)
 *   'carta_*'     -> cualquier accion que empiece por carta_
 *   'list_mesas'  -> exactamente esa accion
 *
 * Esta lista alimenta LoginRoles::allows que se usa cuando un handler
 * quiere comprobar "este rol puede hacer esta accion concreta?".
 *
 * NOTA: hoy require_role() compara solo contra una lista de roles permitidos
 * (sin granularidad por accion). Esta tabla es la base para que en el futuro
 * el dispatcher decida automaticamente "este rol puede correr esta accion?",
 * eliminando los require_role hardcoded en cada handler. Por ahora coexisten:
 * require_role sigue siendo la red activa, MAP queda disponible para uso
 * opcional.
 */
class optionsLoginPermissions
{
    public const MAP = [
        'superadmin'    => ['*'],
        'administrador' => ['*'],
        'admin'         => ['*'],

        'editor' => [
            'carta_*', 'sala_*', 'qr_*', 'tpv_sala_*',
            'list_*', 'create_*', 'update_*', 'delete_*',
        ],

        'maestro' => [
            'carta_read', 'list_*', 'auth_me', 'auth_logout',
        ],

        'sala' => [
            'tpv_sala_*', 'qr_*', 'sala_resumen', 'list_zonas', 'list_mesas',
            'auth_me', 'auth_logout',
        ],

        'cocina' => [
            'tpv_cocina_*', 'list_pedidos', 'auth_me', 'auth_logout',
        ],

        'camarero' => [
            'tpv_sala_*', 'list_mesas', 'list_zonas',
            'auth_me', 'auth_logout',
        ],

        'estudiante' => [
            'auth_me', 'auth_logout',
        ],

        'cliente' => [
            'auth_me', 'auth_logout',
            'carta_read', 'qr_view', 'qr_send_pedido',
        ],
    ];

    public static function allow(string $role, string $action): bool
    {
        $patterns = self::MAP[$role] ?? [];
        foreach ($patterns as $p) {
            if ($p === '*' || $p === $action) return true;
            if (substr($p, -1) === '*' && strpos($action, rtrim($p, '*')) === 0) return true;
        }
        return false;
    }
}
