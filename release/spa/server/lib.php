<?php
/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL AUTH LOCK - load-bearing                                 ║
   ║ current_user(), data_*, rl_check. Bearer-only en HTTP_AUTHORIZATION.║
   ║ Antes de modificar, leer claude/AUTH_LOCK.md y verificar que     ║
   ║ spa/server/tests/test_login.php sigue pasando despues del cambio.║
   ╚══════════════════════════════════════════════════════════════════╝ */
/**
 * Utilidades compartidas + capa de seguridad del server adelgazado.
 *
 * Agrupa:
 *   - Constantes de rutas.
 *   - Persistencia en JSON con flock (data_*).
 *   - Config loader (estricto y opcional).
 *   - Sesiones con cookie httponly + SameSite Strict.
 *   - Roles: require_auth / require_role.
 *   - CSRF: double-submit cookie (issue_csrf_token / validate_csrf_or_die).
 *   - Rate limit por IP+scope (rl_check).
 *   - HTTP helper (cURL) para llamadas a APIs externas (Revolut, Gemini).
 *   - Sanitización mínima (s_str, s_email, s_id).
 *
 * Documentación completa del modelo de seguridad: docs/SECURITY.md.
 */

declare(strict_types=1);

const SERVER_ROOT = __DIR__;
const DATA_ROOT = __DIR__ . '/data';
const CONFIG_ROOT = __DIR__ . '/config';

/* ══════════════════════════════ Response ══════════════════════════════ */

/**
 * Emite la respuesta JSON estándar y termina.
 */
function resp(bool $success, $data = null, ?string $error = null): void
{
    echo json_encode(
        ['success' => $success, 'data' => $data, 'error' => $error],
        JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
    );
    exit;
}

/* ══════════════════════════════ Config ══════════════════════════════ */

function load_config(string $name): array
{
    $path = CONFIG_ROOT . '/' . $name . '.json';
    if (!file_exists($path)) {
        $example = $path . '.example';
        if (file_exists($example)) {
            throw new RuntimeException(
                "Config '$name.json' no existe. Copia '$name.json.example' a '$name.json' y rellena."
            );
        }
        throw new RuntimeException("Config '$name.json' no encontrada");
    }
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);
    if (!is_array($json)) throw new RuntimeException("Config '$name.json' mal formada");
    return $json;
}

/**
 * Versión tolerante: devuelve null si no existe, sin lanzar excepción.
 * Útil para `cors.json` que puede faltar en dev.
 */
function load_config_optional(string $name): ?array
{
    $path = CONFIG_ROOT . '/' . $name . '.json';
    if (!file_exists($path)) return null;
    try {
        return load_config($name);
    } catch (Throwable) {
        return null;
    }
}

/* ════════════════════════════ Persistencia ════════════════════════════ */

/**
 * Upsert atómico con flock. Aplica merge salvo que $replace = true.
 * Siempre setea _version, _createdAt, _updatedAt.
 */
function data_put(string $collection, string $id, array $data, bool $replace = false): array
{
    $collection = s_id($collection);
    $id = s_id($id);
    $dir = DATA_ROOT . '/' . $collection;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $path = $dir . '/' . $id . '.json';

    $fp = fopen($path, 'c+');
    if (!$fp) throw new RuntimeException("No se pudo abrir $path");
    try {
        if (!flock($fp, LOCK_EX)) throw new RuntimeException("Lock fallido: $path");
        $content = stream_get_contents($fp);
        $existing = $content ? json_decode($content, true) : null;

        if (is_array($existing) && !$replace) {
            $data = array_merge($existing, $data);
        }
        $data['id'] = $id;
        $data['_updatedAt'] = date('c');
        $data['_version'] = (is_array($existing) && isset($existing['_version']) ? (int) $existing['_version'] : 0) + 1;
        if (empty($data['_createdAt'])) {
            $data['_createdAt'] = $existing['_createdAt'] ?? date('c');
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        return $data;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

function data_get(string $collection, string $id): ?array
{
    $collection = s_id($collection);
    $id = s_id($id);
    $path = DATA_ROOT . '/' . $collection . '/' . $id . '.json';
    if (!file_exists($path)) return null;
    $j = json_decode(file_get_contents($path), true);
    return is_array($j) ? $j : null;
}

function data_delete(string $collection, string $id): bool
{
    $collection = s_id($collection);
    $id = s_id($id);
    $path = DATA_ROOT . '/' . $collection . '/' . $id . '.json';
    return file_exists($path) ? @unlink($path) : false;
}

function data_all(string $collection): array
{
    $collection = s_id($collection);
    $dir = DATA_ROOT . '/' . $collection;
    if (!is_dir($dir)) return [];
    $out = [];
    foreach (glob($dir . '/*.json') as $f) {
        $basename = basename($f, '.json');
        if (str_starts_with($basename, '_')) continue;
        $doc = json_decode(file_get_contents($f), true);
        if (is_array($doc)) $out[] = $doc;
    }
    return $out;
}

/* ════════════════════════════ Sanitización ════════════════════════════ */

/** Normaliza un id: solo alfanumérico, guiones, subrayados. Previene path traversal. */
function s_id(string $v): string
{
    $v = preg_replace('/[^a-zA-Z0-9_\-]/', '', $v) ?? '';
    if ($v === '' || str_contains($v, '..')) {
        throw new RuntimeException('Identificador inválido');
    }
    return $v;
}

function s_str(?string $v, int $max = 2000): string
{
    $v = (string) ($v ?? '');
    $v = str_replace(["\0"], '', $v);
    if (mb_strlen($v) > $max) $v = mb_substr($v, 0, $max);
    return trim($v);
}

function s_email(?string $v): string
{
    $v = strtolower(trim((string) ($v ?? '')));
    if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Email inválido');
    }
    if (strlen($v) > 254) throw new RuntimeException('Email demasiado largo');
    return $v;
}

function s_int($v, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
{
    $i = (int) $v;
    if ($i < $min || $i > $max) throw new RuntimeException('Número fuera de rango');
    return $i;
}

/* ════════════════════════════ Sesión / Auth ════════════════════════════ */

function session_cookie_opts(int $ttl): array
{
    return [
        'expires' => time() + $ttl,
        'path' => '/',
        'httponly' => true,                          // JS NO puede leerla → XSS no roba token
        'samesite' => 'Strict',                      // CSRF mitigado por el navegador
        'secure' => !empty($_SERVER['HTTPS']),       // solo por HTTPS en prod
    ];
}

function current_user(): ?array
{
    // Auth bearer-only: el token viaja en Authorization: Bearer <token>.
    // No usamos cookies. El cliente guarda el token en sessionStorage.
    $token = '';
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+([A-Za-z0-9_\-]+)$/', $hdr, $m)) {
        $token = $m[1];
    }
    if ($token === '') return null;

    $session = data_get('sessions', $token);
    if (!$session) return null;
    if (!empty($session['expiresAt']) && strtotime($session['expiresAt']) < time()) {
        // Limpieza perezosa.
        data_delete('sessions', $token);
        return null;
    }

    // Fingerprint básico: si el user-agent cambia drásticamente, invalida.
    $uaStored = $session['ua_hash'] ?? null;
    $uaNow = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($uaStored && $uaStored !== $uaNow) {
        data_delete('sessions', $token);
        return null;
    }

    $user = data_get('users', (string) ($session['userId'] ?? ''));
    if (!$user) return null;
    unset($user['password_hash']);
    return $user;
}

/**
 * Exige rol autenticado. Si no hay usuario → 401. Si rol no permitido → 403.
 */
function require_role(?array $user, array $allowed): void
{
    if (!$user) {
        http_response_code(401);
        resp(false, null, 'Unauthorized');
    }
    $role = strtolower((string) ($user['role'] ?? ''));
    if (!in_array($role, array_map('strtolower', $allowed), true)) {
        http_response_code(403);
        resp(false, null, 'Forbidden: rol insuficiente');
    }
}

/* ════════════════════════════ CSRF ════════════════════════════ */

/**
 * Double-submit cookie pattern:
 *   - Server emite `socola_csrf` cookie (NO httponly, JS puede leerla).
 *   - Cliente la echa en header `X-CSRF-Token` en operaciones state-changing.
 *   - Server compara ambas. Si coinciden y no están vacías → OK.
 *
 * La cookie de CSRF no es secreta por sí misma; la protección viene de la
 * combinación "solo JS del mismo origen puede leer su cookie" + SameSite=Strict
 * de la cookie de sesión.
 */
function issue_csrf_token(): string
{
    $existing = $_COOKIE['socola_csrf'] ?? '';
    if (preg_match('/^[a-f0-9]{64}$/', $existing)) return $existing;
    $token = bin2hex(random_bytes(32));
    setcookie('socola_csrf', $token, [
        'expires' => time() + 86400,
        'path' => '/',
        'httponly' => false,
        'samesite' => 'Strict',
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
    return $token;
}

function validate_csrf_or_die(): void
{
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $cookie = $_COOKIE['socola_csrf'] ?? '';
    if ($header === '' || $cookie === '' || !hash_equals($cookie, $header)) {
        http_response_code(419);
        resp(false, null, 'CSRF token inválido o ausente');
    }
}

/* ════════════════════════════ Rate limit ════════════════════════════ */

/**
 * Contador por minuto en archivo plano. `scope` permite tener buckets
 * distintos (login, ia, pagos).
 */
function rl_check(string $scope, int $limitPerMinute): void
{
    if ($limitPerMinute <= 0) return;
    $ip = preg_replace('/[^0-9a-fA-F:.]/', '_', (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')) ?: 'unknown';
    $dir = DATA_ROOT . '/_rl/' . s_id($scope);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $file = $dir . '/' . $ip . '.json';

    $now = time();
    $window = $now - ($now % 60);
    $state = ['window' => $window, 'count' => 0];
    if (file_exists($file)) {
        $raw = file_get_contents($file);
        $parsed = json_decode($raw, true);
        if (is_array($parsed)) {
            $state = ($parsed['window'] ?? 0) === $window ? $parsed : $state;
        }
    }
    $state['count'] = ((int) $state['count']) + 1;
    file_put_contents($file, json_encode($state), LOCK_EX);

    if ($state['count'] > $limitPerMinute) {
        http_response_code(429);
        resp(false, null, 'Rate limit: demasiadas peticiones');
    }
}

/* ════════════════════════════ HTTP helper ════════════════════════════ */

function http_json(string $url, string $method, array $payload, array $headers = [], int $timeout = 15): array
{
    $ch = curl_init($url);
    $body = json_encode($payload);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
        CURLOPT_POSTFIELDS => $method === 'GET' ? null : $body,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($out === false) throw new RuntimeException("HTTP error: $err");
    $json = json_decode((string) $out, true);
    return ['status' => (int) $code, 'body' => $json, 'raw' => $out];
}
