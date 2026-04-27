<?php
/**
 * AxiDB - Storage\Driver test (Fase 1.4).
 *
 * Verifica: write/read round-trip, delete, list, ensure/dropCollection,
 *           version auto-increment, tmp+rename atomico (simula crash),
 *           path traversal rechazado, flock mutex, PackedDriver NOT_IMPL.
 */

require_once __DIR__ . '/../axi.php';

use Axi\Engine\AxiException;
use Axi\Engine\Storage\FsJsonDriver;
use Axi\Engine\Storage\PackedDriver;

$PASS = 0;
$FAIL = 0;
function check(string $name, bool $cond, string $d = ''): void
{
    global $PASS, $FAIL;
    if ($cond) { $PASS++; echo "  [ok] $name\n"; }
    else       { $FAIL++; echo "  [FAIL] $name" . ($d ? " -- $d" : "") . "\n"; }
}
function cleanup(string $dir): void
{
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
    @rmdir($dir);
}

echo "=== Storage Driver test (Fase 1.4) ===\n\n";

$tmp = __DIR__ . '/_tmp_driver';
cleanup($tmp);
mkdir($tmp, 0777, true);

$drv = new FsJsonDriver($tmp);

// ---------------------------------------------------------------------------
echo "[A] CRUD round-trip\n";
check('driverName = FsJsonDriver', $drv->driverName() === 'FsJsonDriver');

$doc = $drv->writeDoc('items', 'abc', ['title' => 'hola']);
check('write devuelve _id',       ($doc['_id'] ?? null) === 'abc');
check('write devuelve _version=1', ($doc['_version'] ?? null) === 1);
check('write devuelve _createdAt', isset($doc['_createdAt']));

$read = $drv->readDoc('items', 'abc');
check('read devuelve doc',        $read !== null);
check('read.title = hola',        ($read['title'] ?? null) === 'hola');

$doc2 = $drv->writeDoc('items', 'abc', ['body' => 'x']);
check('write#2 version=2',         ($doc2['_version'] ?? null) === 2);
check('merge preserva title',      ($doc2['title'] ?? null) === 'hola');
check('merge anade body',          ($doc2['body'] ?? null) === 'x');

// ---------------------------------------------------------------------------
echo "\n[B] List ids y docs\n";
$drv->writeDoc('items', 'def', ['title' => 'segundo']);
$drv->writeDoc('items', 'ghi', ['title' => 'tercero']);

$ids = $drv->listIds('items');
check('listIds devuelve 3 ids',    count($ids) === 3, json_encode($ids));
check('listIds ordenados',         $ids === ['abc', 'def', 'ghi']);

$docs = $drv->listDocs('items');
check('listDocs devuelve 3 docs',  count($docs) === 3);

// ---------------------------------------------------------------------------
echo "\n[C] Delete\n";
check('delete existente = true',   $drv->deleteDoc('items', 'ghi') === true);
check('delete inexistente = false', $drv->deleteDoc('items', 'nope') === false);
check('read tras delete = null',   $drv->readDoc('items', 'ghi') === null);
check('listIds ahora = 2',         count($drv->listIds('items')) === 2);

// ---------------------------------------------------------------------------
echo "\n[D] Collection lifecycle\n";
check('exists items = true',       $drv->collectionExists('items') === true);
check('exists nope = false',       $drv->collectionExists('nope_' . uniqid()) === false);

$drv->ensureCollection('empty');
check('ensure crea coleccion',     $drv->collectionExists('empty') === true);
check('drop devuelve true',        $drv->dropCollection('empty') === true);
check('drop ya drop = false',      $drv->dropCollection('empty') === false);

// ---------------------------------------------------------------------------
echo "\n[E] Path traversal rechazado\n";

$invalidNames = ['../etc', 'foo/bar', 'foo\\bar', 'foo..bar', '', "\x00null"];
foreach ($invalidNames as $bad) {
    try {
        $drv->writeDoc($bad, 'any', ['x' => 1]);
        check("name invalido '$bad' rechazado", false, 'no tiro');
    } catch (AxiException $e) {
        check("name invalido '$bad' rechazado", $e->getAxiCode() === AxiException::VALIDATION_FAILED);
    } catch (\Throwable $e) {
        check("name invalido '$bad' rechazado", false, 'tiro otra excepcion: ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------------
echo "\n[F] Atomicidad: no quedan .tmp tras write exitoso\n";
$drv->writeDoc('items', 'atomic_test', ['k' => 'v']);
$tmpFiles = glob($tmp . '/items/*.tmp.*');
check('no hay archivos .tmp huerfanos tras write', count($tmpFiles) === 0, 'files=' . json_encode($tmpFiles));

// ---------------------------------------------------------------------------
echo "\n[G] Lock: acquire + release OK, acquire doble en proceso mismo bloquea hasta timeout\n";
$h = $drv->acquireLock('items', 1000);
check('acquireLock devuelve resource', is_resource($h));

// Simular contencion: en mismo proceso flock() sobre mismo FP es re-entrante,
// pero si lo hacemos con DIFFERENT FP del MISMO archivo, bloquea.
$lockFile = $tmp . '/items/._lock';
$fp2 = fopen($lockFile, 'c+');
$t0 = microtime(true);
$gotit = flock($fp2, LOCK_EX | LOCK_NB);
$elapsed = (microtime(true) - $t0) * 1000;
// En la mayoria de FS con lock activo en 1er handle, el 2o fp NO-block falla.
check('segundo flock NB sobre mismo archivo NO obtiene lock', $gotit === false);
if ($gotit) { flock($fp2, LOCK_UN); }
fclose($fp2);

$drv->releaseLock($h);
// Tras release, podemos adquirir de nuevo.
$h2 = $drv->acquireLock('items', 500);
check('acquireLock funciona tras release', is_resource($h2));
$drv->releaseLock($h2);

// ---------------------------------------------------------------------------
echo "\n[H] PackedDriver stub devuelve NOT_IMPLEMENTED en todos los metodos\n";
$pd = new PackedDriver($tmp);
foreach (['writeDoc', 'readDoc', 'deleteDoc', 'listIds', 'listDocs', 'collectionExists', 'ensureCollection', 'dropCollection', 'acquireLock', 'releaseLock'] as $method) {
    try {
        if ($method === 'releaseLock') {
            $pd->releaseLock('handle');
        } elseif ($method === 'collectionExists' || $method === 'dropCollection' || $method === 'deleteDoc') {
            $pd->$method('c', 'id');
        } elseif ($method === 'readDoc' || $method === 'deleteDoc') {
            $pd->$method('c', 'id');
        } elseif ($method === 'writeDoc') {
            $pd->writeDoc('c', 'id', []);
        } elseif ($method === 'acquireLock') {
            $pd->acquireLock('c');
        } else {
            $pd->$method('c');
        }
        check("PackedDriver::$method NOT_IMPLEMENTED", false, 'no tiro');
    } catch (AxiException $e) {
        check("PackedDriver::$method NOT_IMPLEMENTED", $e->getAxiCode() === AxiException::NOT_IMPLEMENTED);
    }
}

// ---------------------------------------------------------------------------
cleanup($tmp);

echo "\n=== Resultado: {$PASS} passed, {$FAIL} failed ===\n";
exit($FAIL === 0 ? 0 : 1);
