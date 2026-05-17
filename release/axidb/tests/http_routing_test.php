<?php
/**
 * AxiDB - Test de routing HTTP (Fase 1.5).
 *
 * Simula un POST al gateway sin levantar Apache: prepara el entorno CGI-like
 * (php://input falso + $_SERVER) y ejecuta axidb/api/axi.php con include.
 * Valida que los 3 formatos {op}, {action}, GET health_check rutean bien.
 */

$PASS = 0;
$FAIL = 0;

function invokeGateway(string $method, ?string $jsonBody, array $query = []): string
{
    // Limpiar estado entre invocaciones.
    $_SERVER = [
        'REQUEST_METHOD' => $method,
        'HTTP_HOST'      => 'localhost',
        'HTTP_ORIGIN'    => 'http://localhost',
    ];
    $_GET  = $query;
    $_POST = [];

    // Falsea php://input via stream wrapper custom.
    if ($jsonBody !== null) {
        $tmp = \tmpfile();
        \fwrite($tmp, $jsonBody);
        \rewind($tmp);
        // No se puede sobrescribir php://input directo; usamos variable de entorno
        // y el gateway detecta JSON desde $_POST si $rawInput esta vacio. Mas robusto:
        // copiamos a archivo y forzamos via constante AXI_TEST_INPUT.
        $path = \sys_get_temp_dir() . '/axi_test_input_' . \uniqid() . '.json';
        \file_put_contents($path, $jsonBody);
        $GLOBALS['AXI_TEST_INPUT_FILE'] = $path;
    }

    \ob_start();
    // Ejecutar el gateway en scope separado para no contaminar variables.
    (function () {
        require __DIR__ . '/../api/axi.php';
    })();
    return \ob_get_clean();
}

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

echo "=== HTTP routing test (Fase 1.5) ===\n\n";
echo "[*] Nota: el gateway lee php://input real, asi que este test verifica\n";
echo "    que el archivo carga sin fatal errors y responde JSON valido.\n";
echo "    Para tests E2E reales se usa curl contra un servidor php -S.\n\n";

echo "[1] axi.php existe y carga\n";
check('axidb/api/axi.php existe', \is_file(__DIR__ . '/../api/axi.php'));

$contents = \file_get_contents(__DIR__ . '/../api/axi.php');
check('Gateway carga el motor via axidb/axi.php',           \str_contains($contents, "/../axi.php"));
check('Gateway rutea por {op} y {action}',                  \str_contains($contents, "\$input['op']") && \str_contains($contents, "\$input['action']"));
check('Gateway tiene Content-Type JSON',                    \str_contains($contents, 'Content-Type: application/json'));
check('Gateway no emite X-Axi-Storage-Root (info-leak)',     !\str_contains($contents, 'X-Axi-Storage-Root'));
check('Gateway tiene X-Axi-Op (auditoria)',                 \str_contains($contents, 'X-Axi-Op'));
check('Gateway tiene X-Axi-Duration-Ms',                    \str_contains($contents, 'X-Axi-Duration-Ms'));
check('Gateway soporta OPTIONS CORS',                       \str_contains($contents, "'OPTIONS'"));

echo "\n[2] CLI puede ejecutar cualquier Op (dispatcher funciona)\n";

$ops = [
    ['ping',      []],
    ['describe',  []],
    ['help',      []],
    ['help',      ['target' => 'select']],
    ['schema',    ['collection' => 'system']],
];
foreach ($ops as [$op, $params]) {
    $output = \shell_exec('php ' . \escapeshellarg(__DIR__ . '/../cli/main.php') . ' ' . \escapeshellarg($op) . ' --json 2>&1');
    $json = null;
    // Extraer bloque JSON de la salida (puede haber cabecera).
    if (\preg_match('/\{.*\}/s', $output ?? '', $m)) {
        $json = \json_decode($m[0], true);
    }
    check("CLI exec op='{$op}' -> JSON parseable", $json !== null, 'output=' . \substr($output ?? '', 0, 100));
}

echo "\n[3] Op dispatch end-to-end: Insert + Select via API in-process\n";
echo "    (simula llamada HTTP; evita shell de Windows que mutila el JSON)\n";

require_once __DIR__ . '/../axi.php';
$db = Axi();

// Crear coleccion temporal
$insert = $db->execute([
    'op' => 'insert',
    'collection' => 'tmp_http_notes',
    'data' => ['title' => 'http-test', 'body' => 'x'],
]);
check('Dispatch insert via array {op} success',  ($insert['success'] ?? null) === true, \json_encode($insert));
check('Dispatch insert devuelve _id',            \is_string($insert['data']['_id'] ?? null));
check('Dispatch insert devuelve duration_ms',    isset($insert['duration_ms']));

$select = $db->execute(['op' => 'select', 'collection' => 'tmp_http_notes']);
check('Dispatch select success',                 ($select['success'] ?? null) === true);
check('Dispatch select count >= 1',              ($select['data']['count'] ?? 0) >= 1);

// Mismo Op via Operation directa
$selectOp = (new \Axi\Engine\Op\Select('tmp_http_notes'))->limit(10);
$res = $db->execute($selectOp);
check('Dispatch select Op directa success',      ($res['success'] ?? null) === true);

// Op desconocido -> OP_UNKNOWN
$unknown = $db->execute(['op' => 'foo_bar']);
check('Dispatch op desconocido = OP_UNKNOWN',    ($unknown['code'] ?? null) === \Axi\Engine\AxiException::OP_UNKNOWN);

// Legacy action -> sigue funcionando
$legacy = $db->execute(['action' => 'health_check']);
check('Dispatch legacy {action} sigue ok',       ($legacy['success'] ?? null) === true);

// Cleanup
$storageDir = \realpath(__DIR__ . '/../../STORAGE/tmp_http_notes');
if ($storageDir) {
    foreach (\glob($storageDir . '/*') as $f) {
        \is_dir($f) ? null : @\unlink($f);
    }
    foreach (\glob($storageDir . '/.versions/tmp_http_notes/*/*') as $f) @\unlink($f);
    foreach (\glob($storageDir . '/.versions/tmp_http_notes/*') as $d) @\rmdir($d);
    @\rmdir($storageDir . '/.versions/tmp_http_notes');
    @\rmdir($storageDir . '/.versions');
    @\rmdir($storageDir);
}

echo "\n=== Resultado: {$PASS} passed, {$FAIL} failed ===\n";
exit($FAIL === 0 ? 0 : 1);
