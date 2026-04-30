<?php
namespace API;

require_once __DIR__ . '/ApiKeyManager.php';
require_once __DIR__ . '/ApiLog.php';

class PublicApi
{
    private $services;
    private $keyManager;
    private $rateLimit = 100;

    public function __construct($services)
    {
        $this->services = $services;
        $this->keyManager = new ApiKeyManager();
    }

    public function handle($slug, $action, $apiKey, $data = [])
    {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'X-Api-Key header required', 'code' => 401];
        }

        if (!$this->keyManager->validate($slug, $apiKey)) {
            return ['success' => false, 'error' => 'Invalid API key', 'code' => 403];
        }

        if (!$this->checkRateLimit($apiKey)) {
            return ['success' => false, 'error' => 'Rate limit exceeded', 'code' => 429];
        }

        $log = new ApiLog();
        $log->log($slug, $apiKey, $action);

        $allowed = ['get_carta', 'get_table_status', 'create_order', 'get_order_status'];
        if (!in_array($action, $allowed)) {
            return ['success' => false, 'error' => 'Action not allowed', 'code' => 400];
        }

        switch ($action) {
            case 'get_carta':
                return $this->getCarta($slug);
            case 'get_table_status':
                return $this->getTableStatus($slug, $data);
            case 'create_order':
                return $this->createOrder($slug, $data);
            case 'get_order_status':
                return $this->getOrderStatus($slug, $data);
            default:
                return ['success' => false, 'error' => 'Unknown action'];
        }
    }

    private function getCarta($slug)
    {
        if (isset($this->services['carta'])) {
            return $this->services['carta']->executeAction('get_carta', ['slug' => $slug]);
        }
        return ['success' => false, 'error' => 'Carta module not available'];
    }

    private function getTableStatus($slug, $data)
    {
        $mesaId = $data['mesa_id'] ?? '';
        $sesiones = $this->services['crud']->list('sesiones_mesa');
        if (!isset($sesiones['success']) || !$sesiones['success']) return $sesiones;
        foreach ($sesiones['data'] ?? [] as $s) {
            if ($s['local_id'] === $slug && $s['mesa_id'] === $mesaId && $s['estado'] === 'abierta') {
                return ['success' => true, 'data' => ['status' => 'occupied', 'since' => $s['abierta_en']]];
            }
        }
        return ['success' => true, 'data' => ['status' => 'free']];
    }

    private function createOrder($slug, $data)
    {
        if (isset($this->services['qr'])) {
            return $this->services['qr']->executeAction('process_external_order', $data);
        }
        return ['success' => false, 'error' => 'QR module not available'];
    }

    private function getOrderStatus($slug, $data)
    {
        $sesionId = $data['sesion_id'] ?? '';
        $sesion = $this->services['crud']->read('sesiones_mesa', $sesionId);
        if (!isset($sesion['id'])) return ['success' => false, 'error' => 'Session not found'];
        if ($sesion['local_id'] !== $slug) return ['success' => false, 'error' => 'Access denied'];
        return ['success' => true, 'data' => ['estado' => $sesion['estado'], 'total' => $sesion['total_bruto'] ?? 0]];
    }

    private function checkRateLimit($apiKey)
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $file = $root . '/logs/rate_' . md5($apiKey) . '.json';
        $now = time();
        $window = 60;

        $data = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        $data = array_filter($data, function ($t) use ($now, $window) { return $t > $now - $window; });
        if (count($data) >= $this->rateLimit) return false;
        $data[] = $now;
        file_put_contents($file, json_encode(array_values($data)));
        return true;
    }
}
