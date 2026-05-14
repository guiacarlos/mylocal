<?php
/**
 * test_notif.php - Tests de integración de NOTIFICACIONES.
 *
 * EJECUTAR DESDE CLI:
 *   php spa/server/tests/test_notif.php
 *
 * Cubre:
 *   1. NoopDriver: envío sin red, resultado correcto
 *   2. NotificationEngine: registra en notif_log
 *   3. Template: guardar y renderizar con variables {{var}}
 *   4. Template: variables desconocidas quedan vacías
 *   5. sendTemplate: integración Engine + Template
 *   6. notif_list: log accesible y ordenado
 */

declare(strict_types=1);

$root = realpath(__DIR__ . '/../../..');
require_once $root . '/spa/server/lib.php';

define('NOTIF_CAP_ROOT', $root . '/CAPABILITIES');
require_once $root . '/CAPABILITIES/NOTIFICACIONES/drivers/NoopDriver.php';
require_once $root . '/CAPABILITIES/NOTIFICACIONES/drivers/EmailDriver.php';
require_once $root . '/CAPABILITIES/NOTIFICACIONES/drivers/WhatsAppDriver.php';
require_once $root . '/CAPABILITIES/NOTIFICACIONES/Template.php';
require_once $root . '/CAPABILITIES/NOTIFICACIONES/NotificationEngine.php';
require_once $root . '/CAPABILITIES/NOTIFICACIONES/NotificationsApi.php';

echo "========================================\n";
echo " MyLocal - Test NOTIFICACIONES\n";
echo "========================================\n";

$failed = 0;
$passed = 0;

function chk(string $name, bool $ok, string $detail = ''): void
{
    global $failed, $passed;
    if ($ok) { $passed++; echo "  [PASS] $name\n"; }
    else      { $failed++; echo "  [FAIL] $name" . ($detail ? " — $detail" : '') . "\n"; }
}

// 1. NoopDriver directo
$noop   = new \Notificaciones\Drivers\NoopDriver();
$result = $noop->send('test@ejemplo.es', 'Asunto', 'Cuerpo');
chk('noop driver nombre', $noop->nombre() === 'noop');
chk('noop enviado=true', ($result['enviado'] ?? false) === true);
chk('noop driver en result', ($result['driver'] ?? '') === 'noop');
chk('noop destinatario en result', ($result['destinatario'] ?? '') === 'test@ejemplo.es');

// 2. NotificationEngine usa noop (por defecto sin config)
// Forzamos noop como driver activo
data_put('config', 'notif_settings', ['id' => 'notif_settings', 'driver' => 'noop'], true);

$log1 = \Notificaciones\NotificationEngine::send('dest@ejemplo.es', 'Prueba', 'Cuerpo de prueba', ['local_id' => 'l_test_notif']);
chk('engine registra en log', isset($log1['id']) && str_starts_with($log1['id'], 'nl_'));
chk('engine log driver=noop', ($log1['driver'] ?? '') === 'noop');
chk('engine log enviado=true', ($log1['enviado'] ?? false) === true);

// 3. Template: guardar y renderizar
\Notificaciones\Template::save(
    'bienvenida',
    'Bienvenido, {{nombre}}!',
    '<p>Hola {{nombre}}, gracias por unirte a {{local}}.</p>'
);
$rendered = \Notificaciones\Template::render('bienvenida', ['nombre' => 'Ana', 'local' => 'Bar El Sol']);
chk('template asunto renderizado', $rendered['asunto'] === 'Bienvenido, Ana!');
chk('template cuerpo renderizado', str_contains($rendered['cuerpo'], 'Bar El Sol'));

// 4. Variable desconocida queda vacía
$rendered2 = \Notificaciones\Template::render('bienvenida', ['nombre' => 'Pedro']);
chk('variable desconocida queda vacía', str_contains($rendered2['cuerpo'], 'Pedro') && !str_contains($rendered2['cuerpo'], '{{local}}'));

// 5. sendTemplate: integración completa
$log2 = \Notificaciones\NotificationEngine::sendTemplate(
    'cliente@ejemplo.es',
    'bienvenida',
    ['nombre' => 'Luis', 'local' => 'Restaurante Bello'],
    ['local_id' => 'l_test_notif']
);
chk('sendTemplate registra log', isset($log2['id']));
chk('sendTemplate asunto correcto', ($log2['asunto'] ?? '') === 'Bienvenido, Luis!');

// 6. notif_list via API
$user = ['id' => 'u_test', 'local_id' => 'l_test_notif', 'role' => 'admin'];
$lista = \Notificaciones\handle_notificaciones('notif_list', ['local_id' => 'l_test_notif'], $user);
chk('notif_list devuelve items', isset($lista['items']) && count($lista['items']) >= 2);

// Plantilla desconocida lanza excepción
$exTpl = false;
try {
    \Notificaciones\Template::render('plantilla_inexistente', []);
} catch (\RuntimeException $e) {
    $exTpl = str_contains($e->getMessage(), 'no encontrada');
}
chk('plantilla inexistente lanza RuntimeException', $exTpl);

// Limpieza
foreach (data_all('notif_log') as $l) {
    if (($l['meta']['local_id'] ?? '') === 'l_test_notif') data_delete('notif_log', $l['id']);
}
data_delete('templates_notif', 'bienvenida');

echo "----------------------------------------\n";
echo " Resultado: $passed pasados, $failed fallidos\n";
echo "========================================\n";
exit($failed > 0 ? 1 : 0);
