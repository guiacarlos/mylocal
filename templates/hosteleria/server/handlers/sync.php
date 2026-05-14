<?php
/**
 * sync.php — push/pull del oplog de SynaxisCore (Fase 3).
 *
 * Protocolo:
 *   Request  { ops: [{op, collection, targetId, version, ts, payload}], since?: ts }
 *   Response { applied: [opIds], remote: [remoteOps since <ts>], serverTs }
 *
 * Resolución de conflictos v0: last-write-wins por `_version` del doc.
 * Si el doc que llega tiene `_version` menor al que ya existe, se descarta
 * (el cliente deberá hacer pull y re-aplicar local).
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib.php';

function handle_sync(array $req, ?array $user = null): array
{
    if (!$user) throw new RuntimeException('sync requiere sesión');
    rl_check('sync', 60);

    $ops = (array) ($req['data']['ops'] ?? []);
    if (count($ops) > 500) throw new RuntimeException('Batch sync > 500 ops');

    $applied = [];
    foreach ($ops as $op) {
        $col = (string) ($op['collection'] ?? '');
        $id = (string) ($op['targetId'] ?? '');
        $type = (string) ($op['op'] ?? '');
        if ($col === '' || $id === '' || $type === '') continue;

        if ($type === 'delete') {
            data_delete($col, $id);
            $applied[] = $op['id'] ?? '';
            continue;
        }

        // put — respetar last-write-wins por _version
        $payload = (array) ($op['payload'] ?? []);
        $existing = data_get($col, $id);
        $incomingVersion = (int) ($payload['_version'] ?? 0);
        $existingVersion = (int) ($existing['_version'] ?? 0);
        if ($existing && $existingVersion > $incomingVersion) {
            continue; // conflicto: cliente se re-sincronizará con pull
        }
        data_put($col, $id, $payload, true);
        $applied[] = $op['id'] ?? '';
    }

    // Pull: operaciones del servidor más recientes que req.data.since.
    // Placeholder: cuando haya log persistente en server, filtrar aquí por
    // timestamp. Fase 3 del roadmap.
    $remote = [];

    return [
        'applied' => $applied,
        'remote' => $remote,
        'serverTs' => date('c'),
    ];
}
