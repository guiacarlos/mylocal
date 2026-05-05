# AUTH_LOCK.md - Contrato bloqueado del flujo de autenticacion

**Status: BLINDADO. NO MODIFICAR sin actualizar este documento Y los tests.**

Este documento describe el flujo de autenticacion de MyLocal exactamente
como esta hoy, despues de varias regresiones que nos costaron sesiones
enteras de depuracion. La regla es simple:

> Cualquier cambio que rompa este flujo debe hacer fallar el test
> `spa/server/tests/test_login.php`. Si el test pasa pero el login no
> funciona, el test esta incompleto - amplialo antes de tocar nada mas.

---

## 1. Contrato del flujo (resumen ejecutivo)

```
Usuario en LoginModal.tsx
   |
   | POST {action:"auth_login", data:{email,password}}
   v
SynaxisClient.execute()
   |
   | scope='server' -> http()
   | fetch(/acide/index.php) con credentials: 'omit', sin cookies
   v
Vite proxy (dev) o Apache/PHP server (prod)
   |
   | /acide/* -> spa/server/index.php
   v
spa/server/index.php
   |
   | 1. headers seguridad
   | 2. CORS
   | 3. parse JSON
   | 4. ALLOWED_ACTIONS check
   | 5. AUTO-BOOTSTRAP si data/users vacio (incluye handlers/auth.php)
   | 6. publicActions check (auth_login esta dentro)
   | 7. current_user() lee Authorization Bearer (NO cookies)
   | 8. Dispatch: require_once handlers/auth.php
   v
handle_auth_login()
   |
   | rl_check + find_user_by_email + password_verify(Argon2id)
   v
issue_session()
   |
   | random_bytes(32) -> token
   | data_put('sessions', token, {...})
   | DEVUELVE {user, token} EN BODY (sin setcookie)
   v
HTTP 200 + JSON {success:true, data:{user, token}}
   |
   v
SynaxisClient lee body JSON aunque !res.ok
   |
   v
auth.service.login() guarda token en sessionStorage('mylocal_token')
   |
   v
Siguientes peticiones llevan Authorization: Bearer <token>
```

---

## 2. Decisiones bloqueadas (NO cambiar)

### 2.1 Bearer-only, sin cookies

- **No setear** `Set-Cookie: socola_session=...` ni `socola_csrf=...`.
- El token viaja en el body de la respuesta de login y se guarda en
  `sessionStorage` del cliente.
- Cada request manda `Authorization: Bearer <token>`.
- `current_user()` SOLO lee `HTTP_AUTHORIZATION`, no `$_COOKIE`.
- `credentials: 'omit'` en el fetch del cliente (no enviar cookies).

**Razon**: las cookies httponly cross-port (5173 vs 8090) traian
problemas de Same-Site, stale sessions y CSRF double-submit que
generaban HTTP 500 silenciosos.

### 2.2 No CSRF double-submit

- Sin cookies no hay vector CSRF cross-site posible (sessionStorage
  no es accesible para origenes ajenos).
- `csrf_token` action sigue existiendo como **no-op** (devuelve placeholder)
  por compatibilidad con codigo cliente antiguo. NO eliminar.

### 2.3 Errores de negocio = HTTP 200

- El catch global de `index.php` mapea `RuntimeException` y
  `InvalidArgumentException` a **HTTP 200** con `{success:false, error:"..."}`.
- HTTP 500 se reserva para errores tecnicos genuinos (Error, TypeError).
- **Razon**: el SynaxisClient solo lee el body JSON correctamente cuando
  res.ok = true. Si devolvieras 500 para "Credenciales invalidas", el
  cliente mostraria "HTTP 500: <body>" en lugar del mensaje real.

### 2.4 SynaxisClient lee body siempre que sea JSON valido

- `SynaxisClient.http()` intenta `JSON.parse(text)` ANTES de mirar
  `res.ok`. Si el body es JSON con campo `success`, lo devuelve.
- Solo si NO es JSON cae al fallback `HTTP <code>: <text>`.

### 2.5 Auto-bootstrap garantizado

- `spa/server/index.php` ejecuta `bin/bootstrap-users.php` al inicio
  si `data/users/` esta vacio.
- `bin/bootstrap-users.php` carga **`handlers/auth.php`** ademas de
  `lib.php` (necesita `find_user_by_email`).
- Despliegue limpio = login operativo en la primera peticion. Sin
  pasos manuales.

### 2.6 Dispatcher con `require_once`

- TODAS las llamadas a handlers en `spa/server/index.php` usan
  `require_once`, NUNCA `require`.
- **Razon**: el bootstrap incluye `handlers/auth.php` antes del
  dispatcher; con `require` se redeclaran las funciones -> fatal error.

### 2.7 Configs materializados

- `build.ps1` copia `spa/server/config/*.json.example` a
  `*.json` automaticamente. Los `.json` reales son los que el server
  lee con `load_config()`.
- En source dev, mantener los `.json` versionados.

### 2.8 cors fallback con auth_login publico

- Si `cors.json` no existe, `index.php` cae a un fallback hardcoded
  que YA INCLUYE `auth_login` (y los demas) en `public_actions`.
- Sin esto, `auth_login` se trataria como privada y el primer login
  daria 401.

---

## 3. Archivos load-bearing (los que tocas con MUCHO cuidado)

> **Desde la migracion a `CAPABILITIES/LOGIN/` (2026-05-05)**: la logica de
> autenticacion vive en una capability bloqueada. Los archivos de
> `spa/server/` que aparecen abajo son ahora **dispatchers delgados** que
> delegan en `\Login\LoginCapability` y sus clases internas. Esto reduce
> drasticamente la superficie load-bearing en `spa/server/`. La fuente
> canonica del flujo y su contrato publico estan en
> [`CAPABILITIES/LOGIN/README.md`](../CAPABILITIES/LOGIN/README.md).

### 3.a Capability LOGIN (logica canonica)

| Archivo | Funcion | Que rompe si lo tocas mal |
|---------|---------|---------------------------|
| `CAPABILITIES/LOGIN/LoginCapability.php` | Fachada publica (login, logout, session, register, resolveUser, requireRole, rateLimit, safe*) | Toda la auth |
| `CAPABILITIES/LOGIN/LoginPasswords.php` | Argon2id + dummy_hash + policy + needs_rehash | Verificacion de password |
| `CAPABILITIES/LOGIN/LoginSessions.php` | issue / resolve / revoke bearer + UA fingerprint | Sesion entera |
| `CAPABILITIES/LOGIN/LoginRoles.php` | requireRole + glob match contra optionsLoginPermissions | Permisos |
| `CAPABILITIES/LOGIN/LoginRateLimit.php` | rl_check con buckets en STORAGE/data/_rl | Brute-force, abuso |
| `CAPABILITIES/LOGIN/LoginVault.php` | findByEmail / findById / upsert / patch en data/users | Lookup de usuario |
| `CAPABILITIES/LOGIN/LoginBootstrap.php` | Auto-seed de los 4 default users | Primer arranque sin users |
| `CAPABILITIES/LOGIN/LoginSanitize.php` | s_id / s_email / s_str / s_int (path traversal!) | Validacion de input |
| `CAPABILITIES/OPTIONS/optionsLogin.php` | Defaults no-secretos (TTL, argon2, policy) | Parametros de seguridad |
| `CAPABILITIES/OPTIONS/optionsLoginRoles.php` | Whitelist de roles validos | Inyeccion de roles |
| `CAPABILITIES/OPTIONS/optionsLoginPermissions.php` | Mapa role -> [acciones] (uso opcional, glob match) | Granularidad de permisos |

### 3.b Dispatchers delgados en spa/server (delegan en la capability)

| Archivo | Funcion | Que rompe si lo tocas mal |
|---------|---------|---------------------------|
| `spa/server/index.php` | Dispatcher | Validacion, CORS, dispatch |
| `spa/server/handlers/auth.php` | 1-line wrappers a LoginCapability::* + 2 shims CLI | El login mismo |
| `spa/server/lib.php` | data_*, resp, http_json + shims a Login* | Persistencia + envelope |
| `spa/server/bin/bootstrap-users.php` | Wrapper de LoginBootstrap::run() (CLI + auto-bootstrap) | Sin users no entras |
| `spa/src/synaxis/SynaxisClient.ts` | Cliente HTTP | Headers, body parsing |
| `spa/src/services/auth.service.ts` | Wrapper login/logout | sessionStorage del token |
| `router.php` (raiz y release/) | Enruta /acide/* | Si apunta mal -> 404 o backend equivocado |
| `build.ps1` | Build pipeline | Falta copiar spa/server -> backend roto |

Estos archivos llevan en su cabecera el bloque `MYLOCAL AUTH LOCK`
(seccion 6 de este documento). Si lo eliminas o ignoras, te tocara
volver a depurar el login.

**Regla de oro tras la migracion**: cualquier feature nueva que necesite auth
**importa `LoginCapability`**, NUNCA toca `spa/server/handlers/auth.php` ni
`spa/server/lib.php`. Si te ves modificandolos, es signal de que estas
saltandote la capability; reflexiona antes.

---

## 4. El test que blindado todo: `spa/server/tests/test_login.php`

31 assertions que cubren los 7 modos de fallo historicos:

1. Pre-checks de existencia de archivos criticos.
2. Configs `.json` materializados desde `.example`.
3. `bootstrap-users.php` carga `handlers/auth.php`.
4. Dispatcher usa `require_once`.
5. Bearer-only: `issue_session` no setea cookie, `current_user` lee Authorization.
6. PHP server arrancable y health check OK.
7. Login OK con HTTP 200, devuelve token bearer en body, no setea cookie.
8. Login bad pwd: HTTP 200 + `{success:false, error:"Credenciales invalidas"}`.
9. auth_me con bearer devuelve user.
10. Logout invalida el token.
11. Los 4 users default se loguean.

**Como ejecutar:**
```bash
php spa/server/tests/test_login.php                 # source
php spa/server/tests/test_login.php --root=release  # release
php spa/server/tests/test_login.php --port=9999     # otro puerto
```

**Integrado en `build.ps1`**: paso 2.3 lo ejecuta contra release/.
Si falla -> `exit 1` -> build aborta. La regresion no llega a release.

---

## 5. Modos de fallo historicos (no repetir)

Cada uno fue varios ciclos depurando. Lo dejo escrito para no caer otra vez.

### F1. "Usuario no encontrado"
- **Causa**: alguien bootstrapeo en `STORAGE/.vault/users/` (legacy CORE),
  pero el flujo activo lee `spa/server/data/users/`.
- **Test asociado**: pre-checks de archivos + login default users.

### F2. HTTP 500 + "Call to undefined function find_user_by_email"
- **Causa**: `bin/bootstrap-users.php` solo cargaba `lib.php`.
- **Fix**: `require_once __DIR__ . '/../handlers/auth.php';` en bootstrap.
- **Test asociado**: `[2] Bootstrap-users.php carga handlers/auth.php`.

### F3. HTTP 500 + "Cannot redeclare handle_auth_login"
- **Causa**: dispatcher usaba `require`, redeclaraba al cargarlo dos veces.
- **Fix**: cambiar a `require_once`.
- **Test asociado**: `[3] Dispatcher usa require_once`.

### F4. "Unauthorized: accion 'auth_login' requiere sesion"
- **Causa**: `cors.json` no existia, fallback dejaba `public_actions => []`.
- **Fix**: fallback hardcoded incluye `auth_login`.
- **Test asociado**: el login sin cors.json dispara health check OK.

### F5. "Config 'auth.json' no existe"
- **Causa**: solo el `.example` existia.
- **Fix**: build.ps1 materializa `.example` -> `.json`.
- **Test asociado**: `[1] Configs materializados`.

### F6. `release/` sin backend tras `npm run build`
- **Causa**: `vite build --emptyOutDir` borra `release/`. El user corrio
  solo `npm run build` sin `build.ps1`.
- **Fix**: build.ps1 copia spa/server inmediatamente despues. Documentar
  que NUNCA se ejecuta solo `npm run build`.
- **Test asociado**: `[0] Existe spa/server/index.php`.

### F7. "SPA server no encontrado"
- **Causa**: `build.ps1` no copiaba `spa\server`.
- **Fix**: anadido a `$include`.
- **Test asociado**: `[0] Existe spa/server/index.php`.

### F8. Browser: "HTTP 500: " (body vacio)
- **Causa real**: PHP no esta corriendo en 8090. Vite proxy hace
  ECONNREFUSED -> 500 al cliente con body vacio.
- **Fix permanente**: `run.bat` arranca PHP correctamente. NUNCA matar
  PHP a mano sin reiniciarlo.
- **Test asociado**: `[5] PHP server escuchando en :PORT`.

### F9. "HTTP 500: Credenciales invalidas" (mostrado al usuario)
- **Causa**: el server devolvia HTTP 500 para errores de negocio. El
  cliente concatenaba "HTTP 500: " + body.
- **Fix**: errores de negocio (RuntimeException) -> HTTP 200 + JSON envelope.
  Cliente lee body JSON aunque res.ok=false.
- **Test asociado**: `[7] Login OK responde HTTP 200 (no 500)` +
  `[8] Login bad pwd responde HTTP 200`.

---

## 6. Header obligatorio en archivos load-bearing

Todos los archivos del cuadro de la seccion 3 llevan este bloque arriba:

```
/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL AUTH LOCK - load-bearing                                 ║
   ║ Este archivo es parte del contrato de auth blindado.             ║
   ║ Antes de modificar, leer claude/AUTH_LOCK.md y verificar que     ║
   ║ spa/server/tests/test_login.php sigue pasando despues del cambio.║
   ╚══════════════════════════════════════════════════════════════════╝ */
```

Si veo un archivo del cuadro SIN ese header, es bug -> anadirlo.

---

## 7. Como anadir features sin romper el login

### Si la feature es server (un nuevo handler)

1. Crear `spa/server/handlers/<feature>.php` con sus funciones.
2. Anadir la accion a `ALLOWED_ACTIONS` en `spa/server/index.php`.
3. Anadir un `case` al dispatcher con **`require_once`** (nunca `require`).
4. Si la feature requiere auth: NO anadir a `csrfExempt` (ya no usamos
   CSRF, pero el patron de `current_user()` sigue valido).
5. Si la feature es publica: anadirla a `cors.json.public_actions` Y
   al fallback hardcoded en `index.php`.
6. Anadir entrada al test_login.php si toca el flujo de auth.

### Si la feature es solo cliente (SPA)

1. Anadir la accion a `spa/src/synaxis/actions.ts` con `scope: 'local'`.
2. SynaxisCore en IndexedDB la resuelve sin tocar nada del server.
3. No tocas nada del backend - el login no puede romperse.

### Si la feature involucra auth indirectamente

1. Lee primero AUTH_LOCK.md.
2. Anade el caso al test_login.php.
3. Implementa.
4. Corre el test. Tiene que pasar.
5. Corre `build.ps1`. Tiene que pasar.

---

## 8. Credenciales por defecto

| Email | Password | Rol |
|-------|----------|-----|
| `socola@socola.es` | `socola2026` | admin |
| `sala@socola.es` | `socola2026` | sala |
| `cocina@socola.es` | `socola2026` | cocina |
| `camarero@socola.es` | `socola2026` | camarero |

Definidas en `spa/server/bin/bootstrap-users.php`. Cambiar la lista de
usuarios YA CREADOS no rebootstrapea: hay que borrar `data/users/` para
que el auto-bootstrap actue.

---

## 9. Diagnostico rapido si el login falla

1. **Que dice el test?** `php spa/server/tests/test_login.php`. Si falla,
   el output dice exactamente que assertion no se cumple.

2. **Esta PHP corriendo en 8090 (dev) o el puerto que toque?**
   `curl -X POST http://127.0.0.1:8090/acide/index.php -H 'Content-Type: application/json' -d '{"action":"health_check"}'`

3. **Existen los users?** `ls spa/server/data/users/` debe tener 4 archivos.

4. **Existen los configs reales (no solo .example)?**
   `ls spa/server/config/*.json | grep -v example`

5. **El navegador tiene basura previa?** DevTools -> Application -> Clear
   site data. Hard reload.

Si los 5 anteriores estan OK y el login sigue fallando, NO es un problema
del flujo de auth - mira otra cosa.
