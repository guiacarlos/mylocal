# Changelog

All notable changes to AxiDB are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] — 2026-04-27

Primera version estable. Cierra el plan v1 (Fases 1-7).

### Added

#### Op model unificado (Fase 1)
- `Axi\Engine\Op\Operation` clase base con `validate()`, `execute()`, `help()`,
  `toArray()` / `fromArray()` para serializacion canonica.
- 45 Ops del catalogo (CRUD, schema, vault, backup, auth, AI, system).
- `Axi::opRegistry()` como fuente unica de la verdad para resolver `op_name → class`.
- `Result::ok()` / `Result::fail()` con `code` semantico y `duration_ms`.
- `AxiException` con codigos estables (`VALIDATION_FAILED`, `OP_UNKNOWN`,
  `DOCUMENT_NOT_FOUND`, `FORBIDDEN`, `CONFLICT`, ...).

#### Storage (Fase 1.4)
- `FsJsonDriver` con escrituras atomicas (tmp + rename), `_index.json`
  reconstruido automaticamente, `.versions/<col>/<id>/` para historial.
- Multi-tenant via `STORAGE/system/active_project.json` (rebase a
  `PROJECTS/<slug>/STORAGE`); colecciones `master` (users/roles/projects/
  system_logs) siempre globales.

#### AxiSQL (Fase 2)
- Parser completo: `SELECT INSERT UPDATE DELETE COUNT` con
  `WHERE = != < <= > >= LIKE IN NOT IN BETWEEN IS NULL IS NOT NULL`
  + `AND OR ()` anidado, `ORDER BY`, `LIMIT`, `OFFSET`.
- Op `sql` ejecuta cualquier query y delega a la Op CRUD correspondiente.
- SDK fluent `Collection::where()->orderBy()->get()` mapea a las mismas Ops.

#### Vault + Backups (Fase 3)
- `Vault\Vault` con AES-256-GCM por documento, master key derivada con PBKDF2,
  canary cifrado para detectar passwords incorrectas.
- Cifrado transparente por coleccion: solo activar `_meta.flags.encrypted=true`
  y los inserts/updates futuros pasan por `encryptDoc/decryptDoc`.
- `Backup\SnapshotStore` con full e incremental (basado en `_updatedAt`),
  formato ZIP con `manifest.json` + `data.zip`.
- 4 Ops: `vault.unlock/lock/status` y `backup.create/restore/list/drop`.

#### CLI (Fase 3 + Fase 6)
- `bin/axi` (Unix) / `bin/axi.cmd` (Windows).
- `axi help [op]`, `axi <op-name> [args]` con flags `--key=value` / `--key value`.
- `axi sql "..."`, `axi vault <unlock|lock|status>`,
  `axi backup <create|restore|list|drop>`.
- `axi docs <build|check|clean>` regenera `docs/api/*.md` desde `HelpEntry`.
- **Fase 6**: `axi ai <list-agents|new-agent|ask|run|spawn|kill|kill-all|
  attach|broadcast|audit>` y `axi console` (REPL TTY con 4 modos).

#### Dashboard web vanilla (Fase 4 + Fase 6)
- `axidb/web/index.html` (sidebar de colecciones + editor JSON inline).
- Tab Console con modos `AxiSQL` / `Op JSON` / `ai:` (Fase 6).
- **Fase 6**: tab Agents con arbol padres/hijos, status colorado por estado,
  botones run / kill / Kill all / + New agent.
- Consola REPL dedicada `axidb/web/console.html` (Fase 6) con atajos
  JetBrains-like: `Ctrl+Enter`, `Ctrl+/`, `Ctrl+Space`, `Ctrl+P`,
  `Ctrl+Shift+A`, `F1`, `Alt+1..4`, paleta de Ops y help overlay.
- 100% vanilla JS y CSS, sin frameworks ni build step.

#### Demo apps (Fase 4)
- `examples/notas/` — CRUD vanilla en 4 archivos PHP.
- `examples/portfolio/` — listado de proyectos.
- `examples/remote-client/` — consume AxiDB remoto via `AXI_REMOTE_URL`.

#### Migracion Socola (Fase 5)
- Coexistencia funcional `/acide/` legacy y `/axidb/api/`.
- `class_alias` para compatibilidad con `QueryEngine` legacy.
- 14 paridades validadas en `parity_test.php`.
- Op `legacy.action` como wrapper formal del bridge `{action:...} → ACIDE`.
- `migration/release_adapter.php` post-procesa `release/` tras `build_site`.
- `migration/htaccess.patch` con plan de flip atomico (`<5 min` rollback).
- `migration/build-axidb-zip.sh` / `.ps1` empaqueta zip de ~1.4 MB
  autocontenido (motor + SDK + docs + ejemplos).

#### Capacidad agentica (Fase 6)
- `Agent` (entidad persistible), `AgentStore` (CRUD `_system/agents/<id>/`),
  `AgentKernel` (loop receive→think→act→observe), `Toolbox` (sandbox por Op),
  `Mailbox` (inbox/outbox JSONL append-only), `Manager` (fachada).
- `MicroAgent` colapsado en `Agent` con `parent_id != null && ephemeral=true`,
  profundidad maxima 3, `max_children` por parent.
- 9 Ops AI: `ai.new_agent`, `ai.new_micro_agent`, `ai.run_agent`, `ai.ask`,
  `ai.kill_agent`, `ai.list_agents`, `ai.broadcast`, `ai.attach`, `ai.audit`.
- 5 backends LLM via `LlmRegistry`:
  - `noop` — deterministico offline (default).
  - `groq:<m>` — Llama, Mixtral via Groq Cloud.
  - `ollama:<m>` — modelos locales.
  - `gemini:<m>` — Google Generative Language v1beta.
  - `claude:<m>` — Anthropic Messages API.
- **Auditoria**: `AuditLog` NDJSON append-only con `actor=agent:<id>`,
  `op`, `params` (sanitizado), `success`, `code`, `duration_ms`, `snapshot?`.
- **Auto-snapshot pre-batch**: si un agente ejecuta `op=batch` con >10
  escrituras, dispara `backup.create` automatico con name
  `auto-pre-batch-<YYYYmmdd-HHmmss>-<id6>` antes del batch.
- **Kill switch**: individual (`status=killed`) y global
  (`_global.json` con `kill_switch=true`); con switch activo, cualquier
  `RunAgent` lanza `FORBIDDEN`.
- **Sandbox**: cada agente declara `tools[]` con nombres de Ops permitidas;
  `Toolbox::call` rechaza con `FORBIDDEN` cualquier Op fuera del sandbox.
- **Budget duro**: `max_steps` y `max_tokens` cuentan en cada vuelta;
  agotamiento → `status=waiting` (no rescate automatico).

#### HTTP API (Fase 1.5)
- `axidb/api/axi.php` endpoint unico `POST` con `{op, ...}`.
- CORS abierto para acciones publicas (`ping`, `describe`, `schema`, `help`,
  `auth.login`, `auth.logout`).
- Cookie `acide_session` (HttpOnly, SameSite=Lax) o `Authorization: Bearer
  <token>`.

#### SDK PHP (Fase 1.6)
- `Axi\Sdk\Php\Client` con dos transports (`embedded` y `http`).
- `Collection` fluent: `where`, `orderBy`, `limit`, `offset`, `fields`,
  `get`, `count`, `first`, `insert`, `update`, `delete`.
- Mismo codigo de aplicacion sirve embebido y remoto — solo cambia el
  constructor.

#### Auth (Fase 3)
- 5 Ops: `auth.login`, `auth.logout`, `auth.create_user`, `auth.grant_role`,
  `auth.revoke_role`.
- `Auth::validateRequest()` con allowlist publica.
- `RoleManager` con permisos `<recurso>:<accion>`.
- Bootstrap del primer admin via `auth/setup.php` o
  `auth/create_superadmin.php`.

#### Documentacion
- `docs/guide/` (8 archivos): quickstart, embedded-vs-remote, AxiSQL,
  vault-snapshots, agentes, dashboard, console, README.
- `docs/standard/` (6 archivos): op-model, wire-protocol, storage-format,
  migration-socola, **agent-protocol** (nuevo Fase 6), **auth** (nuevo
  Fase 7).
- `docs/api/` (45 + 1 README): generado desde `HelpEntry` de cada Op,
  regenerable con `axi docs build`.

### Tests

- **745 checks** en 13 archivos (~9 s con `tests/run.php`):
  - `agents_test.php` 52 (Fase 6: persistencia, sandbox, kernel, microagent,
    mailbox, broadcast, kill switch, audit log, auto-snapshot pre-batch).
  - `axisql_test.php` 92 (Fase 2: parser y ejecucion).
  - `backup_test.php` 36 (Fase 3: full + incremental + restore).
  - `dashboard_test.php` 40 (Fase 4 + Fase 6: gate, demo Notas, console.html).
  - `full_catalog_test.php` 317 (cobertura completa de las 45 Ops).
  - `http_routing_test.php` 21, `op_model_test.php` 44, `parity_test.php` 14,
    `sdk_test.php` 32, `storage_driver_test.php` 41, `sugar_test.php` 22,
    `vault_test.php` 33, `test_axidb.php` 1.

### Migracion desde ACIDE

El motor original ACIDE (Socola CMS) sigue funcionando intacto. AxiDB
coexiste como un transport alternativo que tunela al mismo storage.

**Para migrar una app ACIDE a AxiDB**:
1. `require 'axidb/axi.php'` en lugar de `require 'CORE/core/ACIDE.php'`.
2. Reemplazar `$acide->execute(['action' => '...'])` por
   `$db->execute(['op' => '...'])`. La forma `{action,...}` legacy sigue
   soportada via `Op\System\LegacyAction`.
3. Opcionalmente, mover handlers privativos a Ops formales.

Ver [`docs/standard/migration-socola.md`](docs/standard/migration-socola.md).

### Excluded from v1.0 (planned for P2)

- Driver MySQL-compatible para clientes existentes.
- Adapter WordPress.
- Vector search.
- Video tutoriales (sustituidos por scripts asciinema-style en
  `docs/demos/`).
- Hosting de docs en GitHub Pages (config preparada en
  `.github/workflows/pages.yml`; activar pages requiere paso manual).

---

## [Unreleased]

Sin cambios todavia. Proximo hito: P2 (drivers compatibilidad).
