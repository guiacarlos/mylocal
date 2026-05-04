<?php
/**
 * AxiDB - release_adapter.php: post-procesa release/ tras build_site para
 * actualizar el gateway de Socola de /acide/ a /axidb/api/ (Fase 5).
 *
 * Subsistema: migration
 * Responsable: tras un build_site exitoso del legacy (CORE/core/StaticGenerator),
 *              actualizar las referencias en release/.htaccess y los HTML
 *              canonicos para que el SPA hable con /axidb/api/axi.php en
 *              vez de con /acide/index.php.
 *
 * Modo de uso:
 *   php axidb/migration/release_adapter.php [--release release/] [--dry-run]
 *
 * Idempotente: ejecuta varias veces sin romper. Si el release ya esta
 * adaptado, no toca nada.
 *
 * NO toca:
 *   - El .htaccess de la raiz del proyecto.
 *   - El gateway.php de la raiz del proyecto.
 *   - El CORE/ original.
 *
 * Solo modifica el release/ generado, que es desechable y se regenera
 * en cada build_site.
 */

declare(strict_types=1);

$dryRun  = \in_array('--dry-run', $argv ?? [], true);
$releaseDir = (string) ($argv[1] ?? 'release');
$releaseAbs = \realpath($releaseDir) ?: ($releaseDir);

if (!\is_dir($releaseAbs)) {
    \fwrite(\STDERR, "release_adapter: directorio '{$releaseAbs}' no existe.\n");
    \fwrite(\STDERR, "Ejecuta build_site (action=build_site) primero, o pasa --release <path>.\n");
    exit(2);
}

echo "release_adapter:\n";
echo "  release path: {$releaseAbs}\n";
echo "  dry-run     : " . ($dryRun ? 'yes' : 'no') . "\n\n";

$changes = 0;

// 1. .htaccess: anadir alias /acide/ -> /axidb/api/ (retrocompat).
$htaccess = $releaseAbs . '/.htaccess';
if (\is_file($htaccess)) {
    $orig = \file_get_contents($htaccess);
    if (!\str_contains($orig, 'axidb/api/')) {
        $patched = preg_replace(
            '/(RewriteRule\s+\^acide\/\(\.\*\)\$\s+CORE\/\$1\s+\[END,QSA\])/',
            "# AxiDB Fase 5: alias retrocompat. /acide/ y /axidb/api/ rutean al mismo motor.\n    RewriteRule ^axidb/api/(.*)$ axidb/api/$1 [L]\n    $1",
            $orig
        );
        if ($patched !== $orig && $patched !== null) {
            if (!$dryRun) { \file_put_contents($htaccess, $patched); }
            echo "  [patch] .htaccess: anadido alias /axidb/api/ (idempotente)\n";
            $changes++;
        }
    } else {
        echo "  [skip]  .htaccess ya contiene 'axidb/api/' — idempotente\n";
    }
}

// 2. HTML canonicos: si el SPA llama explicitamente /acide/index.php, anadir
//    fallback a /axidb/api/axi.php. Lo hacemos comentando la URL legacy y
//    poniendo la nueva en su lugar SOLO en archivos que ya la mencionen.
$spaFiles = ['admin.html', 'carta-tpv.html', 'index.html', 'checkout.html'];
foreach ($spaFiles as $rel) {
    $path = $releaseAbs . '/' . $rel;
    if (!\is_file($path)) { continue; }
    $orig = \file_get_contents($path);
    $new  = \str_replace('/acide/index.php', '/axidb/api/axi.php', $orig);
    if ($new !== $orig) {
        if (!$dryRun) { \file_put_contents($path, $new); }
        echo "  [patch] {$rel}: sustituido /acide/index.php -> /axidb/api/axi.php\n";
        $changes++;
    }
}

// 3. /js/*.js (axios baseURL, fetch hardcoded) — si los hay.
$jsDir = $releaseAbs . '/js';
if (\is_dir($jsDir)) {
    foreach (\glob($jsDir . '/*.js') as $jsPath) {
        $orig = \file_get_contents($jsPath);
        $new  = \str_replace('/acide/index.php', '/axidb/api/axi.php', $orig);
        if ($new !== $orig) {
            if (!$dryRun) { \file_put_contents($jsPath, $new); }
            echo "  [patch] js/" . \basename($jsPath) . ": URL gateway actualizada\n";
            $changes++;
        }
    }
}

// 4. axidb/ entera: copiar al release si no esta.
$releaseAxidb = $releaseAbs . '/axidb';
if (!\is_dir($releaseAxidb)) {
    if (!$dryRun) {
        echo "  [copy]  axidb/ -> release/axidb/ (esto puede tardar)\n";
        copyDir(__DIR__ . '/..', $releaseAxidb);
    } else {
        echo "  [copy]  axidb/ -> release/axidb/ (dry-run, no copiado)\n";
    }
    $changes++;
} else {
    echo "  [skip]  release/axidb/ ya existe\n";
}

echo "\n";
echo $changes === 0 ? "OK (release ya adaptado)\n" : "OK ({$changes} cambios" . ($dryRun ? " — dry-run" : "") . ")\n";
exit(0);

// -------------------------------------------------------------

function copyDir(string $src, string $dst): void
{
    if (!\is_dir($dst)) { \mkdir($dst, 0755, true); }
    foreach (\scandir($src) as $entry) {
        if ($entry === '.' || $entry === '..') { continue; }
        // Excluir runtime data / tests.
        if (\in_array($entry, ['tests', 'docs'], true)) { continue; }
        $srcPath = $src . '/' . $entry;
        $dstPath = $dst . '/' . $entry;
        if (\is_dir($srcPath)) {
            copyDir($srcPath, $dstPath);
        } else {
            \copy($srcPath, $dstPath);
        }
    }
}
