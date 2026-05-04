<?php
namespace API;

class ApiLog
{
    public function log($slug, $apiKey, $action)
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $dir = $root . '/locales/' . $slug . '/api_log';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $file = $dir . '/' . date('Y-m-d') . '.json';
        $entries = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];

        $entries[] = [
            'timestamp' => date('c'),
            'action' => $action,
            'key_prefix' => substr($apiKey, 0, 12),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        file_put_contents($file, json_encode($entries, JSON_PRETTY_PRINT));
    }

    public function getRecent($slug, $limit = 100)
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $dir = $root . '/locales/' . $slug . '/api_log';
        $file = $dir . '/' . date('Y-m-d') . '.json';
        if (!file_exists($file)) return [];
        $entries = json_decode(file_get_contents($file), true) ?: [];
        return array_slice(array_reverse($entries), 0, $limit);
    }
}
