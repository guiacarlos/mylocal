# AxiDB Guide

Tutoriales ordenados por nivel de entrada.

## v1.0 disponible ya

- [**01-quickstart.md**](01-quickstart.md) — Primer contacto: instalar, CRUD embebido, CRUD remoto, CLI.
- [**02-embedded-vs-remote.md**](02-embedded-vs-remote.md) — Cuando usar cada transport, criterios de decision.
- [**03-axisql.md**](03-axisql.md) — Parser AxiSQL con gramatica completa y 14 ejemplos. (Fase 2)
- [**04-vault-snapshots.md**](04-vault-snapshots.md) — Cifrado AES-256-GCM por coleccion + snapshots full/incremental. (Fase 3)
- [**05-agentes.md**](05-agentes.md) — AxiAgent + MicroAgent persistentes, kernel think/act/observe, backends NoopLlm/Groq/Ollama/Gemini/Claude, dashboard Agents tab. (Fase 6)
- [**06-dashboard.md**](06-dashboard.md) — UI vanilla bajo `/axidb/web/`. Sidebar de colecciones, editor inline, consola AxiSQL/`ai:`, tab Agents. (Fase 4 + Fase 6)
- [**06-console.md**](06-console.md) — Consola REPL dedicada `/axidb/web/console.html` con modos sql/op/ai/js + atajos JetBrains, y `axi console` TTY. (Fase 6)

## Tambien util

- [../standard/](../standard/) — specs formales (op-model, wire-protocol, storage-format).
- [../api/](../api/) — referencia generada desde `HelpEntry` de cada Op (34 Ops en v1).
- `axi help` / `axi help <op>` — ayuda desde la terminal.
