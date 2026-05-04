<?php
/**
 * create-admin.php — CLI para bootstrap del primer superadmin.
 *
 * Ejecutar desde el servidor:
 *   php server/bin/create-admin.php
 *
 * Rechaza la ejecución vía HTTP. El directorio bin/ está bloqueado por el
 * .htaccess, pero como defensa en profundidad se valida `php_sapi_name`.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden: solo CLI.\n");
}

require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../handlers/auth.php';

echo "\n┌─────────────────────────────────────────────┐\n";
echo   "│  Socolá — bootstrap de superadmin            │\n";
echo   "└─────────────────────────────────────────────┘\n\n";

fwrite(STDOUT, 'Email: ');
$email = trim((string) fgets(STDIN));
fwrite(STDOUT, 'Nombre completo: ');
$name = trim((string) fgets(STDIN));

fwrite(STDOUT, 'Contraseña (no se mostrará): ');
system('stty -echo 2>/dev/null');
$password = trim((string) fgets(STDIN));
system('stty echo 2>/dev/null');
echo "\n";
fwrite(STDOUT, 'Repetir contraseña: ');
system('stty -echo 2>/dev/null');
$password2 = trim((string) fgets(STDIN));
system('stty echo 2>/dev/null');
echo "\n";

if ($password !== $password2) {
    fwrite(STDERR, "\nLas contraseñas no coinciden.\n");
    exit(1);
}

try {
    $normalized = s_email($email);
    assert_password_strength($password);

    if (find_user_by_email($normalized)) {
        fwrite(STDERR, "\nYa existe un usuario con ese email.\n");
        exit(2);
    }

    $cfg = load_config('auth');
    $hash = password_hash($password, PASSWORD_ARGON2ID, $cfg['argon2'] ?? []);
    $id = 'u_' . bin2hex(random_bytes(8));
    data_put('users', $id, [
        'id' => $id,
        'email' => $normalized,
        'name' => $name,
        'role' => 'superadmin',
        'password_hash' => $hash,
    ], true);

    echo "\n✅ Superadmin creado: $normalized (id=$id)\n";
    echo "   Entra en https://tu-dominio/login con esas credenciales.\n\n";
} catch (Throwable $e) {
    fwrite(STDERR, "\n❌ " . $e->getMessage() . "\n");
    exit(1);
}
