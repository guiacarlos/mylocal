<?php
/**
 * Test de Seguridad - Fase 1: Path Traversal y Jailing
 */

require_once __DIR__ . '/../engine/StorageManager.php';

use Axi\Engine\StorageManager;

// Definir constantes si no existen
if (!defined('DATA_ROOT')) define('DATA_ROOT', __DIR__ . '/storage_test_data');
if (!defined('STORAGE_ROOT')) define('STORAGE_ROOT', __DIR__ . '/storage_test_projects');

// Limpiar directorios de test
function cleanDir($dir) {
    if (!is_dir($dir)) return;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }
    rmdir($dir);
}

cleanDir(DATA_ROOT);
cleanDir(STORAGE_ROOT);

$storage = new StorageManager(DATA_ROOT, STORAGE_ROOT);

echo "--- Iniciando Test de Seguridad Fase 1 ---\n";

// 1. Test de Inyección de Directorio (Path Traversal)
echo "[1] Probando Path Traversal...\n";
$traversalAttempts = [
    ['collection' => '../config', 'id' => 'malicious'],
    ['collection' => 'users', 'id' => '../../index'],
    ['collection' => 'test', 'id' => 'id/with/slashes'],
    ['collection' => 'test', 'id' => '..\\win.ini'],
];

foreach ($traversalAttempts as $attempt) {
    try {
        $storage->update($attempt['collection'], $attempt['id'], ['data' => 'evil']);
        echo "❌ ERROR: Se permitió una ruta maliciosa: {$attempt['collection']} / {$attempt['id']}\n";
        exit(1);
    } catch (Exception $e) {
        echo "✅ Bloqueado correctamente: " . $e->getMessage() . "\n";
    }
}

// 2. Test de Operaciones Legales
echo "\n[2] Probando operaciones legales...\n";
try {
    $storage->update('products', 'p1', ['name' => 'Test Product']);
    $data = $storage->read('products', 'p1');
    if ($data['name'] === 'Test Product') {
        echo "✅ Lectura/Escritura legal exitosa.\n";
    } else {
        echo "❌ Error en consistencia de datos.\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ ERROR: Se bloqueó una operación legal: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Test de Estrés (Simulado)
echo "\n[3] Iniciando Test de Estrés (1000 iteraciones rápidas)...\n";
$start = microtime(true);
$iterations = 1000;
for ($i = 0; $i < $iterations; $i++) {
    $storage->update('stress_test', 'item_' . $i, ['val' => $i]);
}
$end = microtime(true);
$time = $end - $start;
echo "✅ Test de estrés completado.\n";
echo "Tiempo total: " . round($time, 4) . "s\n";
echo "Promedio: " . round(($time / $iterations) * 1000, 4) . "ms por operación.\n";

if (($time / $iterations) > 0.05) { // Más de 50ms por op en local es sospechoso
    echo "⚠️ ADVERTENCIA: Rendimiento degradado detectado.\n";
}

echo "\n--- Test de Seguridad Fase 1 Finalizado con ÉXITO ---\n";
