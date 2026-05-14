# CAPABILITIES — Catálogo completo

Módulos PHP reutilizables. Cada uno vive en `CAPABILITIES/<ID>/` y declara sus dependencias, colecciones y acciones en `capability.json`.

---

## Núcleo (bloqueado — no modificar sin leer AUTH_LOCK.md)

### LOGIN
**Descripción:** Autenticación. Bearer-only, sin cookies. Gate de seguridad del sistema.
**Depende de:** OPTIONS
**Bloqueado:** sí — cualquier cambio requiere pasar `test_login.php` (31 assertions)
**Exporta:** LoginCapability, LoginSessions, LoginRoles, LoginVault, LoginBootstrap
**Default:** `socola@socola.es` / `socola2026` (auto-bootstrap en primer arranque)

### OPTIONS
**Descripción:** Source of truth de configuración. Namespaces JSON en `STORAGE/options/`.
**Tipo:** core (todas las capabilities lo necesitan)
**Acciones:** `options_get`, `options_set`, `options_get_namespace`, `options_set_namespace`, `options_list_namespaces`
**Namespaces predefinidos:** `ai`, `branding`, `billing`, `fiscal`, `openclaude`, `openclaw`

---

## Transversales (usables por cualquier vertical)

### CRM
**Descripción:** Contactos B2C/B2B, interacciones, etiquetas y segmentación básica.
**Depende de:** LOGIN, OPTIONS
**Colecciones:** `crm_contactos`, `crm_interacciones`
**Acciones:**
- `crm_contacto_create` / `crm_contacto_update` / `crm_contacto_get` / `crm_contacto_list` / `crm_contacto_delete`
- `crm_interaccion_add` / `crm_interaccion_list`
- `crm_segmento_query`

**Roles requeridos:** admin, administrador, superadmin (escritura); editor (lectura)

---

### CITAS
**Descripción:** Agenda de citas para cualquier servicio agendable. Reutilizable en clínicas, asesorías, talleres.
**Depende de:** LOGIN, OPTIONS, NOTIFICACIONES
**Colecciones:** `citas`, `recursos_agenda`
**Acciones:**
- `cita_create` / `cita_update` / `cita_list` / `cita_cancel` / `cita_get`
- `recurso_create` / `recurso_update` / `recurso_list` / `recurso_delete`
- `cita_publica_crear` (sin auth — para formularios públicos)

**Nota:** La asesoria reutiliza esta capability con `recurso_id: 'r_fiscal'` para el calendario fiscal.

---

### NOTIFICACIONES
**Descripción:** Envío de notificaciones con drivers intercambiables.
**Depende de:** LOGIN, OPTIONS
**Colecciones:** `notif_log`
**Acciones:** `notif_send`, `notif_list`, `notif_template_list`
**Drivers:** `noop` (default, silencioso), `email` (SMTP), `whatsapp`
**Configurar driver:** `data.config.notif_settings.driver = "email"`

---

### TAREAS
**Descripción:** Kanban mínimo de 3 columnas para cualquier vertical.
**Depende de:** LOGIN, OPTIONS
**Colecciones:** `tareas`
**Acciones:** `tarea_create`, `tarea_list`, `tarea_update`, `tarea_delete`
**Estados:** `pendiente` → `en_curso` → `hecho`
**Prioridades:** `alta`, `media` (default), `baja`

---

### AI
**Descripción:** Motor IA — cliente OpenAI-compatible (llama.cpp/vLLM) + conector Anthropic Claude + EventBus transversal.
**Depende de:** LOGIN, OPTIONS
**Acciones:** `chat`, `vision`, `ocr_extract`, `ocr_parse`, `openclaude_status`, `openclaude_complete`
**Config keys:** `ai.local_endpoint`, `ai.local_api_key`, `ai.local_model`, `openclaude.api_key`, `openclaude.model`, `openclaude.timeout`
**Nota:** Si `openclaude.api_key` está vacío → `isEnabled() = false`, la app sigue sin errores.

---

### OPENCLAW
**Descripción:** Integración bidireccional con el agente OpenClaw.
**Depende de:** LOGIN, OPTIONS
**Acciones:** `openclaw_manifest` (público), `openclaw_call` (auth por skill-key), `openclaw_status` (admin), `openclaw_event_push` (admin)
**Config keys:**

| Clave | Descripción |
|-------|-------------|
| `openclaw.skill_api_key` | Clave que OpenClaw envía en `X-MyLocal-Skill-Key` |
| `openclaw.push_url` | Webhook de OpenClaw (ej: `http://localhost:3001/api/send`) |
| `openclaw.push_channel` | Canal destino del agente (telegram, whatsapp…) |
| `openclaw.allowed_actions` | Array de acciones MyLocal que el agente puede ejecutar |
| `openclaw.tools` | Array de definiciones de herramientas para el manifest |
| `openclaw.app_name` | Nombre público de la app en el manifest |

**Principio:** El manifest y la whitelist son por despliegue, no hardcodeados en el framework.

---

## Hostelería (específicos)

### CARTA
**Descripción:** Carta digital hostelera con multiidioma y alérgenos.
**Uso:** template `hosteleria`

### QR
**Descripción:** Códigos QR por mesa — clientes ven carta y piden desde el móvil.
**Uso:** template `hosteleria`

### TPV
**Descripción:** Terminal Punto de Venta — caja y gestión de tickets en tiempo real.
**Uso:** template `hosteleria`

### AGENTE_RESTAURANTE
**Descripción:** IA especializada en servicio de restaurante (Cocina, Sala, Sommelier).
**Uso:** template `hosteleria`

### GEMINI
**Descripción:** Asistente inteligente integrado con el conocimiento del negocio.
**Uso:** hostelería, extensible a otros verticales

---

## Genéricos

### PRODUCTS
**Descripción:** Gestión de productos, categorías e inventario.
**Uso:** cualquier vertical con catálogo de productos

### PAYMENT
**Descripción:** Pasarela de pagos con drivers intercambiables.
**Uso:** cualquier vertical con cobros online

### FISCAL
**Descripción:** Verifactu y TicketBAI — cumplimiento fiscal español.
**Uso:** asesoria, hostelería con facturación electrónica

### DELIVERY
**Descripción:** Gestión de pedidos, flota y entregas para logística y reparto.
**Depende de:** LOGIN, OPTIONS, CRM, NOTIFICACIONES
**Colecciones:** `pedidos`, `vehiculos`, `entregas`
**Acciones:** `pedido_create`, `pedido_list`, `pedido_get`, `pedido_estado`, `vehiculo_create`, `vehiculo_list`, `vehiculo_update`, `entrega_asignar`, `entrega_list_dia`, `incidencia_add`, `pedido_seguimiento` (público)
**Nota:** `pedido_seguimiento` no requiere auth — es la ruta pública de tracking.

---

## Árbol de dependencias

```
OPTIONS  ←────────────────────────────── todas las capabilities
LOGIN    ← OPTIONS
NOTIFICACIONES ← LOGIN, OPTIONS
CRM      ← LOGIN, OPTIONS
CITAS    ← LOGIN, OPTIONS, NOTIFICACIONES
TAREAS   ← LOGIN, OPTIONS
DELIVERY ← LOGIN, OPTIONS, CRM, NOTIFICACIONES
AI       ← LOGIN, OPTIONS
OPENCLAW ← LOGIN, OPTIONS
CARTA    ← (standalone)
TPV      ← (standalone)
```

---

## Registrar una capability nueva en el dispatcher

1. Crear `spa/server/handlers/<id>.php` con los `require_once` de los archivos PHP
2. Añadir acciones a `ALLOWED_ACTIONS` en `spa/server/index.php`
3. Añadir `case` en el switch de dispatch
4. Añadir entradas en `sdk/src/synaxis/actions.ts` con `scope: 'server'`
5. Declarar en `manifest.json` de cada template que la usa
