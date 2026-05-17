<?php
/**
 * maintenance.php — limpieza periódica de datos caducados en STORAGE/.
 *
 * Acción: purge_expired_data
 * Requiere: rol superadmin.
 *
 * Limpia:
 *   sessions/       — tokens expirados (expiresAt < now)
 *   _rl/            — ventanas de rate-limit antiguas (window < now - 120s)
 *   password_resets/ — tokens de recuperación usados o caducados
 *
 * Llamar manualmente desde el panel o programar via cron externo.
 * No borra datos de negocio (cartas, citas, reseñas…).
 */

declare(strict_types=1);

function handle_purge_expired(array $req, array $user): array
{
    $now = time();
    $sessionsDel = _purge_expired_sessions($now);
    $rlDel       = _purge_stale_rl_buckets($now);
    $resetsDel   = _purge_expired_resets($now);

    return [
        'sessions_purged'     => $sessionsDel,
        'rl_buckets_purged'   => $rlDel,
        'resets_purged'       => $resetsDel,
        'purged_at'           => date('c'),
    ];
}

function _purge_expired_sessions(int $now): int
{
    $dir = DATA_ROOT . '/sessions';
    if (!is_dir($dir)) return 0;
    $del = 0;
    foreach (glob($dir . '/*.json') ?: [] as $f) {
        $doc = json_decode((string)@file_get_contents($f), true);
        if (!is_array($doc)) { @unlink($f); $del++; continue; }
        $exp = $doc['expiresAt'] ?? '';
        if ($exp !== '' && strtotime($exp) < $now) { @unlink($f); $del++; }
    }
    return $del;
}

function _purge_stale_rl_buckets(int $now): int
{
    $base = DATA_ROOT . '/_rl';
    if (!is_dir($base)) return 0;
    $del = 0;
    foreach (glob($base . '/*/*.json') ?: [] as $f) {
        $doc = json_decode((string)@file_get_contents($f), true);
        if (!is_array($doc)) { @unlink($f); $del++; continue; }
        // Ventana ya cerrada hace más de 2 minutos — el bucket no se usará más
        if (((int)($doc['window'] ?? 0)) < ($now - 120)) { @unlink($f); $del++; }
    }
    return $del;
}

function _purge_expired_resets(int $now): int
{
    $dir = DATA_ROOT . '/password_resets';
    if (!is_dir($dir)) return 0;
    $del = 0;
    foreach (glob($dir . '/*.json') ?: [] as $f) {
        $doc = json_decode((string)@file_get_contents($f), true);
        if (!is_array($doc)) { @unlink($f); $del++; continue; }
        $used    = (bool)($doc['used'] ?? false);
        $expired = ((int)($doc['expires_at'] ?? 0)) < $now;
        if ($used || $expired) { @unlink($f); $del++; }
    }
    return $del;
}
