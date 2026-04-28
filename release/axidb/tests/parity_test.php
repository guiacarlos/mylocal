<?php
/**
 * AxiDB - Test de paridad legacy ACIDE vs AxiDB (Fase 5).
 *
 * Verifica que un set de acciones representativas de Socola producen
 * **la misma respuesta** ejecutadas via /acide/index.php (gateway legacy)
 * y via /axidb/api/axi.php (gateway nuevo) — ambos en el mismo storage.
 *
 * Es la prueba clave del Caso C del plan: el flip atomico sera seguro
 * solo si los dos endpoints son equivalentes desde el punto de vista
 * del cliente (Socola SPA, build_site, capabilities).
 *
 * Requiere php -S corriendo (lo arranca/mata el test). El motor delega
 * {action:...} al ACIDE legacy en ambos endpoints (en /acide/index.php
 * directamente, en /axidb/api/axi.php via Axi\Engine\Axi::delegateToLegacy).
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

function postJson(string $url, array $payload): array
{
    $ctx = \stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nAccept: application/json",
            'content' => \json_encode($payload),
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @\file_get_contents($url, false, $ctx);
    return $raw === false ? ['success' => false, 'error' => 'network'] : (\json_decode($raw, true) ?? ['success' => false, 'error' => 'no-json']);
}

function startServer(int $port): mixed
{
    $sock = @\fsockopen('localhost', $port, $err, $errno, 0.3);
    if ($sock) { \fclose($sock); return null; }
    $cmd = \PHP_BINARY . ' -c ' . \escapeshellarg(__DIR__ . '/php.ini')
         . ' -S localhost:' . $port . ' -t ' . \escapeshellarg(\dirname(__DIR__, 2));
    $proc = \proc_open($cmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    \usleep(800000);
    return $proc;
}

/** Compara dos responses ignorando timestamps y duraciones. */
function equivalent(array $a, array $b): bool
{
    foreach (['duration_ms', 'timestamp'] as $k) {
        unset($a[$k], $b[$k]);
        if (isset($a['data']) && \is_array($a['data'])) { unset($a['data'][$k]); }
        if (isset($b['data']) && \is_array($b['data'])) { unset($b['data'][$k]); }
    }
    // Diferencia conocida: AxiDB anade 'code' siempre, ACIDE legacy no.
    unset($a['code'], $b['code']);
    return $a === $b;
}

echo "=== Paridad ACIDE legacy vs AxiDB gateway (Fase 5) ===\n\n";

$proc = startServer(9991);
$legacy = 'http://localhost:9991/acide/index.php';   // .htaccess rewrite a CORE/
$axi    = 'http://localhost:9991/axidb/api/axi.php'; // dispatcher nuevo

// ---------------------------------------------------------------------------
echo "[A] El gateway legacy responde\n";
$ping = postJson($legacy, ['action' => 'health_check']);
$reachable = ($ping['success'] ?? null) === true;
check('Legacy /acide/ alcanzable (health_check)', $reachable, $ping['error'] ?? '');

if (!$reachable) {
    // Posible: php -S no respeta .htaccess. Probamos con CORE/index.php directo.
    $legacy = 'http://localhost:9991/CORE/index.php';
    $ping = postJson($legacy, ['action' => 'health_check']);
    $reachable = ($ping['success'] ?? null) === true;
    check('Fallback CORE/index.php directo',     $reachable);
}

if (!$reachable) {
    echo "\n[!] No se puede contactar al gateway legacy. Test abortado.\n";
    if (\is_resource($proc)) { \proc_terminate($proc); \proc_close($proc); }
    echo "\n=== Resultado: $PASS passed, $FAIL failed ===\n";
    exit($FAIL === 0 ? 0 : 1);
}

// ---------------------------------------------------------------------------
echo "\n[B] Pares de acciones equivalentes\n";

// Acciones de negocio — la verdadera prueba de paridad. Excluimos:
//   - health_check: cada motor reporta su propio shape (esperado).
//   - get_media_formats: precondiciones de auth/system distintas en CLI test.
$actions = [
    ['name' => 'list_products',       'payload' => ['action' => 'list_products']],
    ['name' => 'get_mesa_settings',   'payload' => ['action' => 'get_mesa_settings']],
    ['name' => 'get_payment_settings','payload' => ['action' => 'get_payment_settings']],
];

foreach ($actions as $a) {
    $rL = postJson($legacy, $a['payload']);
    $rA = postJson($axi,    $a['payload']);
    $okL = ($rL['success'] ?? null) === true;
    $okA = ($rA['success'] ?? null) === true;

    check("legacy success: {$a['name']}",  $okL,  'err=' . ($rL['error'] ?? '?'));
    check("axidb success: {$a['name']}",   $okA,  'err=' . ($rA['error'] ?? '?'));

    if ($okL && $okA) {
        $eq = equivalent($rL, $rA);
        check("paridad shape: {$a['name']}", $eq);
    }
}

// ---------------------------------------------------------------------------
echo "\n[C] AxiDB tambien acepta el contrato Op model con la misma data\n";

// list_products via op model nuevo seria 'select' sobre store/products.
$rOp = postJson($axi, ['op' => 'select', 'collection' => 'store', 'limit' => 100]);
check('axidb {op:select store} success', ($rOp['success'] ?? null) === true);

// ping (Op nuevo) equivale semanticamente a health_check legacy.
$rOpPing = postJson($axi, ['op' => 'ping']);
$rLegPing = postJson($legacy, ['action' => 'health_check']);
check('Op ping success',                ($rOpPing['success'] ?? null) === true);
check('Legacy health_check success',     ($rLegPing['success'] ?? null) === true);
// Ambas deberian decir engine + status.
$pingEng = $rOpPing['data']['engine'] ?? '';
check('Op ping reporta engine AxiDB',   \str_contains($pingEng, 'AxiDB'));

// ---------------------------------------------------------------------------
echo "\n[D] Headers de auditoria solo presentes en /axidb/api/\n";
// Reuso curl con -i para inspeccionar headers
function fetchHeaders(string $url, array $payload): array
{
    $h = \curl_init($url);
    \curl_setopt_array($h, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => \json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 5,
    ]);
    $raw = \curl_exec($h);
    \curl_close($h);
    return \is_string($raw) ? \explode("\r\n", $raw) : [];
}

if (\function_exists('curl_init')) {
    $hA = fetchHeaders($axi,    ['op' => 'ping']);
    $hL = fetchHeaders($legacy, ['action' => 'health_check']);
    $axiHasOp  = \array_filter($hA, fn($h) => \stripos($h, 'X-Axi-Op:') !== false);
    $legHasOp  = \array_filter($hL, fn($h) => \stripos($h, 'X-Axi-Op:') !== false);
    check('AxiDB envia X-Axi-Op header',    !empty($axiHasOp));
    check('Legacy NO envia X-Axi-Op header', empty($legHasOp));
}

// ---------------------------------------------------------------------------
if (\is_resource($proc)) { \proc_terminate($proc); \proc_close($proc); }

echo "\n=== Resultado: $PASS passed, $FAIL failed ===\n";
exit($FAIL === 0 ? 0 : 1);
