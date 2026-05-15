# 📚 Índice de Documentación - Sistema de Autenticación ACIDE

## Documentos Disponibles

### 1. **README.md** - Guía de Usuario
- Inicio rápido
- Instalación
- Uso básico
- Ejemplos de código
- Troubleshooting

### 2. **AUTHENTICATION_FLOW.md** - Documentación Técnica
- Arquitectura completa
- Flujo detallado paso a paso
- Componentes del sistema
- Seguridad
- API Reference
- Diagramas de secuencia

### 3. **IMPLEMENTATION_SUMMARY.md** - Resumen Ejecutivo
- Visión general
- Características implementadas
- Comparación antes/después
- Próximos pasos

---

## 🚀 Inicio Rápido

### Para Usuarios

1. **Crear primer usuario:**
   ```bash
   cd headless/acide/auth
   php setup.php
   ```

2. **Hacer login:**
   - Email: `info@gestasai.com`
   - Password: `PACOjuan@2025#`

### Para Desarrolladores

1. **Leer arquitectura:**
   - Ver `AUTHENTICATION_FLOW.md` sección "Arquitectura General"

2. **Entender el flujo:**
   - Ver `AUTHENTICATION_FLOW.md` sección "Flujo de Autenticación"

3. **API Reference:**
   - Ver `AUTHENTICATION_FLOW.md` sección "API Reference"

---

## 📂 Estructura de Archivos

```
headless/acide/auth/
├── 📄 README.md                      # Guía de usuario
├── 📄 AUTHENTICATION_FLOW.md         # Documentación técnica
├── 📄 IMPLEMENTATION_SUMMARY.md      # Resumen ejecutivo
├── 📄 INDEX.md                       # Este archivo
│
├── 📄 Auth.php                       # Servicio principal
├── 📄 UserManager.php                # Gestor de usuarios
├── 📄 RoleManager.php                # Gestor de roles
│
├── 📄 setup.php                      # Script de inicialización
├── 📄 test.php                       # Suite de pruebas
├── 📄 init_system.php                # Inicialización automática
│
├── schemas/
│   ├── user.schema.json              # Esquema de usuario
│   └── role.schema.json              # Esquema de roles
│
└── data/
    └── roles.json                    # Definición de roles
```

---

## 🎯 Casos de Uso Comunes

### 1. Crear un Usuario

```php
require_once 'UserManager.php';
$um = new UserManager();

$result = $um->createUser(
    'usuario@ejemplo.com',
    'password123',
    'Nombre Usuario',
    'editor'
);
```

### 2. Autenticar Usuario

```php
require_once 'Auth.php';
$auth = new Auth();

$result = $auth->login('usuario@ejemplo.com', 'password123');

if ($result['success']) {
    $token = $result['token'];
    $user = $result['user'];
}
```

### 3. Verificar Permisos

```php
require_once 'RoleManager.php';
$rm = new RoleManager();

$canCreate = $rm->hasPermission('editor', 'content', 'create');
```

### 4. Validar Token

```php
require_once 'Auth.php';
$auth = new Auth();

$user = $auth->validateRequest();

if ($user) {
    // Usuario autenticado
}
```

---

## 🔐 Seguridad

### Características Implementadas

✅ **Argon2id** - Hashing de contraseñas  
✅ **random_bytes()** - Tokens criptográficamente seguros  
✅ **Expiración** - Sesiones de 30 minutos  
✅ **.htaccess** - Protección de archivos  
✅ **Validaciones** - Email, contraseña, estado  
✅ **Sanitización** - htmlspecialchars, trim  

### Mejores Prácticas

1. **Nunca** almacenar contraseñas en texto plano
2. **Siempre** usar HTTPS en producción
3. **Implementar** rate limiting
4. **Registrar** intentos fallidos
5. **Renovar** tokens periódicamente

---

## 📊 Roles del Sistema

| Rol | Permisos | Uso Recomendado |
|-----|----------|-----------------|
| **superadmin** | Acceso total | Propietario del sistema |
| **admin** | Gestión completa | Administradores |
| **editor** | Crear/editar contenido | Creadores de contenido |
| **viewer** | Solo lectura | Clientes, visitantes |

---

## 🛠️ Herramientas

### Scripts Disponibles

| Script | Descripción | Uso |
|--------|-------------|-----|
| `setup.php` | Crear usuarios interactivamente | `php setup.php` |
| `test.php` | Suite de pruebas | `php test.php` |
| `init_system.php` | Inicialización automática | `php init_system.php` |
| `check_user.php` | Verificar usuario | `php check_user.php` |

### Endpoints de Prueba

| Endpoint | Descripción |
|----------|-------------|
| `/acide/auth/test_login_direct.php` | Login directo (bypass dispatcher) |

---

## 📖 Glosario

**ACIDE:** Advanced Content & Integration Development Engine  
**Argon2id:** Algoritmo de hashing de contraseñas (OWASP 2024)  
**CSPRNG:** Cryptographically Secure Pseudo-Random Number Generator  
**JWT:** JSON Web Token  
**UUID:** Universally Unique Identifier  
**RBAC:** Role-Based Access Control  

---

## 🆘 Soporte

### Problemas Comunes

1. **"Usuario no encontrado"**
   - Verifica que el archivo existe en `data/users/`
   - Verifica el índice en `data/users/index.json`

2. **"Contraseña incorrecta"**
   - Verifica el hash en el archivo del usuario
   - Asegúrate de usar la contraseña correcta

3. **"Sesión expirada"**
   - Las sesiones expiran a los 30 minutos
   - Vuelve a iniciar sesión

### Logs

Los errores se registran en:
- Error log de PHP (configurado en `php.ini`)
- Consola del navegador (para errores de cliente)

---

## 🔄 Actualizaciones

**Versión Actual:** 1.0.0  
**Última Actualización:** 2026-01-06  

### Changelog

**v1.0.0** (2026-01-06)
- ✅ Sistema de autenticación autónomo
- ✅ Hashing Argon2id
- ✅ Sistema de roles (4 roles)
- ✅ Gestión de sesiones
- ✅ CRUD completo de usuarios
- ✅ Documentación completa

---

## 📞 Contacto

Para preguntas o sugerencias sobre el sistema de autenticación:
- Consulta la documentación técnica
- Revisa los scripts de ejemplo
- Ejecuta las pruebas

---

**Sistema ACIDE - Autenticación Autónoma**  
*Simple, Seguro, Soberano*
