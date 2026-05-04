# Synaxis server — handlers

Cada handler implementa **solo las acciones que no pueden vivir en el navegador**. Si puedes resolverlo con `SynaxisCore` (IndexedDB), no va aquí.

Los handlers exportan funciones sueltas (`handle_*`). No hay clases, no hay DI. El archivo [index.php](../index.php) los incluye bajo demanda según la `$action`.

## Stubs pendientes

Estos archivos están referenciados desde [index.php](../index.php) pero aún no existen. Portar desde el ACIDE original (`CORE/core/handlers/*`) manteniendo solo lo mínimo:

| Archivo | Funciones | Origen a portar |
| :-- | :-- | :-- |
| `auth.php` | `handle_auth_login`, `handle_auth_session`, `handle_public_register` | `CORE/auth/Auth.php` + `UserManager.php` |
| `payments.php` | `handle_payment($action, $req)` | `CORE/core/handlers/StoreHandler.php` (sección Revolut) |
| `ai.php` | `handle_ai($action, $req)` | `CORE/chat_gemini.php` + connectors `GroqConnector`, `OllamaConnector` |
| `upload.php` | `handle_upload($files)` | `CORE/core/handlers/SystemHandler.php::upload` |
| `sync.php` | `handle_sync($req)` | **nuevo** — consume `oplog` del cliente, hace push/pull |
| `qr.php` | `handle_qr($action, $req)` | `CORE/core/handlers/QRHandler.php` |
| `reservas.php` | `handle_reserva($req)` | `CORE/core/handlers/ReservasHandler.php` |

## Almacenamiento del servidor

El server adelgazado guarda sólo lo indispensable en `server/data/`:

- `users/<id>.json` — hashes bcrypt/Argon2.
- `sessions/<token>.json` — sesiones activas.
- `orders/<id>.json` — pedidos confirmados (la cola multi-dispositivo).
- `oplog/` — log de operaciones sincronizadas.
- `config/revolut.json`, `config/gemini.json` — secretos (chmod 600, fuera del webroot via `.htaccess`).

El contenido completo (catálogo, temas, reservas, cursos) vive en el cliente. El server solo guarda lo que **el navegador no puede**.
