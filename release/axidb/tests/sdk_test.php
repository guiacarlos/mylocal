<?php
/**
 * AxiDB - SDK PHP test (Fase 1.6).
 *
 * Verifica que el mismo codigo de aplicacion funciona con EmbeddedTransport
 * y HttpTransport. Las dos rutas deben devolver la misma forma de respuesta.
 *
 * Uso: php axidb/tests/sdk_test.php
 */

require_once __DIR__ . '/../axi.php';

use Axi\Sdk\Php\Client;
use Axi\Sdk\Php\HttpTransport;
use Axi\Sdk\Php\EmbeddedTransport;

$PASS = 0;
$FAIL = 0;
function check(string $name, bool $cond, string $d = ''): void
{
    global $PASS, $FAIL;
    if ($cond) { $PASS++; echo "  [ok] $name\n"; }
    else       { $FAIL++; echo "  [FAIL] $name" . ($d ? " -- $d" : "") . "\n"; }
}

echo "=== SDK PHP test (Fase 1.6) ===\n\n";

// ---------------------------------------------------------------------------
echo "[A] Autoload + estructura\n";
check('Axi\\Sdk\\Php\\Client existe',           class_exists('Axi\\Sdk\\Php\\Client'));
check('Axi\\Sdk\\Php\\Transport existe (iface)', interface_exists('Axi\\Sdk\\Php\\Transport'));
check('Axi\\Sdk\\Php\\EmbeddedTransport existe', class_exists('Axi\\Sdk\\Php\\EmbeddedTransport'));
check('Axi\\Sdk\\Php\\HttpTransport existe',     class_exists('Axi\\Sdk\\Php\\HttpTransport'));
check('Axi\\Sdk\\Php\\Collection existe',        class_exists('Axi\\Sdk\\Php\\Collection'));

// ---------------------------------------------------------------------------
echo "\n[B] Construccion dual del Client\n";
$embedded = new Client();
check('Client() sin args = embedded', $embedded->transport()->name() === 'embedded');

$http = new Client('http://localhost:9991/axidb/api/axi.php');
check('Client(http://...) = HttpTransport', str_starts_with($http->transport()->name(), 'http:'));

$axi = new Client('axi://user:pass@localhost:80/ns');
check('Client(axi://...) normaliza a http://...', str_starts_with($axi->transport()->name(), 'http:'));

// ---------------------------------------------------------------------------
echo "\n[C] CRUD via Client embedded (modo happy path)\n";
$c = new Client();
$col = 'sdk_test_' . uniqid();

$ins = $c->collection($col)->insert(['title' => 'uno', 'body' => 'b1']);
check('Collection::insert() success',           ($ins['success'] ?? null) === true);
$id = $ins['data']['_id'] ?? null;
check('Insert devuelve _id string',             is_string($id) && $id !== '');

$c->collection($col)->insert(['title' => 'dos', 'body' => 'b2']);
$c->collection($col)->insert(['title' => 'tres', 'body' => 'b3']);

$docs = $c->collection($col)->get();
check('Collection::get() devuelve 3 docs',      count($docs) === 3, 'count=' . count($docs));

$first = $c->collection($col)->orderBy('title')->first();
check('Collection::first() devuelve 1 doc',     is_array($first));
check('first.title es "dos" (orden asc)',       ($first['title'] ?? null) === 'dos', json_encode($first));

$onlyTres = $c->collection($col)->where('title', '=', 'tres')->get();
check('where devuelve 1 doc',                   count($onlyTres) === 1);

$count = $c->collection($col)->count();
check('count() == 3',                           $count === 3);

$exists = $c->collection($col)->exists($id);
check('exists(id) = true',                      $exists === true);

$notExists = $c->collection($col)->exists('does_not_exist_' . uniqid());
check('exists(id inexistente) = false',         $notExists === false);

$upd = $c->collection($col)->update($id, ['body' => 'editado']);
check('Collection::update success',             ($upd['success'] ?? null) === true);

$del = $c->collection($col)->delete($id);
check('Collection::delete success',             ($del['success'] ?? null) === true);

// Cleanup
$storagePath = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
$colPath = $storagePath . '/' . $col;
if (is_dir($colPath)) {
    foreach (glob($colPath . '/*.json') as $f) @unlink($f);
    foreach (glob($colPath . '/.versions/*/*') as $f) @unlink($f);
    foreach (glob($colPath . '/.versions/*') as $d) @rmdir($d);
    @rmdir($colPath . '/.versions');
    @rmdir($colPath);
}

// ---------------------------------------------------------------------------
echo "\n[D] Matriz: mismos Ops contra Embedded y HTTP\n";
echo "    Arranca php -S localhost:9991 en background para test HTTP.\n";

// Intentar iniciar el server. Si falla, saltamos la parte HTTP.
$serverCmd = PHP_BINARY . ' -S localhost:9991 -t ' . escapeshellarg(realpath(__DIR__ . '/../..')) . ' > /dev/null 2>&1 &';
$descriptors = [];
$process = proc_open($serverCmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
usleep(600000);  // 600ms para que arranque

// Test: ping via HTTP.
$http = new Client('http://localhost:9991/axidb/api/axi.php');
$pingRes = $http->execute(['op' => 'ping']);
$httpReachable = ($pingRes['success'] ?? null) === true;

if (!$httpReachable) {
    echo "  [skip] HTTP transport: servidor no arrancado automaticamente (normal en Windows).\n";
    echo "         Para test HTTP completo: php -S localhost:9991 -t . en terminal aparte.\n";
}

if ($httpReachable) {
    check('HTTP ping reachable', true);
    // Ejecutar misma Op contra ambos transports y comparar forma.
    $emb  = new Client();
    $httpR = $http->execute(['op' => 'help']);
    $embR  = $emb->execute(['op' => 'help']);

    check('HTTP help.success = true',               ($httpR['success'] ?? null) === true);
    check('Embedded help.success = true',           ($embR['success'] ?? null) === true);
    check('HTTP y Embedded devuelven mismo total Ops',
        ($httpR['data']['total'] ?? 0) === ($embR['data']['total'] ?? 0),
        "http=" . ($httpR['data']['total'] ?? '?') . " emb=" . ($embR['data']['total'] ?? '?'));

    // Describe: shape idéntica
    $hd = $http->execute(['op' => 'describe']);
    $ed = $emb->execute(['op' => 'describe']);
    check('Describe success ambos',     ($hd['success'] ?? null) === true && ($ed['success'] ?? null) === true);
    $hCols = array_column($hd['data']['collections'] ?? [], 'collection');
    $eCols = array_column($ed['data']['collections'] ?? [], 'collection');
    sort($hCols); sort($eCols);
    check('Describe devuelve mismas colecciones',   $hCols === $eCols);

    // Op desconocido devuelve OP_UNKNOWN en ambos
    $hUnk = $http->execute(['op' => 'zzz_no_existe']);
    $eUnk = $emb->execute(['op' => 'zzz_no_existe']);
    check('OP_UNKNOWN HTTP',            ($hUnk['code'] ?? '') === 'OP_UNKNOWN');
    check('OP_UNKNOWN Embedded',        ($eUnk['code'] ?? '') === 'OP_UNKNOWN');

    // Ai\Ask (Fase 6) -> success en ambos transports con NoopLlm.
    $hAi = $http->execute(['op' => 'ai.ask', 'prompt' => 'ping']);
    $eAi = $emb->execute(['op' => 'ai.ask', 'prompt' => 'ping']);
    check('Ai\\Ask success HTTP',     ($hAi['success'] ?? null) === true);
    check('Ai\\Ask success Embedded', ($eAi['success'] ?? null) === true);
} else {
    echo "  [skip] tests HTTP: servidor no alcanzable (posible conflicto de puerto)\n";
}

// Matar el servidor
if (is_resource($process)) {
    proc_terminate($process);
    proc_close($process);
}

// ---------------------------------------------------------------------------
echo "\n[E] sql() compila y ejecuta AxiSQL (Fase 2)\n";
$sqlRes = (new Client())->sql('SELECT * FROM does_not_exist_' . uniqid());
check('sql() success (aunque col vacia)',          ($sqlRes['success'] ?? null) === true);
check('sql() devuelve items array',                is_array($sqlRes['data']['items'] ?? null));

$badRes = (new Client())->sql('SELECT garbage');
check('sql() query malformado -> BAD_REQUEST',     ($badRes['code'] ?? '') === 'BAD_REQUEST');

echo "\n=== Resultado: {$PASS} passed, {$FAIL} failed ===\n";
exit($FAIL === 0 ? 0 : 1);
