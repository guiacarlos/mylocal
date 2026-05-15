# AxiDB Agent Protocol — spec formal v1.0 (Fase 6)

Este documento es la fuente de verdad para la mensajeria inter-agente, el formato del audit log y los contratos del kernel. Cualquier cambio al wire format requiere bump explicito de version.

---

## 1. Layout en disco

Todo lo agentico vive bajo `STORAGE/_system/agents/`:

```
STORAGE/_system/agents/
├── _global.json           # kill switch global
├── _audit.log             # NDJSON append-only de TODA Op invocada por agentes
├── <agent-id>/
│   ├── agent.json         # estado persistible del agente
│   ├── inbox.jsonl        # mensajes pendientes (consumidos = drained)
│   └── outbox.jsonl       # registro de mensajes enviados (no se consume)
```

- Los archivos cuyo nombre empieza por `_` los **ignora** `AgentStore::listAll()` (no son agentes).
- El `agent-id` valida con regex `^[A-Za-z0-9][A-Za-z0-9_\-]{0,80}$` para evitar path traversal.

---

## 2. Formato del agente (`agent.json`)

```json
{
  "id":          "ag_20260427110100abcdef",
  "name":        "reviewer",
  "role":        "Eres un agente que...",
  "parent_id":   null,
  "ephemeral":   false,
  "tools":       ["select", "count", "exists"],
  "state":       {},
  "status":      "idle",
  "budget":      {"max_steps": 20, "max_tokens": 5000, "max_children": 3},
  "steps_used":  0,
  "tokens_used": 0,
  "llm":         "noop",
  "created_at":  "2026-04-27T11:01:00+00:00",
  "updated_at":  "2026-04-27T11:01:00+00:00"
}
```

### Estados (`status`)

| Valor    | Significado                                                            |
| -------- | ---------------------------------------------------------------------- |
| `idle`   | Creado, sin tarea activa.                                              |
| `running`| Kernel ejecutando el loop.                                             |
| `waiting`| Budget agotado o esperando input externo. No se reactiva solo.         |
| `done`   | Tarea cerrada por el agente (`done=true` desde el LLM).                |
| `killed` | `ai.kill_agent` invocado o kill switch global activo.                  |
| `errored`| Excepcion no recuperable durante el loop.                              |

Solo `killed` y `errored` bloquean re-runs. `done` se reinterpreta como "tarea anterior cerrada"; un nuevo input arranca tarea nueva.

### Backend LLM (`llm`)

```
noop                          # determinista offline (default)
groq:<model>                  # AXI_GROQ_API_KEY
ollama:<model>                # local, default :11434
gemini:<model>                # AXI_GEMINI_API_KEY
claude:<model>                # AXI_CLAUDE_API_KEY
```

Spec desconocido → se sustituye por `NoopLlm` por seguridad.

---

## 3. Mensajeria inter-agente

### Envelope

Cada linea de `inbox.jsonl` / `outbox.jsonl` es un objeto JSON con esta forma:

```json
{
  "subject":        "check",
  "body":           "Revisa el inventario",
  "from":           "agent_xxx" | "system",
  "to":             "agent_yyy",
  "ts":             "2026-04-27T11:05:00+00:00",
  "correlation_id": "8b3a1c..."
}
```

- `correlation_id` = 16 hex chars (`bin2hex(random_bytes(8))`). Se conserva en respuestas para encadenar conversaciones.
- `from` puede ser `system` cuando el mensaje lo deposita un humano via `ai.attach` sin `--from`.
- `ts` es ISO 8601 con offset.

### Operaciones

- `ai.attach to=<agent_id> subject=... body=...` deposita en `<to>/inbox.jsonl`.
- `ai.broadcast pattern=<glob> message=...` matchea contra `agent.role` y `agent.name` con glob `*` `?`; deposita una copia por cada match.
- En su proxima ejecucion (`ai.run_agent`) el agente *drena* su inbox (lee y trunca). Cada mensaje pendiente se inyecta como turno `user` en el historial.
- `outbox.jsonl` es solo registro: si `from` esta seteado en un attach, se anade tambien al outbox del emisor para auditoria local.

### Garantias

- **Append-only**: nunca se reescribe linea, solo se trunca al consumir el inbox.
- **At-least-once**: si el kernel cae a mitad del drain, el mensaje queda re-entregado en la siguiente vuelta (el drain trunca tras leer).
- **Sin orden global**: mensajes de inboxes distintos no tienen orden total; dentro de un mismo inbox el orden es de llegada.

---

## 4. Loop del kernel

```
run(agent, input):
    if kill_switch_global:
        raise FORBIDDEN
    if status in {killed, errored}:
        raise CONFLICT

    status = running
    save(agent)

    history = [{role: system, content: agent.role}]
    history += drain(inbox)  -> turnos {role: user, content: "[from] subject: body"}
    if input != null: history += [{role: user, content: input}]

    while alive:
        if budget_exhausted: status = waiting; break
        decision = llm.complete(history, agent.tools)
        consume_step(decision.tokens)
        history += [{role: assistant, content, action?}]
        if decision.action and action.op:
            obs = toolbox.call(agent, action.op, action.params)
            history += [{role: tool, content: json(obs), observation: obs}]
            save(agent)
        if decision.done: status = done; break
        if not decision.action: status = idle; break

    return {agent_id, status, steps, tokens, answer, history}
```

### Decision del LLM

Cada `LlmBackend::complete()` debe devolver:

```json
{
  "content": "texto para el usuario",
  "action":  null | {"op": "<op-name>", "...params...": "..."},
  "done":    true | false,
  "tokens":  123
}
```

- `content`: lo que se mostraria al humano. Puede ser vacio.
- `action`: si esta presente, el kernel invoca `Toolbox::call` con `op` + resto de campos como params.
- `done=true` cierra el loop tras esa vuelta. `done=false` permite multi-step (sujeto al budget).
- `tokens`: best-effort para budget; los wrappers de Groq/Ollama leen `usage` del proveedor; NoopLlm aproxima con `strlen/4`.

Cuando el modelo devuelve texto libre (markdown, etc.) que no es JSON, los wrappers degradan a `{content: <texto>, action: null, done: true, tokens: ...}`.

---

## 5. Sandbox de Ops (`Toolbox::call`)

Cada agente declara `tools: string[]` con nombres de Ops del catalogo. El Toolbox:

1. Valida `agent.canExecute(op)`. Si falla → registra denial en audit log y lanza `AxiException::FORBIDDEN`.
2. Si `op === 'batch'` y el batch tiene **>10 operaciones de escritura**, dispara `backup.create` automatico con name `auto-pre-batch-<YYYYmmdd-HHmmss>-<id6>`.
3. Despacha al motor con `engine->execute([op, ...params])`.
4. Registra el resultado en el audit log (incluye `snapshot` si hubo).
5. Devuelve la respuesta del motor sin transformar.

### Operaciones consideradas "write" para el threshold

```
insert, update, delete,
create_collection, drop_collection, alter_collection, rename_collection,
add_field, drop_field, rename_field,
create_index, drop_index
```

`select`, `count`, `exists`, `describe`, `schema`, `ping`, `help`, `sql` (read-only), `vault.*`, `backup.*` y los `ai.*` no cuentan como write.

---

## 6. Audit log

Path: `STORAGE/_system/agents/_audit.log`. Formato NDJSON (una linea = un objeto):

```json
{
  "ts":          "2026-04-27T11:05:31+00:00",
  "actor":       "agent:ag_20260427110100abcdef",
  "op":          "select",
  "params":      {"collection": "products", "limit": 5},
  "success":     true,
  "code":        null,
  "duration_ms": 4.2,
  "snapshot":    null
}
```

- `actor` = literal `agent:` + id. Si en el futuro se anaden actores humanos al log, usar el prefijo `user:<id>` para no colisionar.
- `params` se trunca: strings >200 chars y arrays >20 entries se acortan.
- `snapshot` solo existe si la Op disparo auto-snapshot.

Lectura programatica via `Op\Ai\Audit::tail(limit, agent?)` o CLI `axi ai audit --limit 50`.

---

## 7. Profundidad y `max_children`

- **Profundidad maxima 3** desde el primario: `primario(0) → hijo(1) → nieto(2)`. Crear desde `nieto` lanza `FORBIDDEN`.
- **`max_children`** lo lleva el `budget` del parent. Cuando un parent intenta crear un hijo y ya tiene `>= max_children` vivos, falla con `FORBIDDEN`.

---

## 8. Kill switch

- **Individual**: `ai.kill_agent agent_id=<id>` → `status = killed`. Es persistente.
- **Global**: `ai.kill_agent all=true` → mata todos los agentes vivos y escribe `_global.json` con `kill_switch=true`. Cualquier `ai.run_agent` posterior lanza `FORBIDDEN` hasta resetearlo programaticamente con `AgentStore::setGlobalKillSwitch(false)`.

El estado `killed` queda registrado: nunca se limpia automaticamente.

---

## 9. Versionado

- Esta spec describe el protocolo **v1.0** que ship con AxiDB v1.0.
- Cambios incompatibles (rename de campos, nuevo formato de envelope) requieren bump a `v1.1` y migracion documentada.
- Cambios additivos (nuevo backend LLM, nuevos campos opcionales del audit log) son `v1.0.x` sin bump.

---

## 10. Ver tambien

- [`../guide/05-agentes.md`](../guide/05-agentes.md) — tutorial pragmatico con ejemplos.
- [`../guide/06-console.md`](../guide/06-console.md) — manual de la consola REPL.
- [`op-model.md`](op-model.md) — contrato de Ops del que dependen las "tools".
- [`wire-protocol.md`](wire-protocol.md) — formato HTTP/JSON del transport.
