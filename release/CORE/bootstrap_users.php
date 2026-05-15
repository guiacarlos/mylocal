<?php
/**
 * Bootstrap de usuarios por defecto para SOCOLA.
 * Se ejecuta durante build.php — solo crea usuarios si no existen.
 * Usa directamente el vault de usuarios (no requiere sesión autenticada).
 */
function bootstrapDefaultUsers() {
    $storageBase = defined('GLOBAL_STORAGE') ? GLOBAL_STORAGE : (defined('DATA_ROOT') ? DATA_ROOT : __DIR__ . '/../STORAGE');
    $usersDir = $storageBase . '/.vault/users';
    $indexFile = $usersDir . '/index.json';

    if (!is_dir($usersDir)) {
        mkdir($usersDir, 0700, true);
    }

    $index = file_exists($indexFile)
        ? json_decode(file_get_contents($indexFile), true) ?: []
        : [];

    $defaultUsers = [
        [
            'email' => 'socola@socola.es',
            'password' => 'socola2026',
            'name' => 'Socola Admin',
            'role' => 'admin'
        ],
        [
            'email' => 'sala@socola.es',
            'password' => 'socola2026',
            'name' => 'Editor Sala',
            'role' => 'sala'
        ],
        [
            'email' => 'cocina@socola.es',
            'password' => 'socola2026',
            'name' => 'Editor Cocina',
            'role' => 'cocina'
        ],
        [
            'email' => 'camarero@socola.es',
            'password' => 'socola2026',
            'name' => 'Editor Camarero',
            'role' => 'camarero'
        ]
    ];

    $created = 0;

    foreach ($defaultUsers as $userData) {
        $emailKey = strtolower(trim($userData['email']));

        // Si el usuario ya existe, saltamos
        if (isset($index[$emailKey])) {
            if (PHP_SAPI === 'cli') echo "   [USERS] ⏩ {$userData['email']} ya existe\n";
            continue;
        }

        // Generar UUID v4
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $user = [
            'id' => $uuid,
            'email' => $emailKey,
            'password_hash' => password_hash($userData['password'], PASSWORD_ARGON2ID),
            'name' => $userData['name'],
            'role' => $userData['role'],
            'status' => 'active',
            'metadata' => new \stdClass(),
            'created_at' => date('c'),
            'updated_at' => date('c'),
            'last_login' => null
        ];

        // Guardar archivo de usuario
        $userFile = $usersDir . '/' . $uuid . '.json';
        file_put_contents($userFile, json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Actualizar índice
        $index[$emailKey] = $uuid;

        if (PHP_SAPI === 'cli') echo "   [USERS] ✅ Creado: {$userData['email']} ({$userData['role']})\n";
        $created++;
    }

    // Guardar índice actualizado
    file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $created;
}
