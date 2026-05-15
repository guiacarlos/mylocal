<?php
/**
 * AxiDB - AxiSQL test (Fase 2).
 *
 * Cubre: Lexer, Parser, Planner, WhereEvaluator y la equivalencia byte-a-byte
 * entre las 4 formas de invocar una misma consulta.
 */

require_once __DIR__ . '/../axi.php';

use Axi\Engine\AxiException;
use Axi\Engine\Op\Insert;
use Axi\Engine\Op\Select;
use Axi\Sdk\Php\Client;
use Axi\Sql\Lexer;
use Axi\Sql\Parser;
use Axi\Sql\Planner;
use Axi\Sql\Token;
use Axi\Sql\WhereEvaluator;

$PASS = 0;
$FAIL = 0;
function check(string $name, bool $cond, string $d = ''): void
{
    global $PASS, $FAIL;
    if ($cond) { $PASS++; echo "  [ok] $name\n"; }
    else       { $FAIL++; echo "  [FAIL] $name" . ($d ? " -- $d" : "") . "\n"; }
}
function cleanupCol(string $col): void {
    $c = new Client();
    $docs = $c->collection($col)->get();
    foreach ($docs as $d) {
        $id = $d['_id'] ?? $d['id'] ?? null;
        if ($id) $c->collection($col)->delete($id, hard: true);
    }
    $c->execute(['op' => 'drop_collection', 'collection' => $col]);
}

echo "=== AxiSQL test (Fase 2) ===\n\n";

// ---------------------------------------------------------------------------
echo "[A] Lexer\n";
$lex = new Lexer();

$tok = $lex->tokenize("SELECT * FROM t WHERE a = 1");
check('Lexer produce 8 tokens + EOF',      count($tok) === 9);
check('Primer token es KW SELECT',         $tok[0]->type === Token::KW && $tok[0]->value === 'SELECT');
check('Tercer token es PUNCT *',           $tok[1]->isPunct('*'));
check('Operador = reconocido',             $tok[6]->isOp('='));
check('Ultimo es EOF',                     $tok[8]->type === Token::EOF);

$tok2 = $lex->tokenize("INSERT INTO t VALUES ('hola \"mundo\"', 42, true)");
check('Strings con doble comilla interna', \in_array("hola \"mundo\"", \array_map(fn($t) => $t->value, $tok2), true));

$tok3 = $lex->tokenize("x >= 3.14 AND y != 'abc'");
check('Float reconocido',                  $tok3[2]->value === 3.14);
check('Operador >= multichar',             $tok3[1]->isOp('>='));
check('Operador != multichar',             $tok3[5]->isOp('!='));

try {
    $lex->tokenize("SELECT @x");
    check('Lexer rechaza char invalido', false, 'no tiro');
} catch (AxiException $e) {
    check('Lexer rechaza @ con BAD_REQUEST', $e->getAxiCode() === AxiException::BAD_REQUEST);
}

try {
    $lex->tokenize("'string sin cerrar");
    check('Lexer rechaza string sin cerrar', false, 'no tiro');
} catch (AxiException $e) {
    check('String sin cerrar con BAD_REQUEST', $e->getAxiCode() === AxiException::BAD_REQUEST);
}

// ---------------------------------------------------------------------------
echo "\n[B] Parser - SELECT con clausulas\n";
$par = new Parser();

$ast = $par->parse($lex->tokenize("SELECT * FROM products WHERE price < 3 ORDER BY price DESC LIMIT 5 OFFSET 10"));
check('AST.type = select',                 $ast['type'] === 'select');
check('AST.collection = products',         $ast['collection'] === 'products');
check('AST.fields = [*]',                  $ast['fields'] === ['*']);
check('AST.where_expr es cmp',             ($ast['where_expr']['type'] ?? '') === 'cmp');
check('AST.where_expr.field = price',      ($ast['where_expr']['field'] ?? '') === 'price');
check('AST.where_expr.op = <',             ($ast['where_expr']['op'] ?? '') === '<');
check('AST.where_expr.value = 3',          ($ast['where_expr']['value'] ?? null) === 3);
check('AST.order_by[0].field = price',     $ast['order_by'][0]['field'] === 'price');
check('AST.order_by[0].dir = desc',        $ast['order_by'][0]['dir'] === 'desc');
check('AST.limit = 5',                     $ast['limit'] === 5);
check('AST.offset = 10',                   $ast['offset'] === 10);

// Proyeccion de campos + COUNT
$ast = $par->parse($lex->tokenize("SELECT name, price FROM products"));
check('AST.fields = [name, price]',        $ast['fields'] === ['name', 'price']);

$ast = $par->parse($lex->tokenize("SELECT COUNT(*) FROM products WHERE stock > 0"));
check('COUNT detectado como type=count',   $ast['type'] === 'count');

// ---------------------------------------------------------------------------
echo "\n[C] Parser - WHERE AND/OR/NOT/paréntesis\n";
$ast = $par->parse($lex->tokenize("SELECT * FROM t WHERE a = 1 AND b = 2"));
check('AND en where_expr',                 $ast['where_expr']['type'] === 'and');

$ast = $par->parse($lex->tokenize("SELECT * FROM t WHERE a = 1 OR b = 2"));
check('OR en where_expr',                  $ast['where_expr']['type'] === 'or');

$ast = $par->parse($lex->tokenize("SELECT * FROM t WHERE NOT a = 1"));
check('NOT en where_expr',                 $ast['where_expr']['type'] === 'not');

$ast = $par->parse($lex->tokenize("SELECT * FROM t WHERE (a = 1 OR b = 2) AND c = 3"));
check('Parentesis respetan precedencia',   $ast['where_expr']['type'] === 'and' && $ast['where_expr']['left']['type'] === 'or');

// IN / LIKE / CONTAINS / IS NULL
$ast = $par->parse($lex->tokenize("SELECT * FROM t WHERE status IN ('a', 'b', 'c')"));
check('IN produce array de valores',       $ast['where_expr']['op'] === 'IN' && count($ast['where_expr']['value']) === 3);

$ast = $par->parse($lex->tokenize("SELECT * FROM t WHERE name LIKE '%foo%'"));
check('LIKE reconocido',                   $ast['where_expr']['op'] === 'LIKE');

$ast = $par->parse($lex->tokenize("SELECT * FROM t WHERE name CONTAINS 'bar'"));
check('CONTAINS reconocido',               $ast['where_expr']['op'] === 'contains');

$ast = $par->parse($lex->tokenize("SELECT * FROM t WHERE deleted IS NULL"));
check('IS NULL reconocido',                $ast['where_expr']['op'] === 'IS_NULL');

$ast = $par->parse($lex->tokenize("SELECT * FROM t WHERE deleted IS NOT NULL"));
check('IS NOT NULL reconocido',            $ast['where_expr']['op'] === 'IS_NOT_NULL');

// ---------------------------------------------------------------------------
echo "\n[D] Parser - INSERT / UPDATE / DELETE\n";
$ast = $par->parse($lex->tokenize("INSERT INTO notas (title, body) VALUES ('t', 'b')"));
check('INSERT type',                       $ast['type'] === 'insert');
check('INSERT data.title',                 $ast['data']['title'] === 't');

$ast = $par->parse($lex->tokenize("UPDATE notas SET body = 'x' WHERE title = 't'"));
check('UPDATE type + set',                 $ast['type'] === 'update' && $ast['set']['body'] === 'x');

$ast = $par->parse($lex->tokenize("DELETE FROM notas WHERE status = 'archived'"));
check('DELETE type + where',               $ast['type'] === 'delete' && ($ast['where_expr']['field'] ?? '') === 'status');

// ---------------------------------------------------------------------------
echo "\n[E] Parser - Schema (CREATE/DROP/ALTER)\n";
$ast = $par->parse($lex->tokenize("CREATE COLLECTION notas WITH (keep_versions = true)"));
check('CREATE COLLECTION con flags',       $ast['type'] === 'create_collection' && $ast['flags']['keep_versions'] === true);

$ast = $par->parse($lex->tokenize("DROP COLLECTION tmp"));
check('DROP COLLECTION',                   $ast['type'] === 'drop_collection');

$ast = $par->parse($lex->tokenize("ALTER COLLECTION notas ADD FIELD pinned bool DEFAULT false"));
check('ALTER ADD FIELD con default',       $ast['type'] === 'alter_add_field' && $ast['field']['default'] === false);

$ast = $par->parse($lex->tokenize("ALTER TABLE notas DROP FIELD legacy"));
check('ALTER DROP FIELD',                  $ast['type'] === 'alter_drop_field');

$ast = $par->parse($lex->tokenize("CREATE UNIQUE INDEX ON users (email)"));
check('CREATE UNIQUE INDEX',               $ast['type'] === 'create_index' && $ast['unique'] === true);

$ast = $par->parse($lex->tokenize("DROP INDEX ON users (email)"));
check('DROP INDEX',                        $ast['type'] === 'drop_index');

// ---------------------------------------------------------------------------
echo "\n[F] Planner - AST -> Op instances\n";
$plan = new Planner();
$op = $plan->plan($par->parse($lex->tokenize("SELECT * FROM t WHERE a = 1 LIMIT 5")));
check('Planner SELECT -> Op\\Select',     $op instanceof Select);
check('Op tiene where_expr',              isset($op->params['where_expr']));
check('Op tiene limit=5',                 ($op->params['limit'] ?? null) === 5);

$op = $plan->plan($par->parse($lex->tokenize("INSERT INTO t (x) VALUES (1)")));
check('Planner INSERT -> Op\\Insert',     $op instanceof Insert);
check('Insert.data.x = 1',                ($op->params['data']['x'] ?? null) === 1);

// ---------------------------------------------------------------------------
echo "\n[G] WhereEvaluator (unidad)\n";
$doc = ['name' => 'juan', 'age' => 30, 'tags' => ['admin', 'editor'], 'deleted' => null];

check('= igual',      WhereEvaluator::matches($doc, ['type' => 'cmp', 'field' => 'age', 'op' => '=',  'value' => 30]));
check('!= distinto',  WhereEvaluator::matches($doc, ['type' => 'cmp', 'field' => 'age', 'op' => '!=', 'value' => 99]));
check('> mayor',      WhereEvaluator::matches($doc, ['type' => 'cmp', 'field' => 'age', 'op' => '>',  'value' => 20]));
check('<=',           WhereEvaluator::matches($doc, ['type' => 'cmp', 'field' => 'age', 'op' => '<=', 'value' => 30]));
check('IN',           WhereEvaluator::matches($doc, ['type' => 'cmp', 'field' => 'name','op' => 'IN', 'value' => ['juan', 'ana']]));
check('LIKE %j%',     WhereEvaluator::matches($doc, ['type' => 'cmp', 'field' => 'name','op' => 'LIKE','value' => '%ju%']));
check('LIKE _uan',    WhereEvaluator::matches($doc, ['type' => 'cmp', 'field' => 'name','op' => 'LIKE','value' => '_uan']));
check('contains str', WhereEvaluator::matches($doc, ['type' => 'cmp', 'field' => 'name','op' => 'contains','value' => 'ua']));
check('contains arr', WhereEvaluator::matches($doc, ['type' => 'cmp', 'field' => 'tags','op' => 'contains','value' => 'admin']));
check('IS NULL',      WhereEvaluator::matches($doc, ['type' => 'cmp', 'field' => 'deleted','op' => 'IS_NULL','value' => null]));
check('IS NOT NULL',  WhereEvaluator::matches($doc, ['type' => 'cmp', 'field' => 'name','op' => 'IS_NOT_NULL','value' => null]));

$and = ['type' => 'and',
    'left'  => ['type' => 'cmp', 'field' => 'age', 'op' => '>', 'value' => 10],
    'right' => ['type' => 'cmp', 'field' => 'name','op' => '=', 'value' => 'juan'],
];
check('AND combinado', WhereEvaluator::matches($doc, $and));

$or = ['type' => 'or',
    'left'  => ['type' => 'cmp', 'field' => 'age', 'op' => '<', 'value' => 10],
    'right' => ['type' => 'cmp', 'field' => 'name','op' => '=', 'value' => 'juan'],
];
check('OR con una rama verdadera', WhereEvaluator::matches($doc, $or));

// ---------------------------------------------------------------------------
echo "\n[H] Execute end-to-end (AxiSQL real sobre storage temporal)\n";
$c = new Client();
cleanupCol('axisql_e2e');

$c->sql("INSERT INTO axisql_e2e (name, price, stock) VALUES ('cafe',   2, 50)");
$c->sql("INSERT INTO axisql_e2e (name, price, stock) VALUES ('te',     3, 10)");
$c->sql("INSERT INTO axisql_e2e (name, price, stock) VALUES ('dulce',  5,  0)");
$c->sql("INSERT INTO axisql_e2e (name, price, stock) VALUES ('vegano', 7, 20)");

$r = $c->sql("SELECT name, price FROM axisql_e2e WHERE price < 4 ORDER BY price ASC");
check('SELECT filtra + ordena',         count($r['data']['items']) === 2);
check('Primer resultado: cafe',         $r['data']['items'][0]['name'] === 'cafe');
check('Proyeccion solo 2 fields',       count($r['data']['items'][0]) === 2);

$r = $c->sql("SELECT COUNT(*) FROM axisql_e2e WHERE stock > 0");
check('COUNT con where',                ($r['data']['count'] ?? 0) === 3);

$r = $c->sql("SELECT * FROM axisql_e2e WHERE name LIKE 'c%'");
check('LIKE %prefix%',                  count($r['data']['items']) === 1);

$r = $c->sql("SELECT * FROM axisql_e2e WHERE price IN (2, 7)");
check('IN con 2 matches',               count($r['data']['items']) === 2);

$r = $c->sql("SELECT * FROM axisql_e2e WHERE stock > 0 AND price < 5");
check('AND combinado',                  count($r['data']['items']) === 2);

$r = $c->sql("SELECT * FROM axisql_e2e WHERE (price = 2 OR price = 7) AND stock > 0");
check('Paren + OR + AND',               count($r['data']['items']) === 2);

$r = $c->sql("UPDATE axisql_e2e SET stock = 999 WHERE name = 'dulce'");
check('UPDATE match 1',                 ($r['data']['updated'] ?? 0) === 1);

$r = $c->sql("SELECT stock FROM axisql_e2e WHERE name = 'dulce'");
check('UPDATE persistio',               ($r['data']['items'][0]['stock'] ?? 0) === 999);

$r = $c->sql("DELETE FROM axisql_e2e WHERE stock > 500");
check('DELETE (soft) match 1',          ($r['data']['deleted'] ?? 0) === 1);

// Soft delete mantiene el doc con _deletedAt marker; COUNT total sigue viendo 4.
$r = $c->sql("SELECT COUNT(*) FROM axisql_e2e");
check('Count total tras soft-delete = 4',  ($r['data']['count'] ?? 0) === 4);

// Para contar no-borrados: WHERE deleted_at IS NULL (pero campo del motor es _deletedAt reservado).
// En v1 los clientes deben filtrar explicitamente o usar hard delete.
$r = $c->sql("SELECT * FROM axisql_e2e WHERE _deletedAt IS NULL");
check('Filtro IS NULL en _deletedAt = 3',  count($r['data']['items'] ?? []) === 3);

// Schema ops via AxiSQL
$r = $c->sql("CREATE COLLECTION axisql_schema WITH (keep_versions = true)");
check('CREATE COLLECTION via SQL',      ($r['data']['flags']['keep_versions'] ?? null) === true);

$r = $c->sql("ALTER COLLECTION axisql_schema ADD FIELD foo string DEFAULT 'bar'");
check('ALTER ADD FIELD via SQL',        count($r['data']['fields']) === 1);

$r = $c->sql("CREATE UNIQUE INDEX ON axisql_schema (foo)");
check('CREATE UNIQUE INDEX via SQL',    ($r['data']['indexes'][0]['unique'] ?? null) === true);

$r = $c->sql("DROP COLLECTION axisql_schema");
check('DROP COLLECTION via SQL',        ($r['data']['dropped'] ?? null) === true);

// ---------------------------------------------------------------------------
echo "\n[I] Equivalencia: las 4 formas dan el mismo Result.data.items\n";
// Dado un mismo resultado observable: items con {name, price} para price < 4 orden asc

// Forma 1: PHP Op directo
$r1 = $c->execute((new Select('axisql_e2e'))
    ->fields(['name', 'price'])
    ->orderBy('price', 'asc')
    ->limit(100));
// Filtramos por PHP para no depender de where_expr flat (que no soporta composicion compleja del flat API)
$items1 = array_values(array_filter($r1['data']['items'], fn($d) => $d['price'] < 4));

// Forma 2: JSON {op: select} via client.execute
$r2 = $c->execute([
    'op' => 'select', 'collection' => 'axisql_e2e',
    'fields' => ['name', 'price'],
    'where_expr' => ['type' => 'cmp', 'field' => 'price', 'op' => '<', 'value' => 4],
    'order_by' => [['field' => 'price', 'dir' => 'asc']],
]);
$items2 = $r2['data']['items'];

// Forma 3: AxiSQL
$r3 = $c->sql("SELECT name, price FROM axisql_e2e WHERE price < 4 ORDER BY price ASC");
$items3 = $r3['data']['items'];

// Forma 4: CLI (shell_exec -> JSON output)
$cliCmd = \escapeshellarg(\PHP_BINARY) . ' ' . \escapeshellarg(__DIR__ . '/../cli/main.php')
    . ' sql ' . \escapeshellarg("SELECT name, price FROM axisql_e2e WHERE price < 4 ORDER BY price ASC")
    . ' --json 2>&1';
$cliOut = \shell_exec($cliCmd) ?? '';
$cliRes = \json_decode($cliOut, true);
$items4 = $cliRes['data']['items'] ?? [];

check('Forma 1 PHP Op',       count($items1) === 2);
check('Forma 2 JSON {op}',    count($items2) === 2);
check('Forma 3 AxiSQL',       count($items3) === 2);
check('Forma 4 CLI axi sql',  count($items4) === 2);

$keys = ['name', 'price'];
$norm = function($arr) use ($keys) {
    return array_map(fn($d) => array_intersect_key($d, array_flip($keys)), $arr);
};
check('Forma 1 == Forma 2',   $norm($items1) === $norm($items2));
check('Forma 2 == Forma 3',   $norm($items2) === $norm($items3));
check('Forma 3 == Forma 4',   $norm($items3) === $norm($items4));

// Cleanup
cleanupCol('axisql_e2e');

// ---------------------------------------------------------------------------
echo "\n[J] Errores: queries invalidas se rechazan con BAD_REQUEST\n";
$bad = [
    "SELECT",                                          // incompleto
    "SELECT * FROM t WHERE",                           // WHERE vacio
    "INSERT INTO t (a, b) VALUES (1)",                 // mismatch count
    "UPDATE t SET x",                                  // sin =
    "CREATE WHATEVER t",                               // keyword desconocido
    "ALTER TABLE t ADD",                               // ADD sin FIELD
    "garbage not sql",                                 // no keyword
];
foreach ($bad as $q) {
    $r = $c->sql($q);
    check("Rechaza: " . substr($q, 0, 30) . "...",
        ($r['success'] ?? null) === false && ($r['code'] ?? '') === AxiException::BAD_REQUEST);
}

echo "\n=== Resultado: {$PASS} passed, {$FAIL} failed ===\n";
exit($FAIL === 0 ? 0 : 1);
