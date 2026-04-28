# SECURITY — Políticas y medidas aplicadas

Este documento es la **referencia única** del modelo de seguridad de Socolá. Cada medida lista el archivo donde vive y el motivo. Usable como checklist de auditoría.

## 1. Modelo de amenazas

| Amenaza | Mitigación primaria | Defensa en profundidad |
| :-- | :-- | :-- |
| XSS → robo de sesión | Cookie `socola_session` con `httponly` | CSP `default-src 'none'`, headers `X-Content-Type-Options: nosniff` |
| CSRF | Cookie de sesión `SameSite=Strict` | Double-submit cookie + header `X-CSRF-Token` validado en server |
| Clickjacking | `X-Frame-Options: DENY` | CSP `frame-ancestors 'none'` |
| Enumeración de usuarios | `password_verify` contra hash dummy + `sleep(1)` constante | Rate limit login 5/min por IP |
| Fuerza bruta passwords | Argon2id con parámetros configurables | Rate limit `login` + política mínima 10 chars / 3 clases |
| Robo de cookie de sesión | Fingerprint de user-agent en sesión | Cookie `secure` sobre HTTPS, rolling TTL |
| Path traversal | `s_id()` whitelist `[a-zA-Z0-9_-]` | `realpath()` compara contra `DATA_ROOT` en upload |
| Upload de ejecutable / XSS en SVG | Whitelist MIME **sin SVG** + verificación extensión | Nombre reescrito a hash, chmod 644, dir sin execute |
| Exfiltración de claves | `server/config/*.json` en `.htaccess` deny + `.gitignore` | CSP, `Require all denied` sobre el directorio |
| Listado de directorios | `Options -Indexes` | Apache devuelve 403 a cualquier `/dir/` |
| Prompt injection Gemini | System prompt fijo + reglas "no inventes" | Rate limit IA + trim history + length max |
| Abuso API (DoS aplicativo) | `rl_check` por IP y por scope (`login`, `ai`, `payments`, `sync`) | `LimitRequestBody 10 MB`, CORS restringido |
| Webhook Revolut falsificado | HMAC-SHA256 con `webhook_secret` validado | Logging de firmas inválidas |
| Cadenas temporales | `hash_equals()` constante en comparaciones de CSRF y firmas | |
| Disclosure en errores | `display_errors=0`, respuestas genéricas | `error_log` interno con el mensaje real |

## 2. Cabeceras de seguridad

### En `.htaccess`

Ver [`server/.htaccess`](../server/.htaccess):

```apache
Header always set X-Frame-Options "DENY"
Header always set X-Content-Type-Options "nosniff"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set X-Permitted-Cross-Domain-Policies "none"
Header always set Permissions-Policy "accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(self), usb=()"
Header always set Content-Security-Policy "default-src 'none'; frame-ancestors 'none'"
Header always unset X-Powered-By
Header always unset Server
# HSTS: activar cuando HTTPS esté garantizado
# Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

### En `index.php` (redundancia de defensa)

```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store');
header_remove('X-Powered-By');
```

## 3. Protección de archivos sensibles (.htaccess)

Bloqueados:

- Extensiones: `.env .ini .log .lock .bak .backup .old .sql .sqlite .db .tar .gz .zip .7z .rar .pem .key .crt .p12`.
- Archivos exactos: `gemini.json`, `revolut.json`, `auth.json`, `cors.json` (los `.example` sí se sirven).
- Cualquier **dotfile** (`^\.`): `.env`, `.git`, `.htpasswd`, etc.
- Scripts internos: `lib.php`, `bootstrap.php`.
- Directorios: `handlers/`, `config/`, `data/`, `bin/`.

Listado de directorios: `Options -Indexes -MultiViews`.

Métodos HTTP: solo `GET POST OPTIONS`.

Tamaño máximo de request: `LimitRequestBody 10485760` (10 MB).

HTTPS en producción (descomentar en deploy):

```apache
RewriteCond %{HTTPS} !=on
RewriteCond %{HTTP:X-Forwarded-Proto} !=https
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
```

## 4. CORS

Whitelist explícita en [`server/config/cors.json`](../server/config/cors.json.example). Orígenes no listados reciben 403 en el preflight. Con `allow_credentials: true` la cookie de sesión viaja solo hacia orígenes autorizados.

```json
{
  "allowed_origins": ["https://socola.miaplic.com"],
  "allow_credentials": true,
  "public_actions": ["auth_login", "list_products", "chat_restaurant", ...]
}
```

En dev: añadir `http://localhost:5173`. **No usar `*`** en producción.

## 5. Autenticación

### Hashing

Argon2id con parámetros configurables en [`server/config/auth.json`](../server/config/auth.json.example):

```json
{ "argon2": { "memory_cost": 65536, "time_cost": 4, "threads": 1 } }
```

`password_needs_rehash` comprueba en cada login si hay que re-hashear con parámetros actualizados.

### Política de contraseña

`assert_password_strength` en [`auth.php`](../server/handlers/auth.php):

- Longitud ≥ 10 y ≤ 200.
- Al menos 3 de estas 4 clases: minúsculas, mayúsculas, dígitos, símbolos.
- Rechazo de lista negra mínima (`password`, `12345678`, etc.).

### Sesión

Cookie `socola_session`:

- **httponly**: JS no puede leerla. XSS no la exfiltra.
- **SameSite=Strict**: el navegador nunca la envía en navegación cross-site. CSRF mitigado a nivel de navegador.
- **Secure** (si HTTPS): no viaja por HTTP plano.
- TTL configurable (`session_ttl_seconds`, default 24h).
- **Rolling refresh**: cada `get_current_user` extiende el TTL.
- **Fingerprint**: `ua_hash = sha256(user_agent)` se guarda con la sesión; si cambia, la sesión se invalida (detección básica de robo).

## 6. CSRF

Patrón **double-submit cookie** en [`lib.php`](../server/lib.php):

1. Server emite cookie `socola_csrf` (NO httponly) con token aleatorio de 32 bytes hex.
2. Cliente la lee con JS (ver [`auth.service.ts:ensureCsrfToken`](../src/services/auth.service.ts)) y la envía en `X-CSRF-Token` en cada POST.
3. Server compara cookie vs header con `hash_equals()` (constant-time).
4. Si no coinciden → **HTTP 419**.

Exentos (con justificación):

- `auth_login`, `public_register`: aún no hay sesión.
- `revolut_webhook`: viene de Revolut, autenticado por HMAC.
- `process_external_order`, `get_table_order`, `table_request`, `chat_restaurant`, `validate_coupon`, `get_mesa_settings`, `get_payment_settings`, `list_products`: flujo anónimo (cliente sin cuenta).
- `csrf_token`, `health_check`.

## 7. Rate limiting

Archivo plano por IP+scope en `server/data/_rl/<scope>/<ip>.json`, ventana de 1 minuto. Helper: `rl_check($scope, $limitPerMinute)` en [`lib.php`](../server/lib.php).

| Scope | Límite/min |
| :-- | :-- |
| `login` | 5 |
| `register` | 10 |
| `ai` | 30 (anon), 120 (auth) |
| `payments` | 20 (anon), 120 (auth) |
| `sync` | 60 |

En exceso → **HTTP 429**.

## 8. Autorización por rol

`require_role($user, [roles])` en [`lib.php`](../server/lib.php):

| Acción | Roles |
| :-- | :-- |
| `upload` | `superadmin`, `administrador`, `admin`, `editor` |
| `synaxis_sync` | todos los roles internos + administrativos |
| `update_table_cart`, `clear_table`, `get_table_requests`, `acknowledge_request` | `superadmin`, `admin`, `sala`, `cocina`, `camarero` |

El resto de acciones `server` requieren al menos sesión válida (`current_user() !== null`). Las acciones públicas (ver `cors.json → public_actions`) son anónimas.

Rol **nunca** se acepta desde el body: `public_register` fuerza `role: 'cliente'` e ignora lo que envíe el cliente. Para crear otros roles → `server/bin/create-admin.php` (CLI) o panel de admin con sesión superadmin.

## 9. Upload seguro

Ver [`upload.php`](../server/handlers/upload.php):

- Solo roles admin/editor (gate en index).
- Límite 8 MB.
- MIME por **sniff del contenido** (`mime_content_type`), no `$_FILES['type']` (es atacante-controlado).
- Whitelist: `jpeg`, `png`, `webp`, `gif`. **SVG BLOQUEADO** (XSS en SVG con JS embebido).
- Extensión de nombre debe coincidir con el MIME — previene `foo.jpg.php`.
- Nombre final: `sha256(contenido)[:24] + ext`. El nombre original se descarta.
- `is_uploaded_file()` + `realpath()` + `str_starts_with($real, DATA_ROOT)` impide escritura fuera del directorio media.
- Idempotencia: mismo hash reutiliza el archivo existente.

## 10. Validación y sanitización

Helpers en [`lib.php`](../server/lib.php):

- `s_id(string)`: whitelist `[a-zA-Z0-9_-]`, rechaza `..`. Se aplica a toda clave de colección/id antes de construir rutas.
- `s_str(?string, max)`: trim, quita NULs, trunca.
- `s_email(?string)`: lowercase + `FILTER_VALIDATE_EMAIL` + max 254 chars.
- `s_int($v, min, max)`: cast + rango.

Escape XSS: no aplicable al server (solo devuelve JSON). En el cliente, React escapa por defecto todo lo interpolado; nunca usar `dangerouslySetInnerHTML` sin sanitizador.

## 11. Secretos

Ver [SECRETS.md](SECRETS.md). Resumen:

- API keys viven en `server/config/*.json`. Denegadas por `.htaccess`. Excluidas de git por `server/config/.gitignore`.
- JWT secret, webhook secret: igual ubicación.
- Password hashes: `server/data/users/<id>.json`, nunca salen al cliente.
- El cliente JAMÁS guarda tokens en `localStorage`. Solo la cookie httponly (inaccesible a JS) contiene el token real.

## 12. TLS / HTTPS

En producción es **obligatorio**. El checklist de deploy ([SECRETS.md §"Checklist antes de desplegar"](SECRETS.md)):

- [ ] Redirección 301 HTTP → HTTPS activa en `.htaccess`.
- [ ] HSTS activo: `Strict-Transport-Security: max-age=31536000; includeSubDomains`.
- [ ] Certificado TLS válido (Let's Encrypt / similar), auto-renovación.
- [ ] Cookies `Secure: true` (el helper `session_cookie_opts` ya lo hace si `$_SERVER['HTTPS']` está set).

## 13. Defensa en profundidad del cliente

En [`src/synaxis/SynaxisClient.ts`](../src/synaxis/SynaxisClient.ts):

- `credentials: 'include'` → la cookie httponly viaja automáticamente, pero solo a orígenes permitidos por CORS.
- `X-CSRF-Token` inyectado en cada POST si hay token.
- En HTTP 419 (CSRF inválido) → el cliente limpia su token y forzará `ensureCsrfToken` en el próximo ciclo.

En [`src/services/auth.service.ts`](../src/services/auth.service.ts):

- Cache de user en **`sessionStorage`**, nunca `localStorage`. Se borra al cerrar el navegador.
- El cache es solo un atajo de UX; la SPA siempre re-valida contra `get_current_user` al arrancar.

## 14. IndexedDB (datos locales)

- **No es un vault seguro**. Cualquier extensión o script en la página lo puede leer. Regla: **no** guardar ahí PII sensible, passwords, tokens, o números de tarjeta.
- Actualmente cacheamos: productos, agente_restaurante (vault y settings), restaurant_zones. Todo es contenido público o del catálogo.
- Si en el futuro se guardan conversaciones privadas del usuario, evaluar cifrado en reposo con clave derivada de la sesión (fuera del alcance de v0).

## 15. Logs

- `error_log('[scope] ...')` nunca debe incluir: passwords, tokens, API keys, bodies completos.
- El audit mínimo implementado: logout (`[auth] logout user=<id>`), errores por acción (`[synaxis-server] action=<x> err=<msg>`).
- Mover `php_error.log` fuera del webroot en producción.

## 16. Checklist de auditoría

Verificable archivo a archivo:

- [ ] `.htaccess` protege dotfiles, config, handlers, data, bin.
- [ ] `config/*.json` tiene permisos `600` en servidor real.
- [ ] `cors.json` sin `*` en `allowed_origins` en prod.
- [ ] HTTPS redirect activo.
- [ ] HSTS activado tras verificar que todo funciona en HTTPS.
- [ ] `create-admin.php` ejecutado para crear al menos un superadmin.
- [ ] Contraseñas por defecto cambiadas.
- [ ] `webhook_secret` Revolut configurado y dado de alta en su panel.
- [ ] Rate limits probados con `ab` o `wrk` sencillo.
- [ ] `error_log` revisado, sin filtrado de secretos.

## 17. Reporte de vulnerabilidades

Si encuentras una vulnerabilidad, abre un issue **privado** al equipo. No publicar detalles técnicos hasta que haya parche desplegado.
