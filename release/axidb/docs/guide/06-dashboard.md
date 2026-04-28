# Dashboard web — UI vanilla de AxiDB

**Estado**: **DISPONIBLE EN v1.0 tras Fase 4** ✅.
**Implementacion**: `axidb/web/index.php` + `index.html` + `app.css` + `app.js`.
**Stack**: PHP server-rendered + JS vanilla. Sin frameworks, sin build step.

---

## Activar el dashboard

Por defecto el dashboard esta **desactivado** (seguro por defecto). Activarlo:

```bash
# Edita axidb/web/config.json y pon enabled=true
$ cat axidb/web/config.json
{
    "enabled": true,
    "require_auth": false,
    "default_collection": null
}
```

Si tu hosting sirve el directorio AxiDB en `https://tu-dominio/axidb/`, entra a:

```
https://tu-dominio/axidb/web/
```

`require_auth: true` exige que haya cookie `acide_session` o header
`Authorization: Bearer <token>` (login previo via `auth.login`).

`default_collection: "notas_demo"` (por ejemplo) abre directamente esa
coleccion al cargar el dashboard.

---

## Que ofrece

### Tab Collections

- **Sidebar** con todas las colecciones existentes y su conteo.
- Click en una -> ver tabla de documentos con sus campos.
- **Click en un doc** -> editor JSON inline (modal) con autoformat. Save
  hace `update` con `replace=true`.
- **Boton "+ New doc"** -> creator JSON vacio.
- **× rojo** en cada fila -> hard delete con confirmacion.

### Tab Console

- Modos:
  - **AxiSQL**: lo que escribes pasa a `{op:"sql", query: ...}`.
  - **Op JSON**: lo escribes en JSON crudo (`{"op":"select",...}`).
  - **ai:** (Fase 6): el textarea se envia como `{op:"ai.ask", prompt:<texto>}`.
    Genera un ask-bot efimero con tools de solo lectura, ejecuta el prompt
    y se autodestruye. NoopLlm reconoce sin red: `ping`, `count <coleccion>`,
    `list <coleccion> [limit N]`, `describe`, `help [op]`, `exists <coleccion> <id>`.
- Run con `Ctrl+Enter` (UX clasica de REPL).
- Output con duracion en ms y badge de exito/error.
- Historial visible (cada ejecucion deja una entrada). Clear vacia el log.

### Tab Agents (Fase 6)

- Lista todos los agentes persistentes con `id`, `name`, `role`, `status`,
  `llm`, `steps_used / max_steps`, `tools`, `parent_id`.
- **+ New agent**: form para crear (name, role, tools coma-separadas, llm).
- Por agente: `▶` (run con input opcional, prompts via `prompt(...)` del browser),
  `×` (kill individual).
- **Kill all**: detiene todos los agentes y activa el kill switch global.
- Indicador `kill-switch ON` en rojo cuando esta activo.

### Tab Status

- Boton Refresh -> ejecuta `Op\Ping` y muestra el JSON de respuesta:
  estado del motor, services activos, version, timestamp.

---

## Arquitectura

```
axidb/web/
├── config.json         enabled / require_auth / default_collection
├── index.php           gate: lee config y rinde el HTML inyectando
│                       window.AXI_DASHBOARD_CFG. 404 si disabled.
├── index.html          template estatico (~99 lineas).
├── app.css             estilos vanilla (~190 lineas).
└── app.js              SPA (~250 lineas, vanilla, sin deps).
```

`app.js` hace `fetch` a `/axidb/api/axi.php` (mismo origin, cookie
acide_session se manda automatica). Toda la logica usa los Ops del
catalogo del motor — el dashboard es **un cliente mas** del wire
protocol formal, no codigo privilegiado.

---

## Seguridad

- **Same-origin**: el dashboard solo habla con el endpoint del propio
  AxiDB. CORS no aplica.
- **Switch enabled**: deja el dashboard en off en produccion si no lo
  necesitas. La UI no estara expuesta — solo el API.
- **Sin secretos en el cliente**: `index.php` solo inyecta
  `default_collection` y `api_endpoint`. No envia tokens, ni passwords,
  ni claves del vault.
- **No es un panel admin para todo el mundo**. Si abres el dashboard sin
  auth en una IP publica, cualquiera puede ver/editar tus colecciones.
  Activa `require_auth: true` o ponlo detras de una VPN/htaccess.

---

## Ejemplos rapidos

### Listar productos sin tocar terminal

```
1. Asegurate que axidb/web/config.json tiene enabled=true.
2. Visita /axidb/web/.
3. Click en "products" en el sidebar.
4. Veras la tabla con todos los productos. Click en uno para editarlo.
```

### Ejecutar AxiSQL contra Socola

```
1. Tab "Console".
2. Modo "AxiSQL".
3. Escribe:    SELECT name, price FROM products WHERE price < 5
4. Ctrl+Enter.
5. Output JSON con items.
```

### Probar en local mientras pruebas Socola con AxiDB

```
1. php -S localhost:8000 -t .   # desde la raiz del repo
2. http://localhost:8000/axidb/web/
3. Login (si require_auth) -> cookie acide_session se guarda.
4. Funciona contra el mismo storage de Socola en STORAGE/.
```

---

## Limitaciones v1

- **Sin paginacion** en la tabla (limit 100 fijo). Para colecciones con
  miles de docs, usa la consola con `LIMIT n OFFSET m`.
- **Sin filtros visuales**. La barra de busqueda llega en v1.1.
- **Sin syntax highlighting** del JSON ni del SQL en la consola. Para
  eso vale Monaco o Codemirror, prohibitivo para vanilla.
- **Agentes IA**: disponibles en Fase 6 (NoopLlm offline + Groq + Ollama).
  La consola tiene modo `ai:` y hay tab Agents dedicada.
  Sin streaming del LLM por ahora (la respuesta llega entera).
- **Sin gestion de schema** desde la UI: el editor de docs es JSON
  crudo. Los Ops `Alter\*` se ejecutan via consola.

---

## Customizar / extender

El dashboard es deliberadamente sencillo. Si quieres anadir features:

1. Edita `app.js`. Esta partido en bloques (transport, sidebar, docs
   viewer, modal, console, status). Anade tus tabs nuevos en el `<nav>`
   del HTML y un panel correspondiente.
2. Si crece >500 lineas, splittea en archivos JS y cargalos con varios
   `<script>` en orden (la regla §6.2.10 lo permite con justificacion).
3. Para tema oscuro/claro, las variables CSS estan al inicio de
   `app.css`. Cambia `--bg`/`--fg`/`--accent`.

---

## Ver tambien

- [01-quickstart.md](01-quickstart.md) — primer contacto.
- [03-axisql.md](03-axisql.md) — sintaxis para la consola.
- [`../api/`](../api/) — referencia de los Ops disponibles.
- [`../../examples/notas/`](../../examples/notas/) — alternativa: app
  PHP minima sin dashboard.
