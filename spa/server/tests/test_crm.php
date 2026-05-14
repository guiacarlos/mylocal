<?php
/**
 * test_crm.php - Tests de integración de CRM.
 *
 * EJECUTAR DESDE CLI:
 *   php spa/server/tests/test_crm.php
 *
 * Cubre:
 *   1. Crear contacto
 *   2. Email dedup: segundo create con mismo email devuelve duplicate_of
 *   3. Actualizar contacto
 *   4. Obtener contacto
 *   5. Añadir interacción
 *   6. Listar interacciones (orden descendente)
 *   7. Segmento por etiqueta
 *   8. Segmento por email (fragment)
 *   9. Eliminar contacto
 */

declare(strict_types=1);

$root = realpath(__DIR__ . '/../../..');
require_once $root . '/spa/server/lib.php';

define('CRM_CAP_ROOT', $root . '/CAPABILITIES');
require_once $root . '/CAPABILITIES/CRM/ContactoModel.php';
require_once $root . '/CAPABILITIES/CRM/InteraccionModel.php';
require_once $root . '/CAPABILITIES/CRM/SegmentoEngine.php';
require_once $root . '/CAPABILITIES/CRM/CrmAdminApi.php';

echo "========================================\n";
echo " MyLocal - Test CRM\n";
echo "========================================\n";

$failed = 0;
$passed = 0;

function chk(string $name, bool $ok, string $detail = ''): void
{
    global $failed, $passed;
    if ($ok) { $passed++; echo "  [PASS] $name\n"; }
    else      { $failed++; echo "  [FAIL] $name" . ($detail ? " — $detail" : '') . "\n"; }
}

$local = 'l_test_crm';
$user  = ['id' => 'u_test', 'local_id' => $local, 'role' => 'admin'];

// 1. Crear contacto
$ct1 = \Crm\ContactoModel::create($local, [
    'nombre'    => 'María López',
    'email'     => 'maria@ejemplo.es',
    'telefono'  => '600111222',
    'etiquetas' => ['vip', 'habitual'],
    'fuente'    => 'web',
]);
chk('contacto creado', isset($ct1['id']) && str_starts_with($ct1['id'], 'ct_'));
chk('etiquetas guardadas', in_array('vip', $ct1['etiquetas'] ?? [], true));

// 2. Dedup por email
$ct1b = \Crm\ContactoModel::create($local, ['nombre' => 'Maria Otra', 'email' => 'maria@ejemplo.es']);
chk('dedup devuelve duplicate_of', ($ct1b['duplicate_of'] ?? '') === $ct1['id']);
chk('dedup devuelve mismo id', ($ct1b['id'] ?? '') === $ct1['id']);

// 3. Actualizar
$updated = \Crm\ContactoModel::update($ct1['id'], ['telefono' => '600999888', 'etiquetas' => ['vip', 'habitual', 'nueva']]);
chk('telefono actualizado', ($updated['telefono'] ?? '') === '600999888');
chk('nueva etiqueta añadida', in_array('nueva', $updated['etiquetas'] ?? [], true));

// 4. Obtener
$got = \Crm\ContactoModel::get($ct1['id']);
chk('get devuelve contacto', $got !== null && ($got['nombre'] ?? '') === 'María López');

// 5. Añadir interacción
$ct2 = \Crm\ContactoModel::create($local, ['nombre' => 'Pedro Sanz', 'email' => 'pedro@ejemplo.es']);
$inter1 = \Crm\InteraccionModel::add($ct1['id'], $user['id'], ['tipo' => 'llamada', 'nota' => 'Primera llamada']);
chk('interaccion creada', isset($inter1['id']) && str_starts_with($inter1['id'], 'i_'));
chk('tipo guardado', ($inter1['tipo'] ?? '') === 'llamada');

$inter2 = \Crm\InteraccionModel::add($ct1['id'], $user['id'], ['tipo' => 'nota', 'nota' => 'Nota de seguimiento']);
chk('segunda interaccion creada', isset($inter2['id']));

// 6. Listar interacciones (orden desc)
$inters = \Crm\InteraccionModel::listByContacto($ct1['id']);
chk('lista tiene 2 interacciones', count($inters) === 2);
chk('orden descendente por ts', ($inters[0]['ts'] ?? '') >= ($inters[1]['ts'] ?? ''));

// 7. Segmento por etiqueta
$seg = \Crm\SegmentoEngine::query($local, ['etiqueta' => 'vip']);
chk('segmento etiqueta vip', count($seg) >= 1 && ($seg[0]['id'] ?? '') === $ct1['id']);

// 8. Segmento por fragmento de email
$segEmail = \Crm\SegmentoEngine::query($local, ['email' => 'pedro']);
chk('segmento email fragment', count($segEmail) === 1 && ($segEmail[0]['email'] ?? '') === 'pedro@ejemplo.es');

// 9. Eliminar contacto
\Crm\ContactoModel::delete($ct1['id']);
chk('contacto eliminado', \Crm\ContactoModel::get($ct1['id']) === null);

// Limpieza
foreach (data_all('crm_contactos') as $c) {
    if (($c['local_id'] ?? '') === $local) data_delete('crm_contactos', $c['id']);
}
foreach (data_all('crm_interacciones') as $i) {
    if (($i['contacto_id'] ?? '') === $ct1['id']) data_delete('crm_interacciones', $i['id']);
}

echo "----------------------------------------\n";
echo " Resultado: $passed pasados, $failed fallidos\n";
echo "========================================\n";
exit($failed > 0 ? 1 : 0);
