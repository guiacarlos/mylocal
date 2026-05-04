#  Sistema de Autenticación Autónomo ACIDE

## Descripción

ACIDE ahora cuenta con un **sistema de autenticación completamente autónomo** que no depende de servicios externos para funcionar. Gestiona usuarios, roles y permisos de forma local y segura.

##  Características

-  **Autenticación Local**: No requiere conexión a internet
-  **Hashing Seguro**: Argon2id (el más seguro actualmente)
-  **Sistema de Roles**: 4 roles predefinidos con permisos granulares
-  **Gestión de Sesiones**: Tokens seguros con expiración automática
-  **CRUD Completo**: Crear, leer, actualizar y eliminar usuarios
-  **Protección de Datos**: Archivos protegidos con .htaccess
-  **Validación de Permisos**: Control de acceso basado en roles

##  Estructura

```
headless/acide/auth/
├── Auth.php              # Servicio principal de autenticación
├── UserManager.php       # Gestor CRUD de usuarios
├── RoleManager.php       # Gestor de roles y permisos
├── setup.php            # Script de inicialización
├── schemas/
│   ├── user.schema.json # Esquema de usuario
│   └── role.schema.json # Esquema de roles
└── data/
    └── roles.json       # Definición de roles

headless/data/
├── users/
│   ├── index.json       # Índice email -> userId
│   ├── {uuid}.json      # Archivos de usuario
│   └── .htaccess        # Protección HTTP
└── sessions/
    ├── {token}.json     # Sesiones activas
    └── .htaccess        # Protección HTTP
```

##  Inicio Rápido

### 1. Crear el Primer Usuario

Ejecuta el script de configuración desde la terminal:

```bash
cd headless/acide/auth
php setup.php
```

Sigue las instrucciones interactivas:

```
 ACIDE - Inicialización del Sistema
=====================================

 Crear nuevo usuario
---------------------
Email: admin@miempresa.com
Nombre completo: Administrador Principal
Contraseña (mín. 8 caracteres): ********

Roles disponibles:
  1. superadmin - Acceso total sin restricciones
  2. admin - Gestión completa de contenido y usuarios
  3. editor - Creación y edición de contenido
  4. viewer - Solo lectura

Selecciona rol (1-4): 1

 Usuario creado exitosamente!
```

### 2. Iniciar Sesión desde Marco CMS

El frontend ya está configurado para usar el nuevo sistema. Simplemente:

1. Abre Marco CMS en tu navegador
2. Usa las credenciales que creaste
3. ¡Listo!

##  Roles y Permisos

### SuperAdmin
-  Acceso total al sistema
-  Gestión de usuarios (crear, editar, eliminar)
-  Gestión de contenido completa
-  Configuración del sistema
-  Acceso a consola ACIDE
-  Gestión de plugins

### Admin
-  Crear y editar usuarios (no eliminar)
-  Gestión completa de contenido
-  Ver configuración (no editar)
-  Acceso a consola ACIDE
-  No puede gestionar plugins

### Editor
-  Crear y editar contenido
-  Ver usuarios
-  No puede publicar contenido
-  No puede gestionar usuarios
-  Sin acceso a consola

### Viewer
-  Solo lectura de contenido
-  Sin permisos de edición
-  Sin acceso a usuarios
-  Sin acceso a consola

##  Uso Programático

### Crear Usuario

```php
require_once 'auth/UserManager.php';

$userManager = new UserManager();

$result = $userManager->createUser(
    'usuario@ejemplo.com',
    'password123',
    'Nombre Usuario',
    'editor' // superadmin, admin, editor, viewer
);

if ($result['success']) {
    echo "Usuario creado: " . $result['user']['id'];
}
```

### Autenticar Usuario

```php
require_once 'auth/Auth.php';

$auth = new Auth();

$result = $auth->login('usuario@ejemplo.com', 'password123');

if ($result['success']) {
    $token = $result['token'];
    $user = $result['user'];
    // Guardar token en sesión o cookie
}
```

### Verificar Permisos

```php
require_once 'auth/RoleManager.php';

$roleManager = new RoleManager();

// Verificar si un rol puede crear contenido
$canCreate = $roleManager->hasPermission('editor', 'content', 'create');

if ($canCreate) {
    // Permitir acción
}
```

### Validar Token de Sesión

```php
require_once 'auth/Auth.php';

$auth = new Auth();

// Valida el token del header Authorization: Bearer {token}
$user = $auth->validateRequest();

if ($user) {
    echo "Usuario autenticado: " . $user['email'];
} else {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
}
```

##  Seguridad

### Hashing de Contraseñas
- **Algoritmo**: Argon2id (recomendado por OWASP 2024)
- **Automático**: PHP `password_hash()` y `password_verify()`
- **Resistente**: Contra ataques de fuerza bruta y rainbow tables

### Protección de Archivos
```apache
# .htaccess en /data/users/
Order Deny,Allow
Deny from all
```

### Tokens de Sesión
- **Generación**: `random_bytes(32)` (64 caracteres hex)
- **Expiración**: 30 minutos de inactividad
- **Renovación**: Automática en cada petición

### Validaciones
-  Email válido (formato RFC)
-  Contraseña mínima 8 caracteres
-  Sanitización HTML en nombres
-  Verificación de roles existentes

##  Gestión de Usuarios

### Listar Usuarios

```php
$users = $userManager->listUsers();

foreach ($users['users'] as $user) {
    echo "{$user['email']} - {$user['role']}\n";
}
```

### Actualizar Usuario

```php
$userManager->updateUser($userId, [
    'name' => 'Nuevo Nombre',
    'role' => 'admin',
    'status' => 'active' // active, inactive, suspended
]);
```

### Cambiar Contraseña

```php
$result = $userManager->changePassword($userId, 'nuevaPassword123');
```

### Eliminar Usuario

```php
$result = $userManager->deleteUser($userId);
```

##  Migración desde Sistema Anterior

Si tenías usuarios en `.vault/users/`, puedes migrarlos:

```php
// Script de migración (ejecutar una sola vez)
require_once 'auth/UserManager.php';

$userManager = new UserManager();
$oldUsers = json_decode(file_get_contents('../data/.vault/users/index.json'), true);

foreach ($oldUsers as $oldUser) {
    $userManager->createUser(
        $oldUser['email'],
        'temporal123', // Pedir que cambien contraseña
        $oldUser['name'] ?? $oldUser['email'],
        $oldUser['role'] ?? 'viewer'
    );
}
```

##  Troubleshooting

### "El email ya está registrado"
El usuario ya existe. Usa `getUserByEmail()` para verificar.

### "Contraseña incorrecta"
Verifica que estés usando la contraseña correcta. Los hashes son case-sensitive.

### "Sesión expirada"
Las sesiones expiran a los 30 minutos. El usuario debe volver a iniciar sesión.

### Permisos de archivo
Si hay errores al crear usuarios, verifica permisos:
```bash
chmod 700 headless/data/users
chmod 600 headless/data/users/*.json
```

##  Mejores Prácticas

1. **Nunca** almacenes contraseñas en texto plano
2. **Siempre** usa HTTPS en producción
3. **Implementa** rate limiting para prevenir brute force
4. **Registra** intentos de login fallidos para auditoría
5. **Renueva** tokens de sesión periódicamente
6. **Usa** roles con el principio de menor privilegio
7. **Habilita** autenticación de dos factores (2FA) si es posible

##  Notas

- El sistema es **completamente autónomo** y no requiere conexión externa
- Los datos se almacenan en **archivos JSON** (fácil de migrar a DB si crece)
- Compatible con **PHP 7.2+** (Argon2id requiere PHP 7.2+)
- **Escalable**: Puede manejar cientos de usuarios sin problemas
- **Portable**: Funciona en cualquier servidor con PHP

##  Próximas Mejoras

- [ ] Autenticación de dos factores (2FA)
- [ ] Recuperación de contraseña por email
- [ ] Logs de auditoría detallados
- [ ] Rate limiting integrado
- [ ] Migración opcional a base de datos SQL
- [ ] API REST para gestión de usuarios
- [ ] Integración con OAuth2 (Google, GitHub)

---

**¿Necesitas ayuda?** Consulta el código fuente en `headless/acide/auth/` o ejecuta `php setup.php` para crear usuarios.
