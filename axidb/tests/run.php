<?php
/**
 * AxiDB - Test runner: ejecuta todos los tests y reporta totales.
 *
 * Subsistema: tests
 * Responsable: lanzar cada archivo *_test.php del directorio tests/, capturar
 *              su salida, sumar passed/failed, devolver exit code agregado.
 *
 * Uso: php axidb/tests/run.php [--verbose] [--fail-fast]
 *
 * Nota sobre HTTP transport: sdk_test.php incluye una seccion de matriz
 *       Embedded+HTTP. Para que la parte HTTP no se salte, arrancar antes
 *       `php -S localhost:9991 -t .` en otra terminal.
 */

$verbose  = \in_array('--verbose',   $_SERVER['argv'] ?? [], true);
$failFast = \in_array('--fail-fast', $_SERVER['argv'] ?? [], true);

$testDir  = __DIR__;
$tests    = \glob($testDir . '/*_test.php');
$rootTest = \dirname($testDir, 2) . '/test_axidb.php';
if (\is_file($rootTest)) {
    \array_unshift($tests, $rootTest);
}

echo "=== AxiDB test runner ===\n";
echo "Encontrados " . \count($tests) . " archivos de test.\n\n";

$totalPass = 0;
$totalFail = 0;
$filesOk   = 0;
$filesKo   = 0;
$reports   = [];
$startTs   = \microtime(true);

// Si existe axidb/tests/php.ini lo pasamos via -c (habilita openssl en
// entornos donde no esta cargado por defecto, p.ej. Windows winget PHP).
$localIni = __DIR__ . '/php.ini';
$iniArg   = \is_file($localIni) ? '-c ' . \escapeshellarg($localIni) . ' ' : '';

foreach ($tests as $file) {
    $label = \basename($file);
    echo "--- {$label} ---\n";

    $t0 = \microtime(true);
    $cmd = \escapeshellarg(\PHP_BINARY) . ' ' . $iniArg . \escapeshellarg($file) . ' 2>&1';
    $out = \shell_exec($cmd) ?? '';
    $elapsed = (\microtime(true) - $t0) * 1000;

    // Extraer "Resultado: N passed, M failed"
    $pass = 0;
    $fail = 0;
    if (\preg_match('/Resultado:\s*(\d+)\s+passed,\s*(\d+)\s+failed/i', $out, $m)) {
        $pass = (int) $m[1];
        $fail = (int) $m[2];
    } elseif (\str_contains($out, '--- OK ---')) {
        $pass = 1;
        $fail = 0;
    } else {
        // Smoke tests sin formato estandar: success si exit code 0.
        $exit = 0;
        $out2 = [];
        \exec($cmd, $out2, $exit);
        if ($exit === 0) {
            $pass = 1;
        } else {
            $fail = 1;
        }
    }

    $totalPass += $pass;
    $totalFail += $fail;
    ($fail === 0) ? $filesOk++ : $filesKo++;
    $reports[] = [
        'file'    => $label,
        'pass'    => $pass,
        'fail'    => $fail,
        'elapsed' => $elapsed,
    ];

    if ($verbose || $fail > 0) {
        echo $out;
    } else {
        \printf("  %d passed, %d failed (%.0fms)\n", $pass, $fail, $elapsed);
    }
    echo "\n";

    if ($failFast && $fail > 0) {
        echo "[fail-fast] Deteniendo tras fallo en {$label}\n";
        break;
    }
}

$totalElapsed = (\microtime(true) - $startTs) * 1000;

echo "\n===============================================\n";
echo "Resumen:\n";
echo "  Files:  {$filesOk} ok / {$filesKo} ko\n";
\printf("  Checks: %d passed, %d failed\n", $totalPass, $totalFail);
\printf("  Time:   %.0fms\n", $totalElapsed);
echo "===============================================\n";

foreach ($reports as $r) {
    $mark = $r['fail'] === 0 ? '[ok] ' : '[FAIL]';
    \printf("  %s %-30s %4d passed, %3d failed  (%.0fms)\n",
        $mark, $r['file'], $r['pass'], $r['fail'], $r['elapsed']);
}

exit($totalFail === 0 ? 0 : 1);
