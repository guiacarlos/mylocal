<?php
/**
 * PlanLimits — verifica los límites del plan antes de cada write.
 *
 * Límites Demo:
 *   platos  20
 *   zonas    1
 *   mesas    5
 *
 * Uso:
 *   $err = check_plan_limit($localId, 'platos', $currentCount);
 *   if ($err) resp(false, null, $err['error']);
 */

const PLAN_DEMO_LIMITS = [
    'platos' => 20,
    'zonas'  => 1,
    'mesas'  => 5,
];

/**
 * @param string $localId  ID del local ("l_xxx")
 * @param string $resource "platos" | "zonas" | "mesas"
 * @param int    $current  Ítems existentes (antes de añadir el nuevo)
 * @return ?array null si OK, array de error si límite superado
 */
function check_plan_limit(string $localId, string $resource, int $current): ?array
{
    if (!is_on_demo_plan($localId)) return null;

    $limit = PLAN_DEMO_LIMITS[$resource] ?? PHP_INT_MAX;
    if ($current < $limit) return null;

    return [
        'success'     => false,
        'error'       => 'PLAN_LIMIT',
        'resource'    => $resource,
        'limit'       => $limit,
        'current'     => $current,
        'upgrade_url' => '/dashboard/facturacion',
    ];
}

function is_on_demo_plan(string $localId): bool
{
    if (!function_exists('data_get')) return true;
    $sub = data_get('subscriptions', $localId);
    if (!$sub) return true;
    $plan   = strtolower((string) ($sub['plan'] ?? 'demo'));
    $status = strtolower((string) ($sub['status'] ?? 'inactive'));
    return $plan === 'demo' || $status !== 'active';
}
