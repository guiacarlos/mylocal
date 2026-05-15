<?php
/**
 * test_delivery.php — Gate AUTH_LOCK de la capability DELIVERY (Ola H).
 *
 * EJECUTAR DESDE CLI:
 *   php spa/server/tests/test_delivery.php
 *
 * Cubre:
 *   1. Crear pedido (con código de seguimiento generado)
 *   2. Listar pedidos por local
 *   3. Cambiar estado del pedido (transiciones válidas)
 *   4. Estado inválido → InvalidArgumentException
 *   5. Crear vehículo + listar + update toggle activo
 *   6. Asignar entrega (pedido → vehículo)
 *   7. Listar entregas del día
 *   8. Registrar incidencia (TIPOS whitelistados)
 *   9. Seguimiento público por código: respuesta reducida (sin local_id)
 *  10. Seguimiento público con código inexistente: encontrado=false
 *  11. Seguimiento público sin código → InvalidArgumentException
 *
 * Si falla → exit 1 → build.ps1 aborta.
 */

declare(strict_types=1);

$root = realpath(__DIR__ . '/../../..');
require_once $root . '/spa/server/lib.php';

define('DELIVERY_CAP_ROOT', $root . '/CAPABILITIES');
require_once $root . '/CAPABILITIES/DELIVERY/PedidoModel.php';
require_once $root . '/CAPABILITIES/DELIVERY/VehiculoModel.php';
require_once $root . '/CAPABILITIES/DELIVERY/EntregaModel.php';
require_once $root . '/CAPABILITIES/DELIVERY/IncidenciaModel.php';
require_once $root . '/CAPABILITIES/DELIVERY/DeliveryPublicApi.php';

echo "========================================\n";
echo " MyLocal - Test DELIVERY (Ola H)\n";
echo "========================================\n";

$failed = 0;
$passed = 0;
function chk(string $name, bool $ok, string $detail = ''): void
{
    global $failed, $passed;
    if ($ok) { $passed++; echo "  [PASS] $name\n"; }
    else      { $failed++; echo "  [FAIL] $name" . ($detail ? " — $detail" : '') . "\n"; }
}

// Prefijo de test para limpiar al final sin tocar datos reales.
$local = 'l_test_delivery';

// Limpieza inicial: residuos de runs previos crasheados.
foreach (\Delivery\PedidoModel::listByLocal($local) as $p) data_delete('pedidos', $p['id']);
foreach (\Delivery\VehiculoModel::listByLocal($local) as $v) data_delete('vehiculos', $v['id']);
foreach (\Delivery\IncidenciaModel::listByLocal($local) as $i) data_delete('incidencias', $i['id']);

// 1. Crear pedido
$pedido = \Delivery\PedidoModel::create([
    'local_id' => $local,
    'cliente'  => 'Ana García',
    'telefono' => '600000001',
    'email'    => 'ana@example.com',
    'direccion'=> 'Calle Mayor 1, Murcia',
    'items'    => [['ref' => 'X', 'qty' => 2]],
    'notas'    => 'Llamar al portero',
]);
chk('pedido creado con id pd_*', isset($pedido['id']) && str_starts_with($pedido['id'], 'pd_'));
chk('estado inicial = recibido', ($pedido['estado'] ?? '') === 'recibido');
chk('codigo_seguimiento generado (8 chars)', isset($pedido['codigo_seguimiento']) && strlen($pedido['codigo_seguimiento']) === 8);
$codigo = $pedido['codigo_seguimiento'];

// 2. Listar pedidos por local
$lista = \Delivery\PedidoModel::listByLocal($local);
chk('listByLocal devuelve >= 1 pedido', count($lista) >= 1);
chk('listByLocal filtra solo por local', $lista[0]['local_id'] === $local);

// 3. Cambiar estado
foreach (['preparando', 'en_ruta', 'entregado'] as $estado) {
    $upd = \Delivery\PedidoModel::cambiarEstado($pedido['id'], $estado);
    chk("transicion a $estado persistida", ($upd['estado'] ?? '') === $estado);
}

// 4. Estado inválido
$rechazo = false;
try {
    \Delivery\PedidoModel::cambiarEstado($pedido['id'], 'inventado');
} catch (\InvalidArgumentException $e) {
    $rechazo = str_contains($e->getMessage(), 'Estado');
}
chk('estado fuera de whitelist rechazado', $rechazo);

// 5. Vehículo: crear + listar + update
$vehiculo = \Delivery\VehiculoModel::create($local, [
    'matricula' => '1234ABC',
    'modelo'    => 'Renault Kangoo',
    'conductor' => 'Luis López',
]);
chk('vehiculo creado con id vh_*', isset($vehiculo['id']) && str_starts_with($vehiculo['id'], 'vh_'));
chk('vehiculo estado por defecto = activo', ($vehiculo['estado'] ?? null) === 'activo');

$vehs = \Delivery\VehiculoModel::listByLocal($local);
chk('listByLocal vehiculos >= 1', count($vehs) >= 1);

$desactivado = \Delivery\VehiculoModel::update($vehiculo['id'], ['estado' => 'inactivo']);
chk('estado inactivo persiste tras update', ($desactivado['estado'] ?? null) === 'inactivo');

$estadoMalo = \Delivery\VehiculoModel::update($vehiculo['id'], ['estado' => 'fuera_de_whitelist']);
chk('estado fuera de whitelist ignorado (defensivo)', ($estadoMalo['estado'] ?? null) === 'inactivo');

// 6. Asignar entrega
$entrega = \Delivery\EntregaModel::asignar([
    'local_id'    => $local,
    'pedido_id'   => $pedido['id'],
    'vehiculo_id' => $vehiculo['id'],
    'fecha'       => '2026-06-01',
    'orden'       => 1,
]);
chk('entrega creada con id en_*', isset($entrega['id']) && str_starts_with($entrega['id'], 'en_'));
chk('entrega referencia pedido + vehiculo', $entrega['pedido_id'] === $pedido['id'] && $entrega['vehiculo_id'] === $vehiculo['id']);

// 7. Listar entregas del día
$delDia = \Delivery\EntregaModel::listByFecha($local, '2026-06-01');
chk('listByFecha devuelve la entrega creada', count($delDia) === 1 && $delDia[0]['id'] === $entrega['id']);

$otroDia = \Delivery\EntregaModel::listByFecha($local, '2026-06-02');
chk('listByFecha aisla por fecha', count($otroDia) === 0);

// 8. Incidencia
$incid = \Delivery\IncidenciaModel::add([
    'pedido_id'   => $pedido['id'],
    'local_id'    => $local,
    'tipo'        => 'retraso',
    'descripcion' => 'Trafico denso en M-30.',
]);
chk('incidencia creada con id inc_*', isset($incid['id']) && str_starts_with($incid['id'], 'inc_'));
chk('incidencia tipo whitelistado', in_array($incid['tipo'], \Delivery\IncidenciaModel::TIPOS, true));
chk('crear incidencia marca pedido como "incidencia"',
    \Delivery\PedidoModel::get($pedido['id'])['estado'] === 'incidencia');

// Politica del modelo: tipo invalido NO lanza, cae defensivamente a 'otro'.
$incidMalTipo = \Delivery\IncidenciaModel::add([
    'pedido_id'   => $pedido['id'],
    'local_id'    => $local,
    'tipo'        => 'tipo_inventado',
    'descripcion' => 'X',
]);
chk('tipo fuera de whitelist cae a "otro" (defensivo)', ($incidMalTipo['tipo'] ?? '') === 'otro');

$incidSinPedido = false;
try {
    \Delivery\IncidenciaModel::add(['pedido_id' => '', 'tipo' => 'otro']);
} catch (\Throwable $e) {
    // s_id() lanza RuntimeException "Identificador invalido" antes de que
    // IncidenciaModel::add llegue a lanzar su InvalidArgumentException.
    // Cualquiera de las dos es valida: lo que importa es que NO crea el doc.
    $incidSinPedido = true;
}
chk('add sin pedido_id lanza excepcion (no crea doc)', $incidSinPedido);

$delPedido = \Delivery\IncidenciaModel::listByPedido($pedido['id']);
chk('listByPedido devuelve las incidencias', count($delPedido) >= 2);

// 9. Seguimiento público (sin auth) — respuesta reducida
// El pedido quedo en estado "incidencia" tras el paso 8 (politica del modelo).
$seg = \Delivery\handle_delivery_public('pedido_seguimiento', ['codigo' => $codigo]);
chk('seguimiento publico encontrado=true', ($seg['encontrado'] ?? false) === true);
chk('seguimiento publico expone codigo', ($seg['codigo'] ?? '') === $codigo);
chk('seguimiento publico expone estado actual', ($seg['estado'] ?? '') === 'incidencia');
chk('seguimiento publico NO expone local_id (info interna)', !isset($seg['local_id']));
chk('seguimiento publico NO expone email (info personal)', !isset($seg['email']));
chk('seguimiento publico NO expone telefono (info personal)', !isset($seg['telefono']));
chk('seguimiento publico NO expone items', !isset($seg['items']));

// 10. Código inexistente
$noExiste = \Delivery\handle_delivery_public('pedido_seguimiento', ['codigo' => 'ZZZ99999']);
chk('seguimiento codigo inexistente -> encontrado=false', ($noExiste['encontrado'] ?? null) === false);

// 11. Código vacío
$sinCodigo = false;
try {
    \Delivery\handle_delivery_public('pedido_seguimiento', ['codigo' => '']);
} catch (\InvalidArgumentException $e) {
    $sinCodigo = str_contains($e->getMessage(), 'codigo');
}
chk('seguimiento sin codigo lanza InvalidArgumentException', $sinCodigo);

// ── Limpieza ────────────────────────────────────────────────────
foreach (\Delivery\PedidoModel::listByLocal($local) as $p) data_delete('pedidos', $p['id']);
foreach (\Delivery\VehiculoModel::listByLocal($local) as $v) data_delete('vehiculos', $v['id']);
foreach (\Delivery\EntregaModel::listByFecha($local, '2026-06-01') as $e) data_delete('entregas', $e['id']);
foreach (\Delivery\IncidenciaModel::listByLocal($local) as $i) data_delete('incidencias', $i['id']);

echo "----------------------------------------\n";
echo " Resultado: $passed pasados, $failed fallidos\n";
echo "========================================\n";
exit($failed > 0 ? 1 : 0);
