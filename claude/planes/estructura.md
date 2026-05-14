# Plan de Estructura: MyLocal → Framework Multi-Sector

**Documento:** `claude/planes/estructura.md`
**Proyecto:** MyLocal (a.k.a. "framework PHP+React modular")
**Estado:** Planificación de ejecución por olas, con criterios de salida explícitos
**Filosofía:** modular atómico · cada archivo una responsabilidad · ≤ 250 LOC · cero hardcodeos · cero datos ficticios · cero funciones a medio hacer

---

## 0. Por qué este documento existe

Hoy MyLocal es un monolito de hostelería:

- El **backend** ya es modular (`CAPABILITIES/*` con `capability.json`) — es la pieza buena.
- El **frontend** es monolítico: `App.tsx` cablea rutas de Carta/TPV/MesaQR; `DashboardSidebar.tsx` tiene un `ITEMS[]` hardcodeado con Carta/Mesas/Pedidos.
- No existe un **AppBootstrap** que ensamble una app nueva eligiendo capabilities y sector.
- Faltan **3 capabilities transversales** (CITAS, CRM, NOTIFICACIONES) que cualquier vertical necesita.

Objetivo del plan: convertir MyLocal en un **framework de aplicaciones verticales** sin romper nada de lo que ya funciona (`AUTH_LOCK.md` debe seguir pasando) y sin meter código a medio camino. Cada ola se cierra al 100% antes de pasar a la siguiente.

**Lo que NO se hace en este plan:**

- No se forkea OpenClaude. Se integra como cliente HTTP cuando haya 2–3 demos pagando.
- No se reescribe AxiDB. No se reescribe el core PHP. No se toca `AUTH_LOCK`.
- No se hace white-label, marketplace, app móvil, ni nada que no esté listado aquí.
- No se introducen datos seed con valores de pega ("Plato 1", "Cliente Ejemplo"). Si una pantalla no tiene datos reales, muestra estado vacío real.

---

## 1. Principios de ejecución (no negociables)

Estos son los muros del campo. Si una tarea los pisa, se rediseña.

1. **Atómico.** Cada archivo nuevo se valida por sí solo (typecheck, ejecuta, render OK). No existe el commit "WIP".
2. **≤ 250 LOC.** Si un archivo va a superarlo, se parte en sub-archivos con responsabilidades claras *antes* de seguir escribiendo.
3. **Sin hardcodeos.** Toda lista, mapa, color, ruta, etiqueta de menú, módulo activo, namespace de capability viene de un fichero de configuración (`config.json`, `manifest.json`, `capability.json`) o de AxiDB. Cero `const ITEMS = [...]` en componentes.
4. **Sin datos ficticios.** Estados vacíos reales con CTA, jamás "Producto demo 1". Los tests pueden crear datos *con prefijo* `__test_*` y limpiarlos al final.
5. **Sin funciones a medias.** Una función o no existe o devuelve un valor correcto para todos sus inputs declarados. Nada de `// TODO: handle error case`.
6. **Test antes de check.** Cada checklist tiene un sub-bloque "Tests" con escenarios feliz + bordes + estrés. Sin verde no se marca el item.
7. **Cero regresión `AUTH_LOCK`.** Cada ola termina con `build.ps1` ejecutado completo y el test de login pasando 100%.
8. **Sin commits ni push.** Hasta que el dueño del proyecto lo pida explícitamente. Los cambios viven en working tree, se validan en local.

---

## 2. Arquitectura objetivo (visión de alto nivel)

```
┌─────────────────────────────────────────────────────────────────────┐
│ release/ (lo que se sube por tenant)                                 │
│                                                                      │
│  config.json    ← qué módulo activo, identidad, plan, paleta         │
│  CORE/          ← framework PHP (no cambia entre tenants)            │
│  axidb/         ← motor de datos (no cambia entre tenants)           │
│  CAPABILITIES/  ← solo las que necesite ese tenant                   │
│  spa/...        ← bundle React con SOLO el módulo del tenant         │
└─────────────────────────────────────────────────────────────────────┘
                                ▲
                                │ generado por
                                │
                    ┌───────────┴────────────┐
                    │ tools/AppBootstrap     │
                    │  - lee preset/<sector> │
                    │  - copia capabilities  │
                    │  - copia módulo SPA    │
                    │  - emite config.json   │
                    │  - dispara build       │
                    └────────────────────────┘
```

**Frontend modular** (lo nuevo):

```
spa/src/
  app/                       ← runtime genérico (NO se toca al añadir sector)
    bootstrap.tsx            ← lee config.json + carga módulo activo
    AppShell.tsx             ← Router + PublicLayout + PrivateLayout
    config.ts                ← tipos + loader de config.json
    modules-registry.ts      ← import.meta.glob de los manifests
  modules/
    _shared/                 ← navegación genérica (Config, Facturación, Cuenta)
      manifest.json
      routes.tsx
      pages/
    hosteleria/              ← módulo actual de MyLocal (refactor)
      manifest.json
      routes.tsx
      pages/
      components/
    clinica/                 ← nuevo (Ola F)
    logistica/               ← nuevo (Ola G)
    asesoria/                ← nuevo (Ola H)
  components/                ← componentes verdaderamente genéricos (Header, Footer, LoginModal)
  services/                  ← servicios genéricos (auth, csrf)
  synaxis/                   ← cliente AxiDB (no cambia)
```

**Backend modular** (lo que ya tenemos + 3 capabilities nuevas):

```
CAPABILITIES/
  LOGIN/         ← bloqueado (AUTH_LOCK)
  OPTIONS/       ← bloqueado
  AI/  GEMINI/   ← motor IA
  CARTA/  QR/  TPV/  PDFGEN/  OCR/   ← hostelería
  PRODUCTS/  PAYMENT/  FISCAL/        ← genéricos
  CITAS/         ← NUEVO (Ola E)
  CRM/           ← NUEVO (Ola E)
  NOTIFICACIONES/← NUEVO (Ola E)
```

---

## 3. Mapa de olas (timeline real)

| Ola | Nombre | Entregable | Días estimados |
|----:|--------|-----------|---------------:|
| 0   | Preflight | Auditoría + gitignore + test_login verde tras refactor | 0,5 |
| A   | Refactor frontend → `modules/hosteleria` | MyLocal igual de funcional pero ya dentro de la nueva estructura | 1 |
| B   | Sistema de manifest dinámico | Sidebar y rutas se generan desde `manifest.json` por módulo | 1 |
| C   | `_shared/` + `app/` runtime | Núcleo del framework documentado y probado | 1 |
| D   | AppBootstrap (CLI) | `pnpm bootstrap --sector=clinica --slug=san-anton` produce `release/` lista | 1,5 |
| E   | CAPABILITIES backend: CITAS, CRM, NOTIFICACIONES | 3 capabilities cerradas + test php | 3 |
| F   | Módulo `clinica/` (demo Clínica San Antón) | Frontend + datos reales mínimos + tests | 2 |
| G   | Módulo `logistica/` (demo LogiSpain / Muebles Blanes) | Frontend + datos reales mínimos + tests | 2 |
| H   | Módulo `asesoria/` (demo Gesgasa) | Frontend + datos reales mínimos + tests | 2 |
| I   | Integración OpenClaude como observador | Conector HTTP unidireccional + 1 alerta real | 2 |
| J   | Documentación + handover | `docs/FRAMEWORK.md` + `CONTRIBUTING.md` por módulo | 0,5 |

Total estimado de ejecución continua: **~16,5 días laborables**. No se cierra una ola con sub-tareas marcadas como "para después".

---

## 4. Ola 0 — Preflight

**Objetivo:** dejar el repo y el entorno listos para tocar sin riesgos.

### 4.1. Auditoría rápida

- [ ] Listar archivos > 250 LOC en `spa/src/**` y `CAPABILITIES/**`.
- [ ] Confirmar que `build.ps1` arranca limpio en local y `test_login.php` pasa 100%.
- [ ] Confirmar que `npm run build` en `spa/` termina sin warnings de TS.
- [ ] Snapshot del árbol actual de `spa/src/` en `claude/snapshots/spa-pre-refactor.txt` para diffs futuros.

### 4.2. Higiene git

- [ ] `.claude/` y `claude/recetascocina/` añadidos a `.gitignore` (siguen sin commitearse).
- [ ] `release/assets/index-*.js` y `release/assets/index-*.css` salen del seguimiento — el build los regenera y empachan el historial.
- [ ] `spa/tsconfig.tsbuildinfo` fuera del seguimiento.

### 4.3. Liberación de puertos zombie

- [ ] Script `tools/dev/free-ports.ps1` que mate procesos zombie en 8091, 8766, 8767, 5173. Lo invocan `run.bat` y `build.ps1` antes de empezar.

### 4.4. Tests

- [ ] `build.ps1` corre sin que sobren `php.exe` colgando.
- [ ] Test de regresión visual mínimo: `/`, `/carta`, `/dashboard/carta` cargan sin error en consola (capturado en `claude/snapshots/preflight-console.log`).

### Criterio de salida Ola 0

- Auditoría escrita.
- `git status` sin ruido (gitignore aplicado).
- `build.ps1` verde.
- `tools/dev/free-ports.ps1` documentado en `README` del proyecto.

---

## 5. Ola A — Refactor frontend a `modules/hosteleria/`

**Objetivo:** que MyLocal siga funcionando *exactamente igual* pero con sus archivos ya colocados dentro de `spa/src/modules/hosteleria/`. **Ola de mover, no de añadir.**

### 5.1. Inventario previo

- [ ] Listar todas las páginas de restaurante en `spa/src/pages/` y `spa/src/pages/dashboard/`:
  - Públicas: `Carta.tsx`, `MesaQR.tsx`.
  - Dashboard hostelería: `CartaPage.tsx`, `CartaImportarPage.tsx`, `CartaProductosPage.tsx`, `CartaPdfPage.tsx`, `CartaWebPage.tsx`, `MesasPage.tsx`, `PedidosPage.tsx`.
  - Sistema staff: `TPV.tsx`.
- [ ] Listar componentes acoplados a hostelería: `components/sala/*`, `components/carta/*`.
- [ ] Listar componentes verdaderamente genéricos a *no* mover: `Header`, `Footer`, `LoginModal`, `MarkdownView`, todo `dashboard/*` (Layout, Sidebar, Header, Context).

### 5.2. Movimientos físicos

- [ ] Crear `spa/src/modules/hosteleria/{pages,components,routes.tsx,manifest.json}`.
- [ ] Mover páginas de restaurante a `modules/hosteleria/pages/`.
- [ ] Mover `components/carta/*`, `components/sala/*` a `modules/hosteleria/components/`.
- [ ] Actualizar imports relativos. Cero rutas absolutas que apunten al sitio viejo.
- [ ] Crear `modules/hosteleria/manifest.json` (esquema en §6).

### 5.3. Tests

- [ ] `npm run build` pasa sin errores.
- [ ] `npm run dev` arranca, las 9 rutas hosteleras renderizan sin error en consola.
- [ ] `build.ps1` completo verde (incluye `test_login.php`).
- [ ] Diff visual: capturar `/`, `/carta`, `/dashboard/carta`, `/dashboard/mesas`, `/dashboard/pedidos`, `/sistema/tpv` y compararlos con el snapshot pre-refactor. Cero cambios pixel-perfect esperados.

### Criterio de salida Ola A

- Todo lo de hostelería vive bajo `modules/hosteleria/`.
- `App.tsx` sigue siendo el mismo de hoy (cablea directamente las rutas viejas, todavía). No se ha tocado el dinamismo aún.
- Tests verdes.

---

## 6. Ola B — Manifest dinámico (sidebar + rutas)

**Objetivo:** que el sidebar y las rutas del dashboard se generen a partir del `manifest.json` del módulo activo, sin tocar `DashboardSidebar` cuando aparezca un sector nuevo.

### 6.1. Esquema `manifest.json` por módulo

Archivo único por módulo. Tipo TS en `app/config.ts`.

```jsonc
{
  "id": "hosteleria",
  "name": "MyLocal · Hostelería",
  "version": "1.0.0",
  "public_routes": [
    { "path": "/carta",                  "component": "Carta" },
    { "path": "/carta/:zonaSlug",        "component": "Carta" },
    { "path": "/carta/:zonaSlug/:mesaSlug","component": "Carta" },
    { "path": "/mesa/:slug",             "component": "MesaQR" }
  ],
  "dashboard_nav": [
    { "to": "/dashboard/carta",   "label": "Carta",   "icon": "Book" },
    { "to": "/dashboard/mesas",   "label": "Mesas",   "icon": "Armchair" },
    { "to": "/dashboard/pedidos", "label": "Pedidos", "icon": "Bell" }
  ],
  "dashboard_routes": [
    { "path": "/dashboard/carta/*",   "component": "CartaPage" },
    { "path": "/dashboard/mesas",     "component": "MesasPage" },
    { "path": "/dashboard/pedidos",   "component": "PedidosPage" }
  ],
  "staff_routes": [
    { "path": "/sistema/tpv/*", "component": "TPV" }
  ],
  "requires_capabilities": ["CARTA", "QR", "TPV", "PRODUCTS", "OCR", "PDFGEN"]
}
```

- [ ] Definir tipo `ModuleManifest` en `spa/src/app/config.ts`.
- [ ] Validar al arrancar (Zod o validador minimal propio ≤ 100 LOC) — si falta un campo, fallo de carga claro.

### 6.2. Sidebar dinámico

- [ ] `DashboardSidebar.tsx` deja de tener `ITEMS = [...]`. Recibe `items: NavItem[]` por props.
- [ ] El layout pasa `[...moduleManifest.dashboard_nav, ..._sharedManifest.dashboard_nav]`.
- [ ] Mapeo `icon: string → LucideIcon` vive en `app/icons.ts` (whitelist explícita, ≤ 80 LOC). Si el manifest pide un icono no whitelistado, se loguea y se cae a `Square`.

### 6.3. Rutas dinámicas

- [ ] `App.tsx` deja de listar rutas a mano. Itera `manifest.public_routes`, `dashboard_routes`, `staff_routes` y monta `<Route>` correspondiente.
- [ ] El mapping `component: string → React.ComponentType` vive en `modules/<id>/routes.tsx`. Cero `eval`, cero `new Function`. Usa un registro literal.

### 6.4. `_shared/manifest.json`

- [ ] Crear `modules/_shared/` con manifest y páginas genéricas (Config, Facturación, Cuenta, LegalPage, WikiPage).
- [ ] El runtime carga `_shared` siempre, antes del módulo de sector.

### 6.5. Tests

- [ ] Test unitario: dada una manifest correcta, el sidebar pinta N+M items (módulo + shared) en el orden esperado.
- [ ] Test unitario: dada una manifest con `icon` inválido, no crashea y pinta el fallback.
- [ ] Test de integración: tras mover la lógica, navegar entre las 9 rutas hosteleras sigue sin re-fetch (el fix del commit `3a50c0d` debe seguir vigente — verificación manual con DevTools Network).
- [ ] Estrés: manifest con 30 items en `dashboard_nav` → sidebar scrollable, no rompe el layout.

### Criterio de salida Ola B

- `DashboardSidebar.tsx` ≤ 80 LOC, sin un solo literal de etiqueta o ruta.
- `App.tsx` ≤ 120 LOC, sin un solo path string de un sector concreto.
- Cambiar el menú = editar `manifest.json`. Nada más.

---

## 7. Ola C — Runtime `app/` + `_shared/`

**Objetivo:** consolidar el núcleo del framework como pieza independiente.

### 7.1. `app/bootstrap.tsx`

- [ ] Carga `config.json` desde `/config.json` (fetch al arrancar, una sola vez).
- [ ] Resuelve `modulesRegistry` con `import.meta.glob('../modules/*/routes.tsx', { eager: false })`.
- [ ] Importa dinámicamente sólo el módulo activo + `_shared`.
- [ ] Maneja el caso "config inválida" con una pantalla de error explícita (sin pantalla en blanco).

### 7.2. `config.json` por tenant

- [ ] Schema mínimo: `{ modulo, nombre, slug, color_acento, logo_path, plan }`.
- [ ] `config.json` vive en `spa/public/config.json` en dev (hostelería por defecto) y lo genera AppBootstrap en cada tenant.
- [ ] Validador `app/config.ts` lo carga al arrancar; si falla, `<ConfigError/>` con mensaje accionable.

### 7.3. Theming sin hardcodeos

- [ ] `color_acento` se aplica como CSS variable `--db-accent` en `<html>` al arrancar.
- [ ] `logo_path` lo usa el sidebar; si falta, muestra inicial del nombre.
- [ ] Cero `style={{ color: '#xxx' }}` en componentes que dependa de identidad.

### 7.4. Tests

- [ ] Test: arrancar con `config.json` mínimo válido → arranca, sidebar pintado.
- [ ] Test: arrancar sin `config.json` → pantalla de error legible, no en blanco.
- [ ] Test: arrancar con `modulo` inexistente → pantalla de error que cita el ID y los módulos disponibles.
- [ ] Estrés: cambiar `color_acento` en runtime (script en consola) → tema se actualiza sin recarga.

### Criterio de salida Ola C

- El núcleo `app/` no contiene ninguna referencia a "hosteleria", "clinica" ni a ningún sector.
- Ningún componente fuera de `modules/<sector>/` referencia páginas de sector.
- El test de login sigue pasando.

---

## 8. Ola D — AppBootstrap (CLI)

**Objetivo:** un comando produce un `release/` listo para subir a un servidor, con sólo lo que ese tenant necesita.

### 8.1. Diseño del CLI

`tools/bootstrap/bootstrap.mjs` (Node, sin dependencias raras).

Uso:

```bash
node tools/bootstrap/bootstrap.mjs \
  --preset=clinica \
  --slug=san-anton \
  --nombre="Clínica San Antón" \
  --color=#2d7a4f \
  --out=./builds/san-anton
```

Lo que hace, paso a paso:

1. Lee `tools/bootstrap/presets/<preset>.json` (define qué capabilities y qué módulo SPA).
2. Copia `CORE/`, `axidb/`, `gateway.php`, `router.php`, `.htaccess`, `manifest.json`, `robots.txt`, `schema.json`.
3. Copia sólo las `CAPABILITIES/` declaradas en el preset.
4. Genera `config.json` en `spa/public/` con los flags del CLI.
5. Lanza `npm run build` en `spa/` con `VITE_MODULO=<preset>` (variable que entra en `bootstrap.tsx` como pista para tree-shaking).
6. Mueve el bundle resultante a `<out>/`.
7. Imprime un resumen verificable (lista de capabilities incluidas, tamaño del bundle, ruta de salida).

### 8.2. Schema de preset

`tools/bootstrap/presets/clinica.json`:

```jsonc
{
  "module": "clinica",
  "capabilities": [
    "LOGIN", "OPTIONS", "AI", "GEMINI",
    "PRODUCTS", "FISCAL", "PAYMENT",
    "CITAS", "CRM", "NOTIFICACIONES"
  ],
  "default_role": "admin",
  "default_user": { "email": null, "password": null }
}
```

- [ ] Tipos en `tools/bootstrap/types.mjs`.
- [ ] Validación: si el preset declara `clinica` pero `spa/src/modules/clinica/` no existe, abort con mensaje claro.
- [ ] Validación: si declara una capability inexistente, abort.

### 8.3. Tests

- [ ] `bootstrap --preset=hosteleria --slug=demo-hosteleria` regenera el `release/` actual byte por byte (excepto hashes de bundle). Es el control: si rompe esto, rompe la app de referencia.
- [ ] `bootstrap --preset=clinica` falla limpio mientras Ola F no esté hecha — no genera basura.
- [ ] Estrés: ejecutar 5 bootstraps en paralelo a directorios distintos → no se corrompen entre sí.
- [ ] Validación: si falta `--slug`, error con uso completo en stdout.

### Criterio de salida Ola D

- Una clínica nueva tarda lo que tarde Vite en compilar (~5s) en pasar de "no existe" a "tengo carpeta deployable".
- El comando es idempotente: ejecutarlo dos veces produce el mismo output (modulo hashes de bundle).

---

## 9. Ola E — CAPABILITIES backend nuevas

Se cierran las tres capabilities transversales antes de tocar cualquier vertical nuevo. Cada una sigue la disciplina actual: `capability.json`, ≤ 250 LOC por archivo, sin SQL, datos en AxiDB.

### 9.1. `CAPABILITIES/CITAS/`

Responsabilidad: gestión de citas (clínicas, asesorías, talleres, cualquier servicio agendable).

Archivos:

- `capability.json` (depends_on: `LOGIN`, `OPTIONS`, `NOTIFICACIONES`)
- `CitasModel.php` (CRUD AxiDB: `c_<uuid>`, campos: local_id, cliente_id, recurso_id, inicio, fin, estado, notas)
- `RecursosModel.php` (consultorios, vehículos, mesas reservables — genérico)
- `CitasEngine.php` (lógica: conflictos de horario, recordatorios, cancelación)
- `CitasAdminApi.php` (handler `cita_create/update/list/cancel`)
- `CitasPublicApi.php` (handler público para que un cliente pida hora desde un formulario embebido)
- `README.md`

Checklist:

- [ ] `CitasModel.php` ≤ 250 LOC, devuelve `['success' => bool, 'data' => ..., 'error' => ?string]`.
- [ ] Conflictos de horario detectados en `CitasEngine::tryReserve()` (test cubre solapamiento parcial, anidado, idéntico, exacto-borde).
- [ ] Sanitización con `s_id/s_str` heredados de `LOGIN/LoginSanitize`.
- [ ] Handler registrado en `spa/server/index.php` con `require_role`.

Tests (`spa/server/tests/test_citas.php`):

- [ ] Crear cita, leer, listar por rango, cancelar.
- [ ] Reservar slot ocupado → error claro.
- [ ] Reservar slot exacto-borde (15:00–16:00 + 16:00–17:00) → OK.
- [ ] Reservar slot que cabalga medianoche → OK.
- [ ] Cancelar cita inexistente → 404 controlado.
- [ ] 50 reservas concurrentes sobre el mismo recurso → solo una gana (flock OK).

### 9.2. `CAPABILITIES/CRM/`

Responsabilidad: clientes (B2C o B2B), interacciones, etiquetas, segmentación básica.

Archivos:

- `capability.json` (depends_on: `LOGIN`, `OPTIONS`)
- `ContactoModel.php` (uuid, local_id, nombre, email, telefono, etiquetas[], notas, fuente)
- `InteraccionModel.php` (uuid, contacto_id, tipo: llamada|email|whatsapp|nota, contenido, autor_id, ts)
- `SegmentoEngine.php` (consulta por tags/fecha/fuente; devuelve listas)
- `CrmAdminApi.php`
- `README.md`

Checklist:

- [ ] Contactos buscables por email, teléfono, etiqueta.
- [ ] Dedupe automática por email exacto al crear (warning, no error).
- [ ] Auditoría: cada interacción registra autor y timestamp; no es editable, solo se añade.

Tests (`spa/server/tests/test_crm.php`):

- [ ] Crear contacto, añadir 3 interacciones, listar.
- [ ] Crear dos contactos con mismo email → segundo trae flag `duplicate_of`.
- [ ] Filtrar por tag → solo los que la tienen.
- [ ] Estrés: 10.000 contactos, búsqueda por email <100ms.

### 9.3. `CAPABILITIES/NOTIFICACIONES/`

Responsabilidad: envío de notificaciones (email transaccional, WhatsApp via webhook, SMS via proveedor) — pluggable.

Archivos:

- `capability.json` (depends_on: `LOGIN`, `OPTIONS`)
- `drivers/EmailDriver.php` (SMTP genérico, configurable por `OPTIONS`)
- `drivers/WhatsAppDriver.php` (webhook saliente, sin SDK)
- `drivers/NoopDriver.php` (para tests y entornos sin proveedor configurado)
- `NotificationEngine.php` (dispatcher: lee tipo + canal y delega al driver)
- `Template.php` (plantillas con `{{var}}`, sin lógica)
- `NotificationsApi.php`
- `README.md`

Checklist:

- [ ] Driver activo se decide leyendo `OPTIONS/notificaciones.json` (cero hardcoding del proveedor).
- [ ] Plantillas en `STORAGE/templates/notificaciones/` (no en código).
- [ ] Reintentos: 3 con backoff exponencial, marcando estado en AxiDB.
- [ ] Tasa límite por destinatario para evitar spam accidental.

Tests (`spa/server/tests/test_notif.php`):

- [ ] Driver Noop registra el envío y devuelve OK.
- [ ] Driver Email con SMTP de testing local (mailpit/docker opcional, o saltado si no hay).
- [ ] Plantilla con vars faltantes → error legible, no crash.
- [ ] 100 notificaciones concurrentes con Noop → cero pérdidas.

### Criterio de salida Ola E

- 3 capabilities con `capability.json`, README, tests verdes incluidos en el `build.ps1`.
- `test_login.php` sigue verde.
- Ningún archivo > 250 LOC.

---

## 10. Ola F — Módulo `clinica/` (demo Clínica San Antón)

**Objetivo:** primer vertical no-hosteleria funcional, no maqueta. Usa CITAS + CRM + NOTIFICACIONES + AI ya construidos.

### 10.1. Páginas (`modules/clinica/pages/`)

- [ ] `AgendaPage.tsx` — vista calendario semanal de citas, con drag-and-drop básico (lib nativa o lo mínimo posible, no traemos FullCalendar).
- [ ] `PacientesPage.tsx` — listado + ficha (datos del CRM con campos extra: especie, raza, peso, edad).
- [ ] `HistorialPage.tsx` — detalle por paciente: visitas, vacunas, pruebas, archivos PDF.
- [ ] `StockPage.tsx` — inventario de medicamentos con alerta de mínimo (usa `PRODUCTS`).
- [ ] `RecordatoriosPage.tsx` — cola de notificaciones pendientes/enviadas (usa `NOTIFICACIONES`).

### 10.2. `modules/clinica/manifest.json`

- [ ] Declara las 5 rutas + 5 items de sidebar.
- [ ] `requires_capabilities` lista lo que necesita.

### 10.3. Datos reales (no ficticios)

- [ ] Estados vacíos con CTA real ("No hay pacientes — Crear primer paciente").
- [ ] Seed sólo si AxiDB está vacío *Y* el operador lo pide explícitamente (botón "Cargar datos de demostración" — claramente marcado como tales).

### 10.4. Tests

- [ ] Vacíos en cada página renderizan sin error y con CTA.
- [ ] Crear paciente → aparece en CRM también (es el mismo `contacto` con extras).
- [ ] Reservar cita en hueco ocupado → toast de error claro.
- [ ] Enviar recordatorio con driver Noop → registro visible en `RecordatoriosPage`.
- [ ] Estrés: 1.000 pacientes, scroll virtual o paginado real (no DOM completo).
- [ ] `bootstrap --preset=clinica --slug=test-clinica` genera `release/` que arranca y sirve `/dashboard/agenda`.

### Criterio de salida Ola F

- Demo desplegable con `bootstrap` + `npm run build`.
- Recorrido completo "alta paciente → reserva cita → enviar recordatorio" sin tocar código.
- Cero pantallas con "Lorem ipsum" o "Paciente 1".

---

## 11. Ola G — Módulo `logistica/`

**Objetivo:** demo para LogiSpain / Muebles Blanes — seguimiento de pedidos y flota.

### 11.1. Páginas

- [ ] `PedidosPage.tsx` — listado de pedidos con estados (recibido, en preparación, en ruta, entregado, incidencia).
- [ ] `FlotaPage.tsx` — vehículos y conductores (modelo simple, no GPS real en MVP — campo manual de "última posición").
- [ ] `EntregasPage.tsx` — vista del día: qué entrega cada vehículo.
- [ ] `SeguimientoPublicoPage.tsx` (ruta pública `/seguimiento/:codigo`) — el cliente final ve el estado de su pedido por código (no requiere login).
- [ ] `IncidenciasPage.tsx` — entradas con motivo, foto, resolución.

### 11.2. Manifest + capabilities

- [ ] `requires_capabilities: ["PRODUCTS", "DELIVERY", "CRM", "NOTIFICACIONES", "AI"]`.
- [ ] El público `/seguimiento/:codigo` está en `public_routes` del manifest.

### 11.3. Tests

- [ ] Cambio de estado dispara notificación al cliente vía driver Noop.
- [ ] `/seguimiento/<codigo-inexistente>` muestra error 404 controlado, no leak de datos.
- [ ] Cargar 5.000 pedidos → tabla virtualizada o paginada.
- [ ] `bootstrap --preset=logistica` produce `release/` válido.

### Criterio de salida Ola G

- Recorrido "crear pedido → cliente recibe enlace → cliente ve estado público → cierre con foto" funciona end-to-end.

---

## 12. Ola H — Módulo `asesoria/`

**Objetivo:** demo para Gesgasa — gestión documental, OCR de facturas, recordatorios fiscales.

### 12.1. Páginas

- [ ] `ClientesPage.tsx` — CRM extendido con datos fiscales (NIF, régimen, próximas obligaciones).
- [ ] `DocumentosPage.tsx` — bandeja de subida + OCR (reutiliza `OCR`), clasificación manual asistida por IA (reutiliza `AI`).
- [ ] `CalendarioFiscalPage.tsx` — agenda de vencimientos por cliente (usa `CITAS`).
- [ ] `TareasPage.tsx` — kanban mínimo (3 columnas: pendiente, en curso, hecho).
- [ ] `FacturasPage.tsx` — emisión simple (reutiliza `FISCAL` si activa Verifactu).

### 12.2. Tests

- [ ] OCR de un PDF real (uno del propio repo, no inventado) extrae al menos NIF + total.
- [ ] Crear vencimiento → recordatorio se programa automáticamente N días antes.
- [ ] Mover tarea entre columnas persiste tras recargar.
- [ ] `bootstrap --preset=asesoria` produce `release/` válido.

### Criterio de salida Ola H

- Demo presentable a Gesgasa con datos cargados durante la demo (no pre-poblados).

---

## 13. Ola I — Integración OpenClaude (asistente transversal)

**Objetivo:** una única instancia OpenClaude observa todas las apps via API. **No fork. No fusión.**

### 13.1. Conector saliente desde MyLocal

- [ ] `CAPABILITIES/AI/OpenClaudeClient.php` — cliente HTTP (sin SDK) hacia `https://<host>/api/...`.
- [ ] Auth por bearer guardado en `OPTIONS/openclaude.json` (cero hardcoding del host ni del token).
- [ ] Métodos: `notify(eventType, payload)`, `ask(prompt, context)`.
- [ ] Si el servidor no está configurado, los métodos devuelven `['enabled' => false]` y la app sigue funcionando.

### 13.2. Eventos publicables

- [ ] Cada Engine relevante emite eventos a través de un bus interno (`CORE/EventBus.php`, ≤ 150 LOC).
- [ ] `OpenClaudeClient` se suscribe y publica fuera. Si el bus no está conectado, no pasa nada.
- [ ] Eventos iniciales: `pedido.creado`, `pedido.entregado`, `cita.cancelada`, `stock.bajo`.

### 13.3. Servidor OpenClaude

- [ ] Documentar requisitos del servidor en `claude/docs/openclaude-server.md` (no se monta en este repo; se monta como pieza independiente y este repo sólo apunta a él).
- [ ] Diagrama: 1 servidor OpenClaude ↔ N tenants MyLocal. Sin acoplamiento de despliegue.

### 13.4. Caso de uso real (la demo)

- [ ] Una sola alerta funcional end-to-end: "stock bajo de paracetamol" en clínica dispara mensaje del asistente vía email al admin.
- [ ] Se prueba en un tenant generado por `bootstrap` con driver Email Noop primero, luego email real si hay SMTP.

### Criterio de salida Ola I

- El conector se enciende/apaga vía config sin tocar código.
- Si el servidor OpenClaude está caído, MyLocal no se cae ni se ralentiza (timeout estricto, fallback silencioso).

---

## 14. Ola J — Documentación + handover

**Objetivo:** que otro desarrollador entre, lea, y monte un vertical nuevo en un día.

- [ ] `docs/FRAMEWORK.md` — visión, arquitectura, esquema `manifest.json`, esquema `config.json`, esquema `capability.json`, cómo añadir un módulo.
- [ ] `docs/BOOTSTRAP.md` — uso del CLI, presets disponibles, cómo crear un preset nuevo.
- [ ] `docs/CAPABILITIES.md` — listado actualizado de capabilities, dependencias, scopes de roles.
- [ ] `modules/<id>/README.md` para cada módulo: páginas, capabilities requeridas, datos esperados.
- [ ] Actualizar `CLAUDE.md` raíz con un puntero a `claude/planes/estructura.md` y al estado de cada ola.

### Criterio de salida Ola J

- Un dev nuevo, sin contexto previo, puede leer `docs/FRAMEWORK.md` + `docs/BOOTSTRAP.md` y montar un módulo nuevo desde cero en ≤ 1 día.

---

## 15. Gates entre olas

Ninguna ola arranca sin que la anterior haya cumplido **todos** estos gates:

1. **Build verde:** `build.ps1` completa, `test_login.php` 31/31 assertions OK, tests de la ola en curso 100% OK.
2. **Sin warnings TS** en `npm run build`.
3. **Sin archivos > 250 LOC** introducidos por la ola.
4. **Sin TODOs** nuevos en el código de la ola. (Permitidos sólo si están enlazados a una tarea siguiente de este mismo plan.)
5. **Sin datos de pega** en seeds, fixtures ni UI.
6. **Verificación visual manual** firmada (capturas en `claude/snapshots/ola-<x>-<fecha>/`).
7. **Decisión consciente del dueño** ("ok, paso a la siguiente"). Sin esa luz verde, no se avanza.

---

## 16. Política de commits y push

- Durante todo este plan se trabaja en **local**.
- **No se hace commit ni push** hasta que el dueño del proyecto lo pida explícitamente.
- Si una ola se completa, se deja la working tree limpia y se espera instrucción.
- Si se necesita una rama intermedia para una prueba, se hace en una `wip/<ola>` local, sin push.

---

## 17. Riesgos identificados y mitigaciones

| Riesgo | Mitigación |
|--------|-----------|
| El refactor de la Ola A rompe `test_login.php` por cambio de rutas | Ola A no toca PHP. Solo mueve archivos React. El test corre antes y después como gate. |
| `import.meta.glob` mete todos los módulos en el bundle del tenant | `bootstrap` usa `VITE_MODULO` para limitar el glob a ese módulo + `_shared`. |
| Cambios en `config.json` requieren recompilar | Asumido. Reduce superficie de ataque (no se sirven configs por API en runtime). |
| CITAS sin lock por recurso produce dobles reservas | `flock` sobre `STORAGE/citas/<recurso>.lock` antes de escribir. Tests cubren concurrencia. |
| OpenClaude caído ralentiza la app | Timeout 1s, circuit breaker en `OpenClaudeClient`, modo "disabled" si fallan N llamadas seguidas. |
| Tres demos a la vez dispersan al equipo | Olas F, G, H son secuenciales en este plan. No se empieza la siguiente hasta cerrar la actual. |

---

## 18. Indicador de estado en vivo

Cada ola se marca aquí al cerrar:

- [ ] Ola 0 — Preflight
- [ ] Ola A — Refactor `modules/hosteleria/`
- [ ] Ola B — Manifest dinámico
- [ ] Ola C — Runtime `app/` + `_shared/`
- [ ] Ola D — AppBootstrap CLI
- [ ] Ola E — CAPABILITIES CITAS + CRM + NOTIFICACIONES
- [ ] Ola F — Módulo `clinica/`
- [ ] Ola G — Módulo `logistica/`
- [ ] Ola H — Módulo `asesoria/`
- [ ] Ola I — Integración OpenClaude
- [ ] Ola J — Documentación + handover

---

## 19. Próxima acción concreta

Cuando se dé luz verde a este plan:

1. Arrancar **Ola 0 — Preflight** (auditoría + gitignore + `tools/dev/free-ports.ps1`).
2. Reportar resultado.
3. Esperar OK para entrar en **Ola A**.

Nada se ejecuta antes de esa luz verde. Y nada se hace commit/push hasta que se pida.
