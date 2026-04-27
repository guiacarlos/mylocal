# Agentes IA — AxiAgent y MicroAgent

**Estado**: **DISPONIBLE EN v1.0 tras Fase 6** ✅.
**Implementacion**: `axidb/engine/Agents/` + `axidb/engine/Op/Ai/*`.
**Stack**: PHP puro, persistencia JSON, sin frameworks.

---

## Modelo

### AxiAgent (agente primario)

Entidad persistida en `STORAGE/_system/agents/<id>/agent.json`:

- `id` (formato `ag_<YYYYMMDDHHMMSS><hex>`), `name`, `role` (prompt de sistema).
- `tools`: lista de Ops del catalogo que el agente tiene permiso de invocar (sandbox).
- `state`: JSON libre del agente.
- `status`: `idle | running | waiting | errored | killed | done`.
- `budget`: `{max_steps, max_tokens, max_children}`.
- `llm`: identificador del backend (`noop` | `groq:<modelo>` | `ollama:<modelo>`).
- `inbox.jsonl` / `outbox.jsonl`: append-only de mensajes inter-agente.

### MicroAgent

Subtipo de Agent con `parent_id != null`, `ephemeral=true`. Hereda `tools`/`llm`
del parent. Se autodestruye al terminar (`status=done`). Profundidad maxima 3
(`primario -> hijo -> nieto`); un nieto no puede crear bisnietos.

---

## API (Fase 6)

```php
// Crear agente primario
$db->execute((new Axi\Op\Ai\NewAgent())
    ->spec('reviewer', 'Revisa productos sin imagen', ['select', 'count', 'update']));

// Pregunta one-shot (auto-crea ask-bot efimero read-only y lo destruye)
$r = $db->execute((new Axi\Op\Ai\Ask())
    ->prompt('count products'));
echo $r['data']['answer'];                          // "Cuento documentos de 'products'."
echo $r['data']['observation']['data']['count'];    // 23

// Listar agentes vivos
$agents = $db->execute(['op' => 'ai.list_agents'])['data']['agents'];

// Lanzar el kernel sobre un agente persistente
$db->execute((new Axi\Op\Ai\RunAgent())->run($agentId, 'Revisa productos'));

// Mensajeria entre agentes
$db->execute((new Axi\Op\Ai\Attach())->message($agentId, 'check', 'Revisa inventario'));
$db->execute((new Axi\Op\Ai\Broadcast())->send('reviewer*', 'Stop current work'));

// Spawn microagente
$db->execute((new Axi\Op\Ai\NewMicroAgent())
    ->spawn($agentId, 'Indexar documentos sin tag', 50));

// Kill switch individual y global
$db->execute((new Axi\Op\Ai\KillAgent())->target($agentId));
$db->execute((new Axi\Op\Ai\KillAgent())->target('', true));     // all=true
```

---

## Loop del Kernel

```
RunAgent()
└── AgentKernel::run(agent, input?)
    ├── (1) reset status -> running
    ├── (2) drena inbox -> turnos user en history
    ├── (3) loop hasta done | exhausted | killed:
    │     ├── llm->complete(history, agent.tools) -> {content, action?, done}
    │     ├── consume step + tokens
    │     ├── si action != null: Toolbox->call(agent, op, params)
    │     │     - rechaza con FORBIDDEN si op no esta en agent.tools
    │     │     - si pasa, dispatcha a engine->execute([op, ...])
    │     ├── append observation al history
    │     └── done? -> status=done
    └── return {agent_id, status, steps, tokens, answer, history}
```

---

## Consola REPL en el dashboard

UI web vanilla (`/axidb/web/`):

- Tab **Console** -> selector de modos: `AxiSQL` | `Op JSON` | `ai:` (agente).
- En modo `ai:` el textarea envia `{op:'ai.ask', prompt:<texto>}` al endpoint.
- Tab **Agents** -> lista agentes vivos con status/budget/tools, permite
  crear nuevo, ejecutar (`▶`), matar individual (`×`) o todos (`Kill all`).
- Atajos: `Ctrl+Enter` para Run en consola.

---

## Backends LLM

Implementaciones registradas en [LlmRegistry](../../engine/Agents/LlmRegistry.php):

| spec                    | clase               | requisitos                                    |
| ----------------------- | ------------------- | --------------------------------------------- |
| `noop`                  | `NoopLlm`           | ninguno; deterministico, sin red. Default.    |
| `groq:<modelo>`         | `GroqLlm`           | `AXI_GROQ_API_KEY` o `state.llm_api_key`      |
| `ollama:<modelo>`       | `OllamaLlm`         | `ollama serve` corriendo (default :11434)     |

`NoopLlm` reconoce comandos en lenguaje natural y los traduce a Ops:

```
ping                            -> {op:'ping'}
describe / list collections     -> {op:'describe'}
schema                          -> {op:'schema'}
help [op]                       -> {op:'help', target:[op]}
count <coleccion>               -> {op:'count', collection:<coleccion>}
list <coleccion> [limit N]      -> {op:'select', collection:<coleccion>, limit:N}
exists <coleccion> <id>         -> {op:'exists', collection:<coleccion>, id:<id>}
stop / done / fin               -> done=true
```

Cualquier otro prompt cae en mensaje claro pidiendo formato. Es el bloque
ideal para testing y entornos offline.

API keys reales se guardan en Vault (Fase 3) y se inyectan al agente via
`state.llm_api_key` en `Manager::createAgent(...)`.

---

## Seguridad agentica

- **Sandbox por Op**: cada agente declara `tools`. `Toolbox::call` rechaza
  con `FORBIDDEN` cualquier Op fuera del sandbox.
- **Budget duro**: `max_steps` y `max_tokens` se contabilizan en cada vuelta;
  si se agotan, `status -> waiting` y el loop termina sin rescate automatico.
- **Profundidad maxima 3**: un nieto no puede crear bisnietos.
- **`max_children` por parent**: configurable en `budget`.
- **Kill switch global**: `ai.kill_agent all=true` levanta el flag en
  `_system/agents/_global.json`. Cualquier `RunAgent` tras eso lanza
  `FORBIDDEN` hasta que se resetea con `AgentStore::setGlobalKillSwitch(false)`.
- **Validacion de id**: `AgentStore` valida con regex `^[A-Za-z0-9][A-Za-z0-9_\-]{0,80}$`
  para evitar path traversal en `<basePath>/<id>`.
- **Mailbox append-only**: inbox/outbox se almacenan como JSONL; nunca se
  reescribe linea, solo se trunca al consumir.

---

## Ver tambien

- [../api/ai_ask.md](../api/ai_ask.md) — referencia generada del Op.
- [../api/ai_new_agent.md](../api/ai_new_agent.md) — idem.
- [06-dashboard.md](06-dashboard.md) — Tab Agents y `ai:` REPL.
- [`../../tests/agents_test.php`](../../tests/agents_test.php) — 42 checks de cobertura.
