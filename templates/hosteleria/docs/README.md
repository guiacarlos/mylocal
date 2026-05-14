# Documentación Socolá

Índice canónico de la documentación técnica. Cada archivo es autónomo; los enlaces cruzados te llevan al detalle.

## Orden de lectura sugerido

1. [PROJECT.md](PROJECT.md) — **finalidad del proyecto**, problema, objetivos, arquitectura general, plan.
2. [ACIDE.md](ACIDE.md) — filosofía y estructura de SynaxisCore (la librería JS que reemplaza al ACIDE PHP).
3. [DATA_MODEL.md](DATA_MODEL.md) — esquema de las 16 colecciones JSON.
4. [SECURITY.md](SECURITY.md) — **modelo de amenazas y medidas aplicadas** (auditable por archivo).
5. [USERS.md](USERS.md) — flujos completos de registro, login, sesión, logout, roles.
6. [SECRETS.md](SECRETS.md) — dónde viven las claves y cómo rotarlas.
7. [AGENTS.md](AGENTS.md) — Maître / camarero IA (3 capas: vault → catálogo → Gemini).
8. [PAYMENTS.md](PAYMENTS.md) — integración Revolut completa.
9. [QR.md](QR.md) — generación de QR de mesas y flujo de comanda.
10. [ROADMAP.md](ROADMAP.md) — **fases, prioridades, riesgos, decisiones abiertas**.

## Referencia cruzada rápida

| Quiero… | Archivo | Código |
| :-- | :-- | :-- |
| Entender qué resuelve el proyecto | [PROJECT.md](PROJECT.md) | — |
| Añadir una acción nueva | [ACIDE.md §"Cómo añadir una acción"](ACIDE.md) | [`src/synaxis/actions.ts`](../src/synaxis/actions.ts) |
| Saber qué contiene una colección | [DATA_MODEL.md](DATA_MODEL.md) | [`src/types/domain.ts`](../src/types/domain.ts) |
| Revisar un cambio de seguridad | [SECURITY.md §checklist](SECURITY.md#16-checklist-de-auditoría) | [`server/`](../server/) |
| Entender el flujo de login | [USERS.md §5](USERS.md#5-flujo-login) | [`auth.service.ts`](../src/services/auth.service.ts) · [`auth.php`](../server/handlers/auth.php) |
| Configurar Gemini | [AGENTS.md §"Configuración"](AGENTS.md) | [`server/config/gemini.json.example`](../server/config/gemini.json.example) |
| Configurar Revolut | [PAYMENTS.md §"Configuración"](PAYMENTS.md) | [`server/config/revolut.json.example`](../server/config/revolut.json.example) |
| Generar QRs | [QR.md](QR.md) | [`src/services/qr.service.ts`](../src/services/qr.service.ts) |
| Ver qué toca hacer próximo sprint | [ROADMAP.md §"Prioridades próximas 2 semanas"](ROADMAP.md) | — |

## Cambios clave tras la auditoría 2026-04-17

**Seguridad** (ver [SECURITY.md](SECURITY.md)):

- `.htaccess` endurecido (dotfiles, backups, config, handlers, data, bin bloqueados; headers estrictos; CSP).
- CORS con whitelist en `config/cors.json` (se acabó el `*`).
- CSRF double-submit con `hash_equals` constant-time.
- Sesiones `httponly` + `SameSite=Strict` + `Secure` + fingerprint UA + rolling TTL.
- Argon2id con política de contraseña estricta.
- Rate limit por IP+scope (`login`, `ai`, `payments`, `sync`, `register`).
- Upload: whitelist MIME sin SVG, nombre reescrito a hash, validación cruzada extensión↔MIME.
- Sanitización en `lib.php` (`s_id`, `s_email`, `s_str`, `s_int`) que protege contra path traversal y cadenas malformadas.

**Cliente**:

- Token de sesión ya no vive en `localStorage`. La cookie `socola_session` (httponly) es la única fuente de verdad; JS no puede leerla ni exfiltrarla.
- `SynaxisClient` inyecta `X-CSRF-Token` automáticamente en cada POST.
- `auth.service.ts` reescrito: `login`, `logout`, `register`, `getCurrentUser`, `ensureCsrfToken`, con cache de usuario en `sessionStorage` (no `localStorage`).

**Operativa**:

- CLI [`server/bin/create-admin.php`](../server/bin/create-admin.php) para crear el primer superadmin (solo ejecutable en CLI).
- 4 nuevos archivos `.json.example` en `server/config/` cubriendo **gemini, revolut, auth y cors**.
- `.gitignore` protege `config/*.json` reales.

Ver detalles de commit en [CLAUDE.md §"Registro de cambios"](../CLAUDE.md#registro-de-cambios-auditoría-2026-04-17).

## Glosario

- **ACIDE** — Arquitectura original (PHP monolito). El proyecto padre `../../` lo contiene como archivo histórico.
- **SynaxisCore** — Sucesor: librería JS en navegador con el mismo contrato que ACIDE pero sobre IndexedDB.
- **SynaxisClient** — Fachada única que la SPA consume. Decide transporte por scope de acción.
- **Scope** — `local` / `server` / `hybrid`. Declarado en [`actions.ts`](../src/synaxis/actions.ts).
- **Vault** — Banco curado de preguntas/respuestas del Maître; 1ª capa antes de IA.
- **Oplog** — Log append-only local de escrituras, preparado para sync (Fase 3).
- **Master collections** — `users`, `roles`, `projects`, `system_logs`. Viven en IndexedDB separada.
- **Double-submit cookie** — Patrón CSRF: cookie legible por JS se envía también en header; server compara.
