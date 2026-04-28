# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Fresh rewrite of the Socolá app (café/restaurant: carta, TPV, QR mesas, checkout, academia). React + Vite + TypeScript SPA with **SynaxisCore** (client-side JSON database in IndexedDB) handling most actions. A thin PHP `server/` handles only what can't live in the browser (real auth, payment webhooks, AI proxy, file upload, multi-device sync).

This is the clean project. The parent directory (`../`) is the legacy ACIDE PHP monolith — kept for reference, not modified. See [../SYNAXIS_MIGRATION.md](../SYNAXIS_MIGRATION.md) for the full migration plan.

**Primary documentation lives in [docs/](docs/)** — start there for anything non-trivial. The full index is [docs/README.md](docs/README.md).

## Commands

```bash
npm install        # first time
npm run dev        # dev server at :5173, proxies /acide/* to SOCOLA_API
npm run build      # tsc + vite build → dist/
npm run preview    # preview dist/ locally
npm run typecheck  # tsc -b --noEmit
```

Override the API target during dev: `SOCOLA_API=http://host:port npm run dev`.

**There is no test runner wired up yet** — don't invent one. If tests become needed, add Vitest (Vite-native) for the client and PHPUnit for the server.

## Architecture: the three layers

```
┌─────────────── React pages/components ────────────────┐
│  Never call fetch directly, never touch SynaxisCore   │
└────────────────────────┬──────────────────────────────┘
                         │ client.execute({action, ...})
                         ▼
┌─────────────── SynaxisClient (src/synaxis/) ──────────┐
│  decides transport by action scope:                   │
│   local  → SynaxisCore (IndexedDB, no network)        │
│   server → POST /acide/index.php + cookie + CSRF      │
│   hybrid → local first, HTTP fallback + cache write   │
└──────────┬────────────────────────────┬───────────────┘
           ▼                            ▼
    SynaxisCore                  server/index.php
    (browser DB)                 (thin PHP)
```

**The single rule that holds the architecture together**: every action's transport is decided by its `scope` in [src/synaxis/actions.ts](src/synaxis/actions.ts). React code must only talk to `SynaxisClient` (via `useSynaxisClient()`). No `fetch` in pages/services. No direct `SynaxisCore` calls either.

## The action contract

```ts
// request
{ action: string, collection?: string, id?: string, data?: {...}, params?: {...} }
// response
{ success: boolean, data: T | null, error: string | null }
```

When adding a new action:
1. Add an entry to [src/synaxis/actions.ts](src/synaxis/actions.ts) with its `scope` and `domain`.
2. If `local` or `hybrid`: SynaxisCore already handles generic CRUD — no code needed unless it's a custom operation.
3. If `server`: implement the handler in [server/handlers/](server/handlers/) and register it in [server/index.php](server/index.php)'s switch. Add it to the CSRF exemption list only if it's truly stateless or pre-auth.
4. Create a service wrapper in `src/services/<domain>.service.ts` so pages call a typed function instead of raw `client.execute`.
5. Document in [docs/DATA_MODEL.md](docs/DATA_MODEL.md) if it introduces a new collection.

## Security model (post-audit 2026-04-17)

**Full reference**: [docs/SECURITY.md](docs/SECURITY.md).

Quick summary of load-bearing rules:

- **CORS**: whitelist in [server/config/cors.json](server/config/cors.json.example). Never `*` in production.
- **Sessions**: cookie `socola_session` is `httponly` + `SameSite=Strict` + `Secure` (HTTPS). JS **cannot** read it — XSS cannot exfiltrate the token.
- **CSRF**: double-submit cookie. `socola_csrf` cookie (NOT httponly, JS reads it) is echoed in `X-CSRF-Token` header. Server compares with `hash_equals()`. Mismatch → **HTTP 419**.
- **Passwords**: Argon2id. Policy: ≥10 chars + ≥3 classes. `password_needs_rehash` on each login.
- **Rate limit**: per IP+scope in [server/lib.php:rl_check](server/lib.php). Default buckets: `login` 5/min, `ai` 30-120/min, `payments` 20-120/min, `sync` 60/min.
- **Role gating**: `require_role($user, [roles])` in [server/lib.php](server/lib.php). Called from [index.php](server/index.php) per action.
- **Upload**: SVG BLOCKED (XSS risk). MIME sniff + extension cross-check + hash-based filename.
- **Sanitization**: `s_id`, `s_email`, `s_str`, `s_int` in [lib.php](server/lib.php) — `s_id` prevents path traversal on any key that becomes a filename.

Never commit `server/config/*.json` (real secrets). Only `.example` files. `.gitignore` enforces.

## SynaxisCore internals (src/synaxis/)

- **SynaxisStorage.ts** — IndexedDB adapter with promise-chain mutex to prevent VersionError on parallel `ensureStore` calls.
- **SynaxisQuery.ts** — mirror of the legacy PHP `QueryEngine`. Operators: `=`, `==`, `!=`, `>`, `<`, `>=`, `<=`, `IN`, `contains`. Plus `search`, `orderBy`, `limit`, `offset`.
- **SynaxisCore.ts** — CRUD dispatcher. Every write adds `_version`, `_createdAt`, `_updatedAt`. Flag `_REPLACE_: true` forces replace-instead-of-merge. Master collections (`users`, `roles`, `projects`, `system_logs`) go to separate IndexedDB (`<namespace>__master`).
- **SynaxisClient.ts** — facade. Route by scope. Injects `X-CSRF-Token`. Handles HTTP 419 by clearing the csrf token.
- **Versioning**: each update snapshots the previous doc in `<collection>__versions` with key `<id>@<version>`. Last 5 kept.
- **Oplog**: every write appends to `__oplog__` collection (for Fase 3 sync). `core.drainOplog()` / `core.clearOplog(ids)`.

## Seed data

[public/seed/bootstrap.json](public/seed/bootstrap.json) is auto-imported on first load if collections are empty (logic in [src/hooks/useSynaxis.ts](src/hooks/useSynaxis.ts)). Contains 42 products, `tpv_settings`, 2 agents (Maître + camarero), 3 vault entries, 1 restaurant zone with 3 tables, and payment_settings. All from the legacy `../STORAGE/` corpus.

## Router zones

[src/App.tsx](src/App.tsx):

- `/`, `/carta`, `/nosotros`, `/contacto`, `/academia`, `/login` — `PublicLayout`.
- `/dashboard/*`, `/sistema/tpv/*` — private admin, no public layout.
- `/mesa/:slug` — QR table page for customers.

**Auth gating in the router is NOT yet implemented**. When wiring it (see [docs/ROADMAP.md Fase 3](docs/ROADMAP.md)), use a `<RequireRole roles={...}>` component that calls `getCurrentUser(client)` and redirects to `/login` on 401.

## server/ — thin PHP

Only reachable via `/acide/*` (see [server/.htaccess](server/.htaccess), which is the hardened version post-audit). Dispatcher in [server/index.php](server/index.php) has a hard allowlist of actions; any action not on it gets **400 "resolver en cliente"**. This rejection is intentional and load-bearing.

Handlers implemented (with real logic):

- [handlers/auth.php](server/handlers/auth.php) — login, logout, register, session refresh.
- [handlers/ai.php](server/handlers/ai.php) — Gemini proxy + Maître prompt assembly + auto vault.
- [handlers/payments.php](server/handlers/payments.php) — Revolut orders + check + webhook HMAC.
- [handlers/qr.php](server/handlers/qr.php) — table carts with race-safe merge, requests queue.
- [handlers/upload.php](server/handlers/upload.php) — hardened: no SVG, hash filename, MIME cross-check.
- [handlers/sync.php](server/handlers/sync.php) — LWW push (pull pending for Fase 3).
- [handlers/reservas.php](server/handlers/reservas.php) — create reserva.

Utilities in [server/lib.php](server/lib.php): `data_put/get/delete/all` (JSON file + flock), CSRF helpers, `rl_check`, sanitization, `current_user`, `require_role`, `http_json` (curl).

CLI: [server/bin/create-admin.php](server/bin/create-admin.php) — bootstrap first superadmin. Refuses HTTP invocation.

## Doing tasks — project-specific rules

- **Never call `fetch` directly from a page or component.** Always through `SynaxisClient`. If a service needs a new action, add it to the catalog first.
- **Never import from `SynaxisCore` directly from pages/services.** Go through `SynaxisClient` so scope routing applies uniformly.
- **Never store auth tokens in `localStorage`.** The httponly cookie is the only token store. Only user profile caches may go in `sessionStorage` (never `localStorage`).
- **Never use `dangerouslySetInnerHTML`** without a sanitizer (`dompurify`). React's default escaping is our XSS defense.
- **Never put secrets in `payment_settings` or any client-visible JSON.** Keys belong in `server/config/*.json`.
- **Never accept `role` from a request body** in auth. Bootstrap via CLI; promote via admin UI (authenticated, audited).
- **Don't add features to the legacy `../CORE/`** — it's archived.
- **When porting a legacy handler**, write a service wrapper + action catalog entry first; then implement the server handler if and only if it must be server.
- **Don't add build tooling** (Webpack, Babel, PostCSS plugins) unless asked. Vite handles it.
- **The parent repo's pages** (`../carta.html`, `../index.html`, `../login.html`) are legacy. Do NOT edit them — those are deprecated in favor of React routes here.
- **When editing `.htaccess`**, preserve the "deny by default" stance. Any new rule goes in addition to existing deny blocks, never replacing them.
- **When editing a handler**, verify the `$user` parameter is threaded correctly and `require_role()` is called for privileged ops.

## Not yet built / known gaps (from the audit)

- `Checkout`, `TPV`, `MesaQR`, `Dashboard`, `Academia` pages are stubs. They render but don't implement the flows.
- Password reset flow (forgot-password) — requires SMTP. Sketched in [docs/USERS.md §10](docs/USERS.md#10-recuperación-de-contraseña-pendiente).
- `synaxis_sync` pull is a placeholder — only push applies. Fase 3.
- No test runner.
- Router `<RequireRole>` component pending.
- `audit_logs` collection not created yet; only ad-hoc `error_log` lines.

## Registro de cambios, auditoría 2026-04-17

**Seguridad**:
- Reescritura completa de [server/.htaccess](server/.htaccess) con headers (CSP, X-Frame-Options DENY, X-Content-Type-Options, Referrer-Policy, Permissions-Policy), deny de dotfiles, config/, handlers/, data/, bin/, extensiones sensibles, métodos HTTP restringidos a GET/POST/OPTIONS, LimitRequestBody 10MB.
- [server/index.php](server/index.php) reescrito con CORS whitelist desde `config/cors.json`, emisión de CSRF, validación `current_user` + `require_role` por acción, exención explícita documentada.
- [server/lib.php](server/lib.php) ampliado con `rl_check`, `issue_csrf_token`, `validate_csrf_or_die`, `require_role`, `session_cookie_opts`, `s_id/s_email/s_str/s_int`, `current_user` con fingerprint UA.
- [server/handlers/auth.php](server/handlers/auth.php) reescrito: dummy hash para tiempo constante, rate limit login, rolling TTL, `password_needs_rehash`, política de contraseña, logout con audit.
- [server/handlers/upload.php](server/handlers/upload.php): SVG bloqueado, MIME sniff, extensión cross-check, hash filename, realpath check.
- Todos los handlers (`ai`, `payments`, `qr`, `sync`) reciben `?array $user` y llaman `rl_check` con su scope.

**Cliente**:
- [src/services/auth.service.ts](src/services/auth.service.ts) reescrito: sin `localStorage`, cache solo `sessionStorage`, `ensureCsrfToken` al arrancar, `login/logout/register/getCurrentUser/getCachedUser`.
- [src/synaxis/SynaxisClient.ts](src/synaxis/SynaxisClient.ts): reemplazado `authToken`/`Authorization` por `csrfToken`/`X-CSRF-Token`. Manejo de 419.

**Operativa**:
- [server/bin/create-admin.php](server/bin/create-admin.php) creado.
- [server/config/cors.json.example](server/config/cors.json.example) creado.

**Documentación**:
- Nuevos: [docs/SECURITY.md](docs/SECURITY.md), [docs/USERS.md](docs/USERS.md), [docs/PROJECT.md](docs/PROJECT.md), [docs/ROADMAP.md](docs/ROADMAP.md).
- Actualizados: [docs/README.md](docs/README.md), este [CLAUDE.md](CLAUDE.md).

## Decisiones técnicas tomadas en esta auditoría

1. **Double-submit CSRF (cookie + header) en vez de sesión-bound token**. Motivo: funciona sin estado adicional del lado server, es el patrón más simple que ofrece la defensa exigida, y es el estándar de facto para SPAs.
2. **Cookie httponly como única fuente de verdad del token**. Motivo: elimina la superficie de ataque XSS → robo de token. La SPA NO necesita el token para nada (el navegador lo envía).
3. **`sessionStorage` para cache de perfil, no `localStorage`**. Motivo: se borra al cerrar navegador, no persiste entre sesiones físicas, reduce ventana de abuso si el dispositivo se comparte.
4. **Rate limit en archivo plano, no en Redis**. Motivo: mantiene la filosofía "sin dependencias externas". Cuando el volumen lo exija, migrar a Redis es mecánico.
5. **SVG bloqueado en uploads**. Motivo: puede contener `<script>` y causar XSS. Reactivable solo tras pipeline de sanitización (fuera de alcance v0).
6. **Roles hardcoded en whitelist, no dinámicos**. Motivo: ambigüedad menor, seguridad mayor. Si hacen falta roles custom, se añaden explícitamente en `auth.json`.
7. **`require_role` server-side es la única fuente de verdad**. El rol en IndexedDB del cliente es decorativo (para la UI); jamás usado para permisos reales.
8. **Rolling TTL en lugar de refresh tokens**. Motivo: simplicidad operativa; para una app de un solo dominio sin apps móviles, refresh tokens añaden complejidad sin valor.
9. **No implementar bloqueo de cuenta por intentos aún**. Motivo: abre DoS contra usuarios legítimos. Preferido: captcha + rate limit por IP. Decisión aplazada hasta tener tráfico real que medir.
