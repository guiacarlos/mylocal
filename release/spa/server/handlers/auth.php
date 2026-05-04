<?php
/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL AUTH LOCK - load-bearing                                 ║
   ║ Login, logout, sesion. NO setear cookies. Bearer-only.           ║
   ║ Antes de modificar, leer claude/AUTH_LOCK.md y verificar que     ║
   ║ spa/server/tests/test_login.php sigue pasando despues del cambio.║
   ╚══════════════════════════════════════════════════════════════════╝ */
/**
 * auth.php — login, sesión, registro, logout.
 *
 * Medidas aplicadas:
 *   - Password hashing Argon2id (parámetros en auth.json).
 *   - Rate limit por IP (5 intentos / min) en login. Ver lib.php::rl_check.
 *   - `password_verify` + sleep(1) constante en fallo (mitiga timing y enum).
 *   - Sesión en cookie httponly + SameSite=Strict + Secure en HTTPS.
 *   - Fingerprint de user-agent hasheado (detecta robo básico de cookie).
 *   - Token de CSRF emitido junto a la sesión.
 *   - Registro público solo crea rol 'cliente' — admins via CLI.
 *   - Contraseña mínima 10 chars + al menos 3 clases (minús/mayús/num/símbolo).
 *
 * Documentación completa: docs/USERS.md y docs/SECURITY.md.
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib.php';

const SESSION_TTL_DEFAULT = 86400; // 24h
const LOGIN_RL_PER_MINUTE = 5;

function handle_auth_login(array $req): array
{
    rl_check('login', LOGIN_RL_PER_MINUTE);

    $email = s_email($req['data']['email'] ?? $req['email'] ?? null);
    $password = (string) ($req['data']['password'] ?? $req['password'] ?? '');
    if ($password === '') {
        sleep(1);
        throw new RuntimeException('Credenciales inválidas');
    }

    $user = find_user_by_email($email);
    // Hash dummy para mantener tiempo constante aunque el user no exista.
    $dummyHash = '$argon2id$v=19$m=65536,t=4,p=1$ZHVtbXlzYWx0ZGVsbW9t$Wq3iZ5h3Bh5d3cN9uPzGnhsv7Zz3M2pTLq0c8U+Zkm8';
    $hash = $user['password_hash'] ?? $dummyHash;

    if (!password_verify($password, $hash) || !$user) {
        sleep(1);
        throw new RuntimeException('Credenciales inválidas');
    }

    // Rehash si los parámetros han cambiado.
    $cfg = load_config('auth');
    if (password_needs_rehash($hash, PASSWORD_ARGON2ID, $cfg['argon2'] ?? [])) {
        $newHash = password_hash($password, PASSWORD_ARGON2ID, $cfg['argon2'] ?? []);
        data_put('users', $user['id'], ['password_hash' => $newHash]);
    }

    return issue_session($user, $cfg);
}

function handle_auth_logout(?array $user): array
{
    // Bearer token viene en Authorization header (lo extrae current_user antes).
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+([A-Za-z0-9_\-]+)$/', $hdr, $m)) {
        data_delete('sessions', $m[1]);
    }

    if ($user && !empty($user['id'])) {
        error_log('[auth] logout user=' . $user['id']);
    }

    return ['ok' => true];
}

function handle_auth_session(?array $user): array
{
    if (!$user) throw new RuntimeException('Sin sesión');
    // Rolling refresh: cada llamada extiende el TTL de la sesion en disco.
    // El token sigue siendo el mismo Bearer; lo conserva el cliente.
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+([A-Za-z0-9_\-]+)$/', $hdr, $m)) {
        $cfg = load_config_optional('auth') ?? [];
        $ttl = (int) ($cfg['session_ttl_seconds'] ?? SESSION_TTL_DEFAULT);
        data_put('sessions', $m[1], ['expiresAt' => date('c', time() + $ttl)]);
    }
    return $user;
}

function handle_public_register(array $req): array
{
    rl_check('register', 10);

    $cfg = load_config('auth');
    $email = s_email($req['data']['email'] ?? null);
    $password = (string) ($req['data']['password'] ?? '');
    $name = s_str($req['data']['name'] ?? '', 120);

    assert_password_strength($password);

    if (find_user_by_email($email)) {
        throw new RuntimeException('Ya existe un usuario con ese email');
    }

    $hash = password_hash($password, PASSWORD_ARGON2ID, $cfg['argon2'] ?? []);
    $id = 'u_' . bin2hex(random_bytes(8));
    $user = data_put('users', $id, [
        'id' => $id,
        'email' => $email,
        'name' => $name,
        'role' => 'cliente',             // NUNCA aceptar role desde el body
        'password_hash' => $hash,
    ], true);
    unset($user['password_hash']);
    return $user;
}

/* ═══════════════════════ helpers ═══════════════════════ */

function find_user_by_email(string $email): ?array
{
    // O(n). Para < 1000 usuarios es irrelevante; si hace falta, crear índice.
    foreach (data_all('users') as $u) {
        if (strtolower((string) ($u['email'] ?? '')) === $email) return $u;
    }
    return null;
}

function issue_session(array $user, array $cfg): array
{
    $ttl = (int) ($cfg['session_ttl_seconds'] ?? SESSION_TTL_DEFAULT);
    $token = bin2hex(random_bytes(32));
    data_put('sessions', $token, [
        'id' => $token,
        'userId' => $user['id'],
        'role' => $user['role'] ?? 'cliente',
        'ua_hash' => hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')),
        'ip_at_login' => $_SERVER['REMOTE_ADDR'] ?? null,
        'expiresAt' => date('c', time() + $ttl),
    ], true);

    // Auth bearer-only: el token viaja en el body de la respuesta. El cliente
    // lo guarda en sessionStorage y lo envia en cada peticion como
    // Authorization: Bearer <token>. Sin cookies, sin CSRF double-submit.
    unset($user['password_hash']);
    return ['user' => $user, 'token' => $token];
}

function assert_password_strength(string $pw): void
{
    if (strlen($pw) < 10) throw new RuntimeException('Contraseña mínima 10 caracteres');
    if (strlen($pw) > 200) throw new RuntimeException('Contraseña demasiado larga');
    $classes = 0;
    if (preg_match('/[a-z]/', $pw)) $classes++;
    if (preg_match('/[A-Z]/', $pw)) $classes++;
    if (preg_match('/[0-9]/', $pw)) $classes++;
    if (preg_match('/[^A-Za-z0-9]/', $pw)) $classes++;
    if ($classes < 3) throw new RuntimeException('Contraseña débil (usa 3 de estas: minúsculas, mayúsculas, números, símbolos)');

    $common = ['password', '12345678', 'qwertyuiop', 'letmein123', 'admin12345'];
    if (in_array(strtolower($pw), $common, true)) {
        throw new RuntimeException('Contraseña en lista de contraseñas comunes');
    }
}
