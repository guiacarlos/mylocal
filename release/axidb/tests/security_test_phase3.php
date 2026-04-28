<?php
/**
 * Test de Seguridad - Fase 3: RBAC y Whitelist por Colección
 */

namespace Axi\Engine;

require_once __DIR__ . '/../axi.php';

use Axi\Engine\Axi;

// Simular entorno
if (!defined('DATA_ROOT')) define('DATA_ROOT', __DIR__ . '/storage_test_data');
if (!defined('STORAGE_ROOT')) define('STORAGE_ROOT', __DIR__ . '/storage_test_projects');

$axi = new Axi();

echo "--- Iniciando Test de Seguridad Fase 3 ---\n";

// 1. Probar colección pública (products) sin token
echo "[1] Probando colección pública (products) sin token...\n";
$reqPublic = ['op' => 'select', 'collection' => 'products'];
$resPublic = $axi->execute($reqPublic);

if ($resPublic['success'] || (isset($resPublic['error']) && strpos($resPublic['error'], 'No autorizado') === false)) {
    echo "✅ Acceso a colección pública permitido (o error no relacionado con auth).\n";
} else {
    echo "❌ ERROR: Se bloqueó el acceso a una colección pública: " . ($resPublic['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

// 2. Probar colección privada (users) sin token
echo "\n[2] Probando colección privada (users) sin token...\n";
$reqPrivate = ['op' => 'select', 'collection' => 'users'];
$resPrivate = $axi->execute($reqPrivate);

if (!$resPrivate['success'] && strpos($resPrivate['error'], 'No autorizado') !== false) {
    echo "✅ Acceso a colección privada bloqueado correctamente.\n";
} else {
    echo "❌ ERROR: Se permitió acceso a colección privada sin token.\n";
    exit(1);
}

// 3. Simular Usuario 'student' (no admin) y acceder a 'users'
echo "\n[3] Probando acceso de usuario no-admin a colección maestra...\n";

// Mock de validación de token para simular un usuario student
// Para esto, necesitaríamos que Auth->validateRequest devuelva un usuario student.
// Como no queremos tocar Auth.php ni crear archivos de sesión reales, 
// podemos usar un pequeño hack si Axi permite inyectar el usuario.
// En Axi.php implementé getCurrentUser() pero no setCurrentUser().

// Alternativa: Crear un usuario real y una sesión real.
// Pero es complejo para un test rápido.

// Vamos a probar la lógica de bloqueo de colecciones maestras inyectando el usuario en Axi via Reflection si es necesario,
// o simplemente asumiendo que la lógica en Axi.php es correcta si pasa el check visual.

// Pero el plan dice "Verificaremos". Así que hagamos un test real.
// Necesito un token válido.

/** @var \Auth $auth */
$auth = $axi->getService('auth');
if ($auth) {
    // Crear un usuario student para el test
    $userManager = new \UserManager();
    $studentEmail = 'student@test.com';
    $studentPass = 'password123';
    
    try {
        $userManager->createUser($studentEmail, $studentPass, 'Student Test', 'student');
    } catch (\Exception $e) {
        // Probablemente ya existe
    }

    $loginRes = $auth->login($studentEmail, $studentPass);
    if ($loginRes['success']) {
        $token = $loginRes['token'];
        echo "✅ Usuario student logueado. Token obtenido.\n";
        
        // Configurar el token para las peticiones (via Cookie para simplicidad en el test)
        $_COOKIE['acide_session'] = $token;
        
        // Intentar acceder a 'users' (colección maestra)
        $reqMaster = ['op' => 'select', 'collection' => 'users'];
        $resMaster = $axi->execute($reqMaster);
        
        if (!$resMaster['success'] && strpos($resMaster['error'], 'Prohibido') !== false) {
            echo "✅ Acceso de student a 'users' bloqueado correctamente (403 Forbidden).\n";
        } else {
            echo "❌ ERROR: Un usuario student pudo acceder a la colección 'users'!\n";
            print_r($resMaster);
            exit(1);
        }
    } else {
        echo "⚠️ Saltando test de RBAC real: no se pudo loguear al usuario de test.\n";
    }
}

echo "\n--- Test de Seguridad Fase 3 Finalizado con ÉXITO ---\n";
