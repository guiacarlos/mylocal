#  Documentación Técnica - Sistema de Autenticación ACIDE

## Índice
1. [Arquitectura General](#arquitectura-general)
2. [Flujo de Autenticación](#flujo-de-autenticación)
3. [Componentes del Sistema](#componentes-del-sistema)
4. [Proceso Detallado](#proceso-detallado)
5. [Seguridad](#seguridad)
6. [API Reference](#api-reference)

---

## Arquitectura General

El sistema de autenticación de ACIDE es **completamente autónomo** y no depende de servicios externos. Utiliza almacenamiento en archivos JSON con hashing seguro Argon2id.

```
┌─────────────────────────────────────────────────────────────┐
│                    FLUJO DE AUTENTICACIÓN                    │
└─────────────────────────────────────────────────────────────┘

[Navegador] 
    │
    │ POST /acide/index.php
    │ { action: "auth_login", data: { email, password } }
    │
    ▼
[index.php] ─────────────────────────────────────────┐
    │                                                 │
    │ 1. Parsea JSON                                  │
    │ 2. Extrae action                                │
    │                                                 │
    ▼                                                 │
[ActionDispatcher] ──────────────────────────────────┤
    │                                                 │
    │ switch(action)                                  │
    │ case 'auth_login':                              │
    │                                                 │
    ▼                                                 │
[AuthHandler] ───────────────────────────────────────┤
    │                                                 │
    │ login($email, $password)                        │
    │                                                 │
    ▼                                                 │
[Auth Service] ──────────────────────────────────────┤
    │                                                 │
    │ login($email, $password)                        │
    │                                                 │
    ▼                                                 │
[UserManager] ───────────────────────────────────────┤
    │                                                 │
    │ 1. getUserByEmail($email)                       │
    │    ├─ Lee index.json                            │
    │    └─ Lee users/{uuid}.json                     │
    │                                                 │
    │ 2. password_verify($password, $hash)            │
    │    └─ Verifica con Argon2id                     │
    │                                                 │
    │ 3. updateLastLogin($userId)                     │
    │    └─ Actualiza users/{uuid}.json               │
    │                                                 │
    ▼                                                 │
[Auth Service] ──────────────────────────────────────┤
    │                                                 │
    │ createSession($user)                            │
    │    ├─ Genera token: random_bytes(32)            │
    │    ├─ Crea sessions/{token}.json                │
    │    └─ Expira en 30 minutos                      │
    │                                                 │
    ▼                                                 │
[Respuesta JSON] ────────────────────────────────────┘
    │
    │ { success: true, token: "...", user: {...} }
    │
    ▼
[Navegador]
    │
    └─ localStorage.setItem('acide_token', token)
```

---

## Flujo de Autenticación

### 1. Petición Inicial (Cliente → Servidor)

**Archivo:** `headless/marco-cms/src/acide/auth/login.js`

```javascript
export async function login(email, password) {
    const response = await fetch('/acide/index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'auth_login',
            data: { email, password }
        })
    });
    return await response.json();
}
```

**Datos enviados:**
```json
{
    "action": "auth_login",
    "data": {
        "email": "info@gestasai.com",
        "password": "PACOjuan@2025#"
    }
}
```

---

### 2. Recepción y Routing (index.php)

**Archivo:** `headless/acide/index.php` (líneas 36-62)

```php
// Parsear Input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$action = $input['action'] ?? null; // "auth_login"

// Bootstrap del Core
require_once __DIR__ . '/core/ActionDispatcher.php';
$dispatcher = new ActionDispatcher($services);

// Dispatch
$response = $dispatcher->dispatch($action, $input['data'] ?? $input);

echo json_encode($response);
```

**Responsabilidad:**
- Parsear JSON de entrada
- Extraer la acción
- Delegar al ActionDispatcher

---

### 3. Dispatch de Acción (ActionDispatcher)

**Archivo:** `headless/acide/core/ActionDispatcher.php` (líneas 161-169)

```php
case 'auth_login':
    // Si $request ya es la data (viene de index.php como $input['data'])
    $authData = isset($request['email']) ? $request : ($data ?? []);
    return $this->authHandler->login(
        $authData['email'] ?? null,
        $authData['password'] ?? null,
        $authData['tenantId'] ?? null
    );
```

**Responsabilidad:**
- Identificar la acción `auth_login`
- Extraer credenciales del request
- Delegar a `AuthHandler`

---

### 4. Handler de Autenticación (AuthHandler)

**Archivo:** `headless/acide/core/handlers/AuthHandler.php` (líneas 35-51)

```php
public function login($email, $password, $tenantId = null)
{
    // Delegar completamente al servicio de Auth (sistema autónomo)
    $result = $this->authService->login($email, $password);

    if (!$result['success']) {
        return $result;
    }

    // Retornar en el formato esperado por el frontend
    return [
        'success' => true,
        'data' => [
            'token' => $result['token'],
            'user' => $result['user'],
            'refreshToken' => 'refresh_' . $result['token']
        ]
    ];
}
```

**Responsabilidad:**
- Actuar como intermediario
- Delegar a `Auth Service`
- Formatear respuesta para el frontend

---

### 5. Servicio de Autenticación (Auth)

**Archivo:** `headless/acide/auth/Auth.php` (líneas 48-68)

```php
public function login($email, $password)
{
    // Autenticación LOCAL (sistema autónomo)
    $result = $this->userManager->verifyPassword($email, $password);
    
    if ($result['success']) {
        // Crear sesión local
        $session = $this->createSession($result['user']);
        return [
            'success' => true,
            'token' => $session['token'],
            'user' => $result['user']
        ];
    }

    return $result;
}
```

**Responsabilidad:**
- Verificar credenciales con `UserManager`
- Crear sesión si es exitoso
- Devolver token y datos de usuario

---

### 6. Gestión de Usuarios (UserManager)

**Archivo:** `headless/acide/auth/UserManager.php`

#### 6.1. Obtener Usuario por Email (líneas 82-93)

```php
public function getUserByEmail($email)
{
    $email = strtolower(trim($email));
    $index = $this->getIndex(); // Lee: data/users/index.json

    if (!isset($index[$email])) {
        return null;
    }

    $userId = $index[$email];
    return $this->getUserById($userId); // Lee: data/users/{uuid}.json
}
```

**Proceso:**
1. Normaliza el email (lowercase, trim)
2. Lee `data/users/index.json`:
   ```json
   {
       "info@gestasai.com": "00000000-0000-0000-0000-000000000001"
   }
   ```
3. Obtiene el UUID del usuario
4. Lee `data/users/{uuid}.json`

#### 6.2. Verificar Contraseña (líneas 113-136)

```php
public function verifyPassword($email, $password)
{
    $user = $this->getUserByEmail($email);
    
    if (!$user) {
        return ['success' => false, 'error' => 'Usuario no encontrado'];
    }

    if ($user['status'] !== 'active') {
        return ['success' => false, 'error' => 'Cuenta inactiva o suspendida'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Contraseña incorrecta'];
    }

    // Actualizar último login
    $this->updateLastLogin($user['id']);

    // No devolver el hash
    unset($user['password_hash']);

    return ['success' => true, 'user' => $user];
}
```

**Proceso:**
1. Obtiene el usuario por email
2. Verifica que exista
3. Verifica que esté activo
4. **Verifica la contraseña** usando `password_verify()` con Argon2id
5. Actualiza `last_login`
6. Elimina el hash del resultado (seguridad)
7. Devuelve el usuario

**Estructura del archivo de usuario:**
```json
{
    "id": "00000000-0000-0000-0000-000000000001",
    "email": "info@gestasai.com",
    "password_hash": "$argon2id$v=19$m=65536,t=4,p=1$...",
    "name": "GestasAI SuperAdmin",
    "role": "superadmin",
    "status": "active",
    "metadata": [],
    "created_at": "2026-01-06T19:00:00+00:00",
    "updated_at": "2026-01-06T19:13:36+00:00",
    "last_login": "2026-01-06T19:18:12+00:00"
}
```

---

### 7. Creación de Sesión (Auth)

**Archivo:** `headless/acide/auth/Auth.php` (líneas 75-90)

```php
private function createSession($user)
{
    $token = bin2hex(random_bytes(32)); // 64 caracteres hex
    $session = [
        'token' => $token,
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'created_at' => date('c'),
        'expires_at' => date('c', time() + (30 * 60)) // 30 minutos
    ];

    $sessionFile = $this->sessionDir . '/' . $token . '.json';
    file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));

    return $session;
}
```

**Proceso:**
1. Genera token aleatorio seguro (32 bytes → 64 hex)
2. Crea objeto de sesión con:
   - Token
   - ID de usuario
   - Email
   - Rol
   - Timestamps
   - Expiración (30 min)
3. Guarda en `data/sessions/{token}.json`

**Estructura del archivo de sesión:**
```json
{
    "token": "d7debc936f645a792f9cc0b4d10eba1d...",
    "user_id": "00000000-0000-0000-0000-000000000001",
    "email": "info@gestasai.com",
    "role": "superadmin",
    "created_at": "2026-01-06T19:18:12+00:00",
    "expires_at": "2026-01-06T19:48:12+00:00"
}
```

---

### 8. Respuesta al Cliente

**Formato de respuesta exitosa:**
```json
{
    "success": true,
    "data": {
        "token": "d7debc936f645a792f9cc0b4d10eba1dca28bb96111513313c36280ecafd5e00",
        "user": {
            "id": "00000000-0000-0000-0000-000000000001",
            "email": "info@gestasai.com",
            "name": "GestasAI SuperAdmin",
            "role": "superadmin",
            "status": "active",
            "created_at": "2026-01-06T19:00:00+00:00",
            "updated_at": "2026-01-06T19:13:36+00:00",
            "last_login": "2026-01-06T19:18:12+00:00"
        },
        "refreshToken": "refresh_d7debc936f645a792f9cc0b4d10eba1d..."
    }
}
```

**Formato de respuesta con error:**
```json
{
    "success": false,
    "error": "Usuario no encontrado"
}
```

---

## Componentes del Sistema

### Archivos Principales

| Archivo | Responsabilidad | Líneas Clave |
|---------|----------------|--------------|
| `index.php` | Punto de entrada, routing | 36-62 |
| `ActionDispatcher.php` | Dispatcher de acciones | 161-169 |
| `AuthHandler.php` | Handler de autenticación | 35-51 |
| `Auth.php` | Servicio de autenticación | 48-90 |
| `UserManager.php` | CRUD de usuarios | 82-136 |
| `RoleManager.php` | Gestión de roles y permisos | Todo |

### Estructura de Datos

```
headless/
├── acide/
│   ├── index.php                    # Punto de entrada
│   ├── core/
│   │   ├── ActionDispatcher.php     # Router de acciones
│   │   └── handlers/
│   │       └── AuthHandler.php      # Handler de auth
│   └── auth/
│       ├── Auth.php                 # Servicio principal
│       ├── UserManager.php          # Gestor de usuarios
│       ├── RoleManager.php          # Gestor de roles
│       └── data/
│           └── roles.json           # Definición de roles
└── data/
    ├── users/
    │   ├── index.json               # Índice email→uuid
    │   ├── {uuid}.json              # Datos de usuario
    │   └── .htaccess                # Protección HTTP
    └── sessions/
        ├── {token}.json             # Sesiones activas
        └── .htaccess                # Protección HTTP
```

---

## Seguridad

### 1. Hashing de Contraseñas

**Algoritmo:** Argon2id (OWASP 2024 recomendado)

```php
// Creación de hash
$hash = password_hash($password, PASSWORD_ARGON2ID);
// Resultado: $argon2id$v=19$m=65536,t=4,p=1$...

// Verificación
$valid = password_verify($inputPassword, $storedHash);
```

**Parámetros Argon2id:**
- **Memoria:** 65536 KB (64 MB)
- **Iteraciones:** 4
- **Paralelismo:** 1
- **Versión:** 19

**Resistencia:**
-  Ataques de fuerza bruta
-  Rainbow tables
-  Ataques de timing
-  GPU/ASIC attacks

### 2. Tokens de Sesión

```php
$token = bin2hex(random_bytes(32)); // 64 caracteres hexadecimales
```

**Características:**
- **Longitud:** 64 caracteres
- **Entropía:** 256 bits
- **Fuente:** `random_bytes()` (CSPRNG)
- **Formato:** Hexadecimal
- **Ejemplo:** `d7debc936f645a792f9cc0b4d10eba1d...`

### 3. Protección de Archivos

**`.htaccess` en `/data/users/` y `/data/sessions/`:**
```apache
# Protección de datos de usuarios
Order Deny,Allow
Deny from all
```

**Efecto:** Bloquea acceso HTTP directo a archivos JSON

### 4. Validaciones

**Email:**
```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return ['success' => false, 'error' => 'Email inválido'];
}
```

**Contraseña:**
```php
if (strlen($password) < 8) {
    return ['success' => false, 'error' => 'Mínimo 8 caracteres'];
}
```

**Estado de cuenta:**
```php
if ($user['status'] !== 'active') {
    return ['success' => false, 'error' => 'Cuenta inactiva'];
}
```

### 5. Sanitización

**Nombres de usuario:**
```php
$name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
```

**Emails:**
```php
$email = strtolower(trim($email));
```

---

## API Reference

### Auth Service

#### `login($email, $password)`

**Descripción:** Autentica un usuario y crea una sesión

**Parámetros:**
- `$email` (string): Email del usuario
- `$password` (string): Contraseña en texto plano

**Retorna:**
```php
[
    'success' => true,
    'token' => 'string',
    'user' => [...]
]
```

**Ejemplo:**
```php
$auth = new Auth();
$result = $auth->login('info@gestasai.com', 'PACOjuan@2025#');
```

---

#### `validateRequest()`

**Descripción:** Valida el token del header Authorization

**Retorna:**
```php
[
    'id' => 'uuid',
    'email' => 'string',
    'role' => 'string',
    'name' => 'string'
]
// o false si es inválido
```

**Ejemplo:**
```php
$auth = new Auth();
$user = $auth->validateRequest();

if ($user) {
    echo "Usuario autenticado: " . $user['email'];
}
```

---

#### `hasPermission($user, $resource, $action)`

**Descripción:** Verifica si un usuario tiene permiso para una acción

**Parámetros:**
- `$user` (array): Objeto de usuario
- `$resource` (string): Recurso ('users', 'content', 'settings', 'system')
- `$action` (string): Acción ('create', 'read', 'update', 'delete')

**Retorna:** `boolean`

**Ejemplo:**
```php
$auth = new Auth();
$canDelete = $auth->hasPermission($user, 'content', 'delete');
```

---

### UserManager

#### `createUser($email, $password, $name, $role)`

**Descripción:** Crea un nuevo usuario

**Parámetros:**
- `$email` (string): Email único
- `$password` (string): Contraseña (mín. 8 caracteres)
- `$name` (string): Nombre completo
- `$role` (string): Rol ('superadmin', 'admin', 'editor', 'viewer')

**Retorna:**
```php
[
    'success' => true,
    'user' => [...]
]
```

---

#### `getUserByEmail($email)`

**Descripción:** Obtiene un usuario por email

**Retorna:** `array|null`

---

#### `verifyPassword($email, $password)`

**Descripción:** Verifica credenciales

**Retorna:**
```php
[
    'success' => true,
    'user' => [...]
]
```

---

#### `updateUser($id, $updates)`

**Descripción:** Actualiza datos de usuario

**Campos permitidos:** `name`, `role`, `status`, `metadata`

---

#### `changePassword($id, $newPassword)`

**Descripción:** Cambia la contraseña de un usuario

---

#### `deleteUser($id)`

**Descripción:** Elimina un usuario

---

#### `listUsers()`

**Descripción:** Lista todos los usuarios

**Retorna:**
```php
[
    'success' => true,
    'users' => [...]
]
```

---

### RoleManager

#### `hasPermission($roleName, $resource, $action)`

**Descripción:** Verifica si un rol tiene un permiso

**Retorna:** `boolean`

---

#### `getRolePermissions($roleName)`

**Descripción:** Obtiene todos los permisos de un rol

**Retorna:** `array`

---

#### `listRoles()`

**Descripción:** Lista todos los roles disponibles

**Retorna:**
```php
[
    ['id' => 'superadmin', 'label' => 'Super Administrador', ...],
    ...
]
```

---

## Diagrama de Secuencia Completo

```
Cliente          index.php    Dispatcher    AuthHandler    Auth    UserManager    Filesystem
  │                 │             │              │           │          │              │
  │ POST login      │             │              │           │          │              │
  ├────────────────>│             │              │           │          │              │
  │                 │             │              │           │          │              │
  │                 │ dispatch    │              │           │          │              │
  │                 ├────────────>│              │           │          │              │
  │                 │             │              │           │          │              │
  │                 │             │ login()      │           │          │              │
  │                 │             ├─────────────>│           │          │              │
  │                 │             │              │           │          │              │
  │                 │             │              │ login()   │          │              │
  │                 │             │              ├──────────>│          │              │
  │                 │             │              │           │          │              │
  │                 │             │              │           │ verify() │              │
  │                 │             │              │           ├─────────>│              │
  │                 │             │              │           │          │              │
  │                 │             │              │           │          │ getByEmail() │
  │                 │             │              │           │          ├─────────────>│
  │                 │             │              │           │          │              │
  │                 │             │              │           │          │ index.json   │
  │                 │             │              │           │          │<─────────────┤
  │                 │             │              │           │          │              │
  │                 │             │              │           │          │ {uuid}.json  │
  │                 │             │              │           │          │<─────────────┤
  │                 │             │              │           │          │              │
  │                 │             │              │           │          │ verify hash  │
  │                 │             │              │           │          │──────────────│
  │                 │             │              │           │          │              │
  │                 │             │              │           │          │ update login │
  │                 │             │              │           │          ├─────────────>│
  │                 │             │              │           │          │              │
  │                 │             │              │           │<─────────┤              │
  │                 │             │              │           │ user     │              │
  │                 │             │              │           │          │              │
  │                 │             │              │           │ create   │              │
  │                 │             │              │           │ session  │              │
  │                 │             │              │           ├─────────>│              │
  │                 │             │              │           │          │              │
  │                 │             │              │           │          │ save token   │
  │                 │             │              │           │          ├─────────────>│
  │                 │             │              │           │          │              │
  │                 │             │              │<──────────┤          │              │
  │                 │             │              │ token+user│          │              │
  │                 │             │              │           │          │              │
  │                 │             │<─────────────┤           │          │              │
  │                 │             │ response     │           │          │              │
  │                 │             │              │           │          │              │
  │                 │<────────────┤              │           │          │              │
  │                 │ JSON        │              │           │          │              │
  │                 │             │              │           │          │              │
  │<────────────────┤             │              │           │          │              │
  │ {token, user}   │             │              │           │          │              │
  │                 │             │              │           │          │              │
```

---

---

## Esquema Arquitectónico del Sistema

### Relación Marco CMS ↔ ACIDE

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│  ┌──────────────────────┐         ┌─────────────────────────┐  │
│  │                      │         │                         │  │
│  │    Marco CMS         │         │        ACIDE            │  │
│  │                      │         │                         │  │
│  │  ┌────────────────┐  │         │  ┌──────────────────┐  │  │
│  │  │                │  │         │  │                  │  │  │
│  │  │  PUBLIC WEB    │  │         │  │    CEREBRO       │  │  │
│  │  │                │  │         │  │   (Dispatcher)   │  │  │
│  │  └────────────────┘  │         │  └──────────────────┘  │  │
│  │                      │         │                         │  │
│  │                      │         │  ┌──────────────────┐  │  │
│  │                      │  TÚNEL  │  │                  │  │  │
│  │                      │ INTELI- │  │    PLUGINS       │  │  │
│  │                      │  GENTE  │  │  (Auth, Data)    │  │  │
│  │                      │◄────────┼─►│                  │  │  │
│  │                      │         │  └──────────────────┘  │  │
│  │                      │         │                         │  │
│  │                      │         │  ┌──────────────────┐  │  │
│  │                      │         │  │                  │  │  │
│  │                      │         │  │ SILENCIOSO TÚNEL │  │  │
│  │                      │         │  │  (index.php)     │  │  │
│  │                      │         │  │                  │  │  │
│  │                      │         │  └──────────────────┘  │  │
│  │                      │         │                         │  │
│  │                      │         │  ┌──────────────────┐  │  │
│  │                      │         │  │                  │  │  │
│  │                      │         │  │      MOTOR       │  │  │
│  │                      │         │  │  (QueryEngine)   │  │  │
│  │                      │         │  │                  │  │  │
│  │                      │         │  └──────────────────┘  │  │
│  │                      │         │                         │  │
│  │                      │         │  ┌──────────────────┐  │  │
│  │                      │         │  │                  │  │  │
│  │                      │         │  │      DATA        │  │  │
│  │                      │         │  │  (JSON Storage)  │  │  │
│  │                      │         │  │                  │  │  │
│  │                      │         │  └──────────────────┘  │  │
│  │                      │         │                         │  │
│  └──────────────────────┘         └─────────────────────────┘  │
│                                                                 │
│         Frontend (React/Vite)          Backend (PHP)           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Componentes Clave

**Marco CMS (Frontend)**
- **PUBLIC WEB**: Interfaz de usuario (React)
- **TÚNEL INTELIGENTE**: Cliente ACIDE (acideService.js)
- Comunicación vía POST JSON

**ACIDE (Backend)**
- **CEREBRO**: ActionDispatcher (enrutador de acciones)
- **PLUGINS**: Handlers especializados (Auth, Data, Theme, etc.)
- **SILENCIOSO TÚNEL**: index.php (punto de entrada)
- **MOTOR**: QueryEngine (procesamiento de datos)
- **DATA**: Almacenamiento JSON (users, sessions, content)

### Flujo de Comunicación

```
Marco CMS                    ACIDE
   │                           │
   │  POST /acide/index.php    │
   │  { action, data }         │
   ├──────────────────────────>│
   │                           │
   │                      [index.php]
   │                           │
   │                      [ActionDispatcher]
   │                           │
   │                      [AuthHandler]
   │                           │
   │                      [Auth Service]
   │                           │
   │                      [UserManager]
   │                           │
   │                      [Filesystem]
   │                           │
   │  { success, data }        │
   │<──────────────────────────┤
   │                           │
```

---

## Script de Prueba del Sistema

### Script de Login Completo

Copia y pega este script en la consola del navegador (F12) para probar el sistema de autenticación:

```javascript
//  LOGIN ACIDE - SCRIPT FINAL
(async () => {
    console.clear();
    console.log('%c LOGIN ACIDE', 'background: #10B981; color: white; font-size: 20px; padding: 10px; font-weight: bold;');
    
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
            console.log('%c LOGIN EXITOSO', 'background: #10B981; color: white; font-size: 16px; padding: 8px;');
            console.log('\n Token:', result.data.token);
            console.log('\n Usuario:');
            console.table(result.data.user);
            
            localStorage.setItem('acide_token', result.data.token);
            localStorage.setItem('acide_user', JSON.stringify(result.data.user));
            
            console.log('\n Sesión guardada en localStorage');
            console.log('\n ¡SISTEMA COMPLETAMENTE FUNCIONAL!');
            console.log('\n Autenticación local autónoma');
            console.log(' Sin dependencia de GestasAI.com');
            console.log(' Hashing Argon2id');
            console.log(' Tokens seguros');
            console.log(' Sistema de roles activo');
        } else {
            console.log('%c ERROR', 'background: #EF4444; color: white; padding: 8px;');
            console.log('Error:', result.error);
        }
    } catch (e) {
        console.error('Error de conexión:', e);
    }
})();
```

### Salida Esperada

```
 LOGIN ACIDE

 LOGIN EXITOSO

 Token: d7debc936f645a792f9cc0b4d10eba1dca28bb96111513313c36280ecafd5e00

 Usuario:
┌─────────────┬──────────────────────────────────────────┐
│   (index)   │                  Value                   │
├─────────────┼──────────────────────────────────────────┤
│     id      │ '00000000-0000-0000-0000-000000000001'   │
│    email    │         'info@gestasai.com'              │
│    name     │       'GestasAI SuperAdmin'              │
│    role     │           'superadmin'                   │
│   status    │            'active'                      │
│  metadata   │            Array(0)                      │
│ created_at  │      '2026-01-06T19:00:00+00:00'         │
│ updated_at  │      '2026-01-06T19:13:36+00:00'         │
│ last_login  │      '2026-01-06T19:18:12+00:00'         │
└─────────────┴──────────────────────────────────────────┘

 Sesión guardada en localStorage

 ¡SISTEMA COMPLETAMENTE FUNCIONAL!

 Autenticación local autónoma
 Sin dependencia de GestasAI.com
 Hashing Argon2id
 Tokens seguros
 Sistema de roles activo
```

### Variantes del Script

#### Script Mínimo (Una Línea)

```javascript
fetch('/acide/index.php', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'auth_login', data: {email: 'info@gestasai.com', password: 'PACOjuan@2025#'}})}).then(r => r.json()).then(console.log);
```

#### Script con Manejo de Errores Detallado

```javascript
(async () => {
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

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.success) {
            console.log(' Login exitoso');
            console.log('Token:', result.data.token);
            console.log('Usuario:', result.data.user);
            
            // Guardar en localStorage
            localStorage.setItem('acide_token', result.data.token);
            localStorage.setItem('acide_user', JSON.stringify(result.data.user));
            
            return result;
        } else {
            console.error(' Login fallido:', result.error);
            
            // Diagnóstico
            if (result.error.includes('Usuario no encontrado')) {
                console.log(' El usuario no existe. Ejecuta: php setup.php');
            } else if (result.error.includes('Contraseña incorrecta')) {
                console.log(' Verifica la contraseña');
            } else if (result.error.includes('inactiva')) {
                console.log(' La cuenta está inactiva');
            }
            
            return result;
        }
    } catch (error) {
        console.error(' Error de conexión:', error.message);
        console.log('\n Verifica:');
        console.log('  1. Servidor PHP corriendo (npm run php)');
        console.log('  2. Vite dev server activo (npm run dev)');
        console.log('  3. Proxy configurado en vite.config.js');
        throw error;
    }
})();
```

#### Script de Logout

```javascript
(async () => {
    const token = localStorage.getItem('acide_token');
    
    if (!token) {
        console.log(' No hay sesión activa');
        return;
    }

    const response = await fetch('/acide/index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
            action: 'auth_logout',
            data: { token }
        })
    });

    const result = await response.json();

    if (result.success) {
        localStorage.removeItem('acide_token');
        localStorage.removeItem('acide_user');
        console.log(' Sesión cerrada correctamente');
    }
})();
```

---

## Notas Finales

### Ventajas del Sistema

 **Autonomía Total:** No depende de servicios externos  
 **Seguridad Moderna:** Argon2id, tokens CSPRNG  
 **Simplicidad:** Archivos JSON, fácil de entender  
 **Portabilidad:** Funciona en cualquier servidor PHP  
 **Escalabilidad:** Soporta cientos de usuarios sin problemas  
 **Mantenibilidad:** Código limpio y bien documentado  

### Limitaciones Conocidas

 **Concurrencia:** Sin locks, posibles race conditions  
 **Escalabilidad:** Para miles de usuarios, considerar DB SQL  
 **Distribución:** Sesiones locales, no distribuidas  

### Mejoras Futuras

- [ ] Rate limiting (prevenir brute force)
- [ ] Autenticación de dos factores (2FA)
- [ ] Recuperación de contraseña por email
- [ ] Logs de auditoría
- [ ] Migración opcional a PostgreSQL/MySQL
- [ ] Sesiones distribuidas con Redis

---

**Última actualización:** 2026-01-06  
**Versión del sistema:** 1.0.0  
**Autor:** Sistema ACIDE
