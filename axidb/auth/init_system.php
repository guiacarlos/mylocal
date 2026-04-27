<?php
/**
 * init_system.php
 * InicializaciÃ³n automÃ¡tica completa del sistema ACIDE
 * Ejecutar UNA SOLA VEZ para dejar todo listo
 */

echo "ðŸš€ ACIDE - InicializaciÃ³n AutomÃ¡tica del Sistema\n";
echo "================================================\n\n";

// 1. Crear usuario SuperAdmin
echo "1ï¸âƒ£  Creando usuario SuperAdmin...\n";

require_once __DIR__ . '/UserManager.php';
$userManager = new UserManager();

$email = 'info@gestasai.com';
$password = 'PACOjuan@2025#';

// Verificar si ya existe
$existing = $userManager->getUserByEmail($email);

if ($existing) {
    echo "   âš ï¸  Usuario ya existe (ID: {$existing['id']})\n";
    echo "   â„¹ï¸  Actualizando contraseÃ±a...\n";
    $result = $userManager->changePassword($existing['id'], $password);
    if ($result['success']) {
        echo "   âœ… ContraseÃ±a actualizada\n\n";
    }
} else {
    $result = $userManager->createUser($email, $password, 'GestasAI SuperAdmin', 'superadmin');

    if ($result['success']) {
        echo "   âœ… Usuario creado exitosamente\n";
        echo "      ID: {$result['user']['id']}\n";
        echo "      Email: {$result['user']['email']}\n";
        echo "      Rol: {$result['user']['role']}\n\n";
    } else {
        echo "   âŒ Error: {$result['error']}\n\n";
        exit(1);
    }
}

// 2. Verificar archivos de roles
echo "2ï¸âƒ£  Verificando sistema de roles...\n";
$rolesFile = __DIR__ . '/data/roles.json';
if (file_exists($rolesFile)) {
    echo "   âœ… Archivo de roles encontrado\n\n";
} else {
    echo "   âŒ Archivo de roles no encontrado\n\n";
    exit(1);
}

// 3. Test de autenticaciÃ³n
echo "3ï¸âƒ£  Probando autenticaciÃ³n...\n";
require_once __DIR__ . '/Auth.php';
$auth = new Auth();

$loginResult = $auth->login($email, $password);

if ($loginResult['success']) {
    echo "   âœ… AutenticaciÃ³n funcional\n";
    echo "      Token generado: " . substr($loginResult['token'], 0, 16) . "...\n\n";
} else {
    echo "   âŒ Error en autenticaciÃ³n: {$loginResult['error']}\n\n";
    exit(1);
}

// 4. Generar script de prueba para navegador
echo "4ï¸âƒ£  Generando script de prueba...\n\n";

$browserScript = <<<'JAVASCRIPT'
// ðŸŽ¯ SCRIPT DE PRUEBA ACIDE - LISTO PARA USAR
(async () => {
    console.clear();
    console.log('%cðŸ” LOGIN ACIDE', 'background: #10B981; color: white; font-size: 20px; padding: 10px; font-weight: bold;');
    
    try {
        const response = await fetch('/acide/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'auth_login',
                data: {
                    email: 'info@gestasai.com',
                    password: 'PACOjuan@2025#'
                }
            })
        });

        const result = await response.json();

        if (result.success) {
            console.log('%câœ… LOGIN EXITOSO', 'background: #10B981; color: white; font-size: 16px; padding: 8px;');
            console.log('\nðŸ”‘ Token:', result.data.token);
            console.log('\nðŸ‘¤ Usuario:');
            console.table(result.data.user);
            
            localStorage.setItem('acide_token', result.data.token);
            localStorage.setItem('acide_user', JSON.stringify(result.data.user));
            
            console.log('\nðŸ’¾ SesiÃ³n guardada en localStorage');
            console.log('\nðŸŽ‰ Â¡Sistema funcionando correctamente!');
        } else {
            console.log('%câŒ ERROR', 'background: #EF4444; color: white; padding: 8px;');
            console.log('Error:', result.error);
        }
    } catch (e) {
        console.error('Error de conexiÃ³n:', e);
    }
})();
JAVASCRIPT;

file_put_contents(__DIR__ . '/test_browser.js', $browserScript);
echo "   âœ… Script guardado en: test_browser.js\n\n";

// Resumen final
echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";
echo "âœ… SISTEMA INICIALIZADO CORRECTAMENTE\n";
echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n\n";

echo "ðŸ“‹ CREDENCIALES DE ACCESO:\n";
echo "   Email:    $email\n";
echo "   Password: [la que configuraste]\n";
echo "   Rol:      superadmin\n\n";

echo "ðŸŒ PARA PROBAR EN EL NAVEGADOR:\n";
echo "   1. Abre Marco CMS en tu navegador\n";
echo "   2. Abre la consola (F12)\n";
echo "   3. Copia y pega el contenido de: test_browser.js\n";
echo "   4. O simplemente haz login normal en la interfaz\n\n";

echo "ðŸŽ¯ TODO LISTO - SOLO HAZ LOGIN\n\n";
