<?php
/**
 * create_superadmin.php
 * Script para crear el usuario superadmin del sistema
 */

require_once __DIR__ . '/UserManager.php';

echo "ðŸ” Creando usuario SuperAdmin del sistema...\n";
echo "==========================================\n\n";

$userManager = new UserManager();

// Datos del superadmin
$email = 'info@gestasai.com';
$password = 'PACOjuan@2025#';
$name = 'GestasAI SuperAdmin';
$role = 'superadmin';

// Verificar si ya existe
$existingUser = $userManager->getUserByEmail($email);

if ($existingUser) {
    echo "âš ï¸  El usuario ya existe.\n";
    echo "   ID: {$existingUser['id']}\n";
    echo "   Email: {$existingUser['email']}\n";
    echo "   Rol: {$existingUser['role']}\n\n";

    echo "Â¿Deseas actualizar la contraseÃ±a? (s/n): ";
    $update = trim(fgets(STDIN));

    if (strtolower($update) === 's') {
        $result = $userManager->changePassword($existingUser['id'], $password);

        if ($result['success']) {
            echo "\nâœ… ContraseÃ±a actualizada exitosamente!\n";
        } else {
            echo "\nâŒ Error al actualizar contraseÃ±a: {$result['error']}\n";
            exit(1);
        }
    } else {
        echo "\nOperaciÃ³n cancelada.\n";
        exit(0);
    }
} else {
    // Crear nuevo usuario
    $result = $userManager->createUser($email, $password, $name, $role);

    if ($result['success']) {
        echo "âœ… Usuario SuperAdmin creado exitosamente!\n\n";
        echo "Detalles:\n";
        echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n";
        echo "  ID:       {$result['user']['id']}\n";
        echo "  Email:    {$result['user']['email']}\n";
        echo "  Nombre:   {$result['user']['name']}\n";
        echo "  Rol:      {$result['user']['role']}\n";
        echo "  Estado:   {$result['user']['status']}\n";
        echo "  Creado:   {$result['user']['created_at']}\n";
        echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n\n";

        echo "ðŸŽ‰ Ya puedes iniciar sesiÃ³n en Marco CMS con:\n";
        echo "   Email: $email\n";
        echo "   Password: [la contraseÃ±a que configuraste]\n\n";
    } else {
        echo "âŒ Error al crear usuario: {$result['error']}\n";
        exit(1);
    }
}

echo "âœ¨ Proceso completado.\n";
