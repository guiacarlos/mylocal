<?php
/**
 * AxiDB - Dashboard + Notas demo test (Fase 4).
 *
 * Cubre: gate web.enabled, layout HTML, app.css/js servidos,
 *        Notas demo CRUD via HTTP.
 *
 * Requiere php -S localhost:9991 -t . corriendo (lo arranca/mata el test).
 */

declare(strict_types=1);

require_once __DIR__ . '/../axi.php';

$PASS = 0;
$FAIL = 0;
function check(string $name, bool $cond, string $d = ''): void
{
    global $PASS, $FAIL;
    if ($cond) { $PASS++; echo "  [ok] $name\n"; }
    else       { $FAIL++; echo "  [FAIL] $name" . ($d ? " -- $d" : "") . "\n"; }
}

function http(string $method, string $url, ?string $body = null, array $headers = []): array
{
    $ctx = \stream_context_create([
        'http' => [
            'method'  => $method,
            'header'  => \implode("\r\n", $headers + ['Content-Type: application/x-www-form-urlencoded']),
            'content' => $body,
            'ignore_errors' => true,
            'follow_location' => 0,
        ],
    ]);
    $resp = @\file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header[0]) && \preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
        $code = (int) $m[1];
    }
    $loc = '';
    foreach ($http_response_header ?? [] as $h) {
        if (\stripos($h, 'Location:') === 0) { $loc = \trim(\substr($h, 9)); }
    }
    return ['code' => $code, 'body' => $resp ?: '', 'location' => $loc];
}

function startServer(int $port = 9991): mixed
{
    // Si ya hay algo en :9991, asumimos que el llamador lo arranco.
    $sock = @\fsockopen('localhost', $port, $err, $errno, 0.3);
    if ($sock) {
        \fclose($sock);
        return null;   // ya esta arriba
    }
    $cmd = \PHP_BINARY . ' -c ' . \escapeshellarg(__DIR__ . '/php.ini')
         . ' -S localhost:' . $port . ' -t ' . \escapeshellarg(\dirname(__DIR__, 2));
    $proc = \proc_open($cmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    \usleep(800000);
    return $proc;
}

echo "=== Dashboard + Notas test (Fase 4) ===\n\n";

$cfgPath = __DIR__ . '/../web/config.json';
$origCfg = \file_get_contents($cfgPath);

// ---------------------------------------------------------------------------
echo "[A] Gate web.enabled (cfg en disco)\n";
\file_put_contents($cfgPath, \json_encode(['enabled' => false]));
check('config.json escribible',           \is_file($cfgPath));
$cfg = \json_decode(\file_get_contents($cfgPath), true);
check('cfg.enabled = false (default)',    ($cfg['enabled'] ?? null) === false);

\file_put_contents($cfgPath, \json_encode(['enabled' => true, 'require_auth' => false]));
$cfg = \json_decode(\file_get_contents($cfgPath), true);
check('cfg.enabled = true',               ($cfg['enabled'] ?? null) === true);

// ---------------------------------------------------------------------------
echo "\n[B] HTTP - dashboard responde\n";
$proc = startServer(9991);

$r = http('GET', 'http://localhost:9991/axidb/web/index.php');
check('GET /axidb/web/index.php = 200',   $r['code'] === 200);
check('HTML contiene <title>AxiDB',        \str_contains($r['body'], '<title>AxiDB Dashboard</title>'));
check('Inyecta cfg en window.AXI_DASHBOARD_CFG', \str_contains($r['body'], 'AXI_DASHBOARD_CFG'));
check('Carga app.css',                     \str_contains($r['body'], 'app.css'));
check('Carga app.js',                      \str_contains($r['body'], 'app.js'));

$css = http('GET', 'http://localhost:9991/axidb/web/app.css');
check('app.css = 200',                     $css['code'] === 200);
check('app.css tiene reglas',              \str_contains($css['body'], '.topbar'));

$js = http('GET', 'http://localhost:9991/axidb/web/app.js');
check('app.js = 200',                      $js['code'] === 200);
check('app.js tiene fetch(...)',           \str_contains($js['body'], 'fetch(cfg.api_endpoint'));

// Consola REPL dedicada (Fase 6)
$r2 = http('GET', 'http://localhost:9991/axidb/web/console.php');
check('GET /axidb/web/console.php = 200',  $r2['code'] === 200);
check('Console HTML tiene modo-tabs',      \str_contains($r2['body'], 'mode-tab'));
check('Console inyecta cfg',               \str_contains($r2['body'], 'AXI_DASHBOARD_CFG'));
$consoleJs = http('GET', 'http://localhost:9991/axidb/web/console.js');
check('console.js = 200',                  $consoleJs['code'] === 200);
check('console.js tiene atajos JetBrains', \str_contains($consoleJs['body'], 'F1'));
$consoleCss = http('GET', 'http://localhost:9991/axidb/web/console.css');
check('console.css = 200',                 $consoleCss['code'] === 200);
check('console.css tiene .repl-input',     \str_contains($consoleCss['body'], '.repl-input'));

// Bajar el switch y reverificar 404
\file_put_contents($cfgPath, \json_encode(['enabled' => false]));
$r = http('GET', 'http://localhost:9991/axidb/web/index.php');
check('GET con enabled=false = 404',       $r['code'] === 404);
check('Mensaje claro en body',             \str_contains($r['body'], 'disabled'));

// ---------------------------------------------------------------------------
echo "\n[C] Notas demo (CRUD)\n";

$base = 'http://localhost:9991/axidb/examples/notas';
$r = http('GET', "$base/index.php");
check('Notas index = 200',                 $r['code'] === 200);
check('Notas tiene form de creacion',      \str_contains($r['body'], 'name="title"'));

// Crear una nota
$r = http('POST', "$base/index.php", \http_build_query([
    'action' => 'create',
    'title' => 'Demo Fase 4',
    'body'  => 'Cuerpo de la nota',
]));
check('POST crear redirige (302)',         $r['code'] === 302);
check('Location contiene ?ok=',            \str_contains($r['location'], '?ok='));

// El id generado lo extraemos del location: ?ok=<id>
\preg_match('/\?ok=([^&]+)/', $r['location'], $m);
$id = $m[1] ?? null;
check('id de nota recuperado',             \is_string($id) && $id !== '');

// Buscar
$r = http('GET', "$base/index.php?q=Demo+Fase+4");
check('GET con search ok',                 $r['code'] === 200);
check('Busqueda devuelve la nota',         \str_contains($r['body'], 'Demo Fase 4'));

// Editar
$r = http('GET', "$base/editor.php?id=" . \urlencode($id));
check('Editor.php = 200',                  $r['code'] === 200);
check('Editor muestra titulo',             \str_contains($r['body'], 'Demo Fase 4'));

$r = http('POST', "$base/editor.php", \http_build_query([
    'id' => $id, 'action' => 'update',
    'title' => 'Demo Fase 4 actualizado',
    'body'  => 'Cuerpo editado',
]));
check('POST update redirige',              $r['code'] === 302);

$r = http('GET', "$base/editor.php?id=" . \urlencode($id));
check('GET tras update muestra cambios',   \str_contains($r['body'], 'actualizado'));

// Borrar
$r = http('POST', "$base/editor.php", \http_build_query([
    'id' => $id, 'action' => 'delete',
]));
check('POST delete redirige',              $r['code'] === 302);
check('Redirige a index?del=',             \str_contains($r['location'], 'index.php?del='));

// Cleanup
$db = \Axi();
$db->execute(['op' => 'drop_collection', 'collection' => 'notas_demo']);

// ---------------------------------------------------------------------------
echo "\n[D] Portfolio demo\n";
$r = http('GET', 'http://localhost:9991/axidb/examples/portfolio/index.php');
check('Portfolio index = 200',             $r['code'] === 200);
check('Portfolio tiene form',              \str_contains($r['body'], 'Anadir proyecto'));

$r = http('POST', 'http://localhost:9991/axidb/examples/portfolio/index.php',
    \http_build_query(['name' => 'AxiDB v1', 'tagline' => 'Test', 'url' => 'https://example.com']));
check('Portfolio POST crea',               $r['code'] === 302);

$r = http('GET', 'http://localhost:9991/axidb/examples/portfolio/index.php');
check('Portfolio muestra el proyecto',     \str_contains($r['body'], 'AxiDB v1'));

$db->execute(['op' => 'drop_collection', 'collection' => 'portfolio_projects']);

// ---------------------------------------------------------------------------
echo "\n[E] Remote-client demo (sin AXI_REMOTE_URL)\n";
$r = http('GET', 'http://localhost:9991/axidb/examples/remote-client/index.php');
check('Remote-client = 200',               $r['code'] === 200);
check('Mensaje pide AXI_REMOTE_URL',       \str_contains($r['body'], 'AXI_REMOTE_URL'));

// ---------------------------------------------------------------------------
// Cleanup
\file_put_contents($cfgPath, $origCfg);
if (\is_resource($proc)) { \proc_terminate($proc); \proc_close($proc); }

echo "\n=== Resultado: $PASS passed, $FAIL failed ===\n";
exit($FAIL === 0 ? 0 : 1);
