<?php
/**
 * setup.php
 * Script de inicialización de ACIDE
 * 
 * Crea el primer usuario administrador del sistema
 * Ejecutar una sola vez: php setup.php
 */

require_once __DIR__ . '/UserManager.php';
require_once __DIR__ . '/RoleManager.php';

echo "🚀 ACIDE - Inicialización del Sistema\n";
echo "=====================================\n\n";

$userManager = new UserManager();

// Verificar si ya existe algún usuario
$users = $userManager->listUsers();

if (!empty($users['users'])) {
    echo "⚠️  El sistema ya tiene usuarios registrados.\n";
    echo "Usuarios existentes: " . count($users['users']) . "\n\n";

    foreach ($users['users'] as $user) {
        echo "  - {$user['email']} ({$user['role']})\n";
    }

    echo "\n¿Deseas crear otro usuario? (s/n): ";
    $continue = trim(fgets(STDIN));

    if (strtolower($continue) !== 's') {
        echo "Cancelado.\n";
        exit(0);
    }
}

echo "\n📝 Crear nuevo usuario\n";
echo "---------------------\n";

// Solicitar datos
echo "Email: ";
$email = trim(fgets(STDIN));

echo "Nombre completo: ";
$name = trim(fgets(STDIN));

echo "Contraseña (mín. 8 caracteres): ";
$password = trim(fgets(STDIN));

echo "\nRoles disponibles:\n";
echo "  1. superadmin - Acceso total sin restricciones\n";
echo "  2. admin - Gestión completa de contenido y usuarios\n";
echo "  3. editor - Creación y edición de contenido\n";
echo "  4. viewer - Solo lectura\n";
echo "\nSelecciona rol (1-4): ";
$roleChoice = trim(fgets(STDIN));

$roles = ['superadmin', 'admin', 'editor', 'viewer'];
$role = $roles[$roleChoice - 1] ?? 'viewer';

// Crear usuario
echo "\n⏳ Creando usuario...\n";

$result = $userManager->createUser($email, $password, $name, $role);

if ($result['success']) {
    echo "\n✅ Usuario creado exitosamente!\n\n";
    echo "Detalles:\n";
    echo "  ID: {$result['user']['id']}\n";
    echo "  Email: {$result['user']['email']}\n";
    echo "  Nombre: {$result['user']['name']}\n";
    echo "  Rol: {$result['user']['role']}\n";
    echo "  Estado: {$result['user']['status']}\n";
    echo "\n🎉 Ya puedes iniciar sesión en Marco CMS con estas credenciales.\n";
} else {
    echo "\n❌ Error: {$result['error']}\n";
    exit(1);
}
