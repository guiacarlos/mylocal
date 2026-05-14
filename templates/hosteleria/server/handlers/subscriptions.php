<?php
/**
 * subscriptions.php — suscripciones SaaS via Revolut Merchant API.
 *
 * Flujo:
 *   1. create_subscription   → crea orden Revolut + guarda estado 'pending' en usuario
 *   2. activate_subscription → verifica pago con Revolut + activa suscripción
 *   3. cancel_subscription   → desactiva auto_renew (acceso hasta renews_at)
 *   4. get_subscription      → devuelve estado actual del plan
 *
 * Sin Recurring Payments API (requiere aprobación especial).
 * Las renovaciones se gestionan via cron (bin/renew-subscriptions.php) que
 * genera nuevas órdenes y marca past_due hasta que el usuario confirma.
 */

declare(strict_types=1);

const SUB_PLANS = [
    'pro_monthly' => ['label' => 'Pro Mensual', 'amount' => 2700,  'currency' => 'EUR', 'days' => 30],
    'pro_annual'  => ['label' => 'Pro Anual',   'amount' => 26000, 'currency' => 'EUR', 'days' => 365],
];

function handle_subscriptions(string $action, array $req, ?array $user): array
{
    switch ($action) {
        case 'create_subscription':   return sub_create($req, $user);
        case 'activate_subscription': return sub_activate($req, $user);
        case 'cancel_subscription':   return sub_cancel($user);
        case 'get_subscription':      return sub_get($user);
        default: throw new RuntimeException("Acción de suscripción no reconocida: $action");
    }
}

/* ─── Crear orden Revolut + guardar pendiente ─────────────────────────── */

function sub_create(array $req, ?array $user): array
{
    if (!$user) throw new RuntimeException('No autenticado');
    $plan = s_str($req['plan'] ?? '');
    if (!isset(SUB_PLANS[$plan])) throw new RuntimeException("Plan no válido: $plan");

    $sub = $user['subscription'] ?? null;
    if ($sub && ($sub['status'] ?? '') === 'active' && ($sub['auto_renew'] ?? true)) {
        throw new RuntimeException('Ya tienes una suscripción activa');
    }

    $p   = SUB_PLANS[$plan];
    [$base, $headers] = sub_revolut_client();

    $proto     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $appUrl    = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:5173');
    $intId     = 'sub_' . bin2hex(random_bytes(8));

    $payload = [
        'amount'            => $p['amount'],
        'currency'          => $p['currency'],
        'capture_mode'      => 'automatic',
        'merchant_order_id' => $intId,
        'description'       => 'MyLocal ' . $p['label'],
        'redirect_url'      => $appUrl . '/checkout?confirm=1',
        'metadata'          => ['plan' => $plan, 'user_id' => (string) ($user['id'] ?? '')],
    ];

    $r = http_json($base . '/orders', 'POST', $payload, $headers, 15);
    if ($r['status'] < 200 || $r['status'] >= 300) {
        throw new RuntimeException('Revolut create HTTP ' . $r['status']);
    }
    $body      = $r['body'] ?? [];
    $revolutId = (string) ($body['id'] ?? '');
    $publicId  = (string) ($body['token'] ?? ($body['public_id'] ?? ''));
    if (!$revolutId) throw new RuntimeException('Respuesta Revolut sin id');

    $userId = (string) ($user['id'] ?? '');
    $u = data_get('users', $userId) ?? [];
    data_put('users', $userId, array_merge($u, [
        'subscription' => [
            'plan'              => $plan,
            'status'            => 'pending',
            'revolut_order_id'  => $revolutId,
            'internal_order_id' => $intId,
            'started_at'        => null,
            'renews_at'         => null,
            'auto_renew'        => true,
            'cancelled_at'      => null,
        ],
    ]), false);

    $sandbox = sub_is_sandbox();
    $checkoutUrl = $sandbox
        ? "https://sandbox-checkout.revolut.com/pay/$publicId"
        : "https://checkout.revolut.com/pay/$publicId";

    return [
        'revolut_order_id' => $revolutId,
        'public_id'        => $publicId,
        'checkout_url'     => $checkoutUrl,
        'amount'           => $p['amount'],
        'currency'         => $p['currency'],
        'plan'             => $plan,
        'label'            => $p['label'],
    ];
}

/* ─── Verificar pago + activar ────────────────────────────────────────── */

function sub_activate(array $req, ?array $user): array
{
    if (!$user) throw new RuntimeException('No autenticado');
    $revolutOrderId = s_str($req['revolut_order_id'] ?? '');
    if (!$revolutOrderId) throw new RuntimeException('revolut_order_id requerido');

    $sub = $user['subscription'] ?? null;
    if (!$sub || ($sub['revolut_order_id'] ?? '') !== $revolutOrderId) {
        throw new RuntimeException('Orden no coincide con la suscripción pendiente');
    }

    [$base, $headers] = sub_revolut_client();
    $r = http_json($base . '/orders/' . rawurlencode($revolutOrderId), 'GET', [], $headers, 15);
    if ($r['status'] < 200 || $r['status'] >= 300) {
        throw new RuntimeException('No se pudo verificar el pago con Revolut');
    }
    $state = (string) ($r['body']['state'] ?? '');
    if ($state !== 'COMPLETED') {
        throw new RuntimeException("Pago no completado. Estado actual: $state");
    }

    $plan   = (string) ($sub['plan'] ?? 'pro_monthly');
    $days   = SUB_PLANS[$plan]['days'] ?? 30;
    $now    = new DateTime();
    $renews = (clone $now)->modify("+{$days} days");

    $userId = (string) ($user['id'] ?? '');
    data_put('users', $userId, array_merge(data_get('users', $userId) ?? [], [
        'subscription' => [
            'plan'              => $plan,
            'status'            => 'active',
            'revolut_order_id'  => $revolutOrderId,
            'internal_order_id' => $sub['internal_order_id'] ?? '',
            'started_at'        => $now->format(DateTime::ATOM),
            'renews_at'         => $renews->format(DateTime::ATOM),
            'auto_renew'        => true,
            'cancelled_at'      => null,
        ],
    ]), false);

    return ['status' => 'active', 'plan' => $plan, 'renews_at' => $renews->format(DateTime::ATOM)];
}

/* ─── Cancelar auto-renovación ────────────────────────────────────────── */

function sub_cancel(?array $user): array
{
    if (!$user) throw new RuntimeException('No autenticado');
    $sub = $user['subscription'] ?? null;
    if (!$sub || ($sub['status'] ?? '') !== 'active') {
        throw new RuntimeException('No hay suscripción activa que cancelar');
    }
    $userId = (string) ($user['id'] ?? '');
    data_put('users', $userId, array_merge(data_get('users', $userId) ?? [], [
        'subscription' => array_merge($sub, [
            'auto_renew'   => false,
            'cancelled_at' => (new DateTime())->format(DateTime::ATOM),
        ]),
    ]), false);
    return ['status' => 'cancelled', 'access_until' => $sub['renews_at']];
}

/* ─── Estado actual ───────────────────────────────────────────────────── */

function sub_get(?array $user): array
{
    if (!$user) throw new RuntimeException('No autenticado');
    $sub = $user['subscription'] ?? null;
    if (!$sub) {
        return ['plan' => 'demo', 'status' => 'trial', 'started_at' => null, 'renews_at' => null, 'auto_renew' => false];
    }
    if (($sub['status'] ?? '') === 'active' && !empty($sub['renews_at'])) {
        if (strtotime($sub['renews_at']) < time() && !($sub['auto_renew'] ?? true)) {
            return array_merge($sub, ['status' => 'expired']);
        }
    }
    return $sub;
}

/* ─── Helpers Revolut ─────────────────────────────────────────────────── */

function sub_revolut_client(): array
{
    $cfg    = load_config('revolut');
    $apiKey = (string) ($cfg['api_key'] ?? '');
    if ($apiKey === '') throw new RuntimeException('API key Revolut no configurada en revolut.json');
    $sandbox = ($cfg['mode'] ?? 'sandbox') === 'sandbox';
    $base    = $sandbox
        ? 'https://sandbox-merchant.revolut.com/api'
        : 'https://merchant.revolut.com/api';
    return [$base, [
        'Authorization: Bearer ' . $apiKey,
        'Revolut-Api-Version: ' . ($cfg['api_version'] ?? '2025-12-04'),
    ]];
}

function sub_is_sandbox(): bool
{
    $cfg = load_config('revolut');
    return ($cfg['mode'] ?? 'sandbox') === 'sandbox';
}
