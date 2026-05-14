# USERS — gestión de usuarios, autenticación y roles

Documento canónico del flujo de usuarios. Complementa [SECURITY.md](SECURITY.md) con el detalle de **qué pasa paso a paso** en registro, login, sesión y permisos.

## 1. Modelo de usuario

Colección master `users` (en IndexedDB del cliente queda sin `password_hash`; en server/data/users/<id>.json vive el documento completo).

```json
{
    "id": "u_a1b2c3d4e5f6...",
    "email": "admin@socola.com",
    "name": "Admin Socolá",
    "role": "superadmin",
    "tenantId": "socola",
    "password_hash": "$argon2id$v=19$m=65536,t=4,p=1$...",
    "_version": 3,
    "_createdAt": "2026-04-17T10:00:00Z",
    "_updatedAt": "2026-04-17T10:00:00Z"
}
```

El cliente **nunca** recibe `password_hash`. [`auth.php`](../server/handlers/auth.php) lo elimina con `unset($user['password_hash'])` antes de devolver el objeto.

## 2. Roles

Definidos en [`server/config/auth.json.example`](../server/config/auth.json.example):

| Rol | Puede | Zona |
| :-- | :-- | :-- |
| `superadmin` | Todo | Dashboard admin |
| `administrador` / `admin` | CRUD, usuarios, config | Dashboard admin |
| `editor` | CRUD contenido, productos, temas | Dashboard admin |
| `maestro` | Editar contenido academia | Dashboard academia |
| `sala` | TPV, mesas, comandas | `/sistema/tpv` |
| `cocina` | Ver comandas, marcar servidas | `/sistema/tpv` (cocina view) |
| `camarero` | Igual que `sala` + limitado | `/sistema/tpv` |
| `estudiante` | Consumir academia | `/academia` |
| `cliente` | Carta, checkout propio, reservas | público |

**Regla dura**: el rol **nunca** se envía desde el cliente en `auth_login` ni `public_register`. El backend ignora cualquier `role` que venga en el body y fuerza `cliente` en registro público. Para crear otros roles:

1. `php server/bin/create-admin.php` (CLI) — primer superadmin.
2. UI de gestión de usuarios (pendiente, en `Dashboard`) — solo accesible a superadmin/admin.

## 3. Flujo: bootstrap del primer admin

```bash
ssh server
cd /var/www/socola/server
php bin/create-admin.php
# pide email, nombre, contraseña (oculta)
# crea server/data/users/u_<id>.json con role=superadmin
```

El CLI rechaza ejecución vía HTTP con `if (PHP_SAPI !== 'cli') exit`. Y el directorio `bin/` está bloqueado por `.htaccess`. Defensa en profundidad.

## 4. Flujo: registro público (role=cliente)

```
SPA                                         Server
 │                                            │
 │── POST /acide/ {action: 'public_register',  │
 │     data: {email, password, name}} ────────▶│
 │                                            │
 │                                  rl_check('register', 10)
 │                                  s_email(email)
 │                                  assert_password_strength(password)
 │                                  find_user_by_email → null
 │                                  password_hash(argon2id)
 │                                  data_put('users', u_xxx, {role:'cliente', ...})
 │                                            │
 │◀── {success, data: {id, email, name, role: 'cliente'}}
 │                                            │
```

Tras el registro, el cliente debe hacer **login explícito** — registro no inicia sesión automáticamente (defensa contra registros forzados por XSS).

## 5. Flujo: login

```
SPA                                         Server
 │── GET  (csrf)                              │
 │── POST /acide/ {action:'csrf_token'} ─────▶│
 │◀── Set-Cookie: socola_csrf=<32hex>         │
 │                                            │
 │   (token leído de la cookie, guardado      │
 │    en SynaxisClient.csrfToken)             │
 │                                            │
 │── POST /acide/ {action:'auth_login',       │
 │     data:{email, password}}                │
 │     X-CSRF-Token: <token>  ←── opcional   │
 │        (login está exento de CSRF)        ─▶│
 │                                            │
 │                                  rl_check('login', 5)
 │                                  find_user_by_email(email)
 │                                  password_verify(password, hash)
 │                                  if fail: sleep(1); 401
 │                                  password_needs_rehash → re-hash si toca
 │                                  issue_session():
 │                                    token = random_bytes(32)
 │                                    data_put('sessions', token, {
 │                                      userId, role, ua_hash, ip_at_login,
 │                                      expiresAt
 │                                    })
 │                                  setcookie socola_session (httponly)
 │                                  issue_csrf_token() (rotación)
 │                                            │
 │◀── Set-Cookie: socola_session=<...>; httponly; SameSite=Strict
 │    Set-Cookie: socola_csrf=<...>
 │    {success, data: {user, csrfToken}}     │
 │                                            │
 │  cacheUser(user) → sessionStorage          │
 │  setCsrfToken(csrfToken) → SynaxisClient   │
 │  navigate(role === 'sala'? '/sistema/tpv'  │
 │                         : '/dashboard')    │
```

Código cliente: [`src/services/auth.service.ts:login`](../src/services/auth.service.ts).
Código server: [`server/handlers/auth.php:handle_auth_login`](../server/handlers/auth.php).

## 6. Flujo: validación de sesión en cada request

En [`lib.php:current_user()`](../server/lib.php):

```
1. Leer cookie `socola_session` (o fallback Bearer para tests).
2. data_get('sessions', token) → null si no existe.
3. expiresAt < now → delete(token) + null.
4. ua_hash guardado ≠ sha256(user_agent_actual) → delete + null.
5. data_get('users', session.userId) → null si no existe.
6. Devolver user (sin password_hash).
```

Cualquier acción no-pública en [`index.php`](../server/index.php) exige `current_user() !== null` o devuelve **401**.

## 7. Flujo: CSRF (double-submit) en acciones autenticadas

```
SPA                                         Server
 │   ya tiene cookie socola_csrf + csrfToken  │
 │                                            │
 │── POST /acide/ {action:'update_product'}   │
 │     Cookie: socola_session=<httponly>     │
 │            socola_csrf=<csrf>              │
 │     X-CSRF-Token: <csrf>                  ─▶│
 │                                            │
 │                                  current_user() → user OK
 │                                  validate_csrf_or_die():
 │                                    header === cookie → hash_equals
 │                                    si no: 419
 │                                  → handler
 │                                            │
 │◀── {success, data:...}                      │
```

Si el token expira (cookie borrada / navegador reiniciado), el server devuelve **419**. El cliente llama a `ensureCsrfToken(client)` y reintenta.

## 8. Flujo: rolling session (renovación transparente)

Cada vez que la SPA llama a `get_current_user` (típicamente al cargar la página o al cambiar de ruta), el handler extiende el TTL:

```php
data_put('sessions', $token, ['expiresAt' => date('c', time() + $ttl)]);
setcookie('socola_session', $token, session_cookie_opts($ttl));
```

La sesión sigue viva mientras haya actividad. Si el usuario deja la pestaña cerrada más de `session_ttl_seconds` (24h default), la sesión caduca sola.

## 9. Flujo: logout

```
SPA                                         Server
 │── POST /acide/ {action:'auth_logout'}       │
 │     X-CSRF-Token: <csrf>                   ─▶│
 │                                            │
 │                                  data_delete('sessions', token)
 │                                  setcookie socola_session (expires=-3600)
 │                                  setcookie socola_csrf (expires=-3600)
 │                                  error_log('[auth] logout user=<id>')
 │                                            │
 │◀── {success:true, data:{ok:true}}           │
 │                                            │
 │  setCsrfToken(null)                        │
 │  sessionStorage.removeItem(user_cache)     │
 │  navigate('/')                             │
```

## 10. Recuperación de contraseña (pendiente)

**No implementado aún.** Cuando se añada, el flujo previsto:

```
1. POST action=password_reset_request {email}
   → rate limit (3/hora/IP)
   → si email existe: genera token one-shot (32 bytes, TTL 30min),
     lo guarda en server/data/reset_tokens/<token>.json
     envía email con enlace https://.../reset?t=<token>
   → respuesta SIEMPRE { success: true, data: { sent: true } }
     (sin revelar si el email existía)

2. POST action=password_reset_confirm {token, newPassword}
   → valida token existe y no expirado
   → assert_password_strength(newPassword)
   → password_hash → data_put users
   → data_delete reset_tokens/<token>
   → invalida TODAS las sesiones de ese userId (fuerza re-login)
```

Requisitos previos: envío de email (SMTP/SendGrid/similar), dominio del enlace HTTPS. Ver [ROADMAP.md](ROADMAP.md) Fase 3.

## 11. Bloqueo de cuenta por intentos

**No implementado aún**. Actualmente solo hay rate-limit por IP en login. Para bloqueo por cuenta (después de N fallos del mismo email), sería:

```
user.loginFailedCount++
if (user.loginFailedCount >= 10) user.lockedUntil = now + 15min
```

Trade-off: bloqueo por email abre DoS contra usuarios legítimos (atacante les bloquea la cuenta). Mitigación: requerir captcha tras X fallos en vez de bloquear del todo. Decisión pospuesta hasta tener tráfico real.

## 12. Auditoría

Mínimo implementado:

- `[auth] logout user=<id>` en `error_log`.
- `[synaxis-server] action=<x> err=<msg>` para cualquier error no capturado.

Propuesto (pendiente):

- `[auth] login_success user=<id> ip=<ip>`
- `[auth] login_fail email=<hash> ip=<ip>`
- `[auth] session_expired user=<id>`
- Colección `audit_logs` append-only (server-side) con retención 90 días.

## 13. Consideraciones de UI

Cache de usuario:

- **`sessionStorage`**, no `localStorage`. Se borra al cerrar el navegador → no persiste entre sesiones físicas.
- La SPA debe revalidar con `getCurrentUser(client)` al arrancar. Si falla → redirige a `/login`.
- En la UI pintamos primero el cache (`getCachedUser()`) para UX fluida, luego revalidamos y actualizamos.

Componente `<RequireRole roles={['superadmin','admin']}>` (pendiente de implementar) envolverá las rutas privadas del Dashboard.

## 14. Acciones vinculadas

| Acción | Scope | Handler | Descrito en |
| :-- | :-- | :-- | :-- |
| `csrf_token` | server | `index.php:issue_csrf_token` | §5, §7 |
| `auth_login` | server | `auth.php:handle_auth_login` | §5 |
| `auth_logout` | server | `auth.php:handle_auth_logout` | §9 |
| `auth_refresh_session` | server | `auth.php:handle_auth_session` | §6, §8 |
| `get_current_user` | server | `auth.php:handle_auth_session` | §8 |
| `public_register` | server | `auth.php:handle_public_register` | §4 |

## 15. Pruebas manuales

Suite mínima a ejecutar tras tocar auth:

1. `php bin/create-admin.php` → crear superadmin.
2. Login OK → dashboard carga.
3. Login con password mal → mensaje "Credenciales inválidas". Repetir 5 veces en < 1 min → 429.
4. DevTools: copiar valor de `document.cookie` de `socola_session` → **no debe ser accesible** (httponly). Confirma que no lo lista `document.cookie`.
5. `document.cookie` sí lista `socola_csrf` → correcto (debe ser legible).
6. Modificar `socola_csrf` manualmente → la próxima acción admin devuelve 419.
7. Cambiar el user-agent (otro navegador con misma cookie) → sesión rechazada.
8. Logout → ambas cookies borradas, navigate a `/`.
9. Esperar 24h + 1 min → `get_current_user` devuelve 401.
10. `POST` desde origen no-whitelist → preflight 403.
