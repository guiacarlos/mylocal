<?php
/**
 * AxiDB - Agents\AuditLog: registro append-only de toda Op invocada por agentes.
 *
 * Subsistema: engine/agents
 * Responsable: una linea NDJSON por cada call() del Toolbox. Formato fijo:
 *   {ts, actor, op, params, success, code, duration_ms, snapshot?}
 *
 *              `actor` = "agent:<id>" — ese es el campo que la regla §6.7 de
 *              seguridad agentica exige. Se persiste en
 *              `STORAGE/_system/agents/audit.log` (no se rota — la rotacion
 *              cae en herramientas externas o operacion manual).
 *
 *              No bloquea la ejecucion: si el write falla por permisos, se
 *              ignora silenciosamente. La auditoria es best-effort, no critica.
 */

namespace Axi\Engine\Agents;

final class AuditLog
{
    public function __construct(private string $logPath) {}

    public function record(
        string $agentId,
        string $opName,
        array  $params,
        array  $result,
        ?string $snapshotName = null
    ): void {
        $row = [
            'ts'          => \date('c'),
            'actor'       => 'agent:' . $agentId,
            'op'          => $opName,
            'params'      => $this->sanitize($params),
            'success'     => (bool) ($result['success'] ?? false),
            'code'        => $result['code']        ?? null,
            'duration_ms' => $result['duration_ms'] ?? null,
        ];
        if ($snapshotName !== null) {
            $row['snapshot'] = $snapshotName;
        }
        $this->append($row);
    }

    /** Lee las ultimas N lineas del log. Util para CLI / dashboard. */
    public function tail(int $n = 50): array
    {
        if (!\is_file($this->logPath)) {
            return [];
        }
        $lines = \file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $tail = \array_slice($lines, -$n);
        $out = [];
        foreach ($tail as $line) {
            $row = \json_decode($line, true);
            if (\is_array($row)) { $out[] = $row; }
        }
        return $out;
    }

    public function path(): string { return $this->logPath; }

    private function append(array $row): void
    {
        $dir = \dirname($this->logPath);
        if (!\is_dir($dir)) {
            if (!@\mkdir($dir, 0700, true) && !\is_dir($dir)) {
                return;
            }
        }
        $line = \json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        @\file_put_contents($this->logPath, $line, FILE_APPEND | LOCK_EX);
    }

    /** Recorta payloads grandes para no inflar el log. */
    private function sanitize(array $params): array
    {
        $maxLen = 200;
        foreach ($params as $k => $v) {
            if (\is_string($v) && \strlen($v) > $maxLen) {
                $params[$k] = \substr($v, 0, $maxLen) . '...[+' . (\strlen($v) - $maxLen) . ']';
            } elseif (\is_array($v)) {
                if (\count($v) > 20) {
                    $params[$k] = ['...truncated', 'count' => \count($v)];
                } else {
                    $params[$k] = $this->sanitize($v);
                }
            }
        }
        return $params;
    }
}
