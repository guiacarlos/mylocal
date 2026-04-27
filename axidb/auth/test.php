<?php
/**
 * test.php
 * Script de prueba del sistema de autenticación
 */

require_once __DIR__ . '/UserManager.php';
require_once __DIR__ . '/RoleManager.php';
require_once __DIR__ . '/Auth.php';

echo " ACIDE Auth System - Test Suite\n";
echo "==================================\n\n";

$userManager = new UserManager();
$roleManager = new RoleManager();
$auth = new Auth();

// Test 1: Crear usuario de prueba
echo "Test 1: Crear usuario de prueba...\n";
$testEmail = 'test@acide.local';
$testPassword = 'test1234';

$result = $userManager->createUser(
    $testEmail,
    $testPassword,
    'Usuario de Prueba',
    'editor'
);

if ($result['success']) {
    echo " Usuario creado: {$result['user']['id']}\n\n";
    $userId = $result['user']['id'];
} else {
    // Si ya existe, obtenerlo
    $existingUser = $userManager->getUserByEmail($testEmail);
    if ($existingUser) {
        echo "ℹ  Usuario ya existe, usando existente\n\n";
        $userId = $existingUser['id'];
    } else {
        echo " Error: {$result['error']}\n";
        exit(1);
    }
}

// Test 2: Autenticación
echo "Test 2: Autenticar usuario...\n";
$loginResult = $auth->login($testEmail, $testPassword);

if ($loginResult['success']) {
    echo " Login exitoso\n";
    echo "   Token: " . substr($loginResult['token'], 0, 16) . "...\n";
    echo "   Usuario: {$loginResult['user']['name']}\n";
    echo "   Rol: {$loginResult['user']['role']}\n\n";
    $token = $loginResult['token'];
} else {
    echo " Login falló: {$loginResult['error']}\n";
    exit(1);
}

// Test 3: Verificar permisos
echo "Test 3: Verificar permisos del rol 'editor'...\n";
$canCreate = $roleManager->hasPermission('editor', 'content', 'create');
$canDelete = $roleManager->hasPermission('editor', 'content', 'delete');
$canManageUsers = $roleManager->hasPermission('editor', 'users', 'create');

echo "   Crear contenido: " . ($canCreate ? " Sí" : " No") . "\n";
echo "   Eliminar contenido: " . ($canDelete ? " Sí" : " No") . "\n";
echo "   Gestionar usuarios: " . ($canManageUsers ? " Sí" : " No") . "\n\n";

// Test 4: Actualizar usuario
echo "Test 4: Actualizar usuario...\n";
$updateResult = $userManager->updateUser($userId, [
    'name' => 'Usuario de Prueba Actualizado'
]);

if ($updateResult['success']) {
    echo " Usuario actualizado\n\n";
} else {
    echo " Error: {$updateResult['error']}\n\n";
}

// Test 5: Listar usuarios
echo "Test 5: Listar todos los usuarios...\n";
$listResult = $userManager->listUsers();

if ($listResult['success']) {
    echo " Total de usuarios: " . count($listResult['users']) . "\n";
    foreach ($listResult['users'] as $user) {
        echo "   - {$user['email']} ({$user['role']})\n";
    }
    echo "\n";
}

// Test 6: Cambiar contraseña
echo "Test 6: Cambiar contraseña...\n";
$newPassword = 'newtest1234';
$changeResult = $userManager->changePassword($userId, $newPassword);

if ($changeResult['success']) {
    echo " Contraseña cambiada\n";

    // Verificar que la nueva contraseña funciona
    $loginResult2 = $auth->login($testEmail, $newPassword);
    if ($loginResult2['success']) {
        echo " Login con nueva contraseña exitoso\n\n";
    } else {
        echo " Login con nueva contraseña falló\n\n";
    }
} else {
    echo " Error: {$changeResult['error']}\n\n";
}

// Test 7: Validación de contraseña incorrecta
echo "Test 7: Intentar login con contraseña incorrecta...\n";
$badLogin = $auth->login($testEmail, 'wrongpassword');

if (!$badLogin['success']) {
    echo " Correctamente rechazado: {$badLogin['error']}\n\n";
} else {
    echo " ERROR: Aceptó contraseña incorrecta!\n\n";
}

// Cleanup (opcional)
echo "¿Deseas eliminar el usuario de prueba? (s/n): ";
$cleanup = trim(fgets(STDIN));

if (strtolower($cleanup) === 's') {
    $deleteResult = $userManager->deleteUser($userId);
    if ($deleteResult['success']) {
        echo " Usuario de prueba eliminado\n";
    }
}

echo "\n Todos los tests completados!\n";
