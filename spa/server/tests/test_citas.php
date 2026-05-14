<?php
/**
 * test_citas.php - Tests de integración de CITAS (agenda y reservas).
 *
 * EJECUTAR DESDE CLI:
 *   php spa/server/tests/test_citas.php
 *
 * Cubre:
 *   1. Crear recurso
 *   2. Crear cita (tryReserve)
 *   3. Listar citas por local
 *   4. Conflicto de horario detectado
 *   5. Borde exacto: fin de A == inicio de B → permitido
 *   6. Cancelar cita
 *   7. Intentar reservar en cita cancelada → permitido (no hay conflicto)
 *   8. Cita pública sin auth
 */

declare(strict_types=1);

$root = realpath(__DIR__ . '/../../..');
require_once $root . '/spa/server/lib.php';

define('CITAS_CAP_ROOT', $root . '/CAPABILITIES');
require_once $root . '/CAPABILITIES/CITAS/CitasModel.php';
require_once $root . '/CAPABILITIES/CITAS/RecursosModel.php';
require_once $root . '/CAPABILITIES/CITAS/CitasEngine.php';
require_once $root . '/CAPABILITIES/CITAS/CitasAdminApi.php';
require_once $root . '/CAPABILITIES/CITAS/CitasPublicApi.php';

echo "========================================\n";
echo " MyLocal - Test CITAS\n";
echo "========================================\n";

$failed = 0;
$passed = 0;

function chk(string $name, bool $ok, string $detail = ''): void
{
    global $failed, $passed;
    if ($ok) { $passed++; echo "  [PASS] $name\n"; }
    else      { $failed++; echo "  [FAIL] $name" . ($detail ? " — $detail" : '') . "\n"; }
}

$local = 'l_test_citas';
$user  = ['id' => 'u_test', 'local_id' => $local, 'role' => 'admin'];

// 1. Crear recurso
$recurso = \Citas\RecursosModel::create($local, ['nombre' => 'Sala A', 'tipo' => 'sala', 'capacidad' => 10]);
chk('recurso creado', isset($recurso['id']) && str_starts_with($recurso['id'], 'r_'));

// 2. Crear cita vía tryReserve
$cita1 = \Citas\CitasEngine::tryReserve([
    'local_id'    => $local,
    'recurso_id'  => $recurso['id'],
    'cliente'     => 'Ana García',
    'inicio'      => '2026-06-01T10:00:00+02:00',
    'fin'         => '2026-06-01T11:00:00+02:00',
    'estado'      => 'confirmada',
]);
chk('cita creada', isset($cita1['id']) && str_starts_with($cita1['id'], 'c_'));
chk('estado inicial pendiente', ($cita1['estado'] ?? '') === 'pendiente');

// 3. Listar citas
$lista = \Citas\CitasModel::listByLocal($local);
chk('lista tiene >= 1 cita', count($lista) >= 1);

// 4. Conflicto de horario
$conflicto = false;
try {
    \Citas\CitasEngine::tryReserve([
        'local_id'   => $local,
        'recurso_id' => $recurso['id'],
        'cliente'    => 'Bob',
        'inicio'     => '2026-06-01T10:30:00+02:00',
        'fin'        => '2026-06-01T11:30:00+02:00',
        'estado'     => 'confirmada',
    ]);
} catch (\InvalidArgumentException $e) {
    $conflicto = str_contains($e->getMessage(), 'Conflicto');
}
chk('conflicto solapamiento detectado', $conflicto);

// 5. Borde exacto: fin de cita1 == inicio de cita2 → sin conflicto
$borde = null;
try {
    $borde = \Citas\CitasEngine::tryReserve([
        'local_id'   => $local,
        'recurso_id' => $recurso['id'],
        'cliente'    => 'Carlos',
        'inicio'     => '2026-06-01T11:00:00+02:00',
        'fin'        => '2026-06-01T12:00:00+02:00',
        'estado'     => 'confirmada',
    ]);
} catch (\InvalidArgumentException $e) {
    $borde = null;
}
chk('borde exacto permitido (fin A == inicio B)', $borde !== null && isset($borde['id']));

// 6. Cancelar cita
$cancelada = \Citas\CitasEngine::cancel($cita1['id']);
chk('cita cancelada', ($cancelada['estado'] ?? '') === 'cancelada');

// 7. Reservar el mismo hueco tras cancelar → debe permitirse
$tras_cancel = null;
try {
    $tras_cancel = \Citas\CitasEngine::tryReserve([
        'local_id'   => $local,
        'recurso_id' => $recurso['id'],
        'cliente'    => 'Diana',
        'inicio'     => '2026-06-01T10:00:00+02:00',
        'fin'        => '2026-06-01T11:00:00+02:00',
        'estado'     => 'confirmada',
    ]);
} catch (\InvalidArgumentException $e) {
    $tras_cancel = null;
}
chk('hueco libre tras cancelar cita previa', $tras_cancel !== null);

// 8. Cita pública (sin auth)
$publica = \Citas\handle_citas_public('cita_publica_crear', [
    'local_id'   => $local,
    'recurso_id' => $recurso['id'],
    'cliente'    => 'Elena',
    'telefono'   => '600000001',
    'inicio'     => '2026-06-02T09:00:00+02:00',
    'fin'        => '2026-06-02T10:00:00+02:00',
]);
chk('cita pública creada', isset($publica['id']) && ($publica['estado'] ?? '') === 'pendiente');

// Limpieza: eliminar datos de test
foreach (\Citas\CitasModel::listByLocal($local) as $c) {
    data_delete('citas', $c['id']);
}
data_delete('recursos_agenda', $recurso['id']);

echo "----------------------------------------\n";
echo " Resultado: $passed pasados, $failed fallidos\n";
echo "========================================\n";
exit($failed > 0 ? 1 : 0);
