# Secretos y configuración sensible

Regla dura: **ningún secreto vive en el navegador**. API keys, webhook secrets, JWT secrets, password hashes — todo en `server/config/` o `server/data/`, que están protegidos por `.htaccess` y nunca se sirven directamente.

## Ubicaciones

| Qué | Dónde | ¿En git? |
| :-- | :-- | :-- |
| API key de Gemini / Google | [`server/config/gemini.json`](../server/config/gemini.json.example) | ❌ (sí el `.example`) |
| API key de Revolut | [`server/config/revolut.json`](../server/config/revolut.json.example) | ❌ (sí el `.example`) |
| Webhook secret Revolut | `server/config/revolut.json` → `webhook_secret` | ❌ |
| JWT secret + Argon2 params | [`server/config/auth.json`](../server/config/auth.json.example) | ❌ (sí el `.example`) |
| Password hashes Argon2id | `server/data/users/<id>.json` | ❌ |
| Sesiones activas | `server/data/sessions/<token>.json` | ❌ |

El [`.gitignore`](../server/config/.gitignore) del directorio `server/config/` es:

```gitignore
*.json
!*.example
```

De modo que **cualquier `.json` queda fuera de git salvo los templates `.example`**.

## Cómo inicializar el server en producción

```bash
cd server/config
cp gemini.json.example  gemini.json   && edit gemini.json   # rellenar api_key
cp revolut.json.example revolut.json  && edit revolut.json  # api_key + mode + webhook_secret
cp auth.json.example    auth.json     && edit auth.json     # generar jwt_secret (256 bits)
chmod 600 *.json
```

Y en `server/.htaccess` (ya está):

```apache
<FilesMatch "\.(env|log|json|lock)$">
    Require all denied
</FilesMatch>
```

Deniega servir cualquier `.json` directamente por HTTP.

## Qué NO debe ocurrir

- ❌ `payment_settings` en IndexedDB **nunca** contiene `api_key`. Solo flags (`active: true`, `mode: "sandbox"`). El secreto real vive en `server/config/revolut.json` y el handler lo añade al `Authorization` antes de llamar a Revolut.
- ❌ `agent_config` en IndexedDB **no** contiene la API key de Gemini. El prompt se construye en el server con la carta + notas internas + la clave oculta.
- ❌ `users` en IndexedDB (master collection) **no** contiene `password_hash`. Solo el perfil público: id, email, name, role, tenantId.
- ❌ El `authToken` (bearer) del usuario **no** se escribe en IndexedDB. Se guarda en `localStorage` porque es de sesión y queremos que expire con el navegador. Si prefieres cookies httponly para defensa extra contra XSS, cambia `auth.service.ts` para no guardar el token en JS.

## Generar secretos

```bash
# JWT secret (256 bits)
openssl rand -hex 32

# Webhook secret Revolut (256 bits)
openssl rand -hex 32
```

En Windows PowerShell:

```powershell
[Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
```

## Rotación

Cambiar una API key:

1. Editar `server/config/<name>.json`.
2. Reiniciar PHP si hay opcode cache agresivo (`opcache_reset()` o restart Apache).

Ningún caché del cliente necesita invalidarse — el cliente no conoce el secreto.

Cambiar el `jwt_secret`:

1. Editar `server/config/auth.json`.
2. **Invalida todas las sesiones existentes** — todos los usuarios tienen que volver a hacer login. Considera mantener la clave antigua durante un grace period si necesitas transición suave.

## Datos sensibles en logs

- `error_log('[synaxis-server] ...')` en [`server/index.php`](../server/index.php) no debería incluir payloads.
- Si usas `error_log(json_encode($req))` para debugging, **bórralo** antes de producción — puede loguear passwords o tarjetas.
- Apache `error.log` no registra request bodies por defecto; si activas `mod_dumpio`, asegúrate de excluir `/acide/*`.

## Qué hacer si se filtra una clave

1. **Revocar inmediatamente** desde el panel del proveedor (Revolut Business / Google Cloud Console).
2. Generar nueva clave.
3. Actualizar `server/config/<name>.json` y desplegar.
4. Buscar en logs usos de la clave antigua para entender el alcance.
5. Si era `webhook_secret`, regenera también en el panel de Revolut y desplega ambos lados a la vez.

## CORS y origen

[`server/index.php`](../server/index.php) actualmente permite `Access-Control-Allow-Origin: *` para simplificar el dev. En producción, restringir a los dominios legítimos:

```php
$allowed = ['https://socola.miaplic.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}
```

Las acciones públicas (`auth_login`, `list_products`, `chat_restaurant`, `validate_coupon`, `get_payment_settings`) pueden ser más laxas si sirves la carta embeddable desde terceros. Valora caso por caso.

## Checklist antes de desplegar

- [ ] Los 3 `server/config/*.json` existen y tienen claves reales.
- [ ] `.htaccess` del server deniega `.json` por HTTP.
- [ ] `mode: "sandbox"` cambiado a `"live"` en `revolut.json`.
- [ ] `webhook_secret` configurado en Revolut panel y en `revolut.json` con el mismo valor.
- [ ] URL del webhook dada de alta en Revolut: `https://socola.miaplic.com/acide/?action=revolut_webhook`.
- [ ] `jwt_secret` rotado desde el valor del ejemplo.
- [ ] `CORS` restringido al dominio real.
- [ ] HTTPS obligatorio en Apache (`RewriteCond %{HTTPS} !=on` + redirect a `https://`).
- [ ] `error_log` no contiene payloads.
- [ ] Al menos 1 superadmin creado con `php server/bin/create-admin.php` *(pendiente de implementar — es el único flujo CLI necesario)*.
