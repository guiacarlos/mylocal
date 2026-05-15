<?php
/**
 * test_openclaw.php — Gate de la capability OPENCLAW (Ola J.4).
 *
 * Cubre las garantias del plan:
 *   1. Manifest publico es ACCESIBLE sin auth (es para que el admin de
 *      OpenClaw lo importe).
 *   2. Manifest dinamico: sin openclaw.tools configurado, devuelve catalogo
 *      vacio + setup_required=true + setup_hint accionable.
 *   3. Manifest con openclaw.tools configurado devuelve esas tools (no las
 *      hardcodea).
 *   4. openclaw_call sin skill key valida → HTTP 401 + success=false.
 *   5. openclaw_call con clave correcta pero accion NO en allowed_actions
 *      → fail-safe (rechaza con mensaje claro).
 *   6. openclaw_call con accion EN allowed_actions → no crashea
 *      (regresion: hasta Ola L _oc_call llamaba a execute() con 2 args
 *      cuando execute() requiere 3 -> ArgumentCountError).
 *   7. openclaw_status sin rol admin → no autorizado.
 *   8. openclaw_status con admin sin config: skill_configured=false,
 *      push_configured=false.
 *   9. openclaw_event_push sin push_url → "no configurada".
 *  10. openclaw_event_push sin rol admin → no autorizado.
 *  11. validateKey rechaza clave vacia / mismatch / sin config (hash_equals).
 *  12. isAllowed con whitelist vacia → false para CUALQUIER accion (fail-safe).
 *
 * Si falla → exit 1 → build.ps1 aborta.
 */

declare(strict_types=1);

$root = realpath(__DIR__ . '/../../..');
require_once $root . '/spa/server/lib.php';
require_once $root . '/CAPABILITIES/OPENCLAW/OpenClawSkillManifest.php';
require_once $root . '/CAPABILITIES/OPENCLAW/OpenClawSkillExecutor.php';
require_once $root . '/CAPABILITIES/OPENCLAW/OpenClawPushClient.php';
require_once $root . '/CAPABILITIES/OPENCLAW/OpenClawApi.php';

echo "========================================\n";
echo " MyLocal - Test OPENCLAW (Ola J.4)\n";
echo "========================================\n";

$failed = 0;
$passed = 0;
function chk(string $name, bool $ok, string $detail = ''): void
{
    global $failed, $passed;
    if ($ok) { $passed++; echo "  [PASS] $name\n"; }
    else      { $failed++; echo "  [FAIL] $name" . ($detail ? " — $detail" : '') . "\n"; }
}

// ── Backup + reset de OPTIONS namespace openclaw ────────────────
require_once $root . '/CAPABILITIES/OPTIONS/optiosconect.php';
$opt = mylocal_options();
$backup = [];
foreach ([
    'openclaw.app_name',
    'openclaw.app_description',
    'openclaw.tools',
    'openclaw.skill_api_key',
    'openclaw.push_url',
    'openclaw.push_channel',
    'openclaw.default_local_id',
    'openclaw.allowed_actions',
    'openclaw.agent_role',
] as $key) {
    $backup[$key] = $opt->get($key, null);
}

// Reset agresivo
$opt->set('openclaw.app_name', '');
$opt->set('openclaw.app_description', '');
$opt->set('openclaw.tools', []);
$opt->set('openclaw.skill_api_key', '');
$opt->set('openclaw.push_url', '');
$opt->set('openclaw.push_channel', '');
$opt->set('openclaw.default_local_id', '');
$opt->set('openclaw.allowed_actions', []);

// ── 1-3. Manifest dinamico ──────────────────────────────────────
$mfRes = \OpenClaw\handle_openclaw_capability('openclaw_manifest', [], [], []);
chk('manifest success=true sin auth (es publico)', ($mfRes['success'] ?? null) === true);

$mf = $mfRes['manifest'] ?? [];
chk('manifest tiene schema_version', ($mf['schema_version'] ?? '') === '1.0');
chk('manifest sin tools configurados → tools=[]', is_array($mf['tools'] ?? null) && count($mf['tools']) === 0);
chk('manifest sin tools → setup_required=true', ($mf['setup_required'] ?? null) === true);
chk('manifest sin tools → setup_hint cita openclaw.tools',
    isset($mf['setup_hint']) && str_contains($mf['setup_hint'], 'openclaw.tools'));

// Configurar dos tools y verificar que aparecen
$opt->set('openclaw.app_name', 'Test App');
$opt->set('openclaw.tools', [
    ['name' => 'tarea_list', 'description' => 'Lista tareas', 'params' => ['local_id']],
    ['name' => 'cita_list',  'description' => 'Lista citas',  'params' => [['name' => 'local_id', 'required' => true]]],
]);
$mfRes2 = \OpenClaw\handle_openclaw_capability('openclaw_manifest', [], [], []);
$mf2 = $mfRes2['manifest'] ?? [];
chk('manifest con 2 tools configurados devuelve 2 tools', count($mf2['tools'] ?? []) === 2);
$toolNames = array_column($mf2['tools'] ?? [], 'name');
chk('manifest expone los names declarados',
    in_array('tarea_list', $toolNames, true) && in_array('cita_list', $toolNames, true));
chk('manifest dinamico: NO existe setup_required cuando hay tools',
    !isset($mf2['setup_required']));

// ── 4. openclaw_call sin clave / clave invalida ─────────────────
$callSinKey = \OpenClaw\handle_openclaw_capability('openclaw_call', ['tool' => 'tarea_list'], [], []);
chk('openclaw_call sin skill_key → success=false', ($callSinKey['success'] ?? null) === false);
chk('openclaw_call sin skill_key → mensaje "Clave"',
    isset($callSinKey['error']) && str_contains((string) $callSinKey['error'], 'Clave'));

$opt->set('openclaw.skill_api_key', 'secret-correct');
$callKeyMala = \OpenClaw\handle_openclaw_capability(
    'openclaw_call',
    ['tool' => 'tarea_list', 'skill_key' => 'secret-wrong'],
    [],
    []
);
chk('openclaw_call con clave incorrecta → success=false',
    ($callKeyMala['success'] ?? null) === false);

// ── 5. fail-safe: clave OK + accion NO en allowed_actions ───────
$callSinWhitelist = \OpenClaw\handle_openclaw_capability(
    'openclaw_call',
    ['tool' => 'tarea_list', 'skill_key' => 'secret-correct'],
    [],
    []
);
chk('openclaw_call con clave OK pero sin allowed_actions → success=false (fail-safe)',
    ($callSinWhitelist['success'] ?? null) === false);
chk('mensaje fail-safe cita openclaw.allowed_actions',
    isset($callSinWhitelist['error']) && str_contains((string) $callSinWhitelist['error'], 'allowed_actions'));

// ── 6. NO crashea con accion en allowed_actions (regresion 3 args) ──
// Antes de la fix de Ola L, _oc_call invocaba execute($tool, $params) con
// 2 args mientras execute() declara 3 args -> ArgumentCountError.
$opt->set('openclaw.allowed_actions', ['tarea_list']);
$callPermitido = \OpenClaw\handle_openclaw_capability(
    'openclaw_call',
    ['tool' => 'tarea_list', 'skill_key' => 'secret-correct', 'params' => ['local_id' => 'l_test_openclaw']],
    [],
    []
);
// El handler de tareas devuelve el array de tareas directamente (sin
// envolver en {success, data}), por eso solo verificamos que NO crashee
// y que SI devuelva un array. Antes de la fix de _oc_call esto lanzaba
// ArgumentCountError y el resultado era null.
chk('openclaw_call con accion permitida NO crashea (regresion ArgumentCountError)',
    is_array($callPermitido));

// ── 7. openclaw_status auth ─────────────────────────────────────
$statusSinAuth = \OpenClaw\handle_openclaw_capability('openclaw_status', [], ['role' => 'sala'], []);
chk('openclaw_status con rol != admin → no autorizado',
    ($statusSinAuth['success'] ?? null) === false
    && isset($statusSinAuth['error'])
    && str_contains((string) $statusSinAuth['error'], 'no autorizado'));

$statusAdmin = \OpenClaw\handle_openclaw_capability('openclaw_status', [], ['role' => 'admin'], []);
chk('openclaw_status admin → success=true', ($statusAdmin['success'] ?? null) === true);
chk('openclaw_status reporta skill_configured=true (lo pusimos arriba)',
    ($statusAdmin['skill_configured'] ?? null) === true);
chk('openclaw_status reporta push_configured=false (sin push_url)',
    ($statusAdmin['push_configured'] ?? null) === false);
chk('openclaw_status tools_available lista las tools configuradas',
    isset($statusAdmin['tools_available'])
    && in_array('tarea_list', $statusAdmin['tools_available'], true));

// ── 8-9. openclaw_event_push ────────────────────────────────────
$pushSinAuth = \OpenClaw\handle_openclaw_capability(
    'openclaw_event_push',
    ['event' => 'test'],
    ['role' => 'camarero'],
    []
);
chk('event_push sin rol admin → no autorizado',
    ($pushSinAuth['success'] ?? null) === false
    && str_contains((string) ($pushSinAuth['error'] ?? ''), 'no autorizado'));

$pushSinUrl = \OpenClaw\handle_openclaw_capability(
    'openclaw_event_push',
    ['event' => 'test'],
    ['role' => 'admin'],
    []
);
chk('event_push admin sin push_url → error "no configurada"',
    ($pushSinUrl['success'] ?? null) === false
    && str_contains((string) ($pushSinUrl['error'] ?? ''), 'push_url'));

// ── 10. validateKey ─────────────────────────────────────────────
chk('validateKey rechaza clave vacia',
    \OpenClaw\OpenClawSkillExecutor::validateKey('') === false);
chk('validateKey rechaza mismatch',
    \OpenClaw\OpenClawSkillExecutor::validateKey('wrong') === false);
chk('validateKey acepta match exacto',
    \OpenClaw\OpenClawSkillExecutor::validateKey('secret-correct') === true);

// ── 11. Accion desconocida ──────────────────────────────────────
$accionRara = \OpenClaw\handle_openclaw_capability('openclaw_inventada', [], ['role' => 'admin'], []);
chk('accion desconocida devuelve success=false',
    ($accionRara['success'] ?? null) === false
    && isset($accionRara['error'])
    && str_contains((string) $accionRara['error'], 'desconocida'));

// ── 12. fail-safe sin whitelist (resetear allowed_actions) ──────
$opt->set('openclaw.allowed_actions', []);
chk('Sin allowed_actions configurado: openclaw_call rechaza CUALQUIER accion',
    (\OpenClaw\handle_openclaw_capability(
        'openclaw_call',
        ['tool' => 'tarea_list', 'skill_key' => 'secret-correct'],
        [],
        []
    )['success'] ?? null) === false);

// ── Restauracion del estado OPTIONS ─────────────────────────────
foreach ($backup as $key => $val) {
    $opt->set($key, $val === null ? '' : $val);
}

echo "----------------------------------------\n";
echo " Resultado: $passed pasados, $failed fallidos\n";
echo "========================================\n";
exit($failed > 0 ? 1 : 0);
