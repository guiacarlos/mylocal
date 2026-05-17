<?php
/**
 * analytics.php — contadores diarios de uso por local.
 *
 * Acciones:
 *   analytics_record (pública) — incrementa carta_visits o qr_scans del día
 *   analytics_get    (admin)   — devuelve los últimos 7 días con contadores
 *
 * Colección: analytics_daily, clave: {localId}_{YYYY-MM-DD}
 * Schema: { local_id, date, carta_visits, qr_scans }
 */

declare(strict_types=1);

function handle_analytics(string $action, array $req, ?array $user): array
{
    return match ($action) {
        'analytics_record' => analytics_record($req),
        'analytics_get'    => analytics_weekly_get($req),
        default            => throw new \RuntimeException("analytics: acción no reconocida: $action"),
    };
}

function analytics_record(array $req): array
{
    $localId = s_id((string)($req['data']['local_id'] ?? $req['local_id'] ?? 'l_default'));
    $type    = (string)($req['data']['type'] ?? $req['type'] ?? 'carta_visit');
    if (!in_array($type, ['carta_visit', 'qr_scan'], true)) return ['ok' => false];

    $key   = $localId . '_' . date('Y-m-d');
    $field = $type === 'qr_scan' ? 'qr_scans' : 'carta_visits';
    $day   = data_get('analytics_daily', $key) ?? [
        'local_id'     => $localId,
        'date'         => date('Y-m-d'),
        'carta_visits' => 0,
        'qr_scans'     => 0,
    ];
    $day[$field] = ((int)($day[$field] ?? 0)) + 1;
    data_put('analytics_daily', $key, $day, true);

    return ['ok' => true];
}

function analytics_weekly_get(array $req): array
{
    $localId = s_id((string)($req['data']['local_id'] ?? $req['local_id'] ?? 'l_default'));
    $result  = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $day  = data_get('analytics_daily', $localId . '_' . $date) ?? [];
        $result[] = [
            'date'         => $date,
            'carta_visits' => (int)($day['carta_visits'] ?? 0),
            'qr_scans'     => (int)($day['qr_scans']     ?? 0),
        ];
    }
    return ['days' => $result, 'local_id' => $localId];
}
