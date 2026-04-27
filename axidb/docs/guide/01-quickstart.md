# Quickstart — AxiDB en 20 lineas

Este tutorial arranca desde cero y acaba con una app PHP persistiendo datos.

**Requisitos**: PHP 8.1+ con `openssl` (viene por defecto). Nada mas.

---

## 1. Instalar (copiar la carpeta)

```bash
# Descargar el zip (o clonar el repo) y descomprimir
unzip axidb-v1.0.zip
ls axidb/
# axi.php  api/  engine/  auth/  sdk/  cli/  bin/  docs/  examples/  tests/
```

Nada mas. No hay `composer install`, no hay `npm install`. Si tu servidor ejecuta PHP, AxiDB ejecuta.

---

## 2. Modo embebido (sin HTTP, mismo proceso)

Crea `hello.php` en la carpeta padre de `axidb/`:

```php
<?php
require 'axidb/axi.php';

use Axi\Sdk\Php\Client;

$db = new Client();
$notas = $db->collection('notas');

$notas->insert(['title' => 'Hola mundo', 'body' => 'Mi primera nota']);
$notas->insert(['title' => 'Segunda', 'body' => 'Otra mas']);

foreach ($notas->orderBy('title')->get() as $n) {
    echo "- {$n['title']}: {$n['body']}\n";
}
```

Ejecuta: `php hello.php`. Veras dos notas. Los datos se guardan en `STORAGE/notas/` como archivos JSON.

---

## 3. Modo HTTP (remoto)

Si prefieres ejecutar AxiDB como servidor y consumirlo desde una app diferente:

**Servidor** (donde corre AxiDB):
```bash
php -S 0.0.0.0:8080 -t /ruta/donde/vive/axidb
```

**Cliente** (otra app, misma maquina o distinta):
```php
<?php
require 'axidb/axi.php';  // mismo SDK

use Axi\Sdk\Php\Client;

$db = new Client('http://tu-servidor:8080/axidb/api/axi.php');
$notas = $db->collection('notas');

// Mismo codigo que antes.
$notas->insert(['title' => 'Remota']);
foreach ($notas->get() as $n) { echo $n['title'] . "\n"; }
```

**La unica diferencia es el constructor**: `new Client()` (embebido) vs `new Client('http://...')` (remoto). El resto del codigo no cambia.

---

## 4. CLI (inspeccion y admin)

Desde la terminal:

```bash
# Lista todas las Ops disponibles (45 en v1.0)
php axidb/cli/main.php help

# Ayuda detallada de un Op
php axidb/cli/main.php help select

# Health check
php axidb/cli/main.php ping

# Describe colecciones existentes
php axidb/cli/main.php describe

# Leer datos
php axidb/cli/main.php select notas --limit 10 --json
```

O crea un alias si usas el binario: `axidb/bin/axi ping`.

---

## 5. AxiSQL (Fase 2 — disponible)

```php
$docs = $db->sql("SELECT title FROM notas WHERE _version > 1 ORDER BY _updatedAt DESC LIMIT 5");
```

Equivalente fluent vía `Collection`:

```php
$docs = $db->collection('notas')->where('_version', '>', 1)
    ->orderBy('_updatedAt', 'desc')->limit(5)
    ->fields(['title'])->get();
```

Gramatica completa en [03-axisql.md](03-axisql.md).

---

## 6. Tu primer agente IA (Fase 6 — disponible)

Sin API key, AxiDB trae el backend `noop` (offline, deterministico). Pruebalo:

```bash
# Pregunta one-shot via CLI
php axidb/cli/main.php ai ask "count notas"
# answer: Cuento documentos de 'notas'.
# observation: {"count":2}
# status:  done  steps: 1

# Lista los Ops que el agente puede invocar (sandbox)
php axidb/cli/main.php ai new-agent reviewer \
    --role "Eres un revisor de notas." \
    --tools select,count,exists --llm noop

# Ver agentes vivos
php axidb/cli/main.php ai list-agents
```

O desde codigo:

```php
$res = $db->execute(['op' => 'ai.ask', 'prompt' => 'count notas']);
echo $res['data']['answer'];                       // texto del agente
echo $res['data']['observation']['data']['count']; // 2
```

Para usar Groq/Gemini/Claude/Ollama, exporta `AXI_GROQ_API_KEY` (o equivalente) y crea el agente con `--llm groq:llama-3.1-8b-instant`.

---

## 7. Consola REPL en el navegador

Activa el dashboard:

```bash
echo '{"enabled":true,"require_auth":false}' > axidb/web/config.json
```

Visita `http://localhost:8080/axidb/web/console.html`. Modos disponibles:

- `sql:` AxiSQL crudo
- `op:` JSON de Op
- `ai:` lenguaje natural al agente primario
- `js:` eval cliente

Atajos JetBrains-like: `Ctrl+Enter` ejecuta, `Ctrl+P` paleta de Ops, `Ctrl+Shift+A` find action, `F1` help del Op bajo el cursor.

---

## 8. Siguientes pasos

- [02-embedded-vs-remote.md](02-embedded-vs-remote.md) — cuando usar cada transport.
- [03-axisql.md](03-axisql.md) — parser AxiSQL completo con gramatica.
- [04-vault-snapshots.md](04-vault-snapshots.md) — cifrado y backups.
- [05-agentes.md](05-agentes.md) — crear agentes IA que consultan tus datos (Fase 6).
- [06-dashboard.md](06-dashboard.md) — UI vanilla con tab Agents.
- [06-console.md](06-console.md) — manual de la consola REPL (web + TTY).
- [../standard/op-model.md](../standard/op-model.md) — especificacion formal del modelo Op.
- [../standard/wire-protocol.md](../standard/wire-protocol.md) — spec HTTP para clientes en otros lenguajes.
- [../standard/agent-protocol.md](../standard/agent-protocol.md) — spec de agentes y mensajeria.
- [../standard/auth.md](../standard/auth.md) — auth, roles, sesiones.
- [../api/README.md](../api/README.md) — referencia completa de las 45 Ops.

Si te atascas, abre un issue en GitHub.
