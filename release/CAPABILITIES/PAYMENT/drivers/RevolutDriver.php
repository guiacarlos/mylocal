<?php
namespace PAYMENT\drivers;

/**
 * RevolutDriver — integración con Revolut Business API para suscripciones.
 *
 * La API key vive en STORAGE/config/revolut.json:
 *   { "api_key": "sk_...", "sandbox": true|false, "webhook_secret": "..." }
 *
 * Google Pay se activa automáticamente en el checkout de Revolut sin código extra.
 *
 * Referencia: https://developer.revolut.com/docs/business/orders
 */
class RevolutDriver
{
    private array $cfg;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }

    /** Crea una orden de pago y devuelve la checkout_url. */
    public function createOrder(string $localId, string $plan, int $amount, string $currency = 'EUR'): array
    {
        if (empty($this->cfg['api_key'])) {
            return ['success' => false, 'error' => 'Revolut no configurado'];
        }

        $orderId  = 'mylocal_' . bin2hex(random_bytes(8));
        $payload  = [
            'amount'              => $amount,
            'currency'            => $currency,
            'merchant_order_ext_ref' => $orderId,
            'capture_mode'        => 'AUTOMATIC',
            'metadata'            => ['local_id' => $localId, 'plan' => $plan],
        ];

        $r = $this->request('POST', '/orders', $payload);
        if (!($r['success'] ?? false)) return $r;

        $body = $r['body'];
        return [
            'success'      => true,
            'order_id'     => $orderId,
            'revolut_id'   => $body['id'] ?? '',
            'checkout_url' => $body['checkout_url'] ?? '',
        ];
    }

    /** Consulta el estado de una orden. */
    public function checkOrder(string $revolutId): array
    {
        $r = $this->request('GET', '/orders/' . urlencode($revolutId));
        if (!($r['success'] ?? false)) return $r;
        $body = $r['body'];
        return [
            'success'    => true,
            'revolut_id' => $body['id'] ?? $revolutId,
            'state'      => $body['state'] ?? 'UNKNOWN',
            'amount'     => $body['order_amount']['value'] ?? 0,
            'currency'   => $body['order_amount']['currency'] ?? 'EUR',
            'metadata'   => $body['metadata'] ?? [],
        ];
    }

    /** Verifica la firma HMAC del webhook de Revolut. */
    public function verifyWebhook(string $payload, string $signature): bool
    {
        $secret = $this->cfg['webhook_secret'] ?? '';
        if ($secret === '') return true; // sandbox sin secret: aceptar
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    private function request(string $method, string $path, array $body = []): array
    {
        $sandbox  = (bool) ($this->cfg['sandbox'] ?? true);
        $base     = $sandbox
            ? 'https://sandbox-business.revolut.com/api/1.0'
            : 'https://business.revolut.com/api/1.0';
        $url      = $base . $path;
        $headers  = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->cfg['api_key'],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => !$sandbox,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false) return ['success' => false, 'error' => 'curl error'];
        $data = json_decode((string) $resp, true);
        if ($code < 200 || $code >= 300) {
            $msg = $data['message'] ?? "HTTP $code";
            return ['success' => false, 'error' => $msg];
        }
        return ['success' => true, 'body' => $data ?? []];
    }
}
