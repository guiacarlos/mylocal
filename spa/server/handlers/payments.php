<?php
/**
 * payments.php — integración Revolut Merchant API.
 *
 * Acciones:
 *   - create_revolut_payment → POST /orders
 *   - create_payment_intent  → alias (SPA puede usar el nombre legacy)
 *   - check_revolut_payment  → GET  /orders/<id>
 *   - revolut_webhook        → entrada pública de Revolut (POST) — debe
 *                              validar la firma con webhook_secret.
 *
 * Docs: https://developer.revolut.com/docs/merchant/
 * API version fijada en server/config/revolut.json (default 2025-12-04).
 *
 * Estados que devuelve Revolut: PENDING, COMPLETED, FAILED, CANCELLED.
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib.php';

function handle_payment(string $action, array $req, ?array $user = null): array
{
    // Rate-limit: el webhook viene de Revolut (no limitar), el resto sí.
    if ($action !== 'revolut_webhook') {
        rl_check('payments', $user ? 120 : 20);
    }
    // Para iniciar un pago se exige sesión activa (o mesa identificada).
    if (in_array($action, ['create_payment_intent', 'create_revolut_payment'], true)) {
        $tableId = (string) ($req['data']['tableId'] ?? '');
        if (!$user && $tableId === '') {
            throw new RuntimeException('Pago requiere sesión o table_id');
        }
    }

    $cfg = load_config('revolut');
    $base = $cfg['endpoints'][$cfg['mode'] ?? 'sandbox'] ?? 'https://sandbox-merchant.revolut.com/api';
    $apiKey = (string) ($cfg['api_key'] ?? '');
    if ($apiKey === '') throw new RuntimeException('Falta api_key en revolut.json');

    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Revolut-Api-Version: ' . ($cfg['api_version'] ?? '2025-12-04'),
    ];

    switch ($action) {
        case 'create_payment_intent':
        case 'create_revolut_payment':
            return create_order($base, $headers, $req, $cfg);

        case 'check_revolut_payment':
            return check_order($base, $headers, $req);

        case 'revolut_webhook':
            return handle_webhook($req, $cfg);

        default:
            throw new RuntimeException("Acción de pago no soportada: $action");
    }
}

function create_order(string $base, array $headers, array $req, array $cfg): array
{
    $data = $req['data'] ?? [];
    $amount = (float) ($data['amount'] ?? 0); // euros
    $currency = (string) ($data['currency'] ?? $cfg['currency_default'] ?? 'EUR');
    $description = (string) ($data['description'] ?? 'Pago Socolá');
    if ($amount <= 0) throw new RuntimeException('amount requerido > 0');

    $payload = [
        'amount' => (int) round($amount * 100), // Revolut usa céntimos
        'currency' => $currency,
        'capture_mode' => $cfg['capture_mode'] ?? 'automatic',
        'description' => $description,
        'metadata' => [
            'order_id' => (string) ($data['orderId'] ?? ''),
            'table_id' => (string) ($data['tableId'] ?? ''),
        ],
    ];

    $res = http_json($base . '/orders', 'POST', $payload, $headers, (int) ($cfg['timeout_seconds'] ?? 15));
    if ($res['status'] !== 201 && $res['status'] !== 200) {
        throw new RuntimeException('Revolut create HTTP ' . $res['status']);
    }
    $body = $res['body'] ?? [];
    return [
        'orderId' => $body['id'] ?? '',
        'publicId' => $body['token'] ?? ($body['public_id'] ?? ''),
        'state' => $body['state'] ?? 'PENDING',
        'mode' => $cfg['mode'] ?? 'sandbox',
    ];
}

function check_order(string $base, array $headers, array $req): array
{
    $orderId = (string) ($req['data']['order_id'] ?? $req['order_id'] ?? '');
    if ($orderId === '') throw new RuntimeException('order_id requerido');

    $res = http_json($base . '/orders/' . rawurlencode($orderId), 'GET', [], $headers);
    if ($res['status'] < 200 || $res['status'] >= 300) {
        throw new RuntimeException('Revolut check HTTP ' . $res['status']);
    }
    $body = $res['body'] ?? [];
    $state = (string) ($body['state'] ?? 'PENDING');

    // Reflejar el estado en server/data/orders/<orderId>.json para que la
    // SPA pueda hacer polling también contra nuestro cache si quisiera.
    data_put('orders', $orderId, [
        'id' => $orderId,
        'revolut_state' => $state,
        'amount' => $body['amount'] ?? null,
        'currency' => $body['currency'] ?? 'EUR',
    ]);

    return [
        'orderId' => $orderId,
        'state' => $state,
        'updatedAt' => date('c'),
    ];
}

/**
 * Webhook de Revolut. En producción, validar firma con HMAC(webhook_secret).
 * Implementación a completar según el formato exacto v2025-12-04.
 */
function handle_webhook(array $req, array $cfg): array
{
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $req;

    $secret = (string) ($cfg['webhook_secret'] ?? '');
    if ($secret !== '') {
        // TODO: validar firma exacta — ver doc Revolut, formato estándar:
        //   Revolut-Signature: v1=<hex-hmac-sha256>
        //   https://developer.revolut.com/docs/guides/accept-payments/payment-flow/webhooks
        $signature = $_SERVER['HTTP_REVOLUT_SIGNATURE'] ?? '';
        $expected = hash_hmac('sha256', $raw, $secret);
        if (!hash_equals('v1=' . $expected, $signature)) {
            http_response_code(401);
            throw new RuntimeException('Firma webhook inválida');
        }
    }

    $orderId = (string) ($payload['order_id'] ?? $payload['data']['id'] ?? '');
    $state = (string) ($payload['event'] ?? $payload['state'] ?? 'PENDING');

    if ($orderId !== '') {
        data_put('orders', $orderId, [
            'id' => $orderId,
            'revolut_state' => $state,
            'webhook_at' => date('c'),
        ]);
    }

    return ['received' => true];
}
