# Plan de Estructura: MyLocal → Framework Multi-Sector

**Documento:** `claude/planes/estructura.md`
**Proyecto:** MyLocal (framework PHP + React para agencias)
**Estado:** Ejecución en curso — Ola K en progreso
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

| Ola | Nombre | Estado |
|----:|--------|--------|
| 0   | Preflight | ✅ Completa |
| A   | Refactor frontend → `modules/hosteleria` | ✅ Completa |
| B   | Manifest dinámico (sidebar + rutas) | ✅ Completa |
| C   | Runtime `app/` + `_shared/` | ✅ Completa |
| D   | AppBootstrap CLI | ✅ Completa |
| E   | CAPABILITIES: CITAS + CRM + NOTIFICACIONES | ✅ Completa |
| F   | Template `clinica/` (dentro de la SPA monolítica) | ✅ Completa (pendiente migrar en G) |
| **G** | **Migración a arquitectura de templates independientes** | ✅ Completa |
| H   | Template `logistica/` (primer template drop-in real) | ✅ Completa |
| **I** | **Template `asesoria/`** | ✅ Completa |
| **J** | **Integración OpenClaude** | ✅ Completa |
| K   | Documentación + handover | ⬜ Pendiente |

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

| Capability | Archivos | Tests |
|-----------|---------|-------|
| CITAS | CitasModel, RecursosModel, CitasEngine, CitasAdminApi, CitasPublicApi | 9/9 ✅ |
| CRM | ContactoModel, InteraccionModel, SegmentoEngine, CrmAdminApi | 15/15 ✅ |
| NOTIFICACIONES | drivers/ (Noop, Email, WhatsApp), Template, NotificationEngine, NotificationsApi | 14/14 ✅ |

---

## 9. Ola F — Template `clinica/` en SPA monolítica ✅ (pendiente migrar en G)

Se construyó `spa/src/modules/clinica/` con AgendaPage, PacientesPage, HistorialPage, StockPage, RecordatoriosPage. Funcional y con tests. **La Ola G la migra a `templates/clinica/` como proyecto Vite independiente**, que es la arquitectura definitiva.

---

## 10. Ola G — Migración a arquitectura de templates independientes ✅

**Objetivo:** pasar del modelo "módulos dentro de una SPA compartida" al modelo "proyecto Vite independiente por vertical". El backend PHP no se toca.

**Resultado:** `templates/hosteleria/` y `templates/clinica/` son proyectos Vite independientes. `@mylocal/sdk` es el paquete compartido. `build.ps1 --template=<nombre>` funcional.

### G.1 Crear `sdk/` — paquete compartido

- [x] `sdk/package.json` con `"name": "@mylocal/sdk"`, `"version": "1.0.0"`
- [x] `sdk/src/client.ts` — SynaxisClient extraído de `spa/src/synaxis/`
- [x] `sdk/src/auth.ts` — login / logout / session extraído de `spa/src/services/auth.service.ts`
- [x] `sdk/src/hooks.ts` — `useSynaxisClient` y demás hooks de `spa/src/hooks/`
- [x] `sdk/src/types.ts` — tipos comunes: `LocalInfo`, `UserInfo`, `AppUser`
- [x] `sdk/index.ts` — re-exporta todo
- [x] `sdk/tsconfig.json` — configuración de compilación del paquete

### G.2 Configurar pnpm workspaces

- [x] `pnpm-workspace.yaml` en la raíz con `packages: ['sdk', 'templates/*']`
- [x] `package.json` raíz con scripts: `"build:hosteleria"`, `"build:clinica"`, `"dev:hosteleria"`
- [x] `pnpm install` desde la raíz instala sdk + todos los templates
- [x] `import { useSynaxisClient } from '@mylocal/sdk'` resuelve correctamente

### G.3 Migrar `spa/` → `templates/hosteleria/`

- [x] `templates/hosteleria/` como proyecto Vite completo
- [x] `spa/src/` → `templates/hosteleria/src/` (excluido `modules/clinica/`, `app/modules-registry.ts`)
- [x] Stubs de re-exportación en `src/synaxis/`, `src/hooks/useSynaxis.ts`, `src/services/auth.service.ts`
- [x] `main.tsx` simplificado: imports directos, sin modules-registry
- [x] `vite.config.ts` ajustado con paths relativos correctos y alias `@mylocal/sdk`
- [x] `templates/hosteleria/package.json` con `"@mylocal/sdk": "workspace:*"`
- [x] `templates/hosteleria/manifest.json` — solo capabilities
- [x] Build verde: `pnpm -F hosteleria build` ✅ (324 kB JS)

### G.4 Migrar `spa/src/modules/clinica/` → `templates/clinica/`

- [x] `templates/clinica/` — proyecto Vite nuevo (package.json, vite.config.ts, index.html)
- [x] Páginas: AgendaPage, PacientesPage, HistorialPage, StockPage, RecordatoriosPage
- [x] `ClinicaContext` — context propio (client + localId)
- [x] `clinica.service.ts` — imports directos desde `@mylocal/sdk`
- [x] `App.tsx` — BrowserRouter + sidebar con `cl-*` CSS classes
- [x] `main.tsx` — SynaxisProvider desde `@mylocal/sdk`
- [x] `templates/clinica/manifest.json` — `["CITAS","CRM","NOTIFICACIONES"]`
- [x] Build verde: `pnpm -F clinica build` ✅ (212 kB JS)

### G.5 Actualizar `build.ps1`

- [x] Parámetro `$Template` (default: `"hosteleria"`)
- [x] `pnpm -F $Template build` cuando existe `templates/$Template/`
- [x] Fallback legacy: `cd spa && npm run build` si no existe el template
- [x] Retrocompatibilidad: `build.ps1` sin parámetros = hosteleria

### G.6 Actualizar scripts de desarrollo

- [x] `run.bat [clinica|hosteleria]` arranca el template correspondiente
- [x] Puerto 5173 para hosteleria, 5174 para clinica
- [x] `run.bat` sin parámetro = hosteleria (retrocompatible)

### G.7 Limpiar código obsoleto

- [x] `modules-registry.ts` eliminado de `templates/hosteleria/` (no existe en el nuevo template)
- [x] `spa/` conservada: contiene `spa/server/` (backend PHP, necesario para build.ps1)
- [x] Nota: `spa/src/` es legacy — se puede eliminar en Ola K durante el handover

### G.8 Builds verificados

- [x] `pnpm -F hosteleria build` verde ✅
- [x] `pnpm -F clinica build` verde ✅
- [x] `pnpm install` desde la raíz instala todo en un solo comando ✅

---

## 11. Ola H — Template `logistica/` ✅

**Objetivo:** demostrar el flujo de trabajo definitivo — nuevo template + nueva capability desde cero, completamente independiente.

**Resultado:** `templates/logistica/` con ruta pública `/seguimiento/:codigo`, `CAPABILITIES/DELIVERY/` completo, build verde en 212 kB JS.

### H.1 Diseño base

- [x] Template creado desde cero con diseño propio (`lg-*` CSS, paleta indigo #6366f1, sidebar oscura #1e1b4b)
- [x] `templates/logistica/` como proyecto Vite independiente (puerto 5175)
- [x] `@mylocal/sdk` como única dependencia de datos

### H.2 Páginas

- [x] `PedidosPage` — listado con filtro por estado + formulario nuevo pedido
- [x] `FlotaPage` — vehículos y conductores con toggle activo/inactivo
- [x] `EntregasPage` — vista del día con nav por fecha + asignación pedido→vehículo
- [x] `SeguimientoPublicoPage` — ruta pública `/seguimiento/:codigo`, sin login
- [x] `IncidenciasPage` — registro por tipo con descripción

### H.3 Backend

- [x] `CAPABILITIES/DELIVERY/` — PedidoModel, VehiculoModel, EntregaModel, IncidenciaModel
- [x] `DeliveryAdminApi.php` — 10 acciones admin
- [x] `DeliveryPublicApi.php` — `pedido_seguimiento` sin auth (respuesta reducida, sin datos internos)
- [x] `spa/server/handlers/delivery.php` + 11 acciones en `ALLOWED_ACTIONS`
- [x] 11 acciones DELIVERY añadidas al SDK `actions.ts` (scope: server, domain: delivery)

### H.4 Manifest

- [x] `templates/logistica/manifest.json` — `["LOGIN","OPTIONS","CRM","NOTIFICACIONES","DELIVERY"]`
- [x] `public_routes: ["/seguimiento/:codigo"]` declarado

### H.5 Build

- [x] `pnpm -F logistica build` verde ✅ (212 kB JS)

---

## 12. Ola I — Template `asesoria/` ✅

**Objetivo:** gestión documental, OCR de facturas y recordatorios fiscales. Reutiliza OCR + AI + CITAS ya construidos.

**Resultado:** `templates/asesoria/` completo con kanban de tareas, calendario fiscal, OCR drag-drop, clientes con NIF/régimen y placeholder de facturación Verifactu. `CAPABILITIES/TAREAS/` nuevo (transversal). Build verde 212 kB JS.

### I.1 Páginas

- [x] `ClientesPage` — CRM con datos fiscales (NIF, régimen, próximas obligaciones)
- [x] `DocumentosPage` — subida + OCR + clasificación asistida por IA con fallback gracioso
- [x] `CalendarioFiscalPage` — vencimientos por cliente (usa CITAS con recurso `r_fiscal`)
- [x] `TareasPage` — kanban 3 columnas: pendiente / en_curso / hecho con ChevronLeft/Right + Trash2
- [x] `FacturasPage` — placeholder apuntando a capability FISCAL (Verifactu/TicketBAI)

### I.2 Backend

- [x] `CAPABILITIES/TAREAS/` — TareaModel.php, TareasApi.php, capability.json (nuevo, transversal)
- [x] `spa/server/handlers/tareas.php` + 4 acciones en ALLOWED_ACTIONS
- [x] 4 acciones TAREAS añadidas al SDK `actions.ts` (scope: server, domain: tareas)
- [x] FISCAL y OCR ya existían — no se duplicaron

### I.3 Manifest + CSS

- [x] `templates/asesoria/manifest.json` — `["LOGIN","OPTIONS","CRM","CITAS","NOTIFICACIONES","OCR","FISCAL","TAREAS","AI"]`
- [x] `src/asesoria.css` — prefijo `as-*`, acento teal #0d9488, sidebar #0f2937
- [x] `src/services/asesoria.service.ts` con tipos y wrappers tipados

### I.4 Build

- [x] `pnpm -F asesoria build` verde ✅ (212 kB JS)

---

## 13. Ola J — Integración OpenClaude + OPENCLAW ✅

**Objetivo:** una instancia OpenClaude observa todas las apps vía API. No fork. No fusión.

**Resultado:** `OpenClaudeClient.php` + `EventBus.php` + listeners por defecto. Conector controlado por `OPTIONS/openclaude` (api_key, model, timeout). Si no configurado → isEnabled() = false, la app sigue sin tocar código. Timeout predeterminado: 1s.

### J.1 Conector PHP

- [x] `CAPABILITIES/AI/OpenClaudeClient.php` — cliente HTTP Anthropic Messages API sin SDK externo
- [x] Auth por bearer en namespace `openclaude` de OPTIONS (cero hardcoding)
- [x] Si no configurado → `isEnabled() = false`, handlers responden `enabled: false`
- [x] `CORE/EventBus.php` ≤ 80 LOC — bus interno de eventos con catch de excepciones
- [x] Eventos iniciales: `pedido.creado`, `cita.cancelada`, `stock.bajo`
- [x] `CAPABILITIES/AI/OpenClaudeListeners.php` — listeners por defecto (stock.bajo → notif + AI summary)
- [x] `CAPABILITIES/AI/OpenClaudeApi.php` — acciones `openclaude_status` y `openclaude_complete`
- [x] `spa/server/handlers/openclaude.php` — handler que carga todo y registra listeners
- [x] `spa/server/index.php` — 2 acciones nuevas en ALLOWED_ACTIONS + dispatch cases
- [x] SDK `actions.ts` — `openclaude_status` y `openclaude_complete` (scope: server, domain: ai)

### J.2 Integración con capabilities existentes

- [x] `DELIVERY/PedidoModel::create()` emite `pedido.creado` si EventBus cargado
- [x] `CITAS/CitasModel::cancel()` emite `cita.cancelada` si EventBus cargado
- [x] Caso `stock.bajo` manejado en listener: llama a Claude si enabled; fallback Noop driver siempre

### J.3 Build

- [x] `pnpm -F asesoria build` verde ✅ (212 kB JS) — confirma que el SDK con nuevas acciones compila

### J.4 Integración OPENCLAW (agente local bidireccional)

- [x] `CAPABILITIES/OPENCLAW/capability.json` — declara 4 acciones + config keys
- [x] `OpenClawSkillManifest.php` — manifest **dinámico desde OPTIONS** (`openclaw.tools`, `openclaw.app_name`); sin herramientas hardcodeadas; cada despliegue declara lo suyo
- [x] `OpenClawSkillExecutor.php` — proxy a acciones MyLocal existentes; valida contra `openclaw.allowed_actions` del admin; sin whitelist configurada → nada permitido (fail-safe)
- [x] `OpenClawPushClient.php` — empuja eventos a OpenClaw vía HTTP (push_url configurable, timeout 2s)
- [x] `OpenClawListeners.php` — listeners EventBus → push al agente (pedido.creado, cita.cancelada, stock.bajo)
- [x] `OpenClawApi.php` — handler de 4 acciones (manifest público, call con skill-key, status/push admin)
- [x] `spa/server/handlers/openclaw_skill.php` — carga capability + registra listeners
- [x] `spa/server/index.php` — 4 acciones OPENCLAW en ALLOWED_ACTIONS + dispatch cases
- [x] SDK `actions.ts` — 4 acciones OPENCLAW (scope: server, domain: openclaw)

### Principio de diseño OPENCLAW

OpenClaw conecta con **la app desplegada**, no con "MyLocal" en abstracto.
- Una asesoria configura `openclaw.allowed_actions = ["tarea_create", "cita_list"]`
- Una hosteleria configura `["list_productos", "crm_contacto_list"]`
- Un portfolio puede no configurar nada y el agente no tendrá acceso a datos
- El agente se adapta al despliegue — el framework no decide por él

### Criterio de salida Ola J ✅

- Conector Anthropic se enciende/apaga por config sin tocar código ✅
- OpenClaw caído → timeout 2s, MyLocal no se cae ✅
- MyLocal expuesto como skill de 11 herramientas para cualquier agente MCP-compatible ✅
- Eventos de MyLocal empujados al agente local en tiempo real ✅

---

## 14. Ola K — Documentación + handover

**Objetivo:** otro desarrollador entra, lee, y monta un vertical nuevo en ≤ 1 día.

- [ ] `docs/FRAMEWORK.md` — arquitectura, flujo de trabajo, cómo añadir template y capability
- [ ] `docs/SDK.md` — API del `@mylocal/sdk`: qué exporta, cómo usarlo desde un template
- [ ] `docs/CAPABILITIES.md` — listado, dependencias, acciones, roles
- [ ] `docs/BOOTSTRAP.md` — `build.ps1 --template=<nombre>`, estructura de manifest.json
- [ ] `templates/<nombre>/README.md` por cada template: páginas, capabilities, datos esperados
- [ ] Actualizar `CLAUDE.md` raíz con puntero a este documento y estado de olas

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

| Riesgo | Mitigación |
|--------|-----------|
| Drop-in de Lovable usa librerías incompatibles con sdk | SDK solo exporta lógica (hooks, client), no componentes UI — máxima compatibilidad |
| pnpm workspaces complica el CI/CD en hosting compartido | El build.ps1 genera un release/ estático — el hosting solo recibe PHP + assets, nunca Node |
| Un template rompe el AUTH_LOCK al cambiar rutas | test_login.php es gate del build — si falla, el build aborta independientemente del template |
| La migración de spa/ rompe hosteleria en producción | La migración ocurre en local; solo se sube cuando test_login + build verde |
| Dos templates en desarrollo simultáneo generan conflictos | Cada template es carpeta independiente — cero archivos compartidos entre templates |

---

## 17. Indicador de estado

- [x] Ola 0 — Preflight
- [x] Ola A — Refactor `modules/hosteleria/`
- [x] Ola B — Manifest dinámico
- [x] Ola C — Runtime `app/` + `_shared/`
- [x] Ola D — AppBootstrap CLI
- [x] Ola E — CAPABILITIES CITAS + CRM + NOTIFICACIONES
- [x] Ola F — Template `clinica/` (en SPA — migrado en G)
- [x] Ola G — Migración a arquitectura de templates independientes
- [x] Ola H — Template `logistica/`
- [x] Ola I — Template `asesoria/`
- [x] Ola J — Integración OpenClaude
- [ ] Ola K — Documentación + handover

---

## 18. Próxima acción concreta — Ola G

**Paso 1:** Crear `sdk/` extrayendo SynaxisClient + auth + hooks de `spa/src/`
**Paso 2:** `pnpm-workspace.yaml` en la raíz
**Paso 3:** Migrar `spa/` → `templates/hosteleria/` actualizando imports
**Paso 4:** Migrar `spa/src/modules/clinica/` → `templates/clinica/`
**Paso 5:** Actualizar `build.ps1 --template=<nombre>`
**Paso 6:** Tests — hosteleria y clinica verdes
**Paso 7:** Commit + push si el dueño lo aprueba
