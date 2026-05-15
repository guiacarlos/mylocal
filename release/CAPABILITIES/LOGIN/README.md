# LOGIN — Capability bloqueada

**Status: BLINDADA. NO MODIFICAR sin actualizar este documento Y los tests.**

Esta capability concentra TODO lo relacionado con autenticacion y autorizacion
de MyLocal. El objetivo es que cualquier feature nueva *importe* esta capability
en lugar de tocar `spa/server/handlers/auth.php`, `spa/server/lib.php` o el
dispatcher. Asi se evitan las regresiones que costaron sesiones enteras de
depuracion antes de existir esta capa.

> Cualquier cambio que rompa el contrato debe hacer fallar el test
> `spa/server/tests/test_login.php`. Si el test pasa pero el login no
> funciona, el test esta incompleto - amplialo antes de tocar nada mas.

---

## 1. Contrato publico

Todo el resto del sistema interactua con LOGIN exclusivamente a traves de la
fachada `LoginCapability`. **No** se llaman directamente las clases internas
(`LoginPasswords`, `LoginSessions`, etc.) desde fuera de esta capability.

```php
require_once __DIR__ . '/../../CAPABILITIES/LOGIN/LoginCapability.php';

// 1. Autenticar (login)
$result = \Login\LoginCapability::authenticate($email, $password, $ip);
//   exito: ['success'=>true, 'user'=>['id','email','role',...], 'token'=>'<64 hex>']
//   fallo: ['success'=>false, 'error'=>'credenciales invalidas']

// 2. Resolver usuario actual desde header Authorization Bearer
$user = \Login\LoginCapability::resolveUser($bearerOrNull);
//   array | null

// 3. Gate de permisos por rol
\Login\LoginCapability::requireRole($user, ['admin','superadmin']);
//   void en exito; emite HTTP 403 + die() en fallo

// 4. Logout
\Login\LoginCapability::logout($bearer);

// 5. Rate limit (no solo login - cualquier scope)
\Login\LoginCapability::rateLimit($scope, $perMinute);

// 6. Sanitizacion
$safeId    = \Login\LoginCapability::safeId($v);
$safeEmail = \Login\LoginCapability::safeEmail($v);
$safeStr   = \Login\LoginCapability::safeStr($v, $max = 2000);
$safeInt   = \Login\LoginCapability::safeInt($v, $min, $max);

// 7. Bootstrap (CLI o auto-bootstrap del dispatcher)
\Login\LoginBootstrap::run();
```

---

## 2. Decisiones bloqueadas (heredan del AUTH_LOCK historico)

1. **Bearer-only, sin cookies httponly**. El token va en `Authorization: Bearer <token>`.
   La SPA lo lee del body del login y lo guarda en `sessionStorage('mylocal_token')`.
2. **Errores de negocio en HTTP 200** con `{success:false, error:"..."}`. NUNCA HTTP 401/403
   para credenciales malas: solo para "no autenticado / sin permiso" en endpoints privilegiados.
3. **Auto-bootstrap**. Si `data/users/` esta vacio, el dispatcher invoca `LoginBootstrap::run()`
   creando los usuarios default antes de procesar la peticion.
4. **Default users**: `socola@socola.es / socola2026`, `sala@socola.es`, `cocina@socola.es`,
   `camarero@socola.es`. Todos con la misma password de bootstrap.
5. **Argon2id** para passwords. Parametros en `OPTIONS/optionsLogin.php`.
6. **Rate limit** 5/min para login, archivo plano en `STORAGE/data/_rl/`.
7. **Token**: `random_bytes(32) -> bin2hex` = 64 chars.
8. **TTL rolling**: cada peticion valida actualiza `last_seen`; expira tras `SESSION_TTL_SECONDS`
   sin actividad.
9. **`require_role` server-side** es la unica fuente de verdad para permisos. El rol del
   IndexedDB cliente es decorativo (UI).
10. **Roles whitelist** en `OPTIONS/optionsLoginRoles.php`. Roles fuera del whitelist
    son rechazados en `authenticate()`.
11. **Permisos por rol** en `OPTIONS/optionsLoginPermissions.php` con glob (`carta_*`).
12. **Secretos NO viven aqui**. `optionsLogin*.php` solo contiene defaults no-secretos.
    El `jwt_secret` (cuando se necesite firma adicional) sigue en `spa/server/config/auth.json`
    o se promueve a `STORAGE/.vault/login.json` (fuera del repo).

---

## 3. Donde viven los datos (NO se almacenan en CAPABILITIES/LOGIN)

| Dato                  | Ruta                                  | Bloqueo web |
|-----------------------|---------------------------------------|-------------|
| Usuarios + hashes     | `spa/server/data/users/u_*.json`      | si (.htaccess) |
| Sessions vivas        | `spa/server/data/sessions/*.json`     | si |
| Rate-limit buckets    | `spa/server/data/_rl/<scope>/*.json`  | si |
| Config secrets        | `spa/server/config/auth.json`         | si (FilesMatch) |
| Defaults no-secretos  | `CAPABILITIES/OPTIONS/optionsLogin*.php` | si (CAPABILITIES Deny all) |

---

## 4. Como anadir features sin romper login

1. **Necesito gate de permisos en un handler nuevo**:
   ```php
   $user = \Login\LoginCapability::resolveUser($bearer);
   \Login\LoginCapability::requireRole($user, ['admin','editor']);
   ```
2. **Necesito otro tipo de rate-limit**:
   ```php
   \Login\LoginCapability::rateLimit('mi_scope', 30);
   ```
3. **Necesito cambiar TTL / argon2 / lista de roles**: edita `OPTIONS/optionsLogin*.php`.
4. **Necesito una accion nueva privilegiada**: registrala en `optionsLoginPermissions::MAP`
   con su pattern. NO hagas un nuevo `if ($user['role']==='admin')` en el handler.

---

## 5. Test gate

`spa/server/tests/test_login.php` corre en `build.ps1` y debe pasar siempre.
Cubre: pre-checks de archivos, OPTIONS source-of-truth, configs materializados,
dispatcher con `require_once`, bearer-only sin cookies, login OK / KO,
auth_me con bearer, OCR, sala (zonas + mesas + tokens), bootstrap de defaults,
y denegacion por rol (camarero intenta accion admin).

Si tu cambio hace fallar el test, **arregla el cambio** o **amplia el test**
si esta cubriendo un caso que ya no aplica. NUNCA bypaseando el gate.

---

## 6. Archivos internos (orientacion para mantenimiento)

| Archivo               | Responsabilidad |
|-----------------------|-----------------|
| `LoginCapability.php` | Fachada publica. Solo enruta. |
| `LoginPasswords.php`  | Argon2id, dummy_hash, policy, needs_rehash. |
| `LoginSessions.php`   | Issue / validate / revoke / rolling TTL del bearer. |
| `LoginRoles.php`      | requireRole + glob match contra optionsLoginPermissions. |
| `LoginRateLimit.php`  | rl_check con buckets en `STORAGE/data/_rl/`. |
| `LoginVault.php`      | Lectura/escritura de `data/users/`. |
| `LoginBootstrap.php`  | Auto-seed de los 4 default users + reset de password. |
| `LoginSanitize.php`   | s_id / s_email / s_str / s_int. |

Cada archivo <= 250 lineas. Si crece mas, revisa que no este absorbiendo
responsabilidad de otro.
