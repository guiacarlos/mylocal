<?php
/**
 * AxiDB - Backup test (Fase 3).
 *
 * Cubre: SnapshotStore::create full, restore full sobre base limpia,
 *        snapshot incremental basado en _updatedAt, list, drop, dry-run.
 *
 * Usa storage aislado en axidb/tests/_tmp_storage_a/ y _tmp_storage_b/
 * para verificar restore byte-identical.
 */

require_once __DIR__ . '/../axi.php';

use Axi\Engine\AxiException;
use Axi\Engine\Backup\Manifest;
use Axi\Engine\Backup\SnapshotStore;

$PASS = 0;
$FAIL = 0;
function check(string $name, bool $cond, string $d = ''): void
{
    global $PASS, $FAIL;
    if ($cond) { $PASS++; echo "  [ok] $name\n"; }
    else       { $FAIL++; echo "  [FAIL] $name" . ($d ? " -- $d" : "") . "\n"; }
}
function rmrf(string $path): void
{
    if (!is_dir($path)) { @unlink($path); return; }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
    @rmdir($path);
}

echo "=== Backup test (Fase 3) ===\n\n";

$storeA = __DIR__ . '/_tmp_storage_a';
$storeB = __DIR__ . '/_tmp_storage_b';
$backups = __DIR__ . '/_tmp_backups';
rmrf($storeA); rmrf($storeB); rmrf($backups);
mkdir($storeA, 0777, true);
mkdir($storeB, 0777, true);

// Sembramos colecciones de prueba en storeA.
mkdir("$storeA/notas", 0777, true);
file_put_contents("$storeA/notas/d1.json", json_encode([
    '_id' => 'd1', 'title' => 'uno', '_version' => 1,
    '_createdAt' => '2026-04-01T00:00:00+00:00',
    '_updatedAt' => '2026-04-01T00:00:00+00:00',
]));
file_put_contents("$storeA/notas/d2.json", json_encode([
    '_id' => 'd2', 'title' => 'dos', '_version' => 1,
    '_createdAt' => '2026-04-02T00:00:00+00:00',
    '_updatedAt' => '2026-04-02T00:00:00+00:00',
]));
file_put_contents("$storeA/notas/_meta.json", json_encode([
    'name' => 'notas', 'fields' => [], 'indexes' => [], 'flags' => [],
]));

mkdir("$storeA/users", 0777, true);
file_put_contents("$storeA/users/u1.json", json_encode([
    '_id' => 'u1', 'email' => 'a@b.c', '_version' => 1,
    '_createdAt' => '2026-04-01T00:00:00+00:00',
    '_updatedAt' => '2026-04-01T00:00:00+00:00',
]));

// ---------------------------------------------------------------------------
echo "[A] Snapshot full\n";
$store = new SnapshotStore($storeA, $backups);
$m = $store->create('full-1');
check('create devuelve Manifest',         $m instanceof Manifest);
check('manifest.type = full',             $m->type === Manifest::TYPE_FULL);
check('manifest.collections incluye notas', in_array('notas', $m->collections, true));
check('manifest.counts.notas = 2',        ($m->counts['notas'] ?? 0) === 2);
check('manifest.counts.users = 1',        ($m->counts['users'] ?? 0) === 1);
check('archivo data.zip existe',          is_file($backups . '/snapshots/full-1/data.zip'));
check('archivo manifest.json existe',     is_file($backups . '/snapshots/full-1/manifest.json'));

// ---------------------------------------------------------------------------
echo "\n[B] Crear duplicado falla con CONFLICT\n";
try {
    $store->create('full-1');
    check('crear nombre duplicado lanza CONFLICT', false, 'no tiro');
} catch (AxiException $e) {
    check('crear nombre duplicado lanza CONFLICT', $e->getAxiCode() === AxiException::CONFLICT);
}

// ---------------------------------------------------------------------------
echo "\n[C] Restore dry-run reporta sin escribir\n";
$storeRestore = new SnapshotStore($storeB, $backups);
$report = $storeRestore->restore('full-1', dryRun: true);
check('dry_run = true en respuesta',      $report['dry_run'] === true);
check('files reportados >= 3',            $report['restored'] >= 3);
check('storeB sigue vacio (dry-run)',     count(glob("$storeB/notas/*.json") ?: []) === 0);

// ---------------------------------------------------------------------------
echo "\n[D] Restore real reproduce el contenido\n";
$report = $storeRestore->restore('full-1');
check('dry_run = false',                  $report['dry_run'] === false);
check('storeB tiene notas/d1.json',       is_file("$storeB/notas/d1.json"));
check('storeB tiene notas/d2.json',       is_file("$storeB/notas/d2.json"));
check('storeB tiene users/u1.json',       is_file("$storeB/users/u1.json"));
check('storeB tiene notas/_meta.json',    is_file("$storeB/notas/_meta.json"));

// Diff byte-a-byte
$origD1 = file_get_contents("$storeA/notas/d1.json");
$restD1 = file_get_contents("$storeB/notas/d1.json");
check('d1 byte-identical',                $origD1 === $restD1);

// ---------------------------------------------------------------------------
echo "\n[E] Snapshot incremental basado en _updatedAt\n";

// Esperamos al menos un segundo y luego anyadimos un doc nuevo en storeA.
sleep(1);
$incTs = date('c');
sleep(1);
file_put_contents("$storeA/notas/d3.json", json_encode([
    '_id' => 'd3', 'title' => 'tres', '_version' => 1,
    '_createdAt' => date('c'),
    '_updatedAt' => date('c'),
]));

$mInc = $store->create('inc-1', 'full-1');
check('incremental devuelve Manifest',    $mInc instanceof Manifest);
check('manifest.type = incremental',      $mInc->type === Manifest::TYPE_INCREMENTAL);
check('manifest.base_snapshot = full-1',  $mInc->baseSnapshot === 'full-1');
check('incremental incluye d3 (1 doc)',   ($mInc->counts['notas'] ?? 0) === 1);

// Verificar que el zip incremental solo tiene d3
$zip = new ZipArchive();
$zip->open($backups . '/snapshots/inc-1/data.zip');
$entries = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $entries[] = $zip->getNameIndex($i);
}
$zip->close();
$nonMeta = array_values(array_filter($entries, fn($e) => !str_ends_with($e, '_meta.json')));
check('incremental zip contiene 1 doc no-meta', count($nonMeta) === 1);
check('incremental zip = notas/d3.json',  $nonMeta[0] === 'notas/d3.json');

// ---------------------------------------------------------------------------
echo "\n[F] List snapshots\n";
$list = $store->listSnapshots();
check('list devuelve 2 snapshots',        count($list) === 2);
check('list ordenado',                    $list === ['full-1', 'inc-1']);

// ---------------------------------------------------------------------------
echo "\n[G] Drop snapshot\n";
check('drop incremental devuelve true',   $store->drop('inc-1') === true);
check('tras drop solo queda full-1',      $store->listSnapshots() === ['full-1']);
check('drop snapshot inexistente = false', $store->drop('does-not-exist') === false);

// ---------------------------------------------------------------------------
echo "\n[H] Validacion de nombres\n";
foreach (['../etc', 'has space', 'has/slash', '', "abc\x00null"] as $bad) {
    try {
        $store->create($bad);
        check("rechaza nombre invalido '$bad'", false, 'no tiro');
    } catch (AxiException $e) {
        check("rechaza nombre invalido '$bad'", $e->getAxiCode() === AxiException::VALIDATION_FAILED);
    }
}

// ---------------------------------------------------------------------------
echo "\n[I] Ops via dispatcher\n";
$db = \Axi();
$opStore = $db->getService('backup');

// El servicio del dispatcher apunta al STORAGE/backups real, no al temp.
// Solo verificamos que las Ops responden bien.
$r = $db->execute(['op' => 'backup.list']);
check('Op backup.list ok',                ($r['success'] ?? null) === true);

$r = $db->execute(['op' => 'backup.create', 'name' => '']);
check('backup.create con name vacio fail', ($r['code'] ?? null) === AxiException::VALIDATION_FAILED);

$r = $db->execute(['op' => 'backup.restore', 'name' => 'no-existe-' . uniqid()]);
check('backup.restore inexistente fail',  ($r['code'] ?? null) === AxiException::DOCUMENT_NOT_FOUND, 'code=' . ($r['code'] ?? '?'));

// ---------------------------------------------------------------------------
// Cleanup
rmrf($storeA); rmrf($storeB); rmrf($backups);

echo "\n=== Resultado: $PASS passed, $FAIL failed ===\n";
exit($FAIL === 0 ? 0 : 1);
