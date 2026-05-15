<?php
namespace API;

class ApiKeyManager
{
    public function generate($localId)
    {
        $key = 'myl_' . bin2hex(random_bytes(24));
        $keys = $this->loadKeys($localId);
        $keys[] = [
            'key' => $key,
            'local_id' => $localId,
            'created_at' => date('c'),
            'active' => true
        ];
        $this->saveKeys($localId, $keys);
        return ['success' => true, 'data' => ['api_key' => $key]];
    }

    public function validate($localId, $key)
    {
        $keys = $this->loadKeys($localId);
        foreach ($keys as $k) {
            if ($k['key'] === $key && $k['active']) return true;
        }
        return false;
    }

    public function revoke($localId, $key)
    {
        $keys = $this->loadKeys($localId);
        foreach ($keys as &$k) {
            if ($k['key'] === $key) { $k['active'] = false; break; }
        }
        $this->saveKeys($localId, $keys);
        return ['success' => true];
    }

    public function listKeys($localId)
    {
        $keys = $this->loadKeys($localId);
        $safe = array_map(function ($k) {
            return ['key_prefix' => substr($k['key'], 0, 12) . '...', 'active' => $k['active'], 'created_at' => $k['created_at']];
        }, $keys);
        return ['success' => true, 'data' => $safe];
    }

    private function loadKeys($localId)
    {
        $path = $this->keysPath($localId);
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?: [];
    }

    private function saveKeys($localId, $keys)
    {
        $path = $this->keysPath($localId);
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($path, json_encode($keys, JSON_PRETTY_PRINT));
    }

    private function keysPath($localId)
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        return $root . '/locales/' . $localId . '/config/api_keys.json';
    }
}
