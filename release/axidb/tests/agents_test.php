<?php
/**
 * AxiDB - Agents test (Fase 6).
 *
 * Cubre:
 *   [A] Manager: createAgent / persistencia / load
 *   [B] Toolbox sandbox: rechazo de Op no permitido (FORBIDDEN)
 *   [C] Kernel + NoopLlm: Ask con count, list, ping, describe
 *   [D] MicroAgent: spawn, profundidad, max_children
 *   [E] Mailbox: attach + drain
 *   [F] Broadcast: matching glob por role
 *   [G] Kill switch individual + global
 *   [H] Persistencia: history y status quedan en disco
 */

declare(strict_types=1);

require_once __DIR__ . '/../axi.php';

use Axi\Engine\Agents\Agent;
use Axi\Engine\Agents\Manager;
use Axi\Engine\AxiException;
use Axi\Engine\Op\Ai\Ask;
use Axi\Engine\Op\Ai\Attach;
use Axi\Engine\Op\Ai\Broadcast;
use Axi\Engine\Op\Ai\KillAgent;
use Axi\Engine\Op\Ai\ListAgents;
use Axi\Engine\Op\Ai\NewAgent;
use Axi\Engine\Op\Ai\NewMicroAgent;
use Axi\Engine\Op\Ai\RunAgent;
use Axi\Engine\Op\Insert;

$PASS = 0;
$FAIL = 0;
function check(string $name, bool $cond, string $d = ''): void
{
    global $PASS, $FAIL;
    if ($cond) { $PASS++; echo "  [ok] $name\n"; }
    else       { $FAIL++; echo "  [FAIL] $name" . ($d ? " -- $d" : "") . "\n"; }
}

echo "=== Agents test (Fase 6) ===\n\n";

$tmp = __DIR__ . '/_tmp_agents';
if (\is_dir($tmp)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tmp, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) { $f->isDir() ? @\rmdir($f->getRealPath()) : @\unlink($f->getRealPath()); }
}
@\mkdir($tmp, 0777, true);

$db = Axi(['data_root' => $tmp]);

// ---------------------------------------------------------------------------
echo "[A] Manager.createAgent + persistencia\n";

/** @var Manager $manager */
$manager = $db->getService('agents');
check('Servicio agents disponible',         $manager instanceof Manager);

$res = $db->execute((new NewAgent())->spec(
    'reviewer', 'Eres un agente de pruebas.', ['select', 'count', 'ping', 'help']
));
check('NewAgent success',                   ($res['success'] ?? null) === true, json_encode($res));
$agentId = $res['data']['id'] ?? '';
check('NewAgent devuelve id',               \is_string($agentId) && \str_starts_with($agentId, 'ag_'));

$resList = $db->execute(new ListAgents());
check('ListAgents success',                 ($resList['success'] ?? null) === true);
check('ListAgents incluye al nuevo agente', ($resList['data']['total'] ?? 0) >= 1);
check('kill_switch global apagado',         ($resList['data']['kill_switch'] ?? true) === false);

// Validacion
$bad = $db->execute(['op' => 'ai.new_agent', 'name' => '', 'role' => 'x']);
check('NewAgent name vacio falla',          ($bad['success'] ?? null) === false);
check('Codigo = VALIDATION_FAILED',         ($bad['code'] ?? null) === AxiException::VALIDATION_FAILED);

// ---------------------------------------------------------------------------
echo "\n[B] Toolbox sandbox (FORBIDDEN si Op no permitido)\n";

$lockedAgent = $manager->createAgent('locked', 'agente sin tools', ['ping']);
$rOk = $manager->run($lockedAgent->id, 'ping');
check('Agente con ping en tools ejecuta',  ($rOk['answer'] ?? '') !== '');

// 'count' no esta en tools del agente locked => NoopLlm responde con action count y kernel
// captura la AxiException FORBIDDEN como observation.
$rDeny = $manager->run($lockedAgent->id, 'count tmp_notes');
$denyObs = null;
foreach (\array_reverse($rDeny['history'] ?? []) as $turn) {
    if (($turn['role'] ?? '') === 'tool' && isset($turn['observation'])) {
        $denyObs = $turn['observation']; break;
    }
}
check('Op no permitida = observation con FORBIDDEN', ($denyObs['code'] ?? null) === AxiException::FORBIDDEN);

// ---------------------------------------------------------------------------
echo "\n[C] Kernel + NoopLlm: Ask one-shot\n";

// Insert un par de docs para count
$db->execute((new Insert('notas'))->data(['title' => 'a', 'body' => '1']));
$db->execute((new Insert('notas'))->data(['title' => 'b', 'body' => '2']));
$db->execute((new Insert('notas'))->data(['title' => 'c', 'body' => '3']));

$ask = $db->execute((new Ask())->prompt('count notas'));
check('Ask success',                        ($ask['success'] ?? null) === true);
check('Ask devuelve answer',                \is_string($ask['data']['answer'] ?? null));
check('Ask observation count = 3',          ($ask['data']['observation']['data']['count'] ?? null) === 3);

$ping = $db->execute((new Ask())->prompt('ping'));
check('Ask ping ok',                        ($ping['data']['observation']['data']['status'] ?? null) === 'online');

$listOp = $db->execute((new Ask())->prompt('list notas limit 2'));
$obs = $listOp['data']['observation'] ?? [];
check('Ask list devuelve items',            isset($obs['data']['items']) && \count($obs['data']['items']) === 2);

$desc = $db->execute((new Ask())->prompt('describe'));
check('Ask describe devuelve collections',  isset($desc['data']['observation']['data']['collections']));

$help = $db->execute((new Ask())->prompt('help select'));
check('Ask help select devuelve params',    isset($help['data']['observation']['data']['params']));

// Tras todos los Ask, cada uno crea ask-bot efimero que se autodestruye:
$listAfter = $db->execute(new ListAgents());
$names = \array_column($listAfter['data']['agents'] ?? [], 'name');
check('Ningun ask-bot persiste',            \count(\array_filter($names, fn($n) => \str_starts_with((string) $n, 'ask-bot-'))) === 0);

// ---------------------------------------------------------------------------
echo "\n[D] MicroAgent: spawn, profundidad y max_children\n";

$parent = $manager->createAgent('parent', 'agente padre', ['ping', 'count'], ['max_children' => 2]);

$m1 = $db->execute((new NewMicroAgent())->spawn($parent->id, 'subtarea 1', 5));
check('Micro 1 success',                    ($m1['success'] ?? null) === true, json_encode($m1));
check('Micro 1 ephemeral=true',             ($m1['data']['ephemeral'] ?? null) === true);
check('Micro 1 parent_id = parent',         ($m1['data']['parent_id'] ?? null) === $parent->id);

$m2 = $db->execute((new NewMicroAgent())->spawn($parent->id, 'subtarea 2', 5));
check('Micro 2 success',                    ($m2['success'] ?? null) === true);

$m3 = $db->execute((new NewMicroAgent())->spawn($parent->id, 'subtarea 3', 5));
check('Micro 3 falla por max_children=2',   ($m3['success'] ?? null) === false);
check('Codigo = FORBIDDEN',                 ($m3['code'] ?? null) === AxiException::FORBIDDEN, 'code=' . ($m3['code'] ?? '?'));

// Bisnieto: spawn desde un micro debe fallar (depth >= 2 ya, micro de micro de micro = 3 = forbidden).
$nieto  = $manager->createMicroAgent($m1['data']['id'], 'subsubtarea', 3); // depth 2
$bisnieto = $db->execute((new NewMicroAgent())->spawn($nieto->id, 'demasiado profundo', 2));
check('Bisnieto bloqueado (depth max 3)',   ($bisnieto['success'] ?? null) === false);

// ---------------------------------------------------------------------------
echo "\n[E] Mailbox: attach + drain en run\n";

$inboxAgent = $manager->createAgent('mailtest', 'agente que lee inbox', ['ping']);
$rAtt = $db->execute((new Attach())->message($inboxAgent->id, 'check', 'verifica inbox'));
check('Attach success',                     ($rAtt['success'] ?? null) === true);
check('Mailbox.peek encuentra 1 mensaje',   \count($manager->mailbox->peek($inboxAgent->id)) === 1);

$rRun = $db->execute((new RunAgent())->run($inboxAgent->id));
check('RunAgent (sin input) drena inbox',   ($rRun['success'] ?? null) === true);
check('Mailbox vacio tras run',             \count($manager->mailbox->peek($inboxAgent->id)) === 0);

// El history del run debe contener el mensaje del inbox como turno user.
$found = false;
foreach ($rRun['data']['history'] ?? [] as $h) {
    if (($h['role'] ?? '') === 'user' && \str_contains((string) ($h['content'] ?? ''), 'verifica inbox')) {
        $found = true; break;
    }
}
check('History contiene mensaje del inbox', $found);

// Attach a destinatario inexistente
$badAtt = $db->execute(['op' => 'ai.attach', 'to' => 'noexiste', 'subject' => 's', 'body' => 'b']);
check('Attach a id inexistente falla',      ($badAtt['success'] ?? null) === false);
check('Codigo = DOCUMENT_NOT_FOUND',        ($badAtt['code'] ?? null) === AxiException::DOCUMENT_NOT_FOUND);

// ---------------------------------------------------------------------------
echo "\n[F] Broadcast: glob sobre role/name\n";

$manager->createAgent('reviewer-1', 'reviewer products', ['select']);
$manager->createAgent('reviewer-2', 'reviewer orders',   ['select']);
$manager->createAgent('cleaner',    'limpia archivos',   ['select']);

$rB = $db->execute((new Broadcast())->send('reviewer-*', 'parad todos'));
check('Broadcast success',                  ($rB['success'] ?? null) === true);
check('delivered = 2 (reviewer-1, reviewer-2)', ($rB['data']['delivered'] ?? null) === 2,
    'delivered=' . ($rB['data']['delivered'] ?? 'null'));

// ---------------------------------------------------------------------------
echo "\n[G] Kill switch individual + global\n";

$rK = $db->execute((new KillAgent())->target($lockedAgent->id));
check('Kill individual success',            ($rK['success'] ?? null) === true);
$reload = $manager->get($lockedAgent->id);
check('Status persistido = killed',         ($reload['status'] ?? null) === Agent::STATUS_KILLED);

$rKAll = $db->execute((new KillAgent())->target('', true));
check('Kill all success',                   ($rKAll['success'] ?? null) === true);
check('kill_switch global ON',              ($rKAll['data']['kill_switch'] ?? null) === true);

// Con kill switch activo, run debe lanzar FORBIDDEN
$revivo = $manager->createAgent('revivo', 'intento post killall', ['ping']);
$rExec = $db->execute((new RunAgent())->run($revivo->id, 'ping'));
check('RunAgent con kill switch falla',     ($rExec['success'] ?? null) === false);
check('Codigo = FORBIDDEN',                 ($rExec['code'] ?? null) === AxiException::FORBIDDEN);

$manager->store->setGlobalKillSwitch(false);

// ---------------------------------------------------------------------------
echo "\n[H] Persistencia: history en disco\n";

// Re-cargar desde disco un agente y comprobar que el contador subio.
$reloaded = $manager->get($parent->id);
check('Parent persistido',                  $reloaded !== null);
check('Status idle/done/killed coherente',  \in_array($reloaded['status'] ?? '', [Agent::STATUS_IDLE, Agent::STATUS_RUNNING, Agent::STATUS_DONE, Agent::STATUS_KILLED, Agent::STATUS_WAITING], true));

// ---------------------------------------------------------------------------
echo "\n[I] AuditLog: cada Op invocada por un agente queda registrada\n";

$auditPath = $manager->audit->path();
check('audit.log existe',                  \is_file($auditPath));
$entries = $manager->audit->tail(200);
check('AuditLog tiene entradas',           \count($entries) > 0, 'count=' . count($entries));

// Verifica formato actor=agent:<id>
$hasAgentPrefix = !empty($entries) && \str_starts_with((string) ($entries[0]['actor'] ?? ''), 'agent:');
check('Entrada tiene actor=agent:<id>',    $hasAgentPrefix);

// Hay registros de la denegacion FORBIDDEN del test [B]
$forbidden = \array_filter($entries, fn($r) => ($r['code'] ?? '') === 'FORBIDDEN');
check('AuditLog registra denegaciones FORBIDDEN', \count($forbidden) > 0);

// Filtro via Op ai.audit
$rAudit = $db->execute(['op' => 'ai.audit', 'limit' => 10]);
check('Op ai.audit responde success',      ($rAudit['success'] ?? null) === true);
check('Op ai.audit devuelve entries[]',    is_array($rAudit['data']['entries'] ?? null));

// ---------------------------------------------------------------------------
echo "\n[J] Auto-snapshot: Batch agentico con >10 writes dispara backup.create\n";

$batchAgent = $manager->createAgent('batch-runner', 'agente con tools de batch',
    ['batch', 'insert', 'select', 'count']);

// Construye batch con 12 inserts (writes) — supera threshold 10.
$ops = [];
for ($i = 0; $i < 12; $i++) {
    $ops[] = ['op' => 'insert', 'collection' => 'tmp_batch', 'data' => ['n' => $i]];
}
// Llamamos directo al toolbox (no via LLM) para verificar el trigger.
$rBatch = $manager->toolbox->call($batchAgent, 'batch', ['ops' => $ops]);
check('Batch ejecuta',                     ($rBatch['success'] ?? null) === true, json_encode($rBatch));

// Verifica que se creo un snapshot auto-pre-batch-*.
$rSnapshots = $db->execute(['op' => 'backup.list']);
$autoSnaps = \array_filter(
    $rSnapshots['data']['snapshots'] ?? [],
    fn($s) => \str_starts_with((string) ($s['name'] ?? ''), 'auto-pre-batch-')
);
check('Snapshot auto-pre-batch-* creado', \count($autoSnaps) >= 1, 'snapshots=' . count($autoSnaps));

// El audit log debe registrar el batch con campo 'snapshot'
$tail = $manager->audit->tail(20);
$batchEntry = null;
foreach (\array_reverse($tail) as $row) {
    if (($row['op'] ?? '') === 'batch') { $batchEntry = $row; break; }
}
check('AuditLog batch tiene snapshot ref', $batchEntry !== null && !empty($batchEntry['snapshot']));

// Batch pequeño (5 inserts) no debe disparar snapshot.
$prevCount = \count($autoSnaps);
$smallOps = [];
for ($i = 0; $i < 5; $i++) {
    $smallOps[] = ['op' => 'insert', 'collection' => 'tmp_batch', 'data' => ['n' => 100 + $i]];
}
$manager->toolbox->call($batchAgent, 'batch', ['ops' => $smallOps]);
$rSnapshots2 = $db->execute(['op' => 'backup.list']);
$autoSnaps2 = \array_filter(
    $rSnapshots2['data']['snapshots'] ?? [],
    fn($s) => \str_starts_with((string) ($s['name'] ?? ''), 'auto-pre-batch-')
);
check('Batch <=10 writes NO dispara snapshot', \count($autoSnaps2) === $prevCount);

// ---------------------------------------------------------------------------
// Cleanup
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmp, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($it as $f) { $f->isDir() ? @\rmdir($f->getRealPath()) : @\unlink($f->getRealPath()); }
@\rmdir($tmp);

echo "\n=== Resultado: $PASS passed, $FAIL failed ===\n";
exit($FAIL === 0 ? 0 : 1);
