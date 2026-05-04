<?php
namespace DELIVERY;

class DeliveryWebhook
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function handle($platform, $slug, $rawBody, $headers)
    {
        $driver = $this->getDriver($platform);
        if (!$driver) {
            return ['success' => false, 'error' => "Plataforma '$platform' no soportada"];
        }

        $config = $this->loadConfig($slug, $platform);
        if (empty($config['secret'])) {
            return ['success' => false, 'error' => "Webhook no configurado para $platform en $slug"];
        }

        if (!$driver->validateSignature($rawBody, $headers, $config['secret'])) {
            return ['success' => false, 'error' => 'Firma HMAC invalida'];
        }

        $payload = json_decode($rawBody, true);
        if (!$payload) {
            return ['success' => false, 'error' => 'Payload invalido'];
        }

        $order = $driver->normalize($payload);
        $order['origen'] = $platform;
        $order['local_slug'] = $slug;

        if (isset($this->services['qr'])) {
            $result = $this->services['qr']->executeAction('process_external_order', [
                'items' => $order['items'],
                'total' => $order['total'],
                'source' => $platform,
                'external_id' => $order['external_id'] ?? ''
            ]);
            return $result;
        }

        return ['success' => false, 'error' => 'QR module not available'];
    }

    private function getDriver($platform)
    {
        $drivers = [
            'glovo' => 'GlovoDriver',
            'ubereats' => 'UberEatsDriver',
            'justeat' => 'JustEatDriver'
        ];

        $class = $drivers[$platform] ?? null;
        if (!$class) return null;

        require_once __DIR__ . '/drivers/' . $class . '.php';
        $fqcn = 'DELIVERY\\drivers\\' . $class;
        return new $fqcn();
    }

    private function loadConfig($slug, $platform)
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $path = $root . '/locales/' . $slug . '/config/delivery.json';
        if (!file_exists($path)) return [];
        $all = json_decode(file_get_contents($path), true) ?: [];
        return $all[$platform] ?? [];
    }
}
