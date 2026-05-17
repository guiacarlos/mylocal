<?php
/**
 * setup.php — asistente web de primera configuración.
 *
 * Accesible en /setup tras el primer despliegue.
 * Solo funciona si no existen usuarios en STORAGE/. Una vez que existe
 * cualquier usuario (sea por auto-bootstrap o por este wizard), se muestra
 * el mensaje "sistema ya configurado" y redirige al login.
 *
 * Seguridad: la única forma de crear usuarios es estar autenticado como admin
 * o usar este wizard cuando la base está vacía. No hay otra superficie.
 */

declare(strict_types=1);

$root     = __DIR__;
$dataRoot = $root . '/spa/server/data';
$usersDir = $dataRoot . '/users';

$alreadyConfigured = is_dir($usersDir) && count(glob($usersDir . '/*.json') ?: []) > 0;

$error   = '';
$created = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyConfigured) {
    $email    = strtolower(trim((string)($_POST['email']    ?? '')));
    $name     = trim((string)($_POST['name']     ?? 'Administrador'));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm']  ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email no válido.';
    } elseif (strlen($password) < 10) {
        $error = 'La contraseña debe tener al menos 10 caracteres.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        if (!is_dir($usersDir)) mkdir($usersDir, 0755, true);
        $id  = 'u_' . bin2hex(random_bytes(8));
        $doc = json_encode([
            'id'            => $id,
            'email'         => $email,
            'name'          => $name,
            'role'          => 'superadmin',
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'active'        => true,
            '_createdAt'    => date('c'),
            '_updatedAt'    => date('c'),
            '_version'      => 1,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($usersDir . '/' . $id . '.json', $doc, LOCK_EX);
        header('Location: /acceder');
        exit;
    }
}
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Configuración inicial — MyLocal</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f9f9f7;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1.5rem}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:1.5rem;padding:2rem;width:100%;max-width:420px}
    h1{font-size:1.4rem;font-weight:700;letter-spacing:-.03em;margin-bottom:.25rem}
    p{font-size:.85rem;color:#6b7280;margin-bottom:1.5rem}
    label{display:block;font-size:.75rem;font-weight:500;color:#374151;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.05em}
    input{width:100%;padding:.65rem 1rem;border:1px solid #e5e7eb;border-radius:.75rem;font-size:.875rem;outline:none;transition:border-color .15s;margin-bottom:1rem}
    input:focus{border-color:#000}
    button{width:100%;padding:.75rem;background:#000;color:#fff;border:none;border-radius:.75rem;font-size:.875rem;font-weight:600;cursor:pointer;margin-top:.25rem}
    button:hover{background:#1f2937}
    .error{background:#fef2f2;color:#dc2626;border-radius:.75rem;padding:.65rem 1rem;font-size:.8rem;margin-bottom:1rem}
    .done{text-align:center;padding:1rem 0}
    a{color:#000;font-weight:500}
  </style>
</head>
<body>
<div class="card">
<?php if ($alreadyConfigured): ?>
  <div class="done">
    <h1>Sistema ya configurado</h1>
    <p style="margin-top:.75rem">Ya existen usuarios registrados.<br><br><a href="/acceder">Ir al acceso &rarr;</a></p>
  </div>
<?php else: ?>
  <h1>Configuración inicial</h1>
  <p>Crea el superadmin de MyLocal. Solo funciona cuando la base está vacía.</p>
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post">
    <label>Email de administrador</label>
    <input type="email" name="email" required autofocus placeholder="admin@minegocio.es"
      value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    <label>Nombre</label>
    <input type="text" name="name" placeholder="Nombre completo"
      value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    <label>Contraseña (mín. 10 caracteres)</label>
    <input type="password" name="password" required minlength="10" placeholder="••••••••••">
    <label>Repetir contraseña</label>
    <input type="password" name="confirm" required minlength="10" placeholder="••••••••••">
    <button type="submit">Crear superadmin y entrar</button>
  </form>
<?php endif; ?>
</div>
</body>
</html>
