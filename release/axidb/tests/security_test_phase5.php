<?php
/**
 * Test de Seguridad - Fase 5: Prevención de Inyección de Comandos (RCE)
 */

require_once __DIR__ . '/../engine/handlers/FileHandler.php';

// Mock de Historia
class MockHistory {
    public function createSnapshot($file, $content, $msg) {}
}

// Configurar entorno de test
$basePath = __DIR__ . '/test_bunker';
if (!is_dir($basePath)) mkdir($basePath, 0777, true);

$handler = new FileHandler($basePath, new MockHistory());

echo "--- Iniciando Test de Seguridad Fase 5 ---\n";

// 1. Intento de inyección de comandos
echo "[1] Probando inyección de comandos en FileHandler::search...\n";
$maliciousQuery = 'test" & echo vulnerable > ' . $basePath . DIRECTORY_SEPARATOR . 'vuln.txt & "';
$vulnFile = $basePath . DIRECTORY_SEPARATOR . 'vuln.txt';

if (file_exists($vulnFile)) unlink($vulnFile);

try {
    $handler->search($maliciousQuery);
    
    if (file_exists($vulnFile)) {
        echo "❌ ERROR: LA INYECCIÓN DE COMANDOS FUE EXITOSA. Archivo 'vuln.txt' creado.\n";
        unlink($vulnFile);
        exit(1);
    } else {
        echo "✅ Inyección neutralizada correctamente. No se creó el archivo.\n";
    }
} catch (Exception $e) {
    echo "✅ Bloqueado con excepción: " . $e->getMessage() . "\n";
}

// 2. Probar que la búsqueda normal sigue funcionando
echo "\n[2] Probando búsqueda normal...\n";
file_put_contents($basePath . '/test.txt', 'buscame aqui');
$res = $handler->search('buscame');

if ($res['status'] === 'success' || $res['status'] === 'info') {
    echo "✅ Búsqueda normal operativa.\n";
} else {
    echo "❌ ERROR: La búsqueda normal falló tras el parcheo.\n";
    print_r($res);
    exit(1);
}

// Limpiar
unlink($basePath . '/test.txt');
rmdir($basePath);

echo "\n--- Test de Seguridad Fase 5 Finalizado con ÉXITO ---\n";
