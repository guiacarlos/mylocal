# AxiDB v1.0

**File-based database engine for PHP 8.1+. No SQL server, no Composer, no build step.**

AxiDB stores collections as JSON files on disk and exposes them through a unified Op model: a single contract — `{op, ...params}` → `{success, data, error, code, duration_ms}` — that runs identically over PHP embedded calls, HTTP JSON, AxiSQL, the CLI, and pluggable IA agents.

```
PHP embebido ─┐
HTTP JSON    ─┤
AxiSQL       ─┼──► Engine::execute() ──► Result
CLI          ─┤
Agente IA    ─┘
```

- **Zero install**: copy the `axidb/` folder, `require 'axidb/axi.php'`, done.
- **45 Ops** in v1.0 (CRUD, schema, vault, backups, auth, AI, system).
- **Agents IA** (Fase 6): kernel, sandbox por Op, audit log, 5 backends LLM (Noop offline, Groq, Ollama, Gemini, Claude).
- **Vault AES-256-GCM** y snapshots full/incremental ZIP.
- **Dashboard vanilla** + **consola REPL** estilo JetBrains, 100% sin frameworks.
- **Suite total**: 745 checks, 13 archivos, 0 failures.

---

## 5 casos copy-paste

### Caso A — CRUD embebido (modo zero-server)

```php
<?php
require 'axidb/axi.php';
use Axi\Sdk\Php\Client;

$db = new Client();
$db->collection('notas')->insert(['title' => 'Hola',  'body' => 'Mundo']);
$db->collection('notas')->insert(['title' => 'Adios', 'body' => 'Mundo']);

foreach ($db->collection('notas')->orderBy('title')->get() as $n) {
    echo "- {$n['title']}: {$n['body']}\n";
}
```

```bash
php hello.php
# - Adios: Mundo
# - Hola: Mundo
```

Datos en `STORAGE/notas/<id>.json`. Sin DB server. Sin migraciones.

---

### Caso B — CRUD remoto (mismo SDK, transport diferente)

**Servidor**:

```bash
php -S 0.0.0.0:8080 -t /home/me/proyectos
```

**Cliente** (otra app, otra maquina):

```php
<?php
require 'axidb/axi.php';
use Axi\Sdk\Php\Client;

$db = new Client('http://servidor:8080/axidb/api/axi.php');
$db->collection('notas')->insert(['title' => 'Remote!']);
```

Solo cambia el constructor. El resto del codigo es identico al Caso A.

---

### Caso C — AxiSQL en 3 lineas

```php
$db->sql("SELECT title, body FROM notas WHERE _version > 0 ORDER BY title LIMIT 10");
```

Operadores soportados: `= != < <= > >= LIKE IN NOT IN BETWEEN IS NULL IS NOT NULL` con `AND OR ()` anidado. Subset SQL: `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `COUNT`. Detalle: [docs/guide/03-axisql.md](docs/guide/03-axisql.md).

---

### Caso D — Cifrado por coleccion + snapshot

```php
// 1. Vault unlock con master password
$db->execute(['op' => 'vault.unlock', 'password' => 'tu-master-password']);

// 2. Marca una coleccion como encrypted
$db->execute(['op' => 'alter_collection', 'collection' => 'secrets',
              'flags' => ['encrypted' => true]]);

// 3. Inserciones futuras se cifran transparentes (AES-256-GCM)
$db->collection('secrets')->insert(['api_key' => 'sk-...']);

// 4. Snapshot completo en zip
$db->execute(['op' => 'backup.create', 'name' => 'pre-deploy-2026-04']);
```

Detalle: [docs/guide/04-vault-snapshots.md](docs/guide/04-vault-snapshots.md).

---

### Caso E — Tu primer agente IA (Fase 6)

Sin API key, ya funciona via `NoopLlm` (deterministico, offline):

```bash
php axidb/cli/main.php ai ask "count notas"
# answer: Cuento documentos de 'notas'.
# observation: {"count":2}
# status:  done  steps: 1
```

Con LLM real (ej. Groq):

```bash
export AXI_GROQ_API_KEY=...
php axidb/cli/main.php ai new-agent reviewer \
    --role "Revisas notas y detectas duplicados." \
    --tools select,count,exists \
    --llm groq:llama-3.1-8b-instant

php axidb/cli/main.php ai run reviewer-agent-id "Hay duplicados de 'Hola' en notas?"
```

El agente:

1. Lee tus tools del sandbox.
2. Pide al LLM que devuelva `{content, action, done}`.
3. Si `action.op` esta permitido, lo despacha al motor.
4. Anota la observacion en el history.
5. Cierra cuando `done=true` o agota el budget.

Cada Op invocada queda en `STORAGE/_system/agents/_audit.log` con `actor=agent:<id>`.

Detalle: [docs/guide/05-agentes.md](docs/guide/05-agentes.md).

---

## Instalacion

```bash
# 1. Descomprime el zip o clona el repo
unzip axidb-v1.0.0.zip

# 2. (Opcional) bootstrap del primer admin
php axidb/auth/setup.php

# 3. Activa el dashboard
echo '{"enabled":true,"require_auth":false}' > axidb/web/config.json

# 4. Sirve con PHP built-in
php -S localhost:8080 -t .

# 5. Visita
# http://localhost:8080/axidb/web/           (dashboard)
# http://localhost:8080/axidb/web/console.html  (REPL)
```

Sin Composer. Sin Docker. Sin Node. Sin migraciones. **Funciona en hosting compartido**.

---

## CLI

```bash
# Catalogo + sub-comandos
php axidb/cli/main.php help

# Operaciones individuales
axi ping
axi describe
axi select notas --limit 10 --json
axi sql "SELECT title FROM notas WHERE _version > 1"

# Sub-comandos sin Op
axi vault   <unlock|lock|status>
axi backup  <create|restore|list|drop>
axi ai      <list-agents|new-agent|ask|run|spawn|kill|kill-all|attach|broadcast|audit>
axi console                      # REPL TTY con modos \sql \op \ai \js
axi docs    build                # regenera docs/api/*.md desde HelpEntry
```

---

## Estructura del repo

```
axidb/
├── axi.php             # entry point: autoloader + factory
├── engine/             # nucleo: Ops, Storage, Vault, Backup, Agents
│   ├── Op/             # 45 clases Op del catalogo
│   ├── Agents/         # Manager, Kernel, Toolbox, Mailbox, AuditLog, LLMs
│   ├── Vault/          # cifrado AES-256-GCM
│   └── Backup/         # snapshots ZIP full/incremental
├── api/                # axi.php: endpoint HTTP unificado
├── auth/               # Auth, RoleManager, sessions
├── sdk/                # Client (embedded + HTTP), Collection
├── cli/                # main.php + commands/{Help,Op,Sql,Vault,Backup,Ai,Console,Docs}
├── bin/                # axi (Unix) / axi.cmd (Windows)
├── web/                # dashboard vanilla + consola REPL dedicada
├── docs/               # guide/, standard/, api/
├── examples/           # notas, portfolio, remote-client
├── migration/          # adapters Socola + zip builder
└── tests/              # 13 archivos *_test.php (745 checks)
```

---

## Documentacion

- **[docs/guide/](docs/guide/)** — tutoriales por nivel.
- **[docs/standard/](docs/standard/)** — specs formales (op-model, wire-protocol, storage-format, agent-protocol, auth, migration-socola).
- **[docs/api/](docs/api/)** — referencia generada de las 45 Ops.

---

## Licencia y soporte

AxiDB es parte de **ACIDE SOBERANO** / SYNAXISCORE. Para reportar issues o pedir features, abre un issue en GitHub.

**No hay tracker oficial publico todavia.** Si encuentras un bug en v1.0, escribe en el repo del proyecto.

---

## Status v1.0

- [x] Fase 1: rebrand + Op model + Storage + docs base
- [x] Fase 2: AxiSQL parser + query builder SDK
- [x] Fase 3: Vault + Backups + CLI
- [x] Fase 4: Dashboard vanilla + demo Notas
- [x] Fase 5: Migracion Socola (coexistencia funcional + zip empaquetado)
- [x] Fase 6: Capacidad agentica (5 backends LLM, sandbox, audit, kill switch, REPL)
- [x] Fase 7: Pulido y release v1.0.0

**Suite**: 745 passed / 0 failed (13 archivos, ~9 s).
