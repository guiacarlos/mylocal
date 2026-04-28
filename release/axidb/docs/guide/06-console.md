# Consola REPL — `axidb/web/console.html` y `axi console`

**Estado**: **DISPONIBLE EN v1.0 tras Fase 6** ✅.
**Implementacion**:
- Web: [`axidb/web/console.html`](../../web/console.html) + [`console.js`](../../web/console.js) + [`console.css`](../../web/console.css). Servida por [`console.php`](../../web/console.php).
- TTY: [`axidb/cli/commands/ConsoleCommand.php`](../../cli/commands/ConsoleCommand.php) (entry: `axi console`).

---

## Acceso

### Web

Asegurate que `axidb/web/config.json` tiene `enabled: true` (mismo gate que el dashboard). Entra a:

```
https://tu-host/axidb/web/console.html
```

o, si tu servidor no resuelve `.html` automaticamente, a:

```
https://tu-host/axidb/web/console.php
```

### TTY

```bash
axi console
# AxiDB console - modos: \sql \op \ai \js   |  Ctrl-C / EOF para salir
# Modo actual: \sql
axi:\sql>
```

---

## Modos

| Modo  | Que envia                          | Atajo web   | Atajo TTY |
| ----- | ---------------------------------- | ----------- | --------- |
| `sql` | `{op:"sql", query:<input>}`        | `Alt+1`     | `\sql`    |
| `op`  | `JSON.parse(input)` directo        | `Alt+2`     | `\op`     |
| `ai`  | `{op:"ai.ask", prompt:<input>}`    | `Alt+3`     | `\ai`     |
| `js`  | Eval local cliente (sin red)       | `Alt+4`     | `\js`     |

`js` en web ejecuta `new Function(input)` en el navegador (sandbox del browser). En TTY solo permite expresiones aritmeticas (digitos + `+-*/()`) por seguridad.

---

## Atajos (web)

| Tecla              | Accion                                                              |
| ------------------ | ------------------------------------------------------------------- |
| `Ctrl+Enter`       | Run (envia el contenido del editor con el modo actual).             |
| `Ctrl+/`           | Toggle comentario (prefijo segun modo: `--`, `//`, `#`).            |
| `Ctrl+Space`       | Autocompletado del Op bajo el cursor. 1 candidato → completa.       |
| `Ctrl+P`           | Quick open: paleta con todos los Ops del catalogo.                  |
| `Ctrl+Shift+A`     | Find action (mismo paleta, scope mas amplio en v1.1).               |
| `F1`               | Help del Op bajo el cursor (overlay con `HelpEntry`).               |
| `Alt+1` ... `Alt+4`| Cambiar modo.                                                       |
| `Alt+ArrowUp/Down` | Navegar historial de la sesion.                                     |

En la paleta: flechas, Enter para confirmar, Esc para cerrar.

---

## Atajos (TTY)

| Comando        | Accion                                          |
| -------------- | ----------------------------------------------- |
| `\sql/\op/\ai/\js` | Cambia el modo activo.                       |
| `\help` / `\h` | Muestra atajos.                                 |
| `\hist`        | Lista el historial de la sesion.                |
| `\q`/`\exit`   | Salir.                                          |

Si PHP tiene `readline` cargado, hay historial con flechas y Ctrl-R search out-of-the-box.

---

## Casos de uso rapidos

### Ver los 5 productos mas baratos (modo SQL)

```sql
SELECT name, price FROM products ORDER BY price ASC LIMIT 5
```

### Probar un Op JSON en bruto (modo Op)

```json
{"op":"select","collection":"products","limit":3}
```

### Pregunta natural a un agente (modo AI)

```
count products
```

→ Internamente envia `{"op":"ai.ask","prompt":"count products"}`. NoopLlm lo traduce a `{op:"count",collection:"products"}` y la consola muestra el conteo.

### Calculo rapido (modo JS)

```javascript
24 * 60 * 60 * 365
```

→ Eval local, no toca el servidor.

### Buscar un Op por palabra clave

`Ctrl+Shift+A` → escribe `vault` → ves `vault.unlock`, `vault.lock`, `vault.status`. Enter → inserta el nombre en el editor.

---

## Limitaciones conocidas

- **Sin syntax highlighting** del SQL/JSON (vanilla; no Monaco). Si lo necesitas para uso intensivo, ejecuta queries via `axi sql` o vendora Monaco fuera del repo principal.
- **`Ctrl+Shift+A`** muestra los mismos Ops que `Ctrl+P` en v1; el "find action" con todas las acciones de UI (cambiar tema, exportar resultados, etc.) llega en v1.1.
- **Eval JS local** en TTY esta restringido a aritmetica; el modo full está en la consola web.
- **Sin streaming**: las respuestas LLM llegan completas, no token-a-token.

---

## Ver tambien

- [05-agentes.md](05-agentes.md) — modelo de agentes y backends LLM.
- [06-dashboard.md](06-dashboard.md) — dashboard con tab Console y tab Agents.
- [`../standard/agent-protocol.md`](../standard/agent-protocol.md) — spec del protocolo de mensajeria inter-agente.
