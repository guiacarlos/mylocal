<?php
/**
 * Test de Seguridad - Fase 2: Escudo de Autenticación (Middleware)
 */

namespace Axi\Engine;

require_once __DIR__ . '/../axi.php';

use Axi\Engine\Axi;

// Simular entorno
if (!defined('DATA_ROOT')) define('DATA_ROOT', __DIR__ . '/storage_test_data');
if (!defined('STORAGE_ROOT')) define('STORAGE_ROOT', __DIR__ . '/storage_test_projects');

$axi = new Axi();

echo "--- Iniciando Test de Seguridad Fase 2 ---\n";

// 1. Probar operación pública (login)
echo "[1] Probando operación pública (auth.login)...\n";
$reqLogin = ['op' => 'auth.login', 'email' => 'test@example.com', 'password' => 'wrong'];
$resLogin = $axi->execute($reqLogin);
// Debería fallar por credenciales, pero NO por falta de token
if (isset($resLogin['error']) && strpos($resLogin['error'], 'No autorizado') !== false) {
    echo "❌ ERROR: auth.login fue bloqueado por el escudo de autenticación.\n";
    exit(1);
} else {
    echo "✅ Operación pública permitida por el escudo.\n";
}

// 2. Probar operación protegida (select) sin token
echo "\n[2] Probando operación protegida (select) sin token...\n";
$reqSelect = ['op' => 'select', 'collection' => 'users'];
$resSelect = $axi->execute($reqSelect);

if (!$resSelect['success'] && $resSelect['error'] === "No autorizado: Se requiere una sesión válida para realizar esta operación.") {
    echo "✅ Operación protegida bloqueada correctamente.\n";
} else {
    echo "❌ ERROR: Se permitió una operación protegida sin token o el error es incorrecto.\n";
    print_r($resSelect);
    exit(1);
}

// 3. Test de Estrés de Bloqueo (10,000 peticiones)
echo "\n[3] Iniciando Test de Estrés (10,000 bloqueos instantáneos)...\n";
$start = microtime(true);
$iterations = 10000;
for ($i = 0; $i < $iterations; $i++) {
    $axi->execute(['op' => 'update', 'collection' => 'test', 'id' => '1', 'data' => []]);
}
$end = microtime(true);
$time = $end - $start;
echo "✅ Test de estrés de bloqueo completado.\n";
echo "Tiempo total: " . round($time, 4) . "s\n";
echo "Promedio: " . round(($time / $iterations) * 1000, 4) . "ms por bloqueo.\n";

if (($time / $iterations) > 0.005) { // Más de 5ms por bloqueo es lento para un middleware simple
    echo "⚠️ ADVERTENCIA: El escudo de autenticación es más lento de lo esperado.\n";
}

echo "\n--- Test de Seguridad Fase 2 Finalizado con ÉXITO ---\n";
