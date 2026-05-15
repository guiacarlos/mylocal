<?php
/**
 * AxiDB - Cobertura del catalogo completo de Ops (Fase 1.3).
 *
 * Verifica: (a) todas las Ops tienen help() valido, (b) round-trip toArray/fromArray,
 *           (c) validate() rechaza params invalidos, (d) execute real de CRUD+Schema+System
 *           sobre coleccion temporal, (e) stubs AI devuelven NOT_IMPLEMENTED consistentemente.
 * Uso: php axidb/tests/full_catalog_test.php
 */

require_once __DIR__ . '/../axi.php';

use Axi\Engine\AxiException;

// Cargar todos los archivos Op para que las clases esten disponibles.
$opDir = __DIR__ . '/../engine/Op';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($opDir, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        require_once $f->getPathname();
    }
}

$PASS = 0;
$FAIL = 0;
$failures = [];

function check(string $name, bool $cond, string $details = ''): void
{
    global $PASS, $FAIL, $failures;
    if ($cond) {
        $PASS++;
    } else {
        $FAIL++;
        $failures[] = $name . ($details ? " -- $details" : "");
    }
}

echo "=== Full catalog test (Fase 1.3) ===\n";

// ---------------------------------------------------------------------------
// Inventario esperado de 33 Ops: 7 CRUD + 9 Schema + 4 System + 5 Auth + 8 AI.
// ---------------------------------------------------------------------------

$catalog = [
    // CRUD
    'Axi\Engine\Op\Select', 'Axi\Engine\Op\Insert', 'Axi\Engine\Op\Update',
    'Axi\Engine\Op\Delete', 'Axi\Engine\Op\Count', 'Axi\Engine\Op\Exists',
    'Axi\Engine\Op\Batch',
    // Schema
    'Axi\Engine\Op\Alter\CreateCollection', 'Axi\Engine\Op\Alter\DropCollection',
    'Axi\Engine\Op\Alter\AlterCollection', 'Axi\Engine\Op\Alter\RenameCollection',
    'Axi\Engine\Op\Alter\AddField', 'Axi\Engine\Op\Alter\DropField',
    'Axi\Engine\Op\Alter\RenameField', 'Axi\Engine\Op\Alter\CreateIndex',
    'Axi\Engine\Op\Alter\DropIndex',
    // System
    'Axi\Engine\Op\System\Ping', 'Axi\Engine\Op\System\Describe',
    'Axi\Engine\Op\System\Schema', 'Axi\Engine\Op\System\Explain',
    // Auth
    'Axi\Engine\Op\Auth\Login', 'Axi\Engine\Op\Auth\Logout',
    'Axi\Engine\Op\Auth\CreateUser', 'Axi\Engine\Op\Auth\GrantRole',
    'Axi\Engine\Op\Auth\RevokeRole',
    // AI
    'Axi\Engine\Op\Ai\Ask', 'Axi\Engine\Op\Ai\NewAgent', 'Axi\Engine\Op\Ai\NewMicroAgent',
    'Axi\Engine\Op\Ai\RunAgent', 'Axi\Engine\Op\Ai\KillAgent', 'Axi\Engine\Op\Ai\ListAgents',
    'Axi\Engine\Op\Ai\Broadcast', 'Axi\Engine\Op\Ai\Attach', 'Axi\Engine\Op\Ai\Audit',
];

echo "\n[A] Todas las clases existen y extienden Operation\n";
foreach ($catalog as $cls) {
    check("Class $cls existe", class_exists($cls));
    if (class_exists($cls)) {
        check("$cls extiende Operation", is_subclass_of($cls, 'Axi\\Engine\\Op\\Operation'));
    }
}

echo "\n[B] Toda Op tiene help() con campos basicos\n";
foreach ($catalog as $cls) {
    if (!class_exists($cls)) continue;
    $h = $cls::help();
    check("$cls::help() es HelpEntry", is_a($h, 'Axi\\Engine\\Help\\HelpEntry'));
    check("$cls help.name no vacio",   ($h->name ?? '') !== '');
    check("$cls help >=1 example",     count($h->examples) >= 1);
    check("$cls help.toArray() ok",    is_array($h->toArray()));
}

echo "\n[C] OP_NAME distinto en cada clase (no colisiones)\n";
$seen = [];
foreach ($catalog as $cls) {
    if (!class_exists($cls)) continue;
    $name = $cls::OP_NAME;
    check("$cls: OP_NAME = '$name' no colisiona", !isset($seen[$name]), "ya estaba en " . ($seen[$name] ?? '?'));
    $seen[$name] = $cls;
}

echo "\n[D] Registry resuelve cada OP_NAME a la clase correcta\n";
$db = Axi(['data_root' => __DIR__ . '/_tmp_fullcat']);
@mkdir(__DIR__ . '/_tmp_fullcat', 0777, true);

// Comprobamos via dispatch: op desconocido -> OP_UNKNOWN; op conocida -> otra ruta.
foreach ($seen as $opName => $cls) {
    $res = $db->execute(['op' => $opName]);  // con params invalidos para casi todas
    check("Registry conoce op '$opName'", ($res['code'] ?? '') !== AxiException::OP_UNKNOWN, "code=" . ($res['code'] ?? '?'));
}

// ---------------------------------------------------------------------------
echo "\n[E] Ejecucion real: CRUD completo + Schema + Describe\n";

// CreateCollection con flags
$r = $db->execute(['op' => 'create_collection', 'collection' => 'tmp_fc_notes', 'flags' => ['keep_versions' => true]]);
check('CreateCollection success',                    ($r['success'] ?? null) === true, json_encode($r));
check('CreateCollection meta.flags.keep_versions',   ($r['data']['flags']['keep_versions'] ?? null) === true);

// AddField
$r = $db->execute([
    'op' => 'add_field', 'collection' => 'tmp_fc_notes',
    'field' => ['name' => 'title', 'type' => 'string', 'required' => true]
]);
check('AddField title success',                      ($r['success'] ?? null) === true);

// AddField duplicada -> CONFLICT
$r = $db->execute([
    'op' => 'add_field', 'collection' => 'tmp_fc_notes',
    'field' => ['name' => 'title', 'type' => 'string']
]);
check('AddField duplicada -> CONFLICT',              ($r['code'] ?? null) === AxiException::CONFLICT);

// CreateIndex
$r = $db->execute(['op' => 'create_index', 'collection' => 'tmp_fc_notes', 'field' => 'title', 'unique' => true]);
check('CreateIndex success',                         ($r['success'] ?? null) === true);

// Schema devuelve meta coherente
$r = $db->execute(['op' => 'schema', 'collection' => 'tmp_fc_notes']);
check('Schema success',                              ($r['success'] ?? null) === true);
check('Schema fields count = 1',                     count($r['data']['fields'] ?? []) === 1);
check('Schema indexes count = 1',                    count($r['data']['indexes'] ?? []) === 1);

// Insert + Select + Update + Count + Exists + Delete
$r = $db->execute(['op' => 'insert', 'collection' => 'tmp_fc_notes', 'data' => ['title' => 'primera']]);
$id = $r['data']['_id'] ?? null;
check('Insert devuelve _id',                         is_string($id) && $id !== '');

$r = $db->execute(['op' => 'insert', 'collection' => 'tmp_fc_notes', 'data' => ['title' => 'segunda']]);
check('Insert #2 success',                           ($r['success'] ?? null) === true);

$r = $db->execute(['op' => 'count', 'collection' => 'tmp_fc_notes']);
check('Count = 2',                                   ($r['data']['count'] ?? null) === 2, json_encode($r));

$r = $db->execute(['op' => 'count', 'collection' => 'tmp_fc_notes', 'where' => [['field' => 'title', 'op' => '=', 'value' => 'primera']]]);
check('Count con where = 1',                         ($r['data']['count'] ?? null) === 1);

$r = $db->execute(['op' => 'exists', 'collection' => 'tmp_fc_notes', 'id' => $id]);
check('Exists por id = true',                        ($r['data']['exists'] ?? null) === true);

$r = $db->execute(['op' => 'exists', 'collection' => 'tmp_fc_notes', 'id' => 'does_not_exist']);
check('Exists id inexistente = false',               ($r['data']['exists'] ?? null) === false);

$r = $db->execute(['op' => 'update', 'collection' => 'tmp_fc_notes', 'id' => $id, 'data' => ['title' => 'editada']]);
check('Update success',                              ($r['success'] ?? null) === true);

$r = $db->execute(['op' => 'select', 'collection' => 'tmp_fc_notes', 'where' => [['field' => 'title', 'op' => '=', 'value' => 'editada']]]);
check('Update persiste (Select encuentra editada)',  ($r['data']['count'] ?? null) === 1);

$r = $db->execute(['op' => 'delete', 'collection' => 'tmp_fc_notes', 'id' => $id]);
check('Delete soft success',                         ($r['success'] ?? null) === true);
check('Delete soft flag hard=false',                 ($r['data']['hard'] ?? null) === false);

// Describe lista la coleccion
$r = $db->execute(['op' => 'describe']);
check('Describe success',                            ($r['success'] ?? null) === true);
$names = array_column($r['data']['collections'] ?? [], 'collection');
check('Describe incluye tmp_fc_notes',               in_array('tmp_fc_notes', $names, true), 'names=' . json_encode($names));

// Batch: 3 inserts
$r = $db->execute([
    'op' => 'batch',
    'ops' => [
        ['op' => 'insert', 'collection' => 'tmp_fc_notes', 'data' => ['title' => 'a']],
        ['op' => 'insert', 'collection' => 'tmp_fc_notes', 'data' => ['title' => 'b']],
        ['op' => 'insert', 'collection' => 'tmp_fc_notes', 'data' => ['title' => 'c']],
    ],
]);
check('Batch success',                               ($r['success'] ?? null) === true);
check('Batch ejecuto 3',                             ($r['data']['executed'] ?? null) === 3);

// RenameCollection
$r = $db->execute(['op' => 'rename_collection', 'collection' => 'tmp_fc_notes', 'to' => 'tmp_fc_renamed']);
check('RenameCollection success',                    ($r['success'] ?? null) === true);

// DropCollection
$r = $db->execute(['op' => 'drop_collection', 'collection' => 'tmp_fc_renamed']);
check('DropCollection success',                      ($r['success'] ?? null) === true);

// ---------------------------------------------------------------------------
echo "\n[F] System Ops\n";

$r = $db->execute(['op' => 'ping']);
check('Ping devuelve success',                       ($r['success'] ?? null) === true);
check('Ping incluye engine',                         isset($r['data']['engine']) || isset($r['data']['status']));

$r = $db->execute(['op' => 'explain', 'target' => ['op' => 'select', 'collection' => 'tmp_fc_notes']]);
check('Explain success (aunque col no exista)',      ($r['success'] ?? null) === true);

// ---------------------------------------------------------------------------
echo "\n[G] Validate rechaza params invalidos (muestreo 5 Ops)\n";

$invalidCases = [
    'select'            => ['op' => 'select'],                          // sin collection
    'insert'            => ['op' => 'insert', 'collection' => 'c'],     // sin data
    'update'            => ['op' => 'update', 'collection' => 'c'],     // sin id/data
    'create_collection' => ['op' => 'create_collection', 'collection' => 'Invalid-Name'], // no snake_case
    'rename_field'      => ['op' => 'rename_field', 'collection' => 'c', 'from' => 'a'],  // sin to
];
foreach ($invalidCases as $op => $payload) {
    $r = $db->execute($payload);
    check("$op con params invalidos -> VALIDATION_FAILED",
        ($r['code'] ?? null) === AxiException::VALIDATION_FAILED,
        'code=' . ($r['code'] ?? '?'));
}

// ---------------------------------------------------------------------------
echo "\n[H] Todos los Ops AI estan implementados (Fase 6) y responden coherentemente\n";

// Caso por caso: cada uno tiene su contrato. Verificamos que NINGUNO devuelva
// NOT_IMPLEMENTED y que los success/code esperados sean los reales tras Fase 6.

// (1) ai.ask con prompt -> success (NoopLlm responde aunque sin observation)
$r = $db->execute(['op' => 'ai.ask', 'prompt' => 'hola']);
check('ai.ask -> success=true', ($r['success'] ?? null) === true, 'r=' . json_encode($r));
check('ai.ask -> no NOT_IMPLEMENTED', ($r['code'] ?? null) !== AxiException::NOT_IMPLEMENTED);

// (2) ai.new_agent crea persistente
$r = $db->execute(['op' => 'ai.new_agent', 'name' => 'cat-test', 'role' => 'test catalog']);
check('ai.new_agent -> success=true',  ($r['success'] ?? null) === true);
$catAgentId = $r['data']['id'] ?? null;
check('ai.new_agent -> devuelve id',   is_string($catAgentId));

// (3) ai.list_agents
$r = $db->execute(['op' => 'ai.list_agents']);
check('ai.list_agents -> success=true', ($r['success'] ?? null) === true);
check('ai.list_agents -> total >= 1',   ($r['data']['total'] ?? 0) >= 1);

// (4) ai.run_agent sobre el catAgentId
$r = $db->execute(['op' => 'ai.run_agent', 'agent_id' => $catAgentId, 'input' => 'ping']);
check('ai.run_agent -> success=true',   ($r['success'] ?? null) === true, 'r=' . json_encode($r));

// (5) ai.new_micro_agent (parent_id valido)
$r = $db->execute(['op' => 'ai.new_micro_agent', 'parent_id' => $catAgentId, 'task' => 'demo']);
check('ai.new_micro_agent -> success=true', ($r['success'] ?? null) === true);

// (6) ai.attach a destinatario valido
$r = $db->execute(['op' => 'ai.attach', 'to' => $catAgentId, 'subject' => 's', 'body' => 'b']);
check('ai.attach -> success=true',          ($r['success'] ?? null) === true);

// (7) ai.broadcast acepta pattern *
$r = $db->execute(['op' => 'ai.broadcast', 'pattern' => '*', 'message' => 'global']);
check('ai.broadcast -> success=true',       ($r['success'] ?? null) === true);

// (8) ai.kill_agent
$r = $db->execute(['op' => 'ai.kill_agent', 'agent_id' => $catAgentId]);
check('ai.kill_agent -> success=true',      ($r['success'] ?? null) === true);

// (9) ai.audit lee el log NDJSON
$r = $db->execute(['op' => 'ai.audit', 'limit' => 5]);
check('ai.audit -> success=true',           ($r['success'] ?? null) === true);
check('ai.audit -> entries[]',              is_array($r['data']['entries'] ?? null));

// ---------------------------------------------------------------------------
// Limpieza
$tmp = __DIR__ . '/_tmp_fullcat';
if (is_dir($tmp)) {
    $it2 = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tmp, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it2 as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($tmp);
}

echo "\n=== Resultado: $PASS passed, $FAIL failed ===\n";
if ($FAIL > 0) {
    echo "\nFallos:\n";
    foreach ($failures as $f) echo "  - $f\n";
}
exit($FAIL === 0 ? 0 : 1);
