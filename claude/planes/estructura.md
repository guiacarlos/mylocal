# Plan de Estructura: MyLocal → Framework Multi-Sector

**Documento:** `claude/planes/estructura.md`
**Proyecto:** MyLocal (framework PHP + React para agencias)
**Estado:** Completado — todas las olas 0–L terminadas, cero deuda
**Filosofía:** modular atómico · cada archivo una responsabilidad · ≤ 250 LOC · cero hardcodeos · cero datos ficticios · cero funciones a medio hacer

---

## 0. Por qué este documento existe

MyLocal se convierte en un **framework para agencias**: el backend PHP y el motor de datos son reutilizables entre proyectos. El frontend es un **template intercambiable** que puede venir de Lovable, Dribbble, ThemeForest o desarrollo propio.

**El problema que resuelve:**

Sin este framework, montar un proyecto para una clínica supone:

- Copiar el proyecto de hostelería
- Eliminar todo lo que no aplica
- Crear lo nuevo desde cero
- Mantener dos codebases desconectadas

Con el framework:

- El backend (CAPABILITIES) ya existe y funciona
- El template define el diseño y las páginas
- `build.ps1 --template=clinica` genera el release listo para subir
- El cliente solo lleva lo que necesita — cero archivos de otros proyectos

---

## 1. Principios de ejecución (no negociables)

1. **Atómico.** Cada archivo nuevo se valida por sí solo. No existe el commit "WIP".
2. **≤ 250 LOC.** Si un archivo va a superarlo, se parte antes de seguir escribiendo.
3. **Sin hardcodeos.** Toda lista, mapa, color, ruta, etiqueta viene de `manifest.json`, `config.json` o AxiDB.
4. **Sin datos ficticios.** Estados vacíos reales con CTA. Los tests usan prefijo `__test_*` y limpian al final.
5. **Sin funciones a medias.** Una función existe completa o no existe.
6. **Test antes de check.** Sin verde no se marca el item.
7. **Cero regresión AUTH_LOCK.** `build.ps1` y `test_login.php` deben pasar antes de cerrar cualquier ola.
8. **Commit y push** solo cuando el dueño del proyecto lo pide explícitamente.

---

## 2. Arquitectura objetivo

### Backend — no cambia entre proyectos

```
CAPABILITIES/          ← módulos PHP reutilizables, uno por dominio
  LOGIN/   OPTIONS/    ← bloqueados (AUTH_LOCK)
  CITAS/   CRM/   NOTIFICACIONES/   ← transversales (cualquier vertical)
  CARTA/   QR/   TPV/  PDFGEN/  OCR/ ← hostelería
  PRODUCTS/  PAYMENT/  FISCAL/       ← genéricos
  AI/   GEMINI/                       ← motor IA

CORE/                  ← auth, gateway, router — framework base
axidb/                 ← motor de datos file-based
```

Cada capability declara sus dependencias en `capability.json`. El build copia **solo las que el template necesita**.

### Frontend — un proyecto Vite autocontenido por vertical

```
sdk/                          ← @mylocal/sdk: paquete compartido
  src/
    client.ts                 ← SynaxisClient (acceso a datos)
    auth.ts                   ← login / logout / session
    hooks.ts                  ← React hooks (useSynaxisClient, etc.)
    types.ts                  ← tipos TypeScript comunes

templates/                    ← UN proyecto Vite por vertical/cliente
  hosteleria/
    package.json              ← depende de @mylocal/sdk
    vite.config.ts
    manifest.json             ← capabilities que usa este template
    src/
      App.tsx                 ← entry point del template
      pages/                  ← páginas propias del template
      styles/                 ← diseño propio
  clinica/
    package.json
    vite.config.ts
    manifest.json             ← ["CITAS","CRM","NOTIFICACIONES"]
    src/
      App.tsx                 ← puede venir de Lovable casi sin tocar
      pages/
      styles/
  renault/                    ← drop de Lovable/Dribbble aquí
    ...
```

### Flujo para un proyecto nuevo

```
1. Ir a Lovable → crear diseño para veterinaria → exportar
2. cp -r lovable-export/ templates/veterinaria/
3. Editar manifest.json: capabilities que necesita
4. Reemplazar llamadas mock por client.execute({ action: '...' })
5. build.ps1 --template=veterinaria → release/ lista para subir
```

### Build por template

```powershell
build.ps1 --template=clinica
  1. Lee templates/clinica/manifest.json
  2. pnpm -F clinica build → dist/ dentro del template
  3. Copia dist/ → release/
  4. Copia CORE/, axidb/, gateway.php, router.php, .htaccess
  5. Copia SOLO las CAPABILITIES declaradas en manifest.json
  6. Ejecuta test_login.php
  7. Imprime resumen (capabilities incluidas, tamaño, ruta)
```

---

## 3. Mapa de olas

|         Ola | Nombre                                                                                                        | Estado                              |
| ----------: | ------------------------------------------------------------------------------------------------------------- | ----------------------------------- |
|           0 | Preflight                                                                                                     | ✅ Completa                         |
|           A | Refactor frontend →`modules/hosteleria`                                                                    | ✅ Completa                         |
|           B | Manifest dinámico (sidebar + rutas)                                                                          | ✅ Completa                         |
|           C | Runtime `app/` + `_shared/`                                                                               | ✅ Completa                         |
|           D | AppBootstrap CLI                                                                                              | ✅ Completa                         |
|           E | CAPABILITIES: CITAS + CRM + NOTIFICACIONES                                                                    | ✅ Completa                         |
|           F | Template `clinica/` (dentro de la SPA monolítica)                                                          | ✅ Completa (pendiente migrar en G) |
| **G** | **Migración a arquitectura de templates independientes**                                               | ✅ Completa                         |
|           H | Template `logistica/` (primer template drop-in real)                                                        | ✅ Completa                         |
| **I** | **Template `asesoria/`**                                                                              | ✅ Completa                         |
| **J** | **Integración OpenClaude**                                                                             | ✅ Completa                         |
|           K | Documentación + handover                                                                                     | ✅ Completa                         |
| **L** | **Cierre técnico: tests AUTH_LOCK pendientes + AppBootstrap v2 + limpieza legacy + split SynaxisCore** | ✅ Completa                         |

---

## 4–6. Olas 0–C (completas, documentadas por referencia)

Las olas 0–C completaron el refactor del frontend de hostelería:

- `CAPABILITIES/` modular con `capability.json` por módulo
- `spa/src/modules/hosteleria/` con manifest dinámico
- Sistema de rutas y sidebar generado desde `manifest.json`
- Runtime `app/` + `_shared/` (Config, Cuenta, Facturación)
- `build.ps1` con gate de login

No requieren re-abrirse. La Ola G las absorbe en la nueva arquitectura.

---

## 7. Ola D — AppBootstrap CLI ✅

CLI `tools/bootstrap/bootstrap.mjs` que produce un `release/` deployable dado un preset.

Uso: `node tools/bootstrap/bootstrap.mjs --preset=hosteleria --slug=demo --nombre="Demo" --out=./builds/demo`

**Nota:** tras la Ola G, el CLI se actualizará para usar `--template=` en lugar de `--preset=` y apuntará a `templates/<nombre>/` en vez de `spa/src/modules/<nombre>/`.

---

## 8. Ola E — CAPABILITIES CITAS + CRM + NOTIFICACIONES ✅

Tres capabilities transversales completas. No se reabren.

| Capability     | Archivos                                                                         | Tests    |
| -------------- | -------------------------------------------------------------------------------- | -------- |
| CITAS          | CitasModel, RecursosModel, CitasEngine, CitasAdminApi, CitasPublicApi            | 9/9 ✅   |
| CRM            | ContactoModel, InteraccionModel, SegmentoEngine, CrmAdminApi                     | 15/15 ✅ |
| NOTIFICACIONES | drivers/ (Noop, Email, WhatsApp), Template, NotificationEngine, NotificationsApi | 14/14 ✅ |

---

## 9. Ola F — Template `clinica/` en SPA monolítica ✅ (pendiente migrar en G)

Se construyó `spa/src/modules/clinica/` con AgendaPage, PacientesPage, HistorialPage, StockPage, RecordatoriosPage. Funcional y con tests. **La Ola G la migra a `templates/clinica/` como proyecto Vite independiente**, que es la arquitectura definitiva.

---

## 10. Ola G — Migración a arquitectura de templates independientes ✅

**Objetivo:** pasar del modelo "módulos dentro de una SPA compartida" al modelo "proyecto Vite independiente por vertical". El backend PHP no se toca.

**Resultado:** `templates/hosteleria/` y `templates/clinica/` son proyectos Vite independientes. `@mylocal/sdk` es el paquete compartido. `build.ps1 --template=<nombre>` funcional.

### G.1 Crear `sdk/` — paquete compartido

- [X] `sdk/package.json` con `"name": "@mylocal/sdk"`, `"version": "1.0.0"`
- [X] `sdk/src/client.ts` — SynaxisClient extraído de `spa/src/synaxis/`
- [X] `sdk/src/auth.ts` — login / logout / session extraído de `spa/src/services/auth.service.ts`
- [X] `sdk/src/hooks.ts` — `useSynaxisClient` y demás hooks de `spa/src/hooks/`
- [X] `sdk/src/types.ts` — tipos comunes: `LocalInfo`, `UserInfo`, `AppUser`
- [X] `sdk/index.ts` — re-exporta todo
- [X] `sdk/tsconfig.json` — configuración de compilación del paquete

### G.2 Configurar pnpm workspaces

- [X] `pnpm-workspace.yaml` en la raíz con `packages: ['sdk', 'templates/*']`
- [X] `package.json` raíz con scripts: `"build:hosteleria"`, `"build:clinica"`, `"dev:hosteleria"`
- [X] `pnpm install` desde la raíz instala sdk + todos los templates
- [X] `import { useSynaxisClient } from '@mylocal/sdk'` resuelve correctamente

### G.3 Migrar `spa/` → `templates/hosteleria/`

- [X] `templates/hosteleria/` como proyecto Vite completo
- [X] `spa/src/` → `templates/hosteleria/src/` (excluido `modules/clinica/`, `app/modules-registry.ts`)
- [X] Stubs de re-exportación en `src/synaxis/`, `src/hooks/useSynaxis.ts`, `src/services/auth.service.ts`
- [X] `main.tsx` simplificado: imports directos, sin modules-registry
- [X] `vite.config.ts` ajustado con paths relativos correctos y alias `@mylocal/sdk`
- [X] `templates/hosteleria/package.json` con `"@mylocal/sdk": "workspace:*"`
- [X] `templates/hosteleria/manifest.json` — solo capabilities
- [X] Build verde: `pnpm -F hosteleria build` ✅ (324 kB JS)

### G.4 Migrar `spa/src/modules/clinica/` → `templates/clinica/`

- [X] `templates/clinica/` — proyecto Vite nuevo (package.json, vite.config.ts, index.html)
- [X] Páginas: AgendaPage, PacientesPage, HistorialPage, StockPage, RecordatoriosPage
- [X] `ClinicaContext` — context propio (client + localId)
- [X] `clinica.service.ts` — imports directos desde `@mylocal/sdk`
- [X] `App.tsx` — BrowserRouter + sidebar con `cl-*` CSS classes
- [X] `main.tsx` — SynaxisProvider desde `@mylocal/sdk`
- [X] `templates/clinica/manifest.json` — `["CITAS","CRM","NOTIFICACIONES"]`
- [X] Build verde: `pnpm -F clinica build` ✅ (212 kB JS)

### G.5 Actualizar `build.ps1`

- [X] Parámetro `$Template` (default: `"hosteleria"`)
- [X] `pnpm -F $Template build` cuando existe `templates/$Template/`
- [X] Fallback legacy: `cd spa && npm run build` si no existe el template
- [X] Retrocompatibilidad: `build.ps1` sin parámetros = hosteleria

### G.6 Actualizar scripts de desarrollo

- [X] `run.bat [clinica|hosteleria]` arranca el template correspondiente
- [X] Puerto 5173 para hosteleria, 5174 para clinica
- [X] `run.bat` sin parámetro = hosteleria (retrocompatible)

### G.7 Limpiar código obsoleto

- [X] `modules-registry.ts` eliminado de `templates/hosteleria/` (no existe en el nuevo template)
- [X] `spa/` conservada: contiene `spa/server/` (backend PHP, necesario para build.ps1)
- [X] Nota: `spa/src/` es legacy — se puede eliminar en Ola K durante el handover

### G.8 Builds verificados

- [X] `pnpm -F hosteleria build` verde ✅
- [X] `pnpm -F clinica build` verde ✅
- [X] `pnpm install` desde la raíz instala todo en un solo comando ✅

---

## 11. Ola H — Template `logistica/` ✅

**Objetivo:** demostrar el flujo de trabajo definitivo — nuevo template + nueva capability desde cero, completamente independiente.

**Resultado:** `templates/logistica/` con ruta pública `/seguimiento/:codigo`, `CAPABILITIES/DELIVERY/` completo, build verde en 212 kB JS.

### H.1 Diseño base

- [X] Template creado desde cero con diseño propio (`lg-*` CSS, paleta indigo #6366f1, sidebar oscura #1e1b4b)
- [X] `templates/logistica/` como proyecto Vite independiente (puerto 5175)
- [X] `@mylocal/sdk` como única dependencia de datos

### H.2 Páginas

- [X] `PedidosPage` — listado con filtro por estado + formulario nuevo pedido
- [X] `FlotaPage` — vehículos y conductores con toggle activo/inactivo
- [X] `EntregasPage` — vista del día con nav por fecha + asignación pedido→vehículo
- [X] `SeguimientoPublicoPage` — ruta pública `/seguimiento/:codigo`, sin login
- [X] `IncidenciasPage` — registro por tipo con descripción

### H.3 Backend

- [X] `CAPABILITIES/DELIVERY/` — PedidoModel, VehiculoModel, EntregaModel, IncidenciaModel
- [X] `DeliveryAdminApi.php` — 10 acciones admin
- [X] `DeliveryPublicApi.php` — `pedido_seguimiento` sin auth (respuesta reducida, sin datos internos)
- [X] `spa/server/handlers/delivery.php` + 11 acciones en `ALLOWED_ACTIONS`
- [X] 11 acciones DELIVERY añadidas al SDK `actions.ts` (scope: server, domain: delivery)

### H.4 Manifest

- [X] `templates/logistica/manifest.json` — `["LOGIN","OPTIONS","CRM","NOTIFICACIONES","DELIVERY"]`
- [X] `public_routes: ["/seguimiento/:codigo"]` declarado

### H.5 Build

- [X] `pnpm -F logistica build` verde ✅ (212 kB JS)

---

## 12. Ola I — Template `asesoria/` ✅

**Objetivo:** gestión documental, OCR de facturas y recordatorios fiscales. Reutiliza OCR + AI + CITAS ya construidos.

**Resultado:** `templates/asesoria/` completo con kanban de tareas, calendario fiscal, OCR drag-drop, clientes con NIF/régimen y placeholder de facturación Verifactu. `CAPABILITIES/TAREAS/` nuevo (transversal). Build verde 212 kB JS.

### I.1 Páginas

- [X] `ClientesPage` — CRM con datos fiscales (NIF, régimen, próximas obligaciones)
- [X] `DocumentosPage` — subida + OCR + clasificación asistida por IA con fallback gracioso
- [X] `CalendarioFiscalPage` — vencimientos por cliente (usa CITAS con recurso `r_fiscal`)
- [X] `TareasPage` — kanban 3 columnas: pendiente / en_curso / hecho con ChevronLeft/Right + Trash2
- [X] `FacturasPage` — placeholder apuntando a capability FISCAL (Verifactu/TicketBAI)

### I.2 Backend

- [X] `CAPABILITIES/TAREAS/` — TareaModel.php, TareasApi.php, capability.json (nuevo, transversal)
- [X] `spa/server/handlers/tareas.php` + 4 acciones en ALLOWED_ACTIONS
- [X] 4 acciones TAREAS añadidas al SDK `actions.ts` (scope: server, domain: tareas)
- [X] FISCAL y OCR ya existían — no se duplicaron

### I.3 Manifest + CSS

- [X] `templates/asesoria/manifest.json` — `["LOGIN","OPTIONS","CRM","CITAS","NOTIFICACIONES","OCR","FISCAL","TAREAS","AI"]`
- [X] `src/asesoria.css` — prefijo `as-*`, acento teal #0d9488, sidebar #0f2937
- [X] `src/services/asesoria.service.ts` con tipos y wrappers tipados

### I.4 Build

- [X] `pnpm -F asesoria build` verde ✅ (212 kB JS)

---

## 13. Ola J — Integración OpenClaude + OPENCLAW ✅

**Objetivo:** conectar MyLocal con agentes IA externos sin acoplamiento. Dos piezas independientes: conector Anthropic Claude (texto/AI) + integración bidireccional con OpenClaw (agente local).

**Resultado:** `EventBus.php` como bus interno de eventos; `OpenClaudeClient.php` para llamadas a la API de Anthropic (toggle por config); `CAPABILITIES/OPENCLAW/` para integración bidireccional con el agente OpenClaw — manifest dinámico por despliegue, executor proxy, push de eventos en tiempo real.

### J.1 Conector PHP

- [X] `CAPABILITIES/AI/OpenClaudeClient.php` — cliente HTTP Anthropic Messages API sin SDK externo
- [X] Auth por bearer en namespace `openclaude` de OPTIONS (cero hardcoding)
- [X] Si no configurado → `isEnabled() = false`, handlers responden `enabled: false`
- [X] `CORE/EventBus.php` ≤ 80 LOC — bus interno de eventos con catch de excepciones
- [X] Eventos iniciales: `pedido.creado`, `cita.cancelada`, `stock.bajo`
- [X] `CAPABILITIES/AI/OpenClaudeListeners.php` — listeners por defecto (stock.bajo → notif + AI summary)
- [X] `CAPABILITIES/AI/OpenClaudeApi.php` — acciones `openclaude_status` y `openclaude_complete`
- [X] `spa/server/handlers/openclaude.php` — handler que carga todo y registra listeners
- [X] `spa/server/index.php` — 2 acciones nuevas en ALLOWED_ACTIONS + dispatch cases
- [X] SDK `actions.ts` — `openclaude_status` y `openclaude_complete` (scope: server, domain: ai)

### J.2 Integración con capabilities existentes

- [X] `DELIVERY/PedidoModel::create()` emite `pedido.creado` si EventBus cargado
- [X] `CITAS/CitasModel::cancel()` emite `cita.cancelada` si EventBus cargado
- [X] Caso `stock.bajo` manejado en listener: llama a Claude si enabled; fallback Noop driver siempre

### J.3 Build

- [X] `pnpm -F asesoria build` verde ✅ (212 kB JS) — confirma que el SDK con nuevas acciones compila

### J.4 Integración OPENCLAW (agente local bidireccional)

- [X] `CAPABILITIES/OPENCLAW/capability.json` — declara 4 acciones + config keys
- [X] `OpenClawSkillManifest.php` — manifest **dinámico desde OPTIONS** (`openclaw.tools`, `openclaw.app_name`); sin herramientas hardcodeadas; cada despliegue declara lo suyo
- [X] `OpenClawSkillExecutor.php` — proxy a acciones MyLocal existentes; valida contra `openclaw.allowed_actions` del admin; sin whitelist configurada → nada permitido (fail-safe)
- [X] `OpenClawPushClient.php` — empuja eventos a OpenClaw vía HTTP (push_url configurable, timeout 2s)
- [X] `OpenClawListeners.php` — listeners EventBus → push al agente (pedido.creado, cita.cancelada, stock.bajo)
- [X] `OpenClawApi.php` — handler de 4 acciones (manifest público, call con skill-key, status/push admin)
- [X] `spa/server/handlers/openclaw_skill.php` — carga capability + registra listeners
- [X] `spa/server/index.php` — 4 acciones OPENCLAW en ALLOWED_ACTIONS + dispatch cases
- [X] SDK `actions.ts` — 4 acciones OPENCLAW (scope: server, domain: openclaw)

### Principio de diseño OPENCLAW

OpenClaw conecta con **la app desplegada**, no con "MyLocal" en abstracto.

- Una asesoria configura `openclaw.allowed_actions = ["tarea_create", "cita_list"]`
- Una hosteleria configura `["list_productos", "crm_contacto_list"]`
- Un portfolio puede no configurar nada y el agente no tendrá acceso a datos
- El agente se adapta al despliegue — el framework no decide por él

### Criterio de salida Ola J ✅

- Conector Anthropic se enciende/apaga por config sin tocar código ✅
- OpenClaw caído → timeout 2s, MyLocal no se cae ✅
- Manifest de skill dinámico — cada despliegue declara sus herramientas en OPTIONS ✅
- Eventos de MyLocal empujados al agente local en tiempo real ✅

---

## 14. Ola K — Documentación + handover

**Objetivo:** otro desarrollador entra, lee, y monta un vertical nuevo en ≤ 1 día.

- [X] `docs/FRAMEWORK.md` — arquitectura, flujo de trabajo, cómo añadir template y capability
- [X] `docs/SDK.md` — API del `@mylocal/sdk`: qué exporta, cómo usarlo desde un template
- [X] `docs/CAPABILITIES.md` — listado, dependencias, acciones, roles
- [X] `docs/BOOTSTRAP.md` — `build.ps1 --template=<nombre>`, estructura de manifest.json
- [X] `templates/<nombre>/README.md` por cada template: páginas, capabilities, datos esperados
- [X] Actualizar `CLAUDE.md` raíz con puntero a este documento y estado de olas

---

## 15. Gates entre olas

Ninguna ola arranca sin que la anterior haya cumplido **todos** estos gates:

1. `build.ps1` verde con `test_login.php` 31/31
2. Sin warnings TS en el build del template activo
3. Sin archivos > 250 LOC introducidos
4. Sin TODOs nuevos en el código (solo permitidos si enlazan a una tarea de este plan)
5. Sin datos ficticios en UI ni seeds
6. El dueño del proyecto da luz verde explícita

---

## 16. Riesgos y mitigaciones

| Riesgo                                                     | Mitigación                                                                                   |
| ---------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| Drop-in de Lovable usa librerías incompatibles con sdk    | SDK solo exporta lógica (hooks, client), no componentes UI — máxima compatibilidad         |
| pnpm workspaces complica el CI/CD en hosting compartido    | El build.ps1 genera un release/ estático — el hosting solo recibe PHP + assets, nunca Node  |
| Un template rompe el AUTH_LOCK al cambiar rutas            | test_login.php es gate del build — si falla, el build aborta independientemente del template |
| La migración de spa/ rompe hosteleria en producción      | La migración ocurre en local; solo se sube cuando test_login + build verde                   |
| Dos templates en desarrollo simultáneo generan conflictos | Cada template es carpeta independiente — cero archivos compartidos entre templates           |

---

## 17. Indicador de estado

- [X] Ola 0 — Preflight
- [X] Ola A — Refactor `modules/hosteleria/`
- [X] Ola B — Manifest dinámico
- [X] Ola C — Runtime `app/` + `_shared/`
- [X] Ola D — AppBootstrap CLI
- [X] Ola E — CAPABILITIES CITAS + CRM + NOTIFICACIONES
- [X] Ola F — Template `clinica/` (en SPA — migrado en G)
- [X] Ola G — Migración a arquitectura de templates independientes
- [X] Ola H — Template `logistica/`
- [X] Ola I — Template `asesoria/`
- [X] Ola J — Integración OpenClaude
- [X] Ola K — Documentación + handover
- [X] Ola L — Cierre técnico (tests AUTH_LOCK pendientes + AppBootstrap v2 + limpieza legacy + split SynaxisCore)

---

## 18. Estado final

**Olas 0–L: todas completadas, cero deuda.**

### Saldo de la Ola L

| Item                                                      | Antes (post Ola K)                                      | Después (Ola L)                                                                                               |
| --------------------------------------------------------- | ------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| Tests AUTH_LOCK para DELIVERY                             | ausente                                                 | `spa/server/tests/test_delivery.php` — 33/33 ✅                                                             |
| Tests AUTH_LOCK para TAREAS                               | ausente                                                 | `spa/server/tests/test_tareas.php` — 17/17 ✅                                                               |
| Tests para OpenClaude + EventBus                          | ausente                                                 | `spa/server/tests/test_openclaude.php` — 19/19 ✅                                                           |
| Tests para OPENCLAW (manifest + fail-safe)                | ausente                                                 | `spa/server/tests/test_openclaw.php` — 26/26 ✅                                                             |
| Bug latente OpenClawApi `_oc_call` (ArgumentCountError) | sin detectar                                            | corregido + cubierto por test (regresión documentada)                                                         |
| AppBootstrap CLI                                          | apuntaba a `spa/src/modules/<id>/` (estructura pre-G) | v2: lee `templates/<id>/manifest.json`, `--template=` (alias `--preset=`), 4 presets disponibles         |
| Limpieza `_rl/` en bootstrap                            | inexistente                                             | bootstrap limpia `data/_rl/` antes del gate                                                                  |
| `spa/src/` legacy                                       | duplicaba `templates/hosteleria/src/`                 | eliminado,`build.ps1` y `run.bat` sin fallbacks legacy                                                     |
| `sdk/src/synaxis/SynaxisCore.ts` 262 LOC                | sobre el límite 250                                    | partido: SynaxisCore 194 + Helpers 48 + Versioning 51 + Oplog 52 + RequestNormalize 49 = 5 archivos ≤ 200 LOC |
| `build.ps1` ejecuta tests de capabilities               | solo `test_login`                                     | 7 gates: CITAS, CRM, NOTIF, DELIVERY, TAREAS, OPENCLAUDE, OPENCLAW                                             |
| `build.ps1` para 4 templates                            | solo hosteleria validado                                | hosteleria + clinica + logistica + asesoria todos verdes                                                       |

### Acumulado de tests verdes

- `test_login.php`              75/75
- `test_citas.php`               9/9
- `test_crm.php`                15/15
- `test_notif.php`              14/14
- `test_delivery.php`           33/33 (Ola L)
- `test_tareas.php`             17/17 (Ola L)
- `test_openclaude.php`         19/19 (Ola L)
- `test_openclaw.php`           26/26 (Ola L)
- **Total: 208 PASS / 0 FAIL**
- Framework SPA (`test-framework.mjs`): 24/24
- Bootstrap CLI (`test-bootstrap.mjs`): 19/19

### Cómo añadir un nuevo vertical ahora mismo

```
1. cp -r templates/clinica/ templates/veterinaria/
2. Editar templates/veterinaria/manifest.json  ← capabilities que necesita
3. Editar templates/veterinaria/package.json   ← name: "veterinaria"
4. Editar src/App.tsx y src/pages/             ← UI propia
5. pnpm install && pnpm -F veterinaria build   ← build verde
6. node tools/bootstrap/bootstrap.mjs --template=veterinaria --slug=cliente --nombre="Cliente"
   --out=./builds/cliente  ← release listo para subir
7. Si necesita agente: OPTIONS openclaw.allowed_actions + openclaw.tools
```
