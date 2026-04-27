# AxiDB Auth — Especificacion formal v1.0

**Estado**: parte de Fase 3 (consolidada en v1.0). Cubre login, logout, sesion, gestion de usuarios y roles.

Este documento describe el contrato. La implementacion vive en [`axidb/auth/`](../../auth/) y los Ops en [`axidb/engine/Op/Auth/`](../../engine/Op/Auth/).

---

## 1. Modelo

```
users (collection)        roles (collection)
├── _id (= user.id)       ├── _id (= role.name)
├── email (unique)        ├── name
├── password_hash         ├── permissions: string[]   ej. "products:read"
├── name                  ├── created_at
├── role: string          └── updated_at
├── status: active|disabled
├── created_at
└── updated_at

sessions (filesystem)     # `STORAGE/sessions/<token>.json`
├── token
├── user_id
├── created_at
└── expires_at
```

- Usuarios y roles son **colecciones globales** del `master` storage (no per-project). Ver [`CRUDOperations.php:11-22`](../../engine/CRUDOperations.php#L11-L22).
- Las contrasenas se guardan con `password_hash($pwd, PASSWORD_DEFAULT)` (bcrypt).
- Tokens de sesion = `bin2hex(random_bytes(32))` (256 bits). TTL por defecto 24 h, configurable.

---

## 2. Ops del catalogo

| Op            | Entrada                        | Salida                    | Codigo en error      |
| ------------- | ------------------------------ | ------------------------- | -------------------- |
| `auth.login`  | `{email, password}`            | `{token, user}`           | UNAUTHORIZED         |
| `auth.logout` | `{token}` (o cookie)           | `{logged_out: true}`      | UNAUTHORIZED         |
| `auth.create_user` | `{email, password, name?, role?}` | `{user}` sin password | VALIDATION_FAILED, CONFLICT |
| `auth.grant_role`  | `{user_id, role}`         | `{user}`                  | DOCUMENT_NOT_FOUND   |
| `auth.revoke_role` | `{user_id, role}`         | `{user}`                  | DOCUMENT_NOT_FOUND   |

Cada Op es ejecutable via los 4 transports (PHP embebido, HTTP JSON, AxiSQL via `Sql.php`, CLI `axi auth.login --email ...`). Documentacion generada en [`docs/api/auth_*.md`](../api/).

---

## 3. Flujo HTTP

### Login

```http
POST /axidb/api/axi.php
Content-Type: application/json

{"op":"auth.login","email":"a@b.com","password":"secret"}
```

Respuesta exitosa:

```json
{
  "success": true,
  "data": {
    "token": "deadbeef...",
    "user":  {"id":"u_xyz","email":"a@b.com","role":"admin","name":"..."}
  },
  "duration_ms": 12.4
}
```

Tras login, el endpoint setea `Set-Cookie: acide_session=<token>; HttpOnly; SameSite=Lax`. Llamadas posteriores autentican via:

1. Cookie `acide_session` (cliente browser).
2. Header `Authorization: Bearer <token>` (cliente API).

### Logout

```http
POST /axidb/api/axi.php
{"op":"auth.logout"}
```

Borra la cookie y la sesion del filesystem.

---

## 4. Validacion de request

`Auth::validateRequest()` se ejecuta antes del dispatcher salvo en la **public allowlist** de Ops. Siempre publicos:

```
ping, describe, schema, help, sql (read-only),
auth.login, auth.logout
```

Las acciones legacy `{action: list_products, ...}` mantienen su propia allowlist en [`CORE/index.php:52`](../../../CORE/index.php#L52). Cualquier Op fuera de las dos allowlists exige sesion valida.

---

## 5. Roles y permisos

### Roles internos

| Rol           | Acceso                                                              |
| ------------- | ------------------------------------------------------------------- |
| `superadmin`  | Todo. Saltea `validateRequest`. Solo se crea via CLI `setup.php`.   |
| `administrador` | CRUD total + dashboard + Tab Agents.                              |
| `editor`      | CRUD + cambios de schema. Sin matar agentes ni vault.               |
| `cliente` / `client` / `standard` / `pro` / `premium` | Lectura de catalogo + sus propios docs. |
| `sala`, `cocina`, `camarero` | TPV/operativa, sin Vault ni gestion de schema.       |
| `estudiante`  | Acceso a Academy.                                                   |

Permisos finos via `roles[<role>].permissions: ["products:read", "products:write", ...]`.
Comprobacion: `Auth::can($user, 'products:write')`.

### Auth en la consola web

[`gateway.php:37-47`](../../../gateway.php#L37-L47) define que zonas SPA acepta cada rol (`/dashboard`, `/sistema`, `/admin`, `/academy`, `/editor`). El gate corta antes de servir el HTML — el rol se valida en backend, no en JS.

---

## 6. Auth en agentes (Fase 6)

Los agentes IA **no tienen credenciales de usuario**. Su autorizacion es:

- **Sandbox por Op** (`agent.tools`) — el Toolbox rechaza con `FORBIDDEN` si la Op no esta declarada en `tools`. Ver [`agent-protocol.md` §5](agent-protocol.md).
- **Audit log con `actor: agent:<id>`** — toda ejecucion queda trazada en `STORAGE/_system/agents/_audit.log`.
- **Kill switch global** — emergencia: `ai.kill_agent all=true` corta todo agente vivo y bloquea futuros runs.

Si en el futuro se quieren correr agentes "como usuario X", la solucion v1.1 es anadir `agent.runas_user_id` y validar con `Auth::can(...)` en el Toolbox antes del despacho. **No esta en v1**.

---

## 7. Bootstrap del primer admin

```bash
# CLI: crea el primer superadmin (interactivo)
php axidb/auth/setup.php
# o non-interactive:
php axidb/auth/create_superadmin.php --email a@b.com --password secret --name "Admin"
```

Tras el bootstrap, futuros usuarios se crean con `auth.create_user` desde una sesion admin. Auth se inicializa solo (sin migraciones).

---

## 8. Errores normalizados

| Codigo          | Cuando                                                            |
| --------------- | ----------------------------------------------------------------- |
| UNAUTHORIZED    | Login fallido, token caducado, sesion no encontrada.              |
| FORBIDDEN       | Sesion valida pero rol/permiso insuficiente, o sandbox de agente. |
| CONFLICT        | `auth.create_user` con email duplicado.                           |
| VALIDATION_FAILED | email/password vacios, formato incorrecto.                      |
| DOCUMENT_NOT_FOUND | grant/revoke sobre user_id inexistente.                        |

---

## 9. Ver tambien

- [`op-model.md`](op-model.md) — base de los Ops.
- [`wire-protocol.md`](wire-protocol.md) — formato HTTP completo.
- [`agent-protocol.md`](agent-protocol.md) — sandbox de agentes.
- [`../../auth/AUTHENTICATION_FLOW.md`](../../auth/AUTHENTICATION_FLOW.md) — diagramas de flujo internos.
- [`../../auth/README.md`](../../auth/README.md) — guia de operacion.
