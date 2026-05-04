<?php
/**
 * AxiDB - Sugar classes test (Fase 1.3).
 * Verifica que Axi\Select, Insert, Update, Delete, Alter, Join, NewRow
 * funcionan como wrappers transparentes sobre los Ops canonicos.
 */

require_once __DIR__ . '/../axi.php';

use Axi\Engine\AxiException;

$PASS = 0;
$FAIL = 0;
function check(string $name, bool $cond, string $d = ''): void
{
    global $PASS, $FAIL;
    if ($cond) { $PASS++; echo "  [ok] $name\n"; }
    else       { $FAIL++; echo "  [FAIL] $name" . ($d ? " -- $d" : "") . "\n"; }
}

echo "=== Sugar classes test ===\n\n";

echo "[A] Sugar classes instanciables y OP_NAME correcto\n";
check('Axi\\Select instanciable',   (new \Axi\Select('c')) instanceof \Axi\Engine\Op\Select);
check('Axi\\Insert instanciable',   (new \Axi\Insert('c')) instanceof \Axi\Engine\Op\Insert);
check('Axi\\Update instanciable',   (new \Axi\Update('c')) instanceof \Axi\Engine\Op\Update);
check('Axi\\Delete instanciable',   (new \Axi\Delete('c')) instanceof \Axi\Engine\Op\Delete);
check('Axi\\NewRow es Insert',      (new \Axi\NewRow('c')) instanceof \Axi\Engine\Op\Insert);

echo "\n[B] Axi\\Alter facade devuelve los Ops correctos\n";
check('Alter::createTable', \Axi\Alter::createTable('x') instanceof \Axi\Engine\Op\Alter\CreateCollection);
check('Alter::dropTable',   \Axi\Alter::dropTable('x')   instanceof \Axi\Engine\Op\Alter\DropCollection);
check('Alter::alterTable',  \Axi\Alter::alterTable('x')  instanceof \Axi\Engine\Op\Alter\AlterCollection);
check('Alter::renameTable', \Axi\Alter::renameTable('x') instanceof \Axi\Engine\Op\Alter\RenameCollection);
check('Alter::addField',    \Axi\Alter::addField('x')    instanceof \Axi\Engine\Op\Alter\AddField);
check('Alter::dropField',   \Axi\Alter::dropField('x')   instanceof \Axi\Engine\Op\Alter\DropField);
check('Alter::renameField', \Axi\Alter::renameField('x') instanceof \Axi\Engine\Op\Alter\RenameField);
check('Alter::createIndex', \Axi\Alter::createIndex('x') instanceof \Axi\Engine\Op\Alter\CreateIndex);
check('Alter::dropIndex',   \Axi\Alter::dropIndex('x')   instanceof \Axi\Engine\Op\Alter\DropIndex);

echo "\n[C] Axi\\Join: validate OK, execute -> NOT_IMPLEMENTED (Fase 2)\n";
$j = (new \Axi\Join())->with('orders', 'users')->on('user_id', '_id');
try {
    $j->validate();
    check('Join validate ok', true);
} catch (\Throwable $e) {
    check('Join validate ok', false, $e->getMessage());
}

$db = \Axi(['data_root' => __DIR__ . '/_tmp_sugar']);
@mkdir(__DIR__ . '/_tmp_sugar', 0777, true);
$r = $db->execute($j);
check('Join execute -> NOT_IMPLEMENTED', ($r['code'] ?? '') === AxiException::NOT_IMPLEMENTED);

echo "\n[D] Sugar + dispatcher: end-to-end Insert + Select\n";
$insert = (new \Axi\Insert('sugar_notes'))->data(['title' => 'hola']);
$r = $db->execute($insert);
check('Axi\\Insert execute ok', ($r['success'] ?? null) === true);

$select = (new \Axi\Select('sugar_notes'))->limit(10);
$r = $db->execute($select);
check('Axi\\Select execute ok', ($r['success'] ?? null) === true);
check('Axi\\Select devuelve 1 doc', ($r['data']['count'] ?? 0) === 1);

echo "\n[E] Axi\\Alter: createTable + addField + dropTable\n";
$r = $db->execute(\Axi\Alter::createTable('sugar_alter'));
check('Alter::createTable execute ok', ($r['success'] ?? null) === true);
$r = $db->execute(\Axi\Alter::addField('sugar_alter')->field('foo', 'string'));
check('Alter::addField execute ok', ($r['success'] ?? null) === true);
$r = $db->execute(\Axi\Alter::dropTable('sugar_alter'));
check('Alter::dropTable execute ok', ($r['success'] ?? null) === true);

// Cleanup
$tmp = __DIR__ . '/_tmp_sugar';
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmp, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
@rmdir($tmp);

echo "\n=== Resultado: {$PASS} passed, {$FAIL} failed ===\n";
exit($FAIL === 0 ? 0 : 1);
