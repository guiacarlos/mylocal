# AxiDB y arquitectura de datos de MyLocal

**Para todas las IAs que trabajen en este proyecto: leer este documento ANTES
de tocar cualquier cosa relacionada con datos, autenticacion, login, build,
spa/server, CORE/ o despliegue.**

> **NOTA**: para el flujo de auth/login en concreto, el documento canonico
> es `claude/AUTH_LOCK.md`. Este AXIDB_SYSTEM.md cubre la arquitectura
> general de datos. Si vas a tocar auth, lee AUTH_LOCK primero.

Este documento existe porque hemos perdido sesiones enteras volviendo atras
por no entender que hay DOS backends en este repo y solo uno esta activo.
Aqui queda fijado.

---

## 1. Hay dos backends. Solo uno esta activo.

```
mylocal/
├── CORE/                  <- LEGACY ARCHIVADO. NO TOCAR. NO USAR.
│   ├── auth/
│   ├── bootstrap_users.php
│   └── index.php
└── spa/server/            <- BACKEND ACTIVO. Es lo unico que se usa.
    ├── handlers/
    │   └── auth.php       <- handle_auth_login, find_user_by_email...
    ├── bin/
    │   └── bootstrap-users.php
    ├── config/
    ├── data/              <- aqui vive la data del SPA
    ├── lib.php
    └── index.php          <- el dispatcher real
```

**`CORE/` es codigo arqueologico**. Lo dice [spa/CLAUDE.md](../spa/CLAUDE.md):
> "Don't add features to the legacy `../CORE/` — it's archived."

Si ves que un fix toca `CORE/auth/` o `CORE/bootstrap_users.php`, **es un fix
inutil**. Esa ruta no se ejecuta. La SPA hace POST a `/acide/index.php` y
`router.php` lo enruta a `spa/server/index.php`, no a `CORE/`.

---

## 2. Como llega una peticion del SPA al backend

```
Navegador (LoginModal.tsx)
   |
   | POST /acide/index.php  (action: auth_login)
   v
router.php (raiz del proyecto, o release/router.php)
   |
   | strpos($path, '/acide/') === 0
   | -> require __DIR__ . '/spa/server/index.php'
   v
spa/server/index.php
   |
   | 1. CORS (cors.json)
   | 2. Auto-bootstrap si data/users vacio
   | 3. ALLOWED_ACTIONS check
   | 4. publicActions check (cors.json public_actions)
   | 5. Dispatcher: require_once handlers/auth.php
   v
spa/server/handlers/auth.php :: handle_auth_login()
   |
   | 1. rl_check (rate limit)
   | 2. find_user_by_email()
   | 3. password_verify()
   | 4. issue_session() -> setcookie + csrf
   v
JSON: {success: true, data: {user, csrfToken}}
```

---

## 3. Donde viven los usuarios (REALMENTE)

```
spa/server/data/users/
├── u_<hex>.json     <- un archivo por usuario
└── ...
```

Cada `u_<hex>.json` contiene:
```json
{
  "id": "u_1a0685a0f10bb67d",
  "email": "socola@socola.es",
  "name": "Socola Admin",
  "role": "admin",
  "password_hash": "$argon2id$v=19$m=65536,t=4,p=1$...",
  "_version": 1,
  "_createdAt": "2026-05-04T06:09:31+00:00",
  "_updatedAt": "2026-05-04T06:09:31+00:00"
}
```

No hay `index.json` aqui. `find_user_by_email` recorre todos los archivos
linealmente (es O(n) y para <1000 usuarios da igual).

**No confundir con `STORAGE/.vault/users/`**: ese es del CORE legacy. Si
veo a alguien bootstrapear ahi, esta perdiendo el tiempo: el SPA no lo lee.

---

## 4. Auto-bootstrap de usuarios (auto-heal)

`spa/server/index.php` lineas 62-67:

```php
$_usersDir = DATA_ROOT . '/users';
if (!is_dir($_usersDir) || count(glob($_usersDir . '/*.json') ?: []) === 0) {
    define('BOOTSTRAP_INTERNAL', true);
    @include_once __DIR__ . '/bin/bootstrap-users.php';
}
```

`bin/bootstrap-users.php` carga `lib.php` Y `handlers/auth.php` (porque
necesita `find_user_by_email`), luego crea cuatro usuarios:

| Email | Password | Rol |
|-------|----------|-----|
| `socola@socola.es` | `socola2026` | admin |
| `sala@socola.es` | `socola2026` | sala |
| `cocina@socola.es` | `socola2026` | cocina |
| `camarero@socola.es` | `socola2026` | camarero |

Idempotente: si ya existen, salta. Hashes con Argon2id (memory_cost 64MB,
time 4, threads 1).

**Como invocar manualmente desde CLI:**
```bash
php spa/server/bin/bootstrap-users.php
```

**Como forzar rebootstrap**: borrar `spa/server/data/users/` y hacer una
peticion HTTP cualquiera al `/acide/index.php`.

---

## 5. Config files: el .example no es la verdad

`spa/server/config/` contiene cuatro archivos `.json.example`:

- `auth.json.example` - parametros Argon2id, TTL de sesion, roles permitidos.
- `cors.json.example` - origenes permitidos y `public_actions` (las que NO
  requieren sesion previa, como `auth_login`).
- `gemini.json.example` - API key de Gemini.
- `revolut.json.example` - API key Revolut.

`load_config('auth')` LANZA EXCEPCION si `auth.json` no existe. Eso provoca
HTTP 500 en login. **Siempre tiene que existir el .json real**.

`build.ps1` materializa los .example a .json automaticamente como parte del
build (paso 2.2). En source, basta con copiar `*.json.example` a `*.json`
una vez.

`cors.json` define `public_actions`. Si el archivo no existe, el codigo cae
a un fallback hardcoded en `index.php` que ya incluye `auth_login`. Esto
ya esta resuelto y tolera la ausencia del fichero.

---

## 6. Dos entornos, dos rutas, mismas reglas

### Desarrollo (run.bat)

```
PHP server: php -S 127.0.0.1:8090 -t . router.php
SPA dev:    npm run dev (vite) en :5173, proxifica /acide -> :8090
Datos:      spa/server/data/
```

El user trabaja en :5173, las peticiones van a :8090, que es el PHP en la
RAIZ del proyecto. Ese PHP carga `router.php` (el de la raiz), que rutea a
`spa/server/index.php`.

### Produccion (build.ps1 + release)

```
PHP server: cualquier PHP-capable (Apache, LiteSpeed, php -S, etc.)
Datos:      release/spa/server/data/
```

El SPA compilado vive en `release/`. Las peticiones a `/acide/*` las maneja
`release/router.php` que enruta a `release/spa/server/index.php`.

`build.ps1` copia `spa/server/` entero a `release/spa/server/` y materializa
los configs. Por eso fue clave anadirlo a la lista `$include` del script.

### Por que dos directorios `data/`?

Porque son entornos distintos. Tu sesion de dev no comparte usuarios con
release. Cada uno tiene sus propios usuarios, sesiones, pedidos. Se rebootstrapean
independientemente.

---

## 7. Scopes en SynaxisClient (que va al server, que se queda local)

`spa/src/synaxis/actions.ts` define el scope de cada accion:

- **local**: SynaxisCore (IndexedDB del navegador). Sin red.
- **server**: POST a `/acide/index.php`. Usa cookie httponly + CSRF.
- **hybrid**: prueba local; si vacio, va al server y cachea el resultado.

`auth_login` es **`server` SIEMPRE**. Cambiarlo a `local` rompe la
seguridad (Argon2id no puede vivir en navegador). Si una IA lo cambia,
revertir.

Acciones siempre `server`: `auth_login`, `auth_logout`, `auth_refresh_session`,
`get_current_user`, `public_register`, todo lo de `payments`, `upload`,
`synaxis_sync`, `chat`/`ai`.

---

## 8. AxiDB: que es, donde encaja

AxiDB (`axidb/engine/`) es el motor de datos file-based del legacy CORE.
**El SPA no lo usa directamente**. La SPA usa `data_put` / `data_get` /
`data_all` en `spa/server/lib.php` que son funciones JSON+flock mas simples.

AxiDB sigue presente porque algunas capabilities legacy lo usan (CARTA,
TPV, FISCAL, QR antes del rewrite). Pero el FLUJO PRINCIPAL del SPA pasa
por `spa/server/`, no por `axidb/engine/`.

`axidb/plugins/` (alergenos, jobs) son plugins que escriben a `STORAGE/`,
y ESO si lo lee algun handler nuevo (CARTA OCR, etc).

**Resumen**:
- Login y sesiones -> spa/server/data/users/
- Carta y productos legacy -> STORAGE/ via AxiDB
- Plugins (jobs, alergenos) -> STORAGE/_system/

---

## 9. Errores tipicos y su causa raiz

| Sintoma | Causa raiz | Fix |
|---------|-----------|-----|
| `Usuario no encontrado` (string del CORE) | Algun codigo quedo apuntando a `CORE/auth/`. NO debe pasar en flujo SPA | Confirmar que router.php apunta a `spa/server/`, no a `CORE/` |
| `HTTP 500` + `Call to undefined function find_user_by_email` | bootstrap-users.php no carga handlers/auth.php | Asegurar `require_once __DIR__ . '/../handlers/auth.php'` en `bin/bootstrap-users.php` |
| `HTTP 500` + `Cannot redeclare handle_auth_login` | El dispatcher usa `require` en lugar de `require_once` | Cambiar `require __DIR__ . '/handlers/'` -> `require_once` en `index.php` |
| `Unauthorized: accion 'auth_login' requiere sesion` | `cors.json` no existe y el fallback no incluye auth_login en public_actions | `index.php` ya tiene fallback con auth_login. Si falla aun, copiar `cors.json.example` a `cors.json` |
| `Config 'auth.json' no existe` | Falta materializar el config | Copiar `auth.json.example` a `auth.json`. Build lo hace automaticamente |
| `SPA server no encontrado` | `release/spa/server/` no existe | `build.ps1` debe copiar `spa\server` a release. Verificar que esta en `$include` |
| `Credenciales invalidas` con la pass correcta | Usuario no fue bootstrapeado o `data/users/` esta vacio | Hacer una peticion HTTP cualquiera para disparar auto-bootstrap, o `php spa/server/bin/bootstrap-users.php` |
| Respuesta HTML en vez de JSON | El servidor no procesa PHP (sirviendo estatico) | Usar `php -S host:port -t release release/router.php` |
| HTTP 419 | CSRF token expirado | Cliente lo limpia y reintenta. Si persiste, revisar que cors.json tiene allow_credentials true |

---

## 10. Diagnostico rapido cuando falla el login (60 segundos)

1. **Hay PHP procesando?** `curl -X POST http://localhost:8765/acide/index.php -H 'Content-Type: application/json' -d '{"action":"health_check"}'`
   - Debe devolver `{"success":true,...}`. Si devuelve HTML, no hay PHP.

2. **El error log dice algo?** Mira `error_log` de PHP o el archivo apuntado con `-d error_log=/tmp/...`.

3. **Existe `spa/server/data/users/`?** Si no, es la primera peticion - hara auto-bootstrap. Si existe pero vacio, borrar la carpeta y reintentar.

4. **Existe `spa/server/config/auth.json`?** Si solo esta el .example, copialo o ejecuta build.ps1.

5. **El user que pruebas existe?** `ls spa/server/data/users/` y graba un user_id, luego `cat spa/server/data/users/<id>.json | grep email`.

6. **Status del usuario es activo?** `cat <archivo>.json | grep status` - si no aparece, no hay status field y se asume activo.

---

## 11. Lo que SI se hace y lo que NO se hace

### SI

- Tocar `spa/server/handlers/*.php` para anadir o modificar acciones server.
- Tocar `spa/server/lib.php` para utilidades comunes.
- Anadir nuevas acciones a `ALLOWED_ACTIONS` en `index.php`.
- Anadir nuevas acciones publicas (sin sesion previa) a
  `cors.json.public_actions` y al fallback hardcoded de `index.php`.
- Anadir el archivo de la accion a `spa/src/synaxis/actions.ts` con su scope.
- Bootstrapear usuarios via CLI: `php spa/server/bin/bootstrap-users.php`.
- Verificar primero con `curl` antes de ir al SPA real (mas rapido).

### NO

- Anadir features a `CORE/` (es legacy archivado).
- Bootstrapear usuarios en `STORAGE/.vault/users/` (es legacy).
- Cambiar `auth_login` a scope `local`.
- Usar `require` en lugar de `require_once` en `spa/server/index.php`.
- Asumir que `release/spa/server/` existe sin haber corrido `build.ps1`.
- Subir `spa/server/data/users/*.json` al repo publico (contienen hashes).
- Subir `spa/server/config/*.json` con secretos reales al repo publico.

---

## 12. Resumen ejecutivo (lo minimo)

1. Hay dos backends: `CORE/` (LEGACY, no tocar) y `spa/server/` (ACTIVO).
2. La SPA hace POST a `/acide/index.php` -> `router.php` -> `spa/server/index.php`.
3. Usuarios viven en `spa/server/data/users/<id>.json` (no en STORAGE/.vault).
4. Auto-bootstrap en `spa/server/index.php` crea los 4 users default si vacio.
5. Default admin: `socola@socola.es` / `socola2026`.
6. `auth_login` es `server` SIEMPRE.
7. Configs viven en `spa/server/config/*.json`. Build.ps1 los materializa
   desde `.example` automaticamente.
8. `release/` necesita `spa/server/` copiado (build.ps1 lo hace).
9. Dev (8090) y release (cualquier puerto) tienen `data/` independientes.
10. Si el login falla, sigue el arbol de diagnostico de 60s seccion 10.

---

## 13. Cambio importante: bearer-only desde 2026-05-04

La auth ya **no usa cookies httponly + CSRF double-submit**. Ahora es:

- Login devuelve `{user, token}` en el body de la respuesta.
- Cliente guarda token en `sessionStorage('mylocal_token')`.
- Cada peticion lleva `Authorization: Bearer <token>`.
- `current_user()` solo lee `HTTP_AUTHORIZATION`, no `$_COOKIE`.
- Sin CSRF (no aplica sin cookies).
- Errores de negocio devuelven HTTP 200 con `{success:false, error:"..."}`.

Ver `claude/AUTH_LOCK.md` para el contrato completo y los modos de fallo
historicos. Cualquier intento de volver a cookies romperia el test
`spa/server/tests/test_login.php`.

---

## 14. Historial de bugs resueltos (no repetir)

Bugs que ya hemos pisado en este proyecto. No volver a caer:

1. **"Usuario no encontrado" en login**: alguien creo usuarios en
   `STORAGE/.vault/users/` pensando que era el sitio. Era el legacy CORE.
   El SPA usa `spa/server/data/users/`.

2. **HTTP 500 con `Call to undefined function find_user_by_email`**:
   `bin/bootstrap-users.php` solo cargaba `lib.php`, no `handlers/auth.php`.

3. **HTTP 500 con `Cannot redeclare handle_auth_login`**: el dispatcher
   en `index.php` usaba `require` en vez de `require_once`. Al cargarse
   antes desde bootstrap, se intentaba redeclarar.

4. **`Unauthorized: accion 'auth_login' requiere sesion`**: el fallback
   de `corsCfg` cuando `cors.json` no existia tenia `public_actions => []`,
   tratando `auth_login` como privada.

5. **`Config 'auth.json' no existe`**: dev tenia solo `.example`. Build
   ahora materializa los `.json` automaticamente.

6. **`release/` quedaba sin backend tras `npm run build`**: `vite build
   --emptyOutDir` borra release/. `build.ps1` debe ejecutarse SIEMPRE
   despues, nunca solo `npm run build`.

7. **`SPA server no encontrado`**: `build.ps1` no copiaba `spa\server`.
   Anadido a `$include`.
