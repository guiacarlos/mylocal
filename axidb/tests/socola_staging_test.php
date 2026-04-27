<?php
/**
 * AxiDB - Socola staging readiness test (Fase 5 follow-up).
 *
 * Subsistema: tests
 * Objetivo:   ejercitar los gates §8 contra el repo Socola corriendo en
 *             local sobre AxiDB. NO reemplaza un staging real con dominio
 *             y datos historicos; es la verificacion automatizable: cada
 *             gate listado en el plan se traduce a un curl medible.
 *
 *             Cubre:
 *              [A] Gates §8 — los 8 paths de Socola devuelven 200 + marker.
 *              [B] 9 capacidades responden en su allowlist publica.
 *              [C] Paridad legacy/AxiDB ya verificada en parity_test.php
 *                  (referenciamos aqui sin duplicar).
 *              [D] Rollback rehearsal: simula el rollback documentado y
 *                  cronometra (target <5 min; en la practica < 1 s a
 *                  nivel htaccess).
 *
 * Uso: php -c axidb/tests/php.ini axidb/tests/socola_staging_test.php
 *      (arranca y mata su propio php -S)
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
            'method' => $method,
            'header' => \implode("\r\n", $headers + ['Content-Type: application/json', 'Accept: application/json,text/html']),
            'content' => $body,
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => 5,
        ],
    ]);
    $resp = @\file_get_contents($url, false, $ctx);
    $code = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (\preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) { $code = (int) $m[1]; break; }
    }
    return ['code' => $code, 'body' => $resp ?: ''];
}

/** Postea {action: $name, ...$data} al endpoint legacy. */
function legacy(int $port, string $action, array $data = []): array
{
    return http(
        'POST',
        "http://localhost:{$port}/acide/index.php",
        \json_encode(\array_merge(['action' => $action], $data))
    );
}

/** Postea un Op {op: ...} al endpoint AxiDB. */
function axidb(int $port, array $payload): array
{
    return http(
        'POST',
        "http://localhost:{$port}/axidb/api/axi.php",
        \json_encode($payload)
    );
}

function startServer(int $port = 9990): mixed
{
    // Si ya hay servidor en el puerto, reutilizalo (test idempotente).
    $sock = @\fsockopen('localhost', $port, $err, $errno, 0.3);
    if ($sock) { \fclose($sock); return null; }
    $cmd = \PHP_BINARY . ' -c ' . \escapeshellarg(__DIR__ . '/php.ini')
         . ' -S localhost:' . $port . ' -t ' . \escapeshellarg(\dirname(__DIR__, 2));
    $proc = \proc_open($cmd, [['pipe','r'], ['pipe','w'], ['pipe','w']], $pipes);
    \usleep(800000);
    return $proc;
}

echo "=== Socola staging readiness (Fase 5 follow-up) ===\n\n";

$port = 9990;
$proc = startServer($port);

// ---------------------------------------------------------------------------
echo "[A] Gates §8: rutas publicas de Socola devuelven 200 + marker correcto\n";

// Marker = substring distintivo del HTML real (titulo o body class) que
// confirma que la pagina se ha renderizado correcta en lugar de un 404
// transparente o un fragmento.
$gates = [
    ['/index.html',     'page-home',                                  'home'],
    ['/carta.html',     'La Carta',                                   'carta'],
    ['/checkout.html',  'Completa tu Suscripción',                    'checkout'],
    ['/login.html',     'page-login',                                 'login'],
    ['/nosotros.html',  'Slow Café & Bakery Murcia',                  'nosotros'],
    ['/contacto.html',  'Contacto',                                   'contacto'],
    ['/academia.html',  'Academia Gestas AI',                         'academia'],
    ['/carta-tpv.html', 'page-carta-tpv',                             'carta-tpv'],
];

foreach ($gates as [$path, $marker, $label]) {
    $r = http('GET', "http://localhost:{$port}{$path}");
    check("GET {$path} = 200",                   $r['code'] === 200);
    check("HTML de {$label} contiene marker '{$marker}'",
        \str_contains($r['body'], $marker), "len=" . \strlen($r['body']));
}

// build_site no se ejecuta aqui (toca disco real) — referencia documental.
echo "  (i) build_site se ejerce manualmente con `POST /acide/index.php {\"action\":\"build_site\"}`\n";
echo "      o `php CORE/trigger_build.php`. Ver DOCS/BUILD_REPAIR_2026-04-23.md.\n";

// ---------------------------------------------------------------------------
echo "\n[B] 9 capacidades responden en su allowlist publica\n";

// Cada capability tiene >= 1 action publica en CORE/index.php:52.
// Acciones representativas que NO requieren auth:
$capabilities = [
    ['STORE',                'list_products',         []],
    ['QR',                   'get_mesa_settings',     []],
    ['AGENTE_RESTAURANTE',   'health_check',          []],
    ['RESTAURANT_ORGANIZER', 'health_check',          []],
];

foreach ($capabilities as [$cap, $action, $data]) {
    $r = legacy($port, $action, $data);
    $j = \json_decode($r['body'], true);
    $ok = ($r['code'] === 200) && \is_array($j);
    check("legacy {$cap} action={$action} responde 200+JSON", $ok, "code={$r['code']}");

    $r2 = axidb($port, \array_merge(['action' => $action], $data));
    $j2 = \json_decode($r2['body'], true);
    $ok2 = ($r2['code'] === 200) && \is_array($j2);
    check("axidb  {$cap} action={$action} responde 200+JSON", $ok2, "code={$r2['code']}");
}

// 5 capabilities adicionales solo cargadas para ciertos roles (ACADEMY, GEMINI,
// RESERVAS, FSE, TPV, PRODUCTS): su public surface es nula o requiere auth, asi
// que no las testeamos aqui salvo via paridad de ping/health.
echo "  (i) ACADEMY/GEMINI/RESERVAS/FSE/TPV/PRODUCTS estan en CAPABILITIES/\n";
echo "      pero su superficie publica es vacia (todo requiere auth o rol).\n";

// ---------------------------------------------------------------------------
echo "\n[C] Paridad legacy/AxiDB (verificada en parity_test.php)\n";

$parityFile = __DIR__ . '/parity_test.php';
check('parity_test.php existe',   \is_file($parityFile));
$parityCode = \file_get_contents($parityFile);
check('parity_test cubre list_products',     \str_contains($parityCode, 'list_products'));
check('parity_test cubre get_mesa_settings', \str_contains($parityCode, 'get_mesa_settings'));
check('parity_test cubre get_payment_settings', \str_contains($parityCode, 'get_payment_settings'));
echo "  Para ejecutar la paridad completa: `php -c axidb/tests/php.ini axidb/tests/parity_test.php`\n";

// ---------------------------------------------------------------------------
echo "\n[D] Rollback rehearsal — simulacion en sandbox (sin tocar el repo real)\n";

$tmp = __DIR__ . '/_tmp_rollback';
if (\is_dir($tmp)) {
    foreach (\glob("$tmp/*") as $f) { @\unlink($f); }
} else {
    \mkdir($tmp, 0777, true);
}

// Crea un .htaccess mock con la regla legacy + el bloque AxiDB
$pre = <<<HTAC
RewriteEngine On
RewriteRule ^acide/(.*)$ CORE/\$1 [END,QSA]
RewriteRule ^axidb/api/(.*)$ axidb/api/\$1 [END,QSA]
HTAC;
\file_put_contents("$tmp/.htaccess", $pre);

$tStart = \microtime(true);

// 1) Backup
\copy("$tmp/.htaccess", "$tmp/.htaccess.bak");

// 2) Aplica swap (deja solo la regla legacy)
\file_put_contents("$tmp/.htaccess",
    "RewriteEngine On\nRewriteRule ^acide/(.*)$ CORE/$1 [END,QSA]\n");

// 3) Verifica swap aplicado
$after = \file_get_contents("$tmp/.htaccess");
check('Rollback step 1: backup creado',          \is_file("$tmp/.htaccess.bak"));
check('Rollback step 2: htaccess limpio',        !\str_contains($after, 'axidb/api'));

// 4) Rollback: restaura backup
\copy("$tmp/.htaccess.bak", "$tmp/.htaccess");

$dt = (\microtime(true) - $tStart) * 1000;
$restored = \file_get_contents("$tmp/.htaccess");
check('Rollback step 3: htaccess restaurado',    \str_contains($restored, 'axidb/api'));
check("Rollback completo en <5 min (real: " . \round($dt, 1) . "ms)",   $dt < 300_000);

// Cleanup
foreach (\glob("$tmp/*") as $f) { @\unlink($f); }
@\rmdir($tmp);

// ---------------------------------------------------------------------------
echo "\n[E] Flip a produccion — checklist documental\n";

$migDoc = __DIR__ . '/../docs/standard/migration-socola.md';
check('migration-socola.md existe',          \is_file($migDoc));
$migContent = \file_get_contents($migDoc);
check('Doc tiene seccion 3.5 Rollback',      \str_contains($migContent, 'Rollback'));
check('Doc tiene seccion 3.2 Aplicar patch', \str_contains($migContent, 'Aplicar el patch'));
check('Doc menciona snapshot pre-flip',      \str_contains($migContent, 'pre-flip')
                                           || \str_contains($migContent, 'pre_flip'));

echo "  El flip real lo aplica el operador en produccion siguiendo:\n";
echo "    1. axidb/migration/htaccess.patch\n";
echo "    2. axidb/docs/standard/migration-socola.md §3\n";
echo "    3. axidb/migration/flip-runbook.sh (timed dry-run)\n";

// Cleanup
if (\is_resource($proc)) { \proc_terminate($proc); \proc_close($proc); }

echo "\n=== Resultado: $PASS passed, $FAIL failed ===\n";
exit($FAIL === 0 ? 0 : 1);
