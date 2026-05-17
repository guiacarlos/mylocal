<?php
/**
 * Billing handler — suscripciones MyLocal con Revolut.
 *
 * Acciones:
 *   get_subscription_status  (auth) estado del plan del local
 *   create_revolut_order     (auth) crea orden Revolut → devuelve checkout_url
 *   check_revolut_order      (auth) consulta estado de una orden
 *   webhook_revolut          (pública, verificada con HMAC) actualiza plan al confirmar pago
 */

declare(strict_types=1);

require_once realpath(__DIR__ . '/../../../CAPABILITIES/BILLING/BillingManager.php');
require_once realpath(__DIR__ . '/../../../CAPABILITIES/PAYMENT/drivers/RevolutDriver.php');

function handle_billing(string $action, array $req): array
{
    return match ($action) {
        'get_subscription_status' => billing_status($req),
        'create_revolut_order'    => billing_create_order($req),
        'check_revolut_order'     => billing_check_order($req),
        'webhook_revolut'         => billing_webhook($req),
        default                   => throw new \RuntimeException("Acción billing no reconocida: $action"),
    };
}

function billing_revolut(): \PAYMENT\drivers\RevolutDriver
{
    static $driver = null;
    if ($driver) return $driver;
    $cfg = [];
    if (function_exists('data_get')) {
        // Configuración de archivo (legacy)
        $cfgDoc = data_get('config', 'revolut') ?: [];
        $cfg    = is_array($cfgDoc) ? $cfgDoc : [];
        // Global config del SuperAdmin tiene prioridad
        $global = data_get('global_config', 'global') ?: [];
        if (!empty($global['revolut_api_key'])) {
            $cfg['api_key'] = $global['revolut_api_key'];
        }
        if (!empty($global['revolut_mode'])) {
            $cfg['sandbox'] = $global['revolut_mode'] !== 'production';
        }
    }
    $driver = new \PAYMENT\drivers\RevolutDriver($cfg);
    return $driver;
}

function billing_status(array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = s_str($data['local_id'] ?? '');
    if ($localId === '') throw new \RuntimeException('local_id obligatorio');
    return \Billing\BillingManager::getStatus($localId);
}

function billing_create_order(array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = s_str($data['local_id'] ?? '');
    $plan    = s_str($data['plan'] ?? 'pro_monthly');
    if ($localId === '') throw new \RuntimeException('local_id obligatorio');

    $planData = \Billing\BillingManager::getPlanData($plan);
    if (!$planData) throw new \RuntimeException('Plan no válido: ' . $plan);

    $r = billing_revolut()->createOrder($localId, $plan, $planData['amount'], $planData['currency']);
    if (!($r['success'] ?? false)) throw new \RuntimeException($r['error'] ?? 'Error Revolut');
    return $r;
}

function billing_check_order(array $req): array
{
    $data      = $req['data'] ?? $req;
    $revolutId = s_str($data['revolut_id'] ?? '');
    if ($revolutId === '') throw new \RuntimeException('revolut_id obligatorio');
    $r = billing_revolut()->checkOrder($revolutId);
    if (!($r['success'] ?? false)) throw new \RuntimeException($r['error'] ?? 'Error Revolut');
    return $r;
}

function billing_webhook(array $req): array
{
    // Leer cuerpo raw y verificar firma
    $rawBody  = file_get_contents('php://input') ?: '';
    $sig      = $_SERVER['HTTP_REVOLUT_SIGNATURE'] ?? '';
    if (!billing_revolut()->verifyWebhook($rawBody, $sig)) {
        http_response_code(401);
        exit(json_encode(['error' => 'firma invalida']));
    }

    $event = json_decode($rawBody, true) ?? [];
    $type  = $event['event'] ?? $event['type'] ?? '';

    // Revolut emite ORDER_COMPLETED cuando el pago se confirma
    if (in_array($type, ['ORDER_COMPLETED', 'order.completed'], true)) {
        $meta    = $event['data']['metadata'] ?? $event['metadata'] ?? [];
        $localId = (string) ($meta['local_id'] ?? '');
        $plan    = (string) ($meta['plan'] ?? 'pro_monthly');
        $orderId = (string) ($event['data']['id'] ?? $event['id'] ?? '');
        $amount  = (int) ($event['data']['order_amount']['value'] ?? 0);

        if ($localId !== '') {
            \Billing\BillingManager::activate($localId, $plan, $orderId, $amount);
        }
    }

    return ['received' => true];
}
