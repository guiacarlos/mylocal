<?php
/**
 * renew-subscriptions.php — cron de renovación de suscripciones.
 *
 * Ejecutar cada hora:
 *   php spa/server/bin/renew-subscriptions.php
 *   0 * * * * php /ruta/al/proyecto/spa/server/bin/renew-subscriptions.php
 *
 * Flujo:
 *   1. Itera usuarios con suscripción activa + auto_renew=true + renews_at vencido
 *   2. Crea nueva orden Revolut (una por usuario)
 *   3. Marca estado 'past_due' + guarda renewal_url para que el usuario renueve
 *   4. El usuario ve el aviso en Dashboard y hace clic para pagar
 *
 * Nota: Revolut Merchant API es one-off. Sin Recurring Payments API (requiere
 * aprobación especial) no se puede cargar automáticamente sin acción del usuario.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo invocable desde CLI');
}

define('BOOTSTRAP_INTERNAL', true);
require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../handlers/subscriptions.php';

$users   = data_all('users');
$now     = time();
$checked = 0;
$marked  = 0;
$errors  = 0;

foreach ($users as $user) {
    $sub = $user['subscription'] ?? null;
    if (!$sub) continue;
    if (($sub['status'] ?? '') !== 'active') continue;
    if (!($sub['auto_renew'] ?? false)) continue;

    $renewsAt = strtotime($sub['renews_at'] ?? '');
    if (!$renewsAt || $renewsAt > $now) continue;

    $checked++;
    $userId = (string) ($user['id'] ?? '');
    $plan   = (string) ($sub['plan'] ?? 'pro_monthly');

    echo "[renew] user=$userId plan=$plan renews_at=" . ($sub['renews_at'] ?? '?') . "\n";

    try {
        $result = sub_create(['plan' => $plan], $user);

        data_put('users', $userId, array_merge(data_get('users', $userId) ?? [], [
            'subscription' => array_merge($sub, [
                'status'            => 'past_due',
                'renewal_url'       => $result['checkout_url'],
                'renewal_order_id'  => $result['revolut_order_id'],
                'renewed_at'        => date('c'),
            ]),
        ]), false);

        echo "  → past_due, URL: {$result['checkout_url']}\n";
        $marked++;
    } catch (Throwable $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n[renew] Revisados=$checked Marcados=$marked Errores=$errors\n";
