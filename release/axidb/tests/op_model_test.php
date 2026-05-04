<?php
/**
 * AxiDB - Smoke test del Op model (Fase 1.3).
 *
 * Verifica: round-trip toArray/fromArray, validate ok/fail, execute real de
 *           Insert y Select sobre una coleccion temporal, y el stub Ai\Ask
 *           devolviendo NOT_IMPLEMENTED con mensaje claro.
 * Uso: php axidb/tests/op_model_test.php
 */

require_once __DIR__ . '/../axi.php';

use Axi\Engine\AxiException;
use Axi\Engine\Op\Ai\Ask;
use Axi\Engine\Op\Insert;
use Axi\Engine\Op\Operation;
use Axi\Engine\Op\Select;
use Axi\Engine\Result;

$PASS = 0;
$FAIL = 0;

function check(string $name, bool $cond, string $details = ''): void
{
    global $PASS, $FAIL;
    if ($cond) {
        $PASS++;
        echo "  [ok] $name\n";
    } else {
        $FAIL++;
        echo "  [FAIL] $name" . ($details ? " -- $details" : "") . "\n";
    }
}

echo "=== Op model smoke test ===\n";

// ---------------------------------------------------------------------------
echo "\n[1] Round-trip toArray/fromArray\n";

$s = (new Select('products'))
    ->where('price', '<', 3)
    ->orderBy('price')
    ->limit(20);

$arr = $s->toArray();
check('Select toArray incluye op=select',       ($arr['op'] ?? null) === 'select');
check('Select toArray incluye collection',       ($arr['collection'] ?? null) === 'products');
check('Select toArray incluye where',            is_array($arr['where'] ?? null) && count($arr['where']) === 1);
check('Select toArray incluye order_by',         is_array($arr['order_by'] ?? null));
check('Select toArray incluye limit=20',         ($arr['limit'] ?? null) === 20);

$s2 = Select::fromArray($arr);
check('Select fromArray devuelve Select',        $s2 instanceof Select);
check('Select fromArray reconstruye collection', $s2->collection === 'products');
check('Select fromArray reconstruye where',      ($s2->params['where'][0]['field'] ?? null) === 'price');
check('Select fromArray reconstruye limit',      ($s2->params['limit'] ?? null) === 20);

// ---------------------------------------------------------------------------
echo "\n[2] validate() OK en Ops bien formados\n";

try {
    $s->validate();
    check('Select valido no lanza', true);
} catch (\Throwable $e) {
    check('Select valido no lanza', false, $e->getMessage());
}

$i = (new Insert('notas'))->data(['title' => 't', 'body' => 'b']);
try {
    $i->validate();
    check('Insert valido no lanza', true);
} catch (\Throwable $e) {
    check('Insert valido no lanza', false, $e->getMessage());
}

// ---------------------------------------------------------------------------
echo "\n[3] validate() FAIL con codigo correcto\n";

$bad = new Select('');  // collection vacia
try {
    $bad->validate();
    check('Select sin collection debe fallar', false, 'no tiro');
} catch (AxiException $e) {
    check('Select sin collection lanza AxiException', true);
    check('Codigo = VALIDATION_FAILED',               $e->getAxiCode() === AxiException::VALIDATION_FAILED);
}

$badInsert = new Insert('notas');  // sin data
try {
    $badInsert->validate();
    check('Insert sin data debe fallar', false, 'no tiro');
} catch (AxiException $e) {
    check('Insert sin data lanza AxiException',       true);
    check('Codigo = VALIDATION_FAILED',               $e->getAxiCode() === AxiException::VALIDATION_FAILED);
}

// ---------------------------------------------------------------------------
echo "\n[4] Execute real: Insert + Select sobre coleccion temporal\n";

$db = Axi(['data_root' => __DIR__ . '/_tmp_opmodel']);
@mkdir(__DIR__ . '/_tmp_opmodel', 0777, true);

// Insert via Operation directa
$res = $db->execute(
    (new Insert('tmp_notes'))->data(['title' => 'hola', 'body' => 'mundo'])
);
check('Insert execute devuelve success',         ($res['success'] ?? null) === true, json_encode($res));
$insertedId = $res['data']['_id'] ?? ($res['data']['id'] ?? null);
check('Insert devuelve _id generado',            is_string($insertedId) && $insertedId !== '');

// Insert via JSON {op:...}
$res2 = $db->execute([
    'op'         => 'insert',
    'collection' => 'tmp_notes',
    'data'       => ['title' => 'segundo', 'body' => 'doc'],
]);
check('Insert via {op:insert} devuelve success', ($res2['success'] ?? null) === true);

// Select via Operation
$sel = $db->execute(new Select('tmp_notes'));
check('Select execute devuelve success',         ($sel['success'] ?? null) === true);
$items = $sel['data']['items'] ?? [];
check('Select devuelve ambos docs insertados',   count($items) >= 2, 'count=' . count($items));

// Select con where via JSON
$sel2 = $db->execute([
    'op'         => 'select',
    'collection' => 'tmp_notes',
    'where'      => [['field' => 'title', 'op' => '=', 'value' => 'segundo']],
]);
check('Select con where filtra',                 ($sel2['success'] ?? null) === true);
$filtered = $sel2['data']['items'] ?? [];
check('Select where devuelve 1 doc',             count($filtered) === 1, 'count=' . count($filtered));

// ---------------------------------------------------------------------------
echo "\n[5] Ai\\Ask (Fase 6): backend NoopLlm responde y ejecuta Op\n";

// Insertamos un par de docs para que el agente tenga algo que contar.
$db->execute((new \Axi\Engine\Op\Insert('tmp_notes'))->data(['title' => 'tercero', 'body' => 'extra']));

$ask = (new Ask())->prompt('count tmp_notes');
$ar  = $db->execute($ask);
check('Ai\\Ask success=true (Fase 6 implementada)', ($ar['success'] ?? null) === true, json_encode($ar));
check('Ai\\Ask devuelve answer string',             is_string($ar['data']['answer'] ?? null) && $ar['data']['answer'] !== '');
check('Ai\\Ask devuelve observation con count',     is_array($ar['data']['observation'] ?? null) && isset($ar['data']['observation']['data']['count']));
check('Ai\\Ask answer status = done',               ($ar['data']['status'] ?? null) === 'done');

// Validacion sigue activa: prompt vacio falla con VALIDATION_FAILED
$bad = $db->execute((new Ask())->prompt(''));
check('Ai\\Ask con prompt vacio falla',             ($bad['success'] ?? null) === false);
check('Codigo = VALIDATION_FAILED',                 ($bad['code'] ?? null) === AxiException::VALIDATION_FAILED);

// ---------------------------------------------------------------------------
echo "\n[6] help() funciona en todas las Ops\n";

foreach ([Select::class, Insert::class, Ask::class] as $opClass) {
    $help = $opClass::help();
    check("$opClass::help() devuelve HelpEntry",           is_object($help));
    check("$opClass::help() tiene name no vacio",          ($help->name ?? '') !== '');
    check("$opClass::help() tiene >=3 examples",           count($help->examples) >= 3);
    check("$opClass::help()->toArray() produce array",     is_array($help->toArray()));
}

// ---------------------------------------------------------------------------
echo "\n[7] Op desconocido devuelve OP_UNKNOWN\n";

$unknown = $db->execute(['op' => 'nopenope', 'collection' => 'x']);
check('Op desconocido success=false',            ($unknown['success'] ?? null) === false);
check('Op desconocido code = OP_UNKNOWN',        ($unknown['code'] ?? null) === AxiException::OP_UNKNOWN);

// ---------------------------------------------------------------------------
echo "\n[8] Retrocompat: contrato legacy {action} sigue funcionando\n";

$legacy = $db->execute(['action' => 'health_check']);
check('Legacy action=health_check ok',           ($legacy['success'] ?? null) === true);
check('Legacy devuelve engine=AxiDB',            str_contains($legacy['data']['engine'] ?? '', 'AxiDB'));

// ---------------------------------------------------------------------------
// Limpieza
$tmp = __DIR__ . '/_tmp_opmodel';
if (is_dir($tmp)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tmp, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($tmp);
}

echo "\n=== Resultado: $PASS passed, $FAIL failed ===\n";
exit($FAIL === 0 ? 0 : 1);
