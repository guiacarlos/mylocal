<?php
namespace AxiDB\Plugins\Jobs;

class JobWorker
{
    private $queue;
    private $handlers = [];

    public function __construct(JobQueue $queue)
    {
        $this->queue = $queue;
    }

    public function register($type, callable $handler)
    {
        $this->handlers[$type] = $handler;
    }

    public function runOnce()
    {
        $job = $this->queue->next();
        if (!$job) return ['ran' => false, 'reason' => 'no_pending_jobs'];

        $type = $job['type'] ?? '';
        if (!isset($this->handlers[$type])) {
            $this->queue->fail($job['id'], "No handler para tipo: $type");
            return ['ran' => true, 'success' => false, 'job_id' => $job['id'], 'error' => 'no_handler'];
        }

        try {
            $result = call_user_func($this->handlers[$type], $job['payload'] ?? []);
            if (is_array($result) && isset($result['success']) && !$result['success']) {
                $this->queue->fail($job['id'], $result['error'] ?? 'unknown');
                return ['ran' => true, 'success' => false, 'job_id' => $job['id'], 'error' => $result['error'] ?? 'unknown'];
            }
            $this->queue->complete($job['id'], $result);
            return ['ran' => true, 'success' => true, 'job_id' => $job['id'], 'result' => $result];
        } catch (\Throwable $e) {
            $this->queue->fail($job['id'], $e->getMessage());
            return ['ran' => true, 'success' => false, 'job_id' => $job['id'], 'error' => $e->getMessage()];
        }
    }

    public function runBatch($max = 10)
    {
        $results = [];
        for ($i = 0; $i < $max; $i++) {
            $r = $this->runOnce();
            if (!$r['ran']) break;
            $results[] = $r;
        }
        return $results;
    }
}
