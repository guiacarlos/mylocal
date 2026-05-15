<?php
/**
 * test_tareas.php — Gate AUTH_LOCK de la capability TAREAS (Ola I).
 *
 * Cubre:
 *   1. Crear tarea (estado inicial pendiente, prioridad valida)
 *   2. Crear sin titulo / sin local_id → InvalidArgumentException
 *   3. Listar por local + filtro por estado
 *   4. Update transiciones de estado validas (pendiente → en_curso → hecho)
 *   5. Update con estado fuera de whitelist → InvalidArgumentException
 *   6. Update con prioridad invalida cae defensivamente a 'media'
 *   7. Delete + listar tras delete
 *   8. Delete de id inexistente → RuntimeException
 *   9. Orden por prioridad (alta → media → baja)
 *
 * Si falla → exit 1 → build.ps1 aborta.
 */

declare(strict_types=1);

$root = realpath(__DIR__ . '/../../..');
require_once $root . '/spa/server/lib.php';
require_once $root . '/CAPABILITIES/TAREAS/TareaModel.php';

echo "========================================\n";
echo " MyLocal - Test TAREAS (Ola I)\n";
echo "========================================\n";

$failed = 0;
$passed = 0;
function chk(string $name, bool $ok, string $detail = ''): void
{
    global $failed, $passed;
    if ($ok) { $passed++; echo "  [PASS] $name\n"; }
    else      { $failed++; echo "  [FAIL] $name" . ($detail ? " — $detail" : '') . "\n"; }
}

$local = 'l_test_tareas';

// Limpieza inicial: borrar datos residuales de runs previos crasheados.
foreach (\Tareas\TareaModel::listByLocal($local) as $t) data_delete('tareas', $t['id']);

// 1. Crear tarea
$t1 = \Tareas\TareaModel::create([
    'local_id'   => $local,
    'cliente_id' => 'cl_demo',
    'titulo'     => 'Cerrar Q4',
    'descripcion'=> 'Revisar facturas y cuadrar IVA del trimestre.',
    'prioridad'  => 'alta',
]);
chk('tarea creada con id ta_*', isset($t1['id']) && str_starts_with($t1['id'], 'ta_'));
chk('estado inicial = pendiente', ($t1['estado'] ?? '') === 'pendiente');
chk('prioridad whitelistada (alta)', ($t1['prioridad'] ?? '') === 'alta');

// 2. Validaciones de creacion
$sinTitulo = false;
try {
    \Tareas\TareaModel::create(['local_id' => $local, 'cliente_id' => 'cl_demo', 'titulo' => '']);
} catch (\InvalidArgumentException $e) {
    $sinTitulo = str_contains($e->getMessage(), 'titulo');
}
chk('crear sin titulo lanza InvalidArgumentException', $sinTitulo);

$sinLocal = false;
try {
    \Tareas\TareaModel::create(['titulo' => 'X', 'cliente_id' => 'cl_demo']);
} catch (\Throwable $e) {
    // s_id() rechaza '' antes de que TareaModel valide local_id.
    $sinLocal = true;
}
chk('crear sin local_id lanza excepcion', $sinLocal);

// 3. Listar + filtro por estado
\Tareas\TareaModel::create(['local_id' => $local, 'cliente_id' => 'cl_demo', 'titulo' => 'Pago nominas', 'prioridad' => 'media']);
\Tareas\TareaModel::create(['local_id' => $local, 'cliente_id' => 'cl_demo', 'titulo' => 'Limpieza vault', 'prioridad' => 'baja']);
$todas = \Tareas\TareaModel::listByLocal($local);
chk('listByLocal devuelve 3 tareas', count($todas) === 3);

$pendientes = \Tareas\TareaModel::listByLocal($local, 'pendiente');
chk('filtro por estado=pendiente devuelve 3', count($pendientes) === 3);

$hechas = \Tareas\TareaModel::listByLocal($local, 'hecho');
chk('filtro por estado=hecho devuelve 0', count($hechas) === 0);

// 4. Transiciones validas
$enCurso = \Tareas\TareaModel::update($t1['id'], ['estado' => 'en_curso']);
chk('transicion a en_curso persiste', ($enCurso['estado'] ?? '') === 'en_curso');

$hecha = \Tareas\TareaModel::update($t1['id'], ['estado' => 'hecho']);
chk('transicion a hecho persiste', ($hecha['estado'] ?? '') === 'hecho');

$pendNow = \Tareas\TareaModel::listByLocal($local, 'pendiente');
chk('tarea hecha sale del filtro pendiente', count($pendNow) === 2);

// 5. Estado invalido
$estadoMalo = false;
try {
    \Tareas\TareaModel::update($t1['id'], ['estado' => 'archived']);
} catch (\InvalidArgumentException $e) {
    $estadoMalo = str_contains($e->getMessage(), 'Estado');
}
chk('update con estado fuera de whitelist rechazado', $estadoMalo);

// 6. Prioridad invalida cae defensivamente a 'media'
$prioMala = \Tareas\TareaModel::update($t1['id'], ['prioridad' => 'urgente']);
chk('prioridad invalida cae a "media" (defensivo)', ($prioMala['prioridad'] ?? '') === 'media');

// 7. Delete
$id = $t1['id'];
\Tareas\TareaModel::delete($id);
chk('delete elimina tarea', \Tareas\TareaModel::get($id) === null);

$trasDelete = \Tareas\TareaModel::listByLocal($local);
chk('listByLocal tras delete = 2', count($trasDelete) === 2);

// 8. Delete inexistente
$delNoExiste = false;
try {
    \Tareas\TareaModel::delete('ta_no_existe_xx');
} catch (\RuntimeException $e) {
    $delNoExiste = str_contains($e->getMessage(), 'no encontrada');
}
chk('delete de id inexistente lanza RuntimeException', $delNoExiste);

// 9. Orden por prioridad — crear 3 con prioridades mezcladas y verificar
foreach (\Tareas\TareaModel::listByLocal($local) as $t) data_delete('tareas', $t['id']);
\Tareas\TareaModel::create(['local_id' => $local, 'cliente_id' => 'cl_demo', 'titulo' => 'C-baja',  'prioridad' => 'baja']);
\Tareas\TareaModel::create(['local_id' => $local, 'cliente_id' => 'cl_demo', 'titulo' => 'A-alta',  'prioridad' => 'alta']);
\Tareas\TareaModel::create(['local_id' => $local, 'cliente_id' => 'cl_demo', 'titulo' => 'B-media', 'prioridad' => 'media']);
$ordenado = \Tareas\TareaModel::listByLocal($local);
$prioridades = array_column($ordenado, 'prioridad');
chk('listByLocal ordena por prioridad alta→media→baja',
    $prioridades === ['alta', 'media', 'baja']);

// ── Limpieza ────────────────────────────────────────────────────
foreach (\Tareas\TareaModel::listByLocal($local) as $t) data_delete('tareas', $t['id']);

echo "----------------------------------------\n";
echo " Resultado: $passed pasados, $failed fallidos\n";
echo "========================================\n";
exit($failed > 0 ? 1 : 0);
