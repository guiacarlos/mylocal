<?php
namespace FISCAL;

class VerifactuLog
{
    private $logPath;

    public function __construct()
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $dir = $root . '/fiscal';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $this->logPath = $dir . '/verifactu_log.json';
    }

    public function write($registroId, $estado, $csv = '', $details = '')
    {
        $log = $this->load();
        $log[] = [
            'registro_id' => $registroId,
            'timestamp' => date('c'),
            'estado' => $estado,
            'csv' => $csv,
            'details' => $details
        ];
        file_put_contents($this->logPath, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function getRecent($limit = 50)
    {
        $log = $this->load();
        return array_slice(array_reverse($log), 0, $limit);
    }

    private function load()
    {
        if (!file_exists($this->logPath)) return [];
        return json_decode(file_get_contents($this->logPath), true) ?: [];
    }
}
