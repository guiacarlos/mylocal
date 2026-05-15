<?php
namespace PAYMENT\drivers;

class StripeDriver
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function initiate($data, $pagoId)
    {
        $config = $this->getConfig($data['local_id'] ?? '');
        if (empty($config['stripe_secret_key'])) {
            return ['success' => false, 'error' => 'Stripe no configurado para este local'];
        }

        $importe = intval(floatval($data['importe'] ?? 0) * 100);
        $currency = 'eur';

        $payload = [
            'amount' => $importe,
            'currency' => $currency,
            'metadata' => [
                'pago_id' => $pagoId,
                'local_id' => $data['local_id'] ?? '',
                'sesion_id' => $data['sesion_id'] ?? ''
            ]
        ];

        $ch = curl_init('https://api.stripe.com/v1/payment_intents');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_USERPWD => $config['stripe_secret_key'] . ':',
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return ['success' => false, 'error' => 'Error al crear PaymentIntent en Stripe'];
        }

        $intent = json_decode($response, true);
        if (empty($intent['client_secret'])) {
            return ['success' => false, 'error' => 'Respuesta de Stripe invalida'];
        }

        return [
            'success' => true,
            'data' => [
                'pago_id' => $pagoId,
                'metodo' => 'tarjeta',
                'client_secret' => $intent['client_secret'],
                'payment_intent_id' => $intent['id'],
                'publishable_key' => $config['stripe_publishable_key'] ?? '',
                'estado' => 'pendiente'
            ]
        ];
    }

    public function checkStatus($paymentIntentId, $localId)
    {
        $config = $this->getConfig($localId);
        if (empty($config['stripe_secret_key'])) {
            return ['success' => false, 'error' => 'Stripe no configurado'];
        }

        $ch = curl_init('https://api.stripe.com/v1/payment_intents/' . $paymentIntentId);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $config['stripe_secret_key'] . ':',
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $intent = json_decode($response, true);
        return [
            'success' => true,
            'data' => [
                'status' => $intent['status'] ?? 'unknown',
                'amount' => ($intent['amount'] ?? 0) / 100
            ]
        ];
    }

    private function getConfig($localId)
    {
        $path = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../../STORAGE';
        $configPath = $path . '/config/payment.json';
        if (!file_exists($configPath)) return [];
        $config = json_decode(file_get_contents($configPath), true);
        return $config[$localId] ?? $config ?? [];
    }
}
