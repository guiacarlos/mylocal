<?php
namespace TPV;

class ComanderoNotifications
{
    private $vapidPath;

    public function __construct()
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $this->vapidPath = $root . '/.vault/vapid.json';
    }

    public function send($subscription, $payload)
    {
        if (!file_exists($this->vapidPath)) {
            return ['success' => false, 'error' => 'VAPID keys no configuradas'];
        }

        $vapid = json_decode(file_get_contents($this->vapidPath), true);
        $endpoint = $subscription['endpoint'] ?? '';
        if (empty($endpoint)) {
            return ['success' => false, 'error' => 'Endpoint de suscripcion vacio'];
        }

        $body = json_encode($payload);
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'TTL: 60',
                'Authorization: vapid t=' . ($vapid['public_key'] ?? '')
            ],
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => ['http_code' => $httpCode]
        ];
    }

    public function notifyItemReady($mesaNumero, $itemNombre, $camareroSubscription)
    {
        return $this->send($camareroSubscription, [
            'title' => 'Plato listo',
            'body' => "Mesa $mesaNumero - $itemNombre",
            'tag' => 'item-ready'
        ]);
    }
}
