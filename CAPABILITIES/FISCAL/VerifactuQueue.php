<?php
namespace FISCAL;

class VerifactuQueue
{
    private $queuePath;

    public function __construct()
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $dir = $root . '/fiscal';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $this->queuePath = $dir . '/verifactu_queue.json';
    }

    public function push($registroId, $signedXml, $certPath, $certPassword, $attempt = 1)
    {
        $queue = $this->load();
        $queue[] = [
            'registro_id' => $registroId,
            'signed_xml' => $signedXml,
            'cert_path' => $certPath,
            'cert_password' => $certPassword,
            'attempt' => $attempt,
            'max_attempts' => 3,
            'created_at' => date('c'),
            'next_retry' => date('c', time() + ($attempt * 60))
        ];
        $this->save($queue);
    }

    public function process()
    {
        $queue = $this->load();
        if (empty($queue)) return ['success' => true, 'data' => ['processed' => 0]];

        $sender = new VerifactuSender();
        $remaining = [];
        $processed = 0;

        foreach ($queue as $item) {
            if ($item['next_retry'] > date('c')) {
                $remaining[] = $item;
                continue;
            }

            $result = $sender->send($item['signed_xml'], $item['cert_path'], $item['cert_password']);
            if ($result['success']) {
                $processed++;
            } elseif (($result['retry'] ?? false) && $item['attempt'] < $item['max_attempts']) {
                $item['attempt']++;
                $item['next_retry'] = date('c', time() + ($item['attempt'] * 120));
                $remaining[] = $item;
            }
        }

        $this->save($remaining);
        return ['success' => true, 'data' => ['processed' => $processed, 'remaining' => count($remaining)]];
    }

    public function count()
    {
        return count($this->load());
    }

    private function load()
    {
        if (!file_exists($this->queuePath)) return [];
        return json_decode(file_get_contents($this->queuePath), true) ?: [];
    }

    private function save($queue)
    {
        file_put_contents($this->queuePath, json_encode($queue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
