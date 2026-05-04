# AxiDB - Documento canonico del sistema de datos de MyLocal

**Para todas las IAs que trabajen en este proyecto: leer este documento ANTES de tocar
cualquier cosa relacionada con datos, autenticacion, STORAGE/, build, o despliegue.**

Este documento existe porque hemos perdido tiempo varias veces volviendo atras
por no entender como funciona AxiDB en MyLocal. Aqui queda fijado.

---

## 1. Que es AxiDB y que no es

AxiDB es un **motor de datos file-based** propio. NO es MySQL, NO es PostgreSQL,
NO es SQLite, NO es MongoDB. NO se instala. NO requiere servicios externos.

- Toda la data vive como **archivos JSON** dentro de `STORAGE/`.
- Las "tablas" son colecciones, cada coleccion es un directorio.
- Cada documento es un archivo `<id>.json`.
- Indices secundarios son archivos JSON (`index.json`, etc).
- No hay queries SQL. Las consultas pasan por la API PHP (`CRUDOperations.php`,
  `QueryEngine.php` en `axidb/engine/`).

**Regla absoluta**: ningun modulo accede a archivos de `STORAGE/` directamente.
Todos pasan por la capa AxiDB (los modelos en `CAPABILITIES/*/models/*.php`
o el motor en `axidb/engine/`).

---

## 2. Donde vive cada cosa (mapa fisico)

```
STORAGE/                         <- raiz de datos en runtime (NO va al repo en limpio)
├── .vault/                      <- credenciales y secretos (NUNCA al cliente)
│   └── users/                   <- aqui viven los usuarios autenticables
│       ├── index.json           <- mapa email -> uuid
│       ├── <uuid>.json          <- un archivo por usuario con password_hash
│       └── .htaccess            <- bloquea acceso HTTP directo
├── _system/                     <- datos internos (jobs, alergenos, sessions)
│   ├── alergenos_catalog.json
│   └── jobs/{pending,running,done,failed}/
├── locales/                     <- locales del hostelero
├── carta_productos/             <- productos de la carta
├── carta_categorias/
├── recetas/
├── sessions/                    <- sesiones activas
├── logs/
└── system/
    └── active_project.json      <- proyecto activo (multi-tenant)
```

`MEDIA/` esta separado: contiene imagenes, fotos de plato, logos. Tambien es
runtime, tambien es persistente, NO se borra en builds.

---

## 3. La regla mas importante: STORAGE NO es codigo

`STORAGE/` contiene **datos de runtime del restaurante**. Una build limpia
(`build.ps1`) **NO copia STORAGE**. Crea una vacia. Esto es intencional.

Consecuencia practica:

- En desarrollo, `STORAGE/` (raiz del repo) tiene tus datos de prueba.
- En `release/STORAGE/` siempre estara vacia despues de un build.
- En produccion, `STORAGE/` es el unico directorio que el cliente NO debe
  borrar al actualizar (sus datos viven ahi).

Si despliegas el `release/` a un servidor nuevo, `STORAGE/` esta vacio.
**Esto incluye los usuarios**. Sin usuarios no hay login.

---

## 4. Bootstrap automatico de usuarios (auto-heal)

Para que un despliegue limpio nunca quede sin acceso, en `CORE/index.php` hay
un **auto-bootstrap** que se ejecuta en CADA peticion al inicio:

```php
$_vaultIndex = STORAGE_ROOT . '/.vault/users/index.json';
$_needsBootstrap = !file_exists($_vaultIndex)
    || trim(@file_get_contents($_vaultIndex)) === '[]'
    || trim(@file_get_contents($_vaultIndex)) === '';
if ($_needsBootstrap && file_exists(__DIR__ . '/bootstrap_users.php')) {
    require_once __DIR__ . '/bootstrap_users.php';
    if (function_exists('bootstrapDefaultUsers')) {
        @bootstrapDefaultUsers();
    }
}
```

Crea cuatro usuarios por defecto definidos en `CORE/bootstrap_users.php`:

| Email | Password | Rol |
|-------|----------|-----|
| `socola@socola.es` | `socola2026` | admin |
| `sala@socola.es` | `socola2026` | sala |
| `cocina@socola.es` | `socola2026` | cocina |
| `camarero@socola.es` | `socola2026` | camarero |

**Reglas alrededor del bootstrap:**

- Solo se ejecuta cuando `index.json` esta vacio (`[]`) o no existe.
- Es idempotente: si vuelves a llamar, no duplica usuarios.
- Usa `password_hash` con `PASSWORD_ARGON2ID`. NUNCA escribas contrasenas en claro.
- Si tocas `bootstrap_users.php`, los cambios solo aplican a despliegues nuevos.
  Los STORAGE existentes no se rebootstrapean salvo que vacies su `index.json`.

**Como crear los usuarios manualmente (CLI):**

```bash
# Para STORAGE de desarrollo (raiz):
php -r "define('STORAGE_ROOT','./STORAGE'); define('GLOBAL_STORAGE','./STORAGE'); require 'CORE/bootstrap_users.php'; bootstrapDefaultUsers();"

# Para release/STORAGE:
cd release && php -r "require 'CORE/index.php';" >/dev/null
# Esto dispara el auto-bootstrap y la primera peticion deja STORAGE listo.
```

---

## 5. Como funciona el login (de punta a punta)

El login es la operacion donde mas hemos tropezado. Esta es la cadena completa:

```
[Usuario en LoginModal.tsx]
       |
       | submit form { email, password }
       v
[services/auth.service.ts: login()]
       |
       | client.execute({ action: 'auth_login', data: {...} })
       v
[SynaxisClient.ts]
       |
       | scope de 'auth_login' = 'server' -> SIEMPRE va al servidor
       | POST /acide/index.php  con cookie y X-CSRF-Token
       v
[CORE/index.php]
       |
       | 1. Auto-bootstrap si vault vacia (paso 4)
       | 2. CORS
       | 3. ActionDispatcher resuelve 'auth_login'
       v
[CORE/auth/Auth.php::login()]
       |
       v
[UserManager::verifyPassword()]
       |
       v
[UserAuthenticator::verify()]
       |
       | 1. UserFinder::getUserByEmail() lee index.json + <uuid>.json
       | 2. password_verify(password, password_hash)
       | 3. Si OK -> setea cookie de sesion + devuelve user
       v
[Respuesta JSON {success, user}]
       |
       v
[LoginModal redirige por rol via window.location.hash]
```

**Puntos donde tipicamente falla y como reconocerlo:**

| Sintoma | Causa raiz | Fix |
|--------|-----------|-----|
| `Usuario no encontrado` | `STORAGE/.vault/users/index.json` esta vacio o no existe | Ejecutar el auto-bootstrap (basta una peticion HTTP) o llamar `bootstrapDefaultUsers()` por CLI |
| `Contrasena incorrecta` | El usuario existe pero la pass no es `socola2026` | Cambiarla via panel o regenerar el `<uuid>.json` |
| Respuesta HTML en vez de JSON | El servidor no procesa PHP, sirve estatico (npx serve, http.server) | Arrancar PHP: `php -S localhost:3000 -t release release/router.php` |
| HTTP 419 | CSRF token expirado | El cliente ya lo maneja: limpia el token y reintenta |
| HTTP 404 en `/acide/index.php` | `.htaccess` no esta enrutando a `CORE/index.php` | Verificar `RewriteRule ^acide/(.*)$ CORE/$1` en `.htaccess` |
| Cuenta inactiva | `status != 'active'` en el `<uuid>.json` | Editar el archivo del usuario |

---

## 6. Scopes en SynaxisClient (local / server / hybrid)

`spa/src/synaxis/actions.ts` define el scope de cada accion. El cliente
elige transporte segun el scope:

- **local**: lo resuelve el SynaxisCore en IndexedDB del navegador. Sin red.
- **server**: SIEMPRE va al backend PHP. Operaciones que requieren secretos
  (Argon2id), webhooks, AI proxy, multi-dispositivo.
- **hybrid**: prueba local primero, si no encuentra cae al servidor y cachea.

**Rule of thumb**: si la operacion involucra password, API key, transaccion
de pago o estado compartido entre dispositivos -> es `server`.

`auth_login`, `auth_refresh_session`, `public_register`, `auth_resolve_tenant`
son TODAS `server`. Cambiarlas a `local` rompe la seguridad.

---

## 7. Source vs release: dos STORAGE distintos

Hay dos `STORAGE/` independientes en este repo:

```
mylocal/
├── STORAGE/             <- usado por desarrollo (run.bat / npm run dev)
└── release/
    └── STORAGE/         <- usado cuando sirves directamente release/
```

Cada uno tiene sus propios usuarios, sus propios pedidos, sus propias sesiones.
Si haces login en desarrollo no vale para release y viceversa.

Cuando ejecutas `build.ps1`:

1. Compila SPA y copia codigo a `release/`.
2. NO toca `release/STORAGE/` si ya existe (la `New-Item` solo crea si no esta).
3. Resultado: si bootstrapaste antes en `release/STORAGE/`, los usuarios se conservan.
   Si la borraste, el primer hit a `/acide/index.php` la rebootstrapea.

---

## 8. La cola de jobs y otros plugins de AxiDB

`axidb/plugins/` contiene extensiones que NO son parte del core de AxiDB pero
que viven dentro de su carpeta porque comparten el mismo principio file-based.

- `axidb/plugins/alergenos/` - catalogo de los 14 alergenos UE + ingredientes
  comunes. Persiste en `STORAGE/_system/alergenos_catalog.json`.
- `axidb/plugins/jobs/` - cola JSON con estados pending/running/done/failed.
  Cada job es un archivo en `STORAGE/_system/jobs/<estado>/<id>.json`.
  Worker en `axidb/plugins/jobs/worker_run.php`, ejecutable via cron.

**Cualquier funcionalidad que necesite procesamiento async va por jobs.**
No bloquees el frontend con OCR, IA pesada, generacion de PDFs masivos.

---

## 9. Lo que SI se hace y lo que NO se hace

### SI

- Leer/escribir datos via los modelos en `CAPABILITIES/*/models/*.php`.
- Usar el motor `axidb/engine/` para CRUD generico.
- Encolar trabajo pesado en jobs.
- Comprobar `STORAGE/.vault/users/index.json` cuando un login falla.
- Ejecutar `build.ps1` solo al cerrar fase / desplegar.

### NO

- Acceder directamente a archivos de `STORAGE/` desde un controlador o handler.
- Subir `STORAGE/.vault/` al repo publico (contiene hashes de passwords).
- Borrar manualmente `STORAGE/.vault/users/index.json` salvo para forzar
  rebootstrap.
- Cambiar el scope de `auth_login` a `local`.
- Confiar en que `release/STORAGE/` tenga datos despues de un deploy limpio
  (siempre delegar al auto-bootstrap o seed manual).
- Usar emojis en codigo o documentacion.
- Crear archivos de mas de 250 lineas.

---

## 10. Diagnostico rapido (60 segundos)

Cuando el login falla, sigue este arbol en orden:

1. **El servidor procesa PHP?**
   - `curl -X POST http://localhost:PORT/acide/index.php -d 'action=health_check'`
   - Si responde HTML, no hay PHP. Arranca con `php -S ... router.php`.

2. **Existe `STORAGE/.vault/users/index.json` y no esta vacio?**
   - `cat STORAGE/.vault/users/index.json`
   - Si no, dispara una peticion (auto-bootstrap) o ejecuta `bootstrapDefaultUsers()` por CLI.

3. **El usuario que pruebas existe en el index?**
   - El index mapea `email -> uuid`. Si tu email no esta, no fue creado.

4. **El `<uuid>.json` tiene `status: 'active'`?**
   - `cat STORAGE/.vault/users/<uuid>.json | grep status`

5. **La password es la correcta?**
   - Bootstrap default: `socola2026`. Si la cambiaste en el panel, recuperala
     desde el panel admin o regenera el archivo.

6. **CSRF / cookies?**
   - El navegador debe permitir cookies. En `localhost` con HTTPS auto-firmado
     a veces falla. Probar con HTTP plano para depurar.

---

## 11. Glosario

- **STORAGE_ROOT**: constante PHP. Apunta al `STORAGE/` activo (puede ser
  global o el del proyecto activo en multi-tenant).
- **GLOBAL_STORAGE**: STORAGE compartido entre proyectos. Usado por auth.
- **DATA_ROOT**: alias historico de STORAGE_ROOT, todavia presente.
- **vault**: subcarpeta protegida `STORAGE/.vault/`. Contiene users y otros
  secretos. `.htaccess` interno bloquea acceso HTTP directo.
- **bootstrap**: proceso de poblado inicial. NO confundir con `seed/`
  (que tiene productos de demo, no usuarios).
- **scope**: nivel de la accion (local/server/hybrid) en SynaxisClient.
- **acide**: alias historico del API endpoint. La SPA hace POST a
  `/acide/index.php` y `.htaccess` lo redirige a `CORE/index.php`.

---

## 12. Resumen ejecutivo (lo minimo que debes saber)

1. AxiDB = JSON files en `STORAGE/`. Sin SQL. Sin instalaciones.
2. Los usuarios viven en `STORAGE/.vault/users/{index.json + uuid.json}`.
3. Una build limpia deja STORAGE vacio. **Esto es correcto.**
4. `CORE/index.php` auto-bootstrapea usuarios si la vault esta vacia.
5. Default: `socola@socola.es` / `socola2026` (admin).
6. `auth_login` SIEMPRE es scope `server`. Nunca cambies eso.
7. Si el login falla -> revisa los 6 pasos del diagnostico.
8. STORAGE de desarrollo y de release son independientes.
9. Nunca subas `.vault/` al repo publico.
10. Nunca toques archivos directamente. Pasa por modelos.
