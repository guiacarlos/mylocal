<?php
/**
 * test_openclaude.php — Gate del conector OpenClaude + EventBus (Ola J).
 *
 * Filosofia: NO llamamos a Anthropic real (sin red, sin gastar tokens).
 * Validamos los invariantes que el plan exige:
 *
 *   EventBus:
 *     1. on() registra listeners por evento
 *     2. emit() invoca todos los listeners del evento
 *     3. emit() inyecta _event y _emitted_at en el payload
 *     4. Listener que lanza excepcion NO burbujea al caller (resiliencia)
 *     5. reset(event) limpia solo ese evento; reset() limpia todos
 *     6. Multiples listeners se ejecutan en orden de registro
 *
 *   OpenClaudeClient:
 *     7. isEnabled() = false cuando api_key esta vacia (default)
 *     8. complete() sin api_key devuelve success=false sin tocar la red
 *
 *   OpenClaudeApi handler:
 *     9. openclaude_status sin config devuelve enabled=false con mensaje
 *    10. openclaude_complete sin auth admin devuelve "no autorizado"
 *    11. openclaude_complete sin config devuelve "no configurado"
 *    12. openclaude_complete sin prompt devuelve "prompt requerido"
 *
 * Si falla → exit 1 → build.ps1 aborta.
 */

declare(strict_types=1);

$root = realpath(__DIR__ . '/../../..');
require_once $root . '/spa/server/lib.php';
require_once $root . '/CORE/EventBus.php';
require_once $root . '/CAPABILITIES/AI/OpenClaudeClient.php';
require_once $root . '/CAPABILITIES/AI/OpenClaudeApi.php';

echo "========================================\n";
echo " MyLocal - Test OpenClaude + EventBus (Ola J)\n";
echo "========================================\n";

$failed = 0;
$passed = 0;
function chk(string $name, bool $ok, string $detail = ''): void
{
    global $failed, $passed;
    if ($ok) { $passed++; echo "  [PASS] $name\n"; }
    else      { $failed++; echo "  [FAIL] $name" . ($detail ? " — $detail" : '') . "\n"; }
}

// ── EventBus ────────────────────────────────────────────────────
EventBus::reset();

$captured = [];
EventBus::on('test.evento', function ($d) use (&$captured) { $captured[] = $d; });
EventBus::emit('test.evento', ['valor' => 42]);
chk('EventBus.emit invoca listener', count($captured) === 1);
chk('EventBus.emit pasa el payload', ($captured[0]['valor'] ?? null) === 42);
chk('EventBus.emit inyecta _event', ($captured[0]['_event'] ?? null) === 'test.evento');
chk('EventBus.emit inyecta _emitted_at',
    isset($captured[0]['_emitted_at']) && is_string($captured[0]['_emitted_at']));

// Resiliencia: listener que lanza excepcion no burbujea
EventBus::reset();
$invocadoSegundo = false;
EventBus::on('test.evento', function () { throw new \RuntimeException('listener roto'); });
EventBus::on('test.evento', function () use (&$invocadoSegundo) { $invocadoSegundo = true; });
$burbujeo = false;
try {
    EventBus::emit('test.evento', []);
} catch (\Throwable $e) {
    $burbujeo = true;
}
chk('emit NO propaga excepciones del listener', $burbujeo === false);
chk('listener subsiguiente se ejecuta aunque uno anterior crashee', $invocadoSegundo);

// Multiples listeners ejecutan en orden
EventBus::reset();
$orden = [];
EventBus::on('orden.test', function () use (&$orden) { $orden[] = 'A'; });
EventBus::on('orden.test', function () use (&$orden) { $orden[] = 'B'; });
EventBus::on('orden.test', function () use (&$orden) { $orden[] = 'C'; });
EventBus::emit('orden.test', []);
chk('listeners ejecutan en orden de registro', $orden === ['A', 'B', 'C']);

// reset(event) limpia solo ese evento
EventBus::reset();
$llamadasA = 0; $llamadasB = 0;
EventBus::on('a', function () use (&$llamadasA) { $llamadasA++; });
EventBus::on('b', function () use (&$llamadasB) { $llamadasB++; });
EventBus::reset('a');
EventBus::emit('a', []);
EventBus::emit('b', []);
chk('reset(event) borra solo ese evento', $llamadasA === 0 && $llamadasB === 1);

EventBus::reset();
chk('registeredEvents tras reset total = []', EventBus::registeredEvents() === []);

// ── OpenClaudeClient ────────────────────────────────────────────
// Antes de tocar OPTIONS confirmamos el estado por defecto del tenant.
// Si algun test residual dejo openclaude.api_key, lo apartamos y
// restauramos al final.
require_once $root . '/CAPABILITIES/OPTIONS/optiosconect.php';
$opt = mylocal_options();
$apiKeyBackup  = $opt->get('openclaude.api_key', null);
$modelBackup   = $opt->get('openclaude.model', null);
$opt->set('openclaude.api_key', '');
$opt->set('openclaude.model', '');

chk('isEnabled() = false cuando api_key vacia', \AI\OpenClaudeClient::isEnabled() === false);

$client = new \AI\OpenClaudeClient('', '', 1);
$resp = $client->complete('hola');
chk('complete() sin api_key devuelve success=false', ($resp['success'] ?? null) === false);
chk('complete() sin api_key NO toca la red (mensaje claro)',
    isset($resp['error']) && str_contains((string) $resp['error'], 'api_key'));

// ── OpenClaudeApi handler ───────────────────────────────────────
$status = \AI\handle_openclaude('openclaude_status', [], ['role' => 'admin']);
chk('openclaude_status success=true sin config', ($status['success'] ?? null) === true);
chk('openclaude_status enabled=false sin config', ($status['enabled'] ?? null) === false);
chk('openclaude_status mensaje cita api_key',
    isset($status['message']) && str_contains((string) $status['message'], 'api_key'));

$sinAuth = \AI\handle_openclaude('openclaude_complete', ['prompt' => 'hola'], ['role' => 'sala']);
chk('openclaude_complete con rol no-admin -> no autorizado',
    ($sinAuth['success'] ?? null) === false
    && isset($sinAuth['error'])
    && str_contains((string) $sinAuth['error'], 'no autorizado'));

$adminSinConfig = \AI\handle_openclaude('openclaude_complete', ['prompt' => 'hola'], ['role' => 'admin']);
chk('openclaude_complete admin sin config -> enabled=false',
    ($adminSinConfig['success'] ?? null) === false
    && ($adminSinConfig['enabled'] ?? null) === false);

// Habilitar parcialmente: ponemos api_key pero el client tiene timeout=1s.
// Con prompt vacio el handler corta ANTES de intentar la llamada.
$opt->set('openclaude.api_key', 'sk-ant-fake-for-test-only');
$promptVacio = \AI\handle_openclaude('openclaude_complete', ['prompt' => ''], ['role' => 'admin']);
chk('openclaude_complete con prompt vacio -> "prompt requerido"',
    ($promptVacio['success'] ?? null) === false
    && isset($promptVacio['error'])
    && str_contains((string) $promptVacio['error'], 'prompt'));

$accionRara = \AI\handle_openclaude('openclaude_inventada', [], ['role' => 'admin']);
chk('accion no reconocida devuelve success=false',
    ($accionRara['success'] ?? null) === false
    && isset($accionRara['error'])
    && str_contains((string) $accionRara['error'], 'desconocida'));

// ── Restauracion del estado OPTIONS ─────────────────────────────
if ($apiKeyBackup === null) {
    // No habia valor antes — lo borramos para no contaminar.
    $opt->set('openclaude.api_key', '');
} else {
    $opt->set('openclaude.api_key', $apiKeyBackup);
}
if ($modelBackup === null) {
    $opt->set('openclaude.model', '');
} else {
    $opt->set('openclaude.model', $modelBackup);
}
EventBus::reset();

echo "----------------------------------------\n";
echo " Resultado: $passed pasados, $failed fallidos\n";
echo "========================================\n";
exit($failed > 0 ? 1 : 0);
