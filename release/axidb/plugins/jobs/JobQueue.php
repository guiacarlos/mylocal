<?php
namespace AxiDB\Plugins\Jobs;

class JobQueue
{
    private $queueDir;

    public function __construct($storageRoot = null)
    {
        $root = $storageRoot ?: (defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../../STORAGE');
        $this->queueDir = $root . '/_system/jobs';
        foreach (['pending', 'running', 'done', 'failed'] as $state) {
            $dir = $this->queueDir . '/' . $state;
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }

    public function enqueue($type, $payload, $priority = 5)
    {
        $job = [
            'id' => uniqid('job_', true),
            'type' => $type,
            'payload' => $payload,
            'priority' => intval($priority),
            'attempts' => 0,
            'max_attempts' => 3,
            'enqueued_at' => date('c'),
            'state' => 'pending'
        ];
        $file = $this->queueDir . '/pending/' . $job['id'] . '.json';
        if (@file_put_contents($file, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            return ['success' => false, 'error' => 'No se pudo escribir el job'];
        }
        return ['success' => true, 'id' => $job['id']];
    }

    public function next()
    {
        $files = glob($this->queueDir . '/pending/*.json');
        if (!$files) return null;
        usort($files, function ($a, $b) {
            $ja = json_decode(@file_get_contents($a), true);
            $jb = json_decode(@file_get_contents($b), true);
            $pa = $ja['priority'] ?? 5;
            $pb = $jb['priority'] ?? 5;
            if ($pa === $pb) return strcmp($ja['enqueued_at'] ?? '', $jb['enqueued_at'] ?? '');
            return $pa - $pb;
        });
        $first = $files[0];
        $job = json_decode(@file_get_contents($first), true);
        if (!$job) return null;
        $job['state'] = 'running';
        $job['started_at'] = date('c');
        $job['attempts'] = ($job['attempts'] ?? 0) + 1;
        $dest = $this->queueDir . '/running/' . $job['id'] . '.json';
        if (!@rename($first, $dest)) return null;
        @file_put_contents($dest, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $job;
    }

    public function complete($id, $result = null)
    {
        $src = $this->queueDir . '/running/' . $id . '.json';
        if (!file_exists($src)) return false;
        $job = json_decode(@file_get_contents($src), true);
        $job['state'] = 'done';
        $job['finished_at'] = date('c');
        if ($result !== null) $job['result'] = $result;
        $dest = $this->queueDir . '/done/' . $id . '.json';
        @file_put_contents($dest, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        @unlink($src);
        return true;
    }

    public function fail($id, $error)
    {
        $src = $this->queueDir . '/running/' . $id . '.json';
        if (!file_exists($src)) return false;
        $job = json_decode(@file_get_contents($src), true);
        $job['last_error'] = $error;
        if ($job['attempts'] < ($job['max_attempts'] ?? 3)) {
            $job['state'] = 'pending';
            $dest = $this->queueDir . '/pending/' . $id . '.json';
            @file_put_contents($dest, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            @unlink($src);
            return true;
        }
        $job['state'] = 'failed';
        $job['failed_at'] = date('c');
        $dest = $this->queueDir . '/failed/' . $id . '.json';
        @file_put_contents($dest, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        @unlink($src);
        return true;
    }

    public function status($id)
    {
        foreach (['pending', 'running', 'done', 'failed'] as $state) {
            $f = $this->queueDir . '/' . $state . '/' . $id . '.json';
            if (file_exists($f)) {
                $job = json_decode(@file_get_contents($f), true);
                return $job;
            }
        }
        return null;
    }

    public function counts()
    {
        $out = [];
        foreach (['pending', 'running', 'done', 'failed'] as $state) {
            $out[$state] = count(glob($this->queueDir . '/' . $state . '/*.json'));
        }
        return $out;
    }
}
