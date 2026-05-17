# Plan de Desarrollo: MyLocal — MVP Fase 1
**Documento:** `claude/planes/desarrolloventasuno.md`
**Última revisión:** 2026-05-17
**Estado:** En ejecución — M1–M9 completas, pendiente M10 (despliegue)

---

## 0. Contexto: lo que existe y lo que falta

### Lo que está construido y funciona (no reabrir)

| Bloque | Estado | Notas |
|--------|--------|-------|
| Framework multi-sector (olas 0–L) | Completo | SDK, templates, CAPABILITIES, build.ps1 |
| `@mylocal/sdk` pnpm workspaces | Completo | SynaxisProvider, login, useSynaxisClient |
| Templates: hosteleria, clinica, logistica, asesoria | Completos como apps Vite | hosteleria = landing page + SDK integrado |
| CAPABILITIES: LOGIN, OPTIONS, CARTA, QR, OCR, PDFGEN, TPV, AI, DELIVERY, CITAS, CRM, NOTIFICACIONES, TAREAS, OPENCLAW | Completos | 208 tests PASS |
| Auth (bearer-only, sin cookies, sin CSRF) | Blindado | `claude/AUTH_LOCK.md` |
| OCR pipeline: Tesseract → Gemma 4 → Gemini | Completo | cascade multi-engine |
| PAYMENT: Cash, Bizum, Stripe (pagos de mesa) | Completo | Para el TPV del hostelero |
| TakeRateManager (comisión transaccional) | Completo | Registro por local y mes |
| PDFGEN (3 plantillas carta + QR sheet + table tents) | Completo | |
| Blog recetas + scraper | Completo | |
| Legales + wiki | Completo | 6 páginas legales, 10 artículos |
| Dashboard SPA hostelería (spa/) | Completo legacy | carta, mesas, sala, QR, importación OCR |
| Carta pública (/carta, /carta/:zona/:mesa) | Completo legacy | BrowserRouter, rutas amigables |
| Landing page hostelería (`templates/hosteleria/`) | Completo | 7 secciones, responsive, splash screen |

### Separación crítica de pagos (no confundir)

Existen dos flujos de pago completamente distintos:

| Flujo | Quién paga | A quién | Método | Estado |
|-------|-----------|---------|--------|--------|
| Pago en mesa (TPV) | Cliente final | Al hostelero | Cash / Bizum / Stripe | Implementado en CAPABILITIES/PAYMENT |
| Suscripción MyLocal | Hostelero | A MyLocal | **Revolut + Google Pay** | Cuenta y credenciales confirmadas, driver pendiente |

El `StripeDriver.php` existente es exclusivamente para cobros de mesa. Las suscripciones (27€/mes o 260€/año) van por **Revolut**. No se usa Stripe para suscripciones.

### El producto que vendemos en Fase 1 (per ventas.md)

No es solo un QR. Ofrecer únicamente QR en 2026 tiene tanta fricción como ofrecer webs estáticas. El mercado ya tiene eso. El producto completo de Fase 1 es:

- **Carta digital QR** — la puerta de entrada, no el producto final
- **Presencia web del local** — subdominio propio, visible en búsquedas
- **Local Vivo** — el dueño publica fotos/videos del día (Micro-Timeline)
- **Reseñas con SEO** — clientes dejan estrellas y comentarios (Schema.org)
- **IA invisible** — genera descripciones de platos, traduce, sugiere categorías
- **Legales automáticos** — RGPD/LSSI generados al registrarse
- **Agente-ready** — reservas vía asistentes IA (Siri, Gemini, ChatGPT)

El argumento comercial principal no es el QR. Es:
> "Tu negocio tiene presencia digital real, reputación y se conecta con los asistentes IA que usan tus clientes. Todo desde un panel, sin instalar nada."

### La brecha crítica para el MVP

El framework está completo. El flujo de usuario de extremo a extremo no existe todavía:

```
Landing mylocal.es → Se registra → Onboarding 10 pasos → Dashboard
→ Configura carta → Publica timeline → Carta online → QR → Cliente escanea
→ Ve carta + reseñas + local vivo → Hostelero cobra suscripción con Revolut
```

---

## 1. Principios de ejecución (no negociables)

1. **Todo el desarrollo es local.** El entorno de desarrollo arranca con `run.bat hosteleria`. La app funciona sin subdominios, sin hosting, sin Cloudflare. Los subdominios se simulan con el header `X-Local-Id` durante el desarrollo. El despliegue real va al final de todo.
2. **Agnóstico al entorno.** Ningún componente asume que está en `mylocal.es`. Toda URL, slug, configuración viene de variables de entorno o del seed de arranque.
3. **Atómico.** Un archivo, una responsabilidad. Sin commits WIP. Cada ola termina con su gate verde antes de abrir la siguiente.
4. **≤ 250 LOC por archivo.** Si se acerca al límite, se parte.
5. **Sin datos ficticios.** Empty states con CTA. Nunca Lorem Ipsum en producción.
6. **AUTH_LOCK intacto.** Antes de tocar auth, login, /acide, SynaxisClient o el fetch: leer `claude/AUTH_LOCK.md` completo.
7. **El framework no se modifica para el template.** El template se adapta al framework.
8. **No se despliega hasta que todo funciona localmente.** El despliegue no es una forma de probar. Es la confirmación de que funciona.

---

## 2. Arquitectura objetivo del MVP

### Una sola SPA, tres experiencias

```
templates/hosteleria/src/
  App.tsx                         ← Router raíz (BrowserRouter)
  pages/
    LandingPage.tsx               ← / (marketing, captación — ya existe)
    RegisterPage.tsx              ← /registro
    DashboardPage.tsx             ← /dashboard/* (auth requerida)
    CartaPublicaPage.tsx          ← /carta, /carta/:zona/:mesa
```

### Multi-tenancy local (desarrollo sin subdominios)

Durante el desarrollo, el subdominio se simula con un header:

```
X-Local-Id: elbar
```

El `SubdomainManager.php` lee primero ese header y cae al dominio solo si no existe. Esto permite desarrollar y probar multi-tenancy completo en local sin tocar DNS.

```
run.bat hosteleria          → PHP en 8091 (X-Local-Id configurable)
                            → Vite HMR en 5173
```

### Cobro de suscripciones

```
Dashboard → FacturacionPage → RevolutDriver → Revolut hosted checkout
                            ← webhook Revolut → actualiza plan en AxiDB
```

---

## 3. Definición del MVP

**MVP = primer hostelero paga y usa el producto de forma autónoma, sin intervención manual.**

| Función | Obligatorio MVP |
|---------|----------------|
| Landing mylocal.es con propuesta de valor completa | Sí |
| Registro (slug, email, contraseña) < 30 segundos | Sí |
| Onboarding 10 pasos guiados (per ventas.md) | Sí |
| Carta digital (categorías, platos, precios, fotos) | Sí |
| Carta pública accesible via QR + subdominio | Sí |
| Generación y descarga de QR (PNG + PDF) | Sí |
| Configuración del local (nombre, logo, tema visual) | Sí |
| Micro-Timeline: el dueño publica fotos/posts del día | Sí |
| Reseñas de clientes con Schema.org (SEO) | Sí |
| SEO estructural automático por local (GEO/AEO + Schema + naming imágenes) | Sí |
| FAQ landing + schema global MyLocal.es | Sí |
| Legales automáticos (RGPD/LSSI) al registrarse | Sí |
| Plan Demo 21 días (sin tarjeta) | Sí |
| Suscripción con Revolut + Google Pay (27€/mes o 260€/año) | Sí |
| Despliegue en Hostinger + Cloudflare (subdominios) | Sí (último paso) |
| IA de descripciones de platos | Sí (usa OpenClaude existente) |
| Multiidioma ES/EN/FR/DE | Deseable, no bloqueante |
| OCR de carta (foto/PDF) | Deseable, no bloqueante |
| Agente IA de reservas (Agent-Ready Interface) | No (Fase 2) |
| TPV completo (pedidos y pagos en mesa) | No (Fase 2) |
| Verifactu / TicketBAI | No (Fase 3) |
| Multi-local por usuario | No (Fase 4) |

---

## 4. Olas MVP — Ordenadas de menor a mayor dificultad

---

### Ola M1 — Routing SPA (solo frontend, sin nuevo backend)

**Dificultad: baja.** Es wiring puro. El componente más difícil (la landing) ya existe y funciona.

**Objetivo:** `templates/hosteleria/src/App.tsx` maneja las tres experiencias con react-router-dom.

**M1.1 — Router raíz**
- [x] Instalar `react-router-dom` en `templates/hosteleria/`
- [x] `App.tsx` → `BrowserRouter` + `Routes`
- [x] Ruta `/` → `LandingPage` (mueve los componentes actuales de App.tsx)
- [x] Ruta `/registro` → `RegisterPage` (componente stub por ahora)
- [x] Ruta `/acceder` → `LoginPage` (o modal reutilizado de la landing)
- [x] Ruta `/dashboard/*` → `DashboardPage` con guard de auth
- [x] Ruta `/carta` → `CartaPublicaPage` (stub)
- [x] Ruta `/carta/:zona/:mesa` → misma `CartaPublicaPage` con params
- [x] `<RequireAuth>` — HOC que lee `getCachedUser()` del SDK; redirige a `/acceder` si no hay sesión

**M1.2 — Vite SPA routing**
- [x] `vite.config.js` → confirmar que el servidor de desarrollo sirve `index.html` para todas las rutas (ya debería estar)
- [x] `.htaccess` en `release/` → `RewriteRule ^ /index.html [L]` (ya existe, verificar)

**Gate M1:** `npx tsc --noEmit` pasa. `run.bat hosteleria` arranca. La landing carga en `/`. Navegar a `/dashboard` sin sesión redirige a `/acceder`. La URL `/carta` no da 404.

---

### Ola M2 — Dashboard layout y navegación (frontend, sin lógica de negocio)

**Dificultad: baja-media.** Estructura visual sin datos reales todavía.

**Objetivo:** El hostelero autenticado entra al dashboard y ve la navegación completa del producto.

**M2.1 — Layout del dashboard**
- [x] `src/pages/dashboard/DashboardLayout.tsx` — sidebar + topbar, envuelve sub-rutas
- [x] Sidebar items: Inicio / Carta / Diseño / QR / Publicar / Reseñas / Ajustes / Facturación
- [x] Topbar: nombre del local + avatar + botón "Ver mi carta pública" (abre subdominio en nueva pestaña)
- [x] Indicador de plan: banner "Demo — X días restantes" cuando aplica
- [x] Móvil: sidebar como sheet lateral (hamburguesa)

**M2.2 — Páginas stub con empty states reales**
- [x] `InicioPage.tsx` — métricas: visitas hoy, escaneos QR, plato más visto (datos vacíos con CTA)
- [x] `CartaPage.tsx` — stub con CTA "Añade tu primer plato"
- [x] `PublicarPage.tsx` — stub con CTA "Publica tu primera foto"
- [x] `ReseñasPage.tsx` — stub con CTA "Comparte el enlace para recibir reseñas"
- [x] `QRPage.tsx` — stub con preview de QR
- [x] `AjustesPage.tsx` — stub con nombre del local y logo
- [x] `FacturacionPage.tsx` — stub con plan actual

**Gate M2:** Iniciar sesión con credenciales de prueba locales accede al dashboard. La navegación entre todas las páginas funciona. En móvil (375px) no hay scroll horizontal.

---

### Ola M3 — Backend: multi-tenancy y registro (backend PHP, testeable local)

**Dificultad: media.** PHP puro, sin nueva dependencia externa. Testeable 100% en local con header `X-Local-Id`.

**Objetivo:** el backend sabe en todo momento qué local sirve y puede crear nuevos locales.

**M3.1 — SubdomainManager**
- [x] `CORE/SubdomainManager.php`
  - Lee `$_SERVER['HTTP_X_LOCAL_ID']` primero (desarrollo local y override de admin)
  - Si no existe, extrae slug de `HTTP_HOST`: `preg_match('/^([a-z0-9\-]+)\.mylocal\.es$/i', $host, $m)`
  - Si es `www` o dominio raíz → slug `mylocal` (landing corporativa)
  - Define la constante global `CURRENT_LOCAL_SLUG`
- [x] `router.php` → llama a `SubdomainManager::detect()` al inicio de cada request
- [x] `spa/server/index.php` → igual
- [x] Función global `get_current_local_id()` disponible en todos los handlers

**M3.2 — Seed dinámico por local**
- [x] El endpoint `/seed/bootstrap.json` devuelve JSON dinámico: `{"local_id":"<slug>","plan":"demo","demo_days_left":21}`
- [x] `SynaxisProvider` en el SDK ya consume este endpoint; verificar que funciona con datos dinámicos

**M3.3 — Backend: registro de nuevos locales**
- [x] `CAPABILITIES/LOGIN/LoginRegister.php`
  - Acción `validate_slug` — sin auth — devuelve `{available: bool, reason: string}`
  - Regex: `^[a-z][a-z0-9-]{2,30}$`
  - Lista de slugs reservados: `admin, dashboard, api, www, mail, ftp, cdn, acide, mylocal, demo, test, staging, panel, support, help, docs, blog, shop, carta, registro, acceder`
  - Acción `register_local` — crea usuario + local + STORAGE inicial → devuelve token de sesión
  - Al registrar: genera legales automáticos para el local (privacidad, cookies, aviso legal) a partir de templates con datos del local
- [x] Añadir `validate_slug` y `register_local` a `ALLOWED_ACTIONS` del dispatcher

**M3.4 — Límites del plan Demo (desde el primer día, no después)**
- [x] Backend verifica el plan antes de writes: si Demo → máximo 20 platos, 1 zona, 5 mesas
- [x] Si supera límite → `{"success":false,"error":"PLAN_LIMIT","upgrade_url":"/dashboard/facturacion"}`
- [x] Función reutilizable `check_plan_limit($localId, $resource, $count)` en CORE

**M3.5 — Test de multi-tenancy local**
- [x] `spa/server/tests/test_multitenant.php` — 15+ assertions
- [x] `curl -H "X-Local-Id: elbar" http://localhost:8091/seed/bootstrap.json` → `{"local_id":"elbar"}`
- [x] Usuario de local A no puede leer datos de local B
- [x] Registro de un local nuevo crea STORAGE aislado y devuelve token válido

**Gate M3:** `test_multitenant.php` pasa. Con `X-Local-Id: elbar` en las peticiones, el sistema sirve el contexto de "elbar". Un registro nuevo crea el local y hace login automático.

---

### Ola M4 — Registro y onboarding (frontend + integración M3)

**Dificultad: media.** Depende de M3 para el backend. El flujo de 10 pasos es el corazón del producto.

**Objetivo:** usuario nuevo puede registrarse y tener su carta online en menos de 10 minutos.

**M4.1 — RegisterPage**
- [x] `src/pages/RegisterPage.tsx` — form: tipo de negocio → nombre del local → slug → email → contraseña
- [x] Validación de slug en tiempo real: llama a `validate_slug` con debounce 400ms
- [x] Feedback visual: punto verde/rojo + preview URL `<slug>.mylocal.es`
- [x] Al enviar: llama a `register_local` → guarda token en SDK → redirige a `/dashboard?onboarding=1`
- [x] Sin verificación de email en MVP (flujo sin fricción)

**M4.2 — Onboarding 10 pasos (per ventas.md)**
- [x] `src/components/OnboardingWizard.tsx` — wizard paso a paso, se activa cuando `?onboarding=1`
- [x] Paso 1: Tipo de negocio (Bar / Restaurante / Cafetería / Otro) → personaliza plantillas
- [x] Paso 2: Identidad — nombre del local + subida de logo. Preview en vivo
- [x] Paso 3: Idiomas — ES por defecto, toggles EN/FR/DE
- [x] Paso 4: Categorías — botón "Sugerir automáticamente" (llama a OpenClaude) o manual
- [x] Paso 5: Primer plato — nombre, precio, foto, descripción + botón "Generar descripción" (IA)
- [x] Paso 6: Diseño — 3 plantillas: Minimal / Elegante / Moderno
- [x] Paso 7: Colores — autogenerados desde logo o selección manual
- [x] Paso 8: Vista previa — simulación de móvil con scroll real + botón "Ver como cliente"
- [x] Paso 9: QR — generar QR general + por mesa si aplica. Descarga PNG y PDF
- [x] Paso 10: Momento WOW — "Tu carta ya está online" + enlace + QR grande + CTA "Compartir"
- [x] Progreso guardado por paso (si cierra y vuelve, retoma donde dejó)

**M4.3 — OnboardingBanner post-wizard**
- [x] Banner contextual en el dashboard los primeros 21 días
- [x] Checklist: ① Sube tu logo ② Añade tu primer plato ③ Descarga tu QR ④ Publica tu primera foto ⑤ Comparte el enlace
- [x] Cada item se marca automáticamente cuando se completa
- [x] Se oculta al 100% o cuando el usuario lo cierra

**Gate M4:** usuario nuevo en `localhost:5173` puede registrarse, completar el wizard y tener su carta accesible en `localhost:8091/carta` con los datos del onboarding.

---

### Ola M5 — Migración del legacy: Carta y QR (frontend + SDK)

**Dificultad: media-alta.** El bloque más denso del MVP. El dashboard legacy existe pero usa patrones distintos al SDK.

**Objetivo:** el hostelero puede gestionar su carta completa desde el nuevo dashboard.

**Estrategia:** copiar componentes de `spa/src/` a `templates/hosteleria/src/pages/dashboard/`, reemplazando cada llamada fetch legacy por `useSynaxisClient()` del SDK. Un componente a la vez, con tests locales tras cada migración.

**M5.1 — CartaPage completa**
- [x] Listado de categorías + platos (lee de CAPABILITIES/CARTA)
- [x] Añadir / editar / eliminar categoría
- [x] Añadir / editar / eliminar plato (nombre, precio, foto, descripción, alérgenos)
- [x] Botón "Generar descripción" → OpenClaude → rellena el campo automáticamente
- [ ] Drag & drop para reordenar platos y categorías
- [x] Respeta límites del plan Demo: si llega a 20 platos, muestra CTA de upgrade

**M5.2 — QRPage funcional**
- [x] Generación de QR general del local
- [ ] Generación de QR por mesa/zona
- [x] Descarga PNG y PDF (QR sheet con instrucciones — usa PDFGEN existente)
- [x] Vista previa del QR antes de descargar

**M5.3 — AjustesPage funcional**
- [x] Nombre del local, descripción corta, dirección, teléfono, horario
- [x] Subida y recorte de logo
- [x] Selector de tema visual (colores del local)
- [ ] Cambio de email y contraseña

**M5.4 — CartaPublicaPage**
- [x] Migrada del legacy: carga la carta del local según `local_id` del contexto
- [x] Categorías filtradas, foto por plato, alérgenos, precio
- [ ] Multiidioma básico: si el local tiene EN activado, botón de cambio
- [x] Funciona en `/carta` y `/carta/:zona/:mesa`

**Gate M5:** el hostelero puede añadir, editar y eliminar platos. La carta pública en `/carta` refleja los cambios en tiempo real. El QR descargable apunta a la URL correcta.

---

### Ola M6 — Local Vivo: Timeline y Reseñas (nuevo backend + frontend)

**Dificultad: media.** Dos modelos nuevos en AxiDB + UI en el dashboard + sección en la carta pública.

**Objetivo:** el local tiene presencia viva en la web, no solo una carta estática. Esto es diferenciación directa frente a NordQR y Bakarta.

**M6.1 — Backend: TimelineModel**
- [x] `CAPABILITIES/TIMELINE/TimelineModel.php` — AxiDB, colección `timeline/<local_id>/`
- [x] Campos: `id`, `tipo` (foto/texto/video), `titulo`, `descripcion`, `media_url`, `publicado_at`
- [x] Acciones: `create_post`, `list_posts`, `delete_post`
- [x] Subida de imagen a `MEDIA/<local_id>/timeline/`
- [x] Máximo 50 posts en Demo, ilimitado en Pro

**M6.2 — Backend: ReviewModel con Schema.org**
- [x] `CAPABILITIES/REVIEWS/ReviewModel.php` — AxiDB, colección `reviews/<local_id>/`
- [x] Campos: `id`, `autor`, `estrellas` (1-5), `comentario`, `fecha`, `verificado`
- [x] Acciones: `create_review` (sin auth — pública), `list_reviews`, `delete_review` (con auth)
- [x] Enlace de invitación firmado con token para que el cliente deje reseña
- [x] Schema.org `AggregateRating` en la carta pública para SEO

**M6.3 — Dashboard: PublicarPage**
- [x] `src/pages/dashboard/PublicarPage.tsx`
- [x] Form: subir foto o video corto + título + descripción
- [x] Lista de posts publicados con opción de eliminar
- [x] Preview de cómo se verá en la carta pública

**M6.4 — Dashboard: ReseñasPage**
- [x] `src/pages/dashboard/ReseñasPage.tsx`
- [x] Lista de reseñas recibidas con estrellas y comentario
- [x] Botón "Generar enlace de invitación" — copia URL al portapapeles
- [x] Puntuación media visible + distribución de estrellas
- [x] Respuesta del dueño a reseñas (visible en carta pública)

**M6.5 — Carta pública: sección Timeline y Reseñas**
- [x] En la carta pública, debajo del menú: sección "Últimas novedades" (timeline)
- [x] Sección "Lo que dicen nuestros clientes" (reseñas + Schema.org)
- [x] Enlace "Deja tu reseña" → formulario público (no requiere cuenta)

**Gate M6:** el dueño publica una foto desde el dashboard y aparece en la carta pública. Un cliente puede dejar una reseña con 5 estrellas desde la carta pública. La carta pública muestra `AggregateRating` en el HTML (verificable con Google Rich Results Test).

---

### Ola M7 — Legales automáticos e IA de carta (integración con lo existente)

**Dificultad: baja-media.** Usa capacidades ya construidas (OpenClaude, PDFGEN). Solo hay que conectarlas al flujo de registro y al dashboard.

**Objetivo:** al registrarse, el hostelero tiene sus legales listos. El copiloto IA funciona en el dashboard.

**M7.1 — Generación automática de legales al registrar**
- [x] `register_local` (M3.3) dispara la generación de legales al finalizar
- [x] `CAPABILITIES/LEGAL/LegalGenerator.php` — toma nombre del local, email, slug, dirección → renderiza plantillas
- [x] Documentos generados: Política de Privacidad, Aviso Legal, Política de Cookies
- [x] Guardados en AxiDB colección `local_legales` como contenido Markdown
- [x] Accesibles en la carta pública en `/legal/privacidad`, `/legal/aviso`, `/legal/cookies`
- [x] En el dashboard: página "Legales" con botón de regenerar si cambian los datos

**M7.2 — Copiloto IA en CartaPage**
- [x] Botón "Generar descripción" en el form de edición de plato (vía MenuEngineer + Gemini)
- [x] Llama a `ai_generar_descripcion` → devuelve descripción en < 5s
- [x] Botón "Sugerir categorías" → `ai_sugerir_categorias` → devuelve lista según tipo de negocio
- [ ] Botón "Traducir carta" (deseable, no bloqueante para el gate)

**Gate M7:** registrar un local nuevo crea automáticamente los 3 documentos legales. En la carta de un plato, el botón "Generar descripción" rellena el campo. Los legales son accesibles públicamente en las URLs correctas.

---

### Ola M8 — Suscripción con Revolut + Google Pay (billing)

**Dificultad: media-alta.** Primera integración con Revolut Business API. Credenciales confirmadas de proyectos anteriores.

**Objetivo:** el hostelero puede pagar su suscripción de forma autónoma con Revolut o Google Pay.

**M8.1 — RevolutDriver para suscripciones**
- [x] `CAPABILITIES/PAYMENT/drivers/RevolutDriver.php`
- [x] Peticiones HTTP directas a Revolut Business API (`curl` nativo, sin SDK)
- [x] Métodos: `createOrder` (checkout URL), `checkOrder`, `verifyWebhook` (HMAC)
- [x] Google Pay: activo en checkout Revolut sin código adicional
- [x] Persistencia: AxiDB colección `subscriptions/<localId>`, facturas en `invoices[]`

**M8.2 — Gestión de suscripciones**
- [x] `CAPABILITIES/BILLING/BillingManager.php`
- [x] Webhook `ORDER_COMPLETED` → `activate()` → actualiza plan en AxiDB, elimina límites Demo
- [x] `downgrade()` → plan=demo, status=expired, bloqueo suave
- [x] `getStatus()` → plan actual, días restantes, historial de facturas
- [x] Acciones `ALLOWED_ACTIONS`: `get_subscription_status`, `create_revolut_order`, `check_revolut_order`, `webhook_revolut`

**M8.3 — FacturacionPage funcional**
- [x] `src/pages/dashboard/FacturacionPage.tsx`
- [x] Cards Demo / Pro mensual (27€) / Pro anual (260€) con plan actual marcado
- [x] Botón "Activar Pro" → `create_revolut_order` → redirect a Revolut hosted checkout
- [x] Regreso con `?success=1` → banner de confirmación
- [x] Histórico de facturas: fecha, importe, enlace Revolut
- [x] Bloqueo suave visible si status=expired

**M8.4 — Flujo de conversión Demo → Pro**
- [ ] Notificaciones en días 7/14/18/21 (email transaccional — pendiente SMTP)
- [x] Día 21: overlay de bloqueo suave con CTA de upgrade (via status=expired en FacturacionPage)

**Gate M8:** en sandbox de Revolut, el hostelero puede completar el pago de 27€, el webhook actualiza el plan a Pro, los límites de Demo desaparecen y aparece la factura en el histórico.

---

### Ola M9 — SEO, rendimiento y calidad (transversal)

**Dificultad: baja.** Todo técnico puro. Sin dependencias de negocio nuevas.

**Objetivo:** el producto no solo funciona, convence y posiciona.

**M9.1 — SEO en la carta pública**
- [x] `<title>` dinámico: "Carta de [Nombre del Local] — MyLocal"
- [x] `<meta name="description">` con descripción del local
- [x] Schema.org `Restaurant` + `Menu` + `MenuItem` completo (useSeoMeta + buildSchemaOrg)
- [x] Schema.org `AggregateRating` (desde M6)
- [x] Open Graph tags para compartir en redes (foto del local + nombre)
- [x] `robots.txt`: permite `/carta/*`, bloquea `/dashboard/*`, `/acide/*`

**M9.2 — Rendimiento**
- [x] Imágenes con lazy loading (`loading="lazy"`) — hero con `loading="eager"`
- [x] Assets JS/CSS con hash (Vite) — cache 1 año
- [ ] Carta pública carga en < 2s en 4G (Lighthouse Mobile ≥ 90) — verificar en producción

**M9.3 — UX del dashboard**
- [x] Skeleton screens en CartaPage, ResenasPage y FacturacionPage
- [x] Mensajes de éxito / error en castellano
- [x] Botones con verbo + objeto

**M9.4 — Landing page textos reales**
- [x] H1: "Tu negocio en la nube en 10 minutos."
- [x] CTA principal: "Empieza gratis — 21 días, sin tarjeta" → `/registro`
- [x] Precios reales en PricingSection: Demo (0€) / Pro mensual (27€+IVA) / Pro anual (260€+IVA)
- [x] Datos fiscales reales de MyLocal Technologies en el Footer
- [x] Header: CTA "Empezar gratis" → `/registro` + enlace "Acceder" → `/acceder`
- [x] Footer: legal links reales (`/legal/privacidad`, `/legal/aviso`, `/legal/cookies`)

**Gate M9:** Lighthouse Mobile ≥ 90 en la carta pública de un local de prueba. Ningún texto de placeholder visible. 3 personas no técnicas hacen el onboarding completo en < 10 minutos sin ayuda.

---

### Ola M9.5 — SEO Estructural Automático (GEO/AEO + Schema por local)

**Dificultad: media-alta.** Requiere PHP, React y una arquitectura de datos nueva (campos de negocio reales). Es la diferencia entre tener SEO de adorno y tener SEO que genera clientes reales.

**Objetivo:** con 500 clientes activos, ningún dato SEO se revisa a mano. Cada contenido que crea un hostelero — plato, foto, reseña — nace ya optimizado para Google, Maps, y los buscadores con IA (ChatGPT, Perplexity, AI Overviews). Cada local es una entidad propia en el grafo de conocimiento de internet, no un subpágina de MyLocal.

**Principio de diseño:** el SEO es consecuencia de crear contenido, no una tarea posterior.

**Referencia obligatoria:** `claude/SKILL/SKILL.md` + sus `references/`.

---

#### M9.5.0 — Datos de negocio reales en AjustesPage (prerequisito de todo lo demás)

El Schema.org de un restaurante sin dirección real, sin coordenadas y sin horario es inútil para Google Maps, para la IA y para el usuario. Este bloque es el cimiento.

**Backend: nuevos campos en `locales` collection**
- [ ] `CAPABILITIES/OPTIONS/LocalOptions.php` → ampliar `update_local` para aceptar:
  - `direccion` — objeto: `{ calle, numero, ciudad, cp, provincia, pais: 'ES' }`
  - `telefono` — E.164 (`+34XXXXXXXXX`)
  - `horario` — array de 7 días: `[{ dia: 'Mo', abre: '12:00', cierra: '16:00', cerrado: false }, ...]`
  - `precio_medio` — enum: `€` / `€€` / `€€€`
  - `tipo_cocina` — array de strings: `['Española', 'Mediterránea', ...]`
  - `url_maps` — enlace de Google Maps opcional (para `sameAs`)
  - `acepta_reservas` — boolean
  - `lat` / `lng` — float, rellenados automáticamente por geocodificación simple (o manualmente)
- [ ] Geocodificación básica: en `update_local`, si llegan `calle+ciudad+cp` sin `lat/lng`, llamar a `nominatim.openstreetmap.org/search` (gratuito, sin clave) para obtener coordenadas y guardarlas
- [ ] `get_local` ya existente → asegurarse de que devuelve los nuevos campos

**Frontend: AjustesPage — sección Información del local**
- [ ] Añadir sub-sección "Datos para buscadores" (dirección, teléfono, horario, precio medio)
- [ ] Formulario de horario: 7 filas (Lunes a Domingo), cada una con: toggle "Cerrado" + hora apertura + hora cierre + toggle "Solo mediodía" (dos turnos)
- [ ] Campo precio medio: selector visual € / €€ / €€€ con descripción ("hasta 15€ / 15-30€ / más de 30€")
- [ ] Campo tipo de cocina: input con chips (el hostelero escribe y añade tags)
- [ ] Preview: al guardar, mostrar cómo quedará la ficha en Google (simulación visual básica con nombre, dirección, estrellas, horario)
- [ ] Aviso de impacto: "Estos datos aparecen en Google, Maps y los asistentes IA que usan tus clientes."

---

#### M9.5.1 — SeoBuilder PHP: el motor central

`CAPABILITIES/SEO/SeoBuilder.php` — namespace `SEO`. Llamado por CARTA, TIMELINE y REVIEWS al escribir. También llamado desde el handler `seo.php` bajo demanda.

**Diseño del archivo (≤ 250 LOC — partir si es necesario):**

```
CAPABILITIES/SEO/
  SeoBuilder.php        ← orquestador + LocalBusiness + AggregateRating
  SeoSchemas.php        ← Menu, MenuItem, Review, Post, FAQ schemas
  SeoCache.php          ← read/write de seo_cache en AxiDB
```

**`SeoBuilder::buildRestaurant(array $local): array`**
- Genera `LocalBusiness` + `Restaurant` con `@id` = `https://{slug}.mylocal.es/#local`
- Campos obligatorios: `name`, `url`, `description`, `image`, `servesCuisine`, `priceRange`
- Campos condicionales (solo si existen en `$local`):
  - `address` → `PostalAddress` con `streetAddress`, `addressLocality`, `postalCode`, `addressRegion`, `addressCountry: ES`
  - `geo` → `GeoCoordinates` con `latitude`, `longitude`
  - `openingHoursSpecification` → array generado desde el campo `horario` (formato Schema.org)
  - `telephone`, `hasMap` (url_maps), `acceptsReservations`
- `sameAs`: array con `url_maps` si existe
- `aggregateRating`: incluido solo si `count > 0`

**`SeoSchemas::menuGraph(array $categorias, array $platos): array`**
- `@type: Menu` con `hasMenuSection[]`
- Cada `MenuSection`: `name`, `hasMenuItem[]`
- Cada `MenuItem`: `name`, `description`, `offers: { price, priceCurrency: EUR }`, `suitableForDiet` si hay alérgenos comunes mapeados
- Alérgenos → `suitableForDiet`: gluten_free, dairy_free, vegan, vegetarian (mapeo básico desde `alergenos[]`)

**`SeoSchemas::reviewGraph(array $reviews): array`**
- Array de `Review`: `@type: Review`, `reviewRating: { ratingValue }`, `author: { @type: Person, name }`, `datePublished`, `reviewBody`
- Solo las 10 más recientes (Google ignora más de eso en el schema)
- Solo reviews con `comentario` no vacío (los vacíos no aportan al rich result)

**`SeoSchemas::postGraph(array $posts, array $local): array`**
- Cada post → `SocialMediaPosting`: `headline`, `text` (descripcion), `image`, `datePublished`, `author: { @type: Organization, name }`
- Solo posts con `media_url` (los puramente de texto no generan rich result visual)

**`SeoSchemas::faqGraph(array $faqs): array`**
- `FAQPage` con `mainEntity[]` de `Question` + `Answer`
- Los `faqs` son un array `[{pregunta, respuesta}]` — generado por el hostelero o por IA

**`SeoBuilder::buildFullPage(string $localId): string`**
- Orquesta todo en un único `@graph`
- Lee `data_get('locales', $localId)`, `list_categorias`, `list_productos`, `list_reviews`, `list_posts`
- Devuelve JSON-LD completo como string
- Guarda en `data_put('seo_cache', $localId, { schema, updated_at })`

**`SeoBuilder::invalidateCache(string $localId): void`**
- Borra `data_delete('seo_cache', $localId)`
- Llamado desde CARTA, REVIEWS y TIMELINE en cada write

**`SeoCache::get(string $localId): ?string`**
- Lee `data_get('seo_cache', $localId)`
- Si `updated_at` > 24h → devuelve null (obliga rebuild)
- Esto evita rebuild en cada pageview: 1 rebuild por día o por evento de escritura

**Checklist de implementación:**
- [ ] Crear `CAPABILITIES/SEO/SeoBuilder.php` con `buildRestaurant()`, `buildFullPage()`, `invalidateCache()`
- [ ] Crear `CAPABILITIES/SEO/SeoSchemas.php` con `menuGraph()`, `reviewGraph()`, `postGraph()`, `faqGraph()`
- [ ] Crear `CAPABILITIES/SEO/SeoCache.php` con `get()`, `set()`, `invalidate()`
- [ ] Añadir `SeoBuilder::invalidateCache($localId)` al final de: `create_producto`, `update_producto`, `delete_producto`, `create_categoria`, `create_review`, `create_post`, `update_local`
- [ ] Crear `spa/server/handlers/seo.php` con acción `get_local_schema` (pública, sin auth)
- [ ] Añadir `get_local_schema` a `ALLOWED_ACTIONS` y `public_actions` en `spa/server/index.php`
- [ ] Test: crear un plato → `data_get('seo_cache', localId)` debe ser null (invalidado). Llamar `get_local_schema` → reconstruye y guarda. Segunda llamada → sirve cache

---

#### M9.5.2 — Naming de imágenes y alt text automático

Cada imagen subida por el hostelero debe nacer con nombre correcto y alt text. Esta es la pieza más invisible y la más impactante para el SEO de imágenes.

**Convención de naming:**
```
{slug-local}_{tipo}_{titulo-slug}_{YYYYMMDD}.{ext}

Ejemplos:
  bar-de-lola_plato_pizza-margarita_20260517.jpg
  cocina-de-ana_timeline_arroz-de-hoy_20260517.jpg
  el-rincon_logo_logo-principal_20260517.jpg
  bar-de-lola_hero_interior-terraza_20260517.jpg
```

**Reglas del naming:**
- `slug-local`: slug del local sin acentos, solo `[a-z0-9-]`
- `tipo`: `plato` / `timeline` / `logo` / `hero` / `qr`
- `titulo-slug`: primeras 40 chars del título/nombre, slugificado
- `YYYYMMDD`: fecha de subida
- Sin espacios, sin caracteres especiales, todo minúsculas

**Alt text automático:**
- Si el item tiene `descripcion` → usar los primeros 100 chars como alt
- Si solo tiene `nombre` → `"{nombre}" en {nombre-local}, {ciudad-local}`
- Si no hay nada → `"{tipo} de {nombre-local}"`
- El alt se guarda en el campo `alt_text` del item en AxiDB

**`CORE/MediaUploader.php` — modificar el método de subida:**
- [ ] Añadir método estático `buildFilename(string $slug, string $tipo, string $titulo): string`
- [ ] Añadir método estático `buildAlt(string $nombre, string $descripcion, string $localNombre, string $ciudad): string`
- [ ] Modificar el path de guardado en `CAPABILITIES/CARTA` (subida de foto de plato) para usar `buildFilename()`
- [ ] Modificar el path de guardado en `CAPABILITIES/TIMELINE` (subida de foto de post) para usar `buildFilename()`
- [ ] Modificar el path de guardado en `CAPABILITIES/OPTIONS` (logo y hero) para usar `buildFilename()`
- [ ] Guardar `alt_text` en el item junto con `media_url`
- [ ] Strip EXIF básico antes de guardar (privacidad): si PHP tiene `exif_read_data`, reescribir sin EXIF usando `imagecreatefromjpeg` + `imagejpeg`
- [ ] Convertir a WebP si la extensión GD lo soporta (`imagewebp()`): guardar `.webp`, mantener `.jpg` como fallback
- [ ] Resize si supera 1920px de ancho (`imagescale()`)

---

#### M9.5.3 — CartaPublicaPage: schema completo desde servidor

La `CartaPublicaPage` actual construye el schema en el cliente (JavaScript). Para que funcione con crawlers lentos y con los bots de IA que no ejecutan JS, el schema debe estar disponible también desde el servidor como endpoint JSON.

**Cambios en `CartaPublicaPage.tsx`:**
- [ ] Añadir llamada a `get_local_schema` (acción pública) junto con las llamadas existentes
- [ ] Si llega schema del servidor → usarlo directamente (sin `buildSchemaOrg` cliente)
- [ ] Si no llega → fallback al `buildSchemaOrg` cliente actual (degradación elegante)
- [ ] Ampliar `buildSchemaOrg` local (fallback) con los nuevos campos cuando estén disponibles: `address`, `geo`, `openingHours`, `telephone`
- [ ] Añadir `Review[]` individuales al schema (max 10) usando `reviews` que ya se cargan
- [ ] Añadir `<link rel="canonical" href="https://{slug}.mylocal.es/carta">` via `useSeoMeta`
- [ ] Añadir `BreadcrumbList`: `MyLocal > {nombre-local} > Carta`
- [ ] En el `<title>`: cambiar de `"Carta de X — MyLocal"` a `"{nombre-local} — Carta digital"` (el local primero, no MyLocal)
- [ ] En `<meta description>`: incluir ciudad si está disponible — `"Consulta la carta de {nombre} en {ciudad}. {N} platos desde {precio-min}€. Abierto {horario-hoy}."`
- [ ] Imagen `<img>` de platos: añadir `alt={plato.alt_text ?? plato.nombre}` en lugar del alt vacío actual
- [ ] Imagen hero: añadir `alt={local.nombre} — {ciudad}`

**`useSeoMeta` — ampliar para canonical:**
- [ ] Añadir prop `canonical?: string` al hook
- [ ] Si `canonical` existe → `<link rel="canonical" href={canonical}>`

---

#### M9.5.4 — Sitemap dinámico y llms.txt por local

Cada local necesita sus propios archivos de descubrimiento. No hay un sitemap global de MyLocal que liste locales (eso sería el sitemap de `mylocal.es`). Cada local tiene el suyo.

**`spa/server/handlers/seo.php` — ampliar con nuevas acciones:**

**Acción `get_sitemap` (pública, sin auth)**
- Genera XML válido de sitemap para el local actual
- URLs incluidas:
  - `/carta` → `priority: 1.0`, `changefreq: weekly`
  - `/carta/zona/{nombre}/mesa/{n}` → por cada mesa del local → `priority: 0.8`
  - Una entrada por plato (con `lastmod` de `updated_at` del plato)
  - Una entrada por post de timeline (con `lastmod` de `publicado_at`)
  - `/legal/privacidad`, `/legal/aviso`, `/legal/cookies` → `priority: 0.3`
- Responde con `Content-Type: application/xml`
- Añadir a `public_actions`

**Acción `get_llms_txt` (pública, sin auth)**
- Responde con `Content-Type: text/plain` en formato `llms.txt` estándar (propuesto por Answer.AI)
- Estructura del archivo:

```
# {nombre-local}
> {descripcion-local}

## Información
Dirección: {calle}, {ciudad}, {cp}
Teléfono: {telefono}
Horario: {resumen-horario}
Precio medio: {precio_medio}
Tipo de cocina: {tipo_cocina.join(', ')}

## Carta
{URL}/carta — Carta completa con precios y alérgenos
{N} platos en {M} categorías.
Categorías: {categorias.join(', ')}

## Reseñas
{aggregate.media} sobre 5 — {aggregate.count} valoraciones verificadas de clientes reales.

## Últimas novedades
{posts recientes: titulo + fecha, 5 máx}

## Reservas
{si acepta_reservas: "Acepta reservas. Contactar en {telefono} o {email-local}"}
{si no: "Sin reservas. Servicio directo."}
```

- Añadir a `public_actions`

**Rutas de acceso (via router.php o .htaccess):**
- [ ] `GET /carta/sitemap.xml` → dispatcher con `action=get_sitemap`
- [ ] `GET /carta/llms.txt` → dispatcher con `action=get_llms_txt`
- [ ] Añadir ambas rutas a `robots.txt`: `Sitemap: https://{slug}.mylocal.es/carta/sitemap.xml`

---

#### M9.5.5 — Landing page: SEO GEO/AEO completo

La landing de MyLocal.es es la página de captación. Debe posicionar para búsquedas como "carta digital restaurante España", "app carta QR hostelería", y ser citada por IA cuando alguien pregunta "¿cómo digitalizar mi bar?".

**Checklist de copy (pirámide invertida + GEO/AEO):**
- [ ] `HeroSection`: actualizar descripción a texto que mencione España:
  - Actual: "Carta QR, presencia web, reseñas con SEO y copiloto IA. Sin instalar nada. Sin permanencias. 21 días gratis."
  - Nueva: "La plataforma para bares y restaurantes de toda España. Carta QR, presencia web, reseñas en Google y copiloto IA — todo desde un panel. 21 días gratis, sin tarjeta."
- [ ] H2 de secciones: reformular para que sean preguntas o beneficios directos, no nombres de funcionalidad
  - ❌ "Generador QR" → ✅ "¿Cómo consigo que mis clientes vean la carta sin descargar nada?"
  - ❌ "Importar carta" → ✅ "Tu carta actual, digitalizada en minutos"
  - ❌ "Web Preview" → ✅ "Tu local, visible en Google desde el día uno"

**`FAQSection.tsx` — componente nuevo:**
- [ ] Crear `templates/hosteleria/src/components/FAQSection.tsx`
- [ ] 7 preguntas reales, ordenadas por volumen de búsqueda estimado:
  1. **¿Qué es MyLocal y para qué sirve a mi bar o restaurante?**
     MyLocal es una plataforma digital para hostelería española que te da carta QR, presencia web propia, reseñas de clientes visibles en Google y un copiloto de inteligencia artificial para gestionar tu menú. Funciona sin instalar nada y está lista en 10 minutos.
  2. **¿Funciona en toda España?**
     Sí. MyLocal está disponible para cualquier bar, restaurante, cafetería o local de hostelería en toda España. Cada local tiene su propio subdominio (`tunegocio.mylocal.es`) con carta pública y presencia en buscadores.
  3. **¿Cómo aparece mi local en Google con MyLocal?**
     Al registrarte, MyLocal crea una ficha digital con los datos de tu negocio (nombre, dirección, horario, carta) en formato Schema.org — el lenguaje que entienden Google, Maps y los asistentes de IA. Sin configuración técnica.
  4. **¿Cuánto cuesta y qué incluye el plan gratuito?**
     El plan Demo es completamente gratuito durante 21 días, sin necesidad de tarjeta de crédito. Incluye carta digital con hasta 20 platos, código QR descargable, subdominio propio y soporte por email. El plan Pro cuesta 27 € al mes (sin IVA) e incluye platos ilimitados, copiloto IA, legales RGPD automáticos y soporte prioritario.
  5. **¿Necesito saber de tecnología para usar MyLocal?**
     No. El proceso de alta completo lleva menos de 10 minutos y está guiado paso a paso. Si tienes una carta en papel o en PDF, puedes importarla directamente. No hay que instalar nada ni contratar ningún servicio adicional.
  6. **¿Puedo importar mi carta desde un PDF o imagen?**
     Sí. MyLocal puede leer tu carta actual desde una foto o un archivo PDF y crear las categorías y platos automáticamente mediante reconocimiento de texto (OCR). También puedes pedirle al copiloto IA que sugiera categorías según tu tipo de negocio.
  7. **¿Qué pasa cuando termina el periodo de prueba de 21 días?**
     Al finalizar el periodo Demo, tu carta pública y los datos que hayas creado se conservan. Para seguir publicando y acceder a las funcionalidades Pro, se activa la suscripción mensual o anual. No hay contratos de permanencia: puedes cancelar cuando quieras.
- [ ] FAQSection visible solo en desktop por defecto, colapsable en móvil (acordeón)
- [ ] `FAQPage` Schema JSON-LD generado dinámicamente desde el array de preguntas
- [ ] Posición en la landing: entre PricingSection y Footer
- [ ] `<section id="faq">` para que el nav pueda enlazar a ella
- [ ] Añadir "FAQ" al navItems de `Header.tsx`

**Schema global de la landing:**
- [ ] Crear `templates/hosteleria/src/components/LandingSchema.tsx` — componente que inyecta `<script type="application/ld+json">` en el DOM
- [ ] Schema `@graph` con:
  - `Organization`: MyLocal Technologies S.L., `hola@mylocal.es`, `sameAs` con Instagram + Twitter, `areaServed: España`, `knowsAbout: ['carta digital', 'hostelería', 'QR', 'SEO para restaurantes', 'copiloto IA']`
  - `WebSite`: con `potentialAction: SearchAction` (búsqueda interna si se implementa)
  - `WebPage`: título + descripción + `inLanguage: es`
  - `SoftwareApplication`: `name: MyLocal`, `applicationCategory: BusinessApplication`, `operatingSystem: Web`, plan Demo `price: 0`, plan Pro `price: 27`, `featureList`, `offers[]`
  - `FAQPage`: las mismas 7 preguntas del componente FAQ (sincronizadas)
- [ ] Importar `LandingSchema` en `LandingPage.tsx` y renderizarlo dentro del `<>` antes del header
- [ ] `meta name="geo.region" content="ES"` + `meta name="geo.placename" content="España"` en `useSeoMeta` de la landing

---

#### M9.5.6 — Validación y gate

El gate de esta ola no es un test unitario. Es la verificación en herramientas reales de que el SEO funciona.

- [ ] **Schema validator**: abrir `https://validator.schema.org`, pegar el HTML de `/carta` de un local de prueba → 0 errores críticos
- [ ] **Rich Results Test**: `https://search.google.com/test/rich-results` con la URL de la carta → debe detectar: Restaurant, Menu, AggregateRating, Review
- [ ] **Rich Results Test landing**: la landing debe detectar: Organization, FAQPage, SoftwareApplication
- [ ] **Naming de imágenes**: subir una foto de plato desde el dashboard → verificar que el archivo en `MEDIA/` tiene el nombre `{slug}_{tipo}_{titulo}_{fecha}.webp`
- [ ] **Alt text**: inspeccionar el HTML de la carta pública → ningún `<img>` tiene `alt=""` o `alt` ausente
- [ ] **llms.txt**: `GET /carta/llms.txt` con `X-Local-Id: demo` → responde texto legible con nombre, dirección, carta resumen
- [ ] **Sitemap**: `GET /carta/sitemap.xml` con `X-Local-Id: demo` → XML válido con al menos 3 URLs (carta + platos)
- [ ] **Meta description con ciudad**: si el local tiene ciudad en su dirección → la meta description la incluye
- [ ] **Canonical**: inspeccionar `<head>` de la carta → `<link rel="canonical" href="https://demo.mylocal.es/carta">`
- [ ] **FAQ en landing**: inspeccionar `<head>` de la landing → el schema `FAQPage` tiene las 7 preguntas

**Gate M9.5:** Los 9 puntos de validación anteriores pasan. `https://validator.schema.org` no reporta errores críticos en la carta pública. La landing supera el Rich Results Test para FAQPage y Organization.

---

### Ola M10 — Despliegue: Cloudflare + Hostinger (último paso)

**Dificultad: media (infraestructura, no código).** Se hace una sola vez. Solo se ejecuta cuando M1–M9 están verdes localmente.

**Premisa absoluta:** si no funciona en local, no se despliega. El despliegue no es una forma de depurar.

**M10.1 — Preparación previa (fuera del código)**
- [ ] Cuenta Revolut Business activa y credenciales de producción disponibles
- [ ] Datos fiscales reales de MyLocal Technologies reunidos
- [ ] Fotografías reales del producto para la landing (no mockups)
- [ ] Dominio `mylocal.es` en posesión

**M10.2 — Cloudflare (una sola vez)**
- [ ] Añadir `mylocal.es` a Cloudflare como sitio
- [ ] Cambiar nameservers en el registrador a los de Cloudflare
- [ ] Esperar propagación DNS (status "Active" en Cloudflare)
- [ ] Registro `A @` → IP de Hostinger (proxied)
- [ ] Registro `A *` → misma IP (proxied) — cubre todos los subdominios
- [ ] SSL/TLS → modo **Full (strict)**
- [ ] Generar Origin Certificate → instalar en Hostinger
- [ ] Page Rule: `*.mylocal.es/MEDIA/*` → cache 1 mes
- [ ] Page Rule: `*.mylocal.es/assets/*` → cache 1 año
- [ ] Page Rule: `*.mylocal.es/acide/*` → bypass cache
- [ ] Page Rule: `*.mylocal.es/seed/*` → bypass cache

**M10.3 — Hostinger (una sola vez)**
- [ ] `.\build.ps1 -Template hosteleria` → genera `release/` completo
- [ ] Subir `release/` a `public_html` (FTP o panel)
- [ ] Instalar Origin Certificate de Cloudflare en el panel
- [ ] PHP ≥ 8.2 con extensiones: `openssl`, `curl`, `fileinfo`, `gd`, `mbstring`, `intl`
- [ ] Permisos: `STORAGE/` y `MEDIA/` en 755
- [ ] Cron a 1 minuto: `php /home/<user>/public_html/axidb/plugins/jobs/worker_run.php`
- [ ] Webhook de Revolut: apuntar a `https://mylocal.es/acide/index.php` (acción `webhook_revolut`)

**M10.4 — Verificación post-despliegue**
- [ ] `https://mylocal.es` → carga landing sin errores de consola
- [ ] `https://demo.mylocal.es/carta` → carga carta pública del local "demo"
- [ ] `https://demo.mylocal.es/dashboard` → redirige a `/acceder`
- [ ] Login → accede al dashboard
- [ ] Registro de un local nuevo → onboarding → carta online en `<slug>.mylocal.es/carta`
- [ ] QR generado → escanearlo con un móvil real → abre la carta
- [ ] Pago de prueba en Revolut sandbox → plan activado
- [ ] Favicon, fuentes, assets → todos en HTTP 200
- [ ] Lighthouse Mobile ≥ 90

**Gate M10 (= Gate MVP):** sistema en producción accesible desde cualquier red. Un hostelero puede registrarse, configurar su carta, publicar una foto, recibir una reseña y tener su QR operativo desde su teléfono. Todo de forma autónoma, sin intervención manual.

---

## 5. Orden de ejecución y tiempos estimados

```
Semana 1: M1 (routing) + M2 (dashboard layout)
          → El esqueleto de la app funciona localmente. La landing sigue en /

Semana 2: M3 (multi-tenancy backend + registro)
          → El sistema sabe qué local sirve. Registro crea locales reales.

Semana 3: M4 (RegisterPage + onboarding 10 pasos)
          → El flujo de captación funciona de extremo a extremo en local.

Semana 4: M5 (carta, QR, ajustes, carta pública)
          → El producto core funciona. El hostelero puede configurar y publicar su carta.

Semana 5: M6 (Timeline + Reseñas)
          → El local tiene presencia viva. Diferenciación real frente a competidores.

Semana 6: M7 (legales automáticos + IA de carta)
          → El onboarding está completo y el copiloto IA funciona.

Semana 7: M8 (Revolut + billing)
          → El negocio puede cobrar. Plan Demo y Pro operativos.

Semana 8: M9 (SEO, rendimiento, UX, textos landing)
          → El producto convence y posiciona. Listo para mostrarlo.

Semana 9: M10 (despliegue Cloudflare + Hostinger)
          → Producción. Primer cliente real puede registrarse.

→ MVP LISTO PARA PRIMER CLIENTE
```

---

## 6. Lo que hereda el MVP del trabajo anterior (no reconstruir)

### Del dashboard legacy (`spa/src/`)
- Gestión de carta: categorías, productos, precios, fotos, alérgenos
- Importación OCR (`ocr_import_carta`)
- Gestión de sala: zonas, mesas, QR tokens
- Carta pública (BrowserRouter, rutas `/carta/:zona/:mesa`)
- GeneradorQR y LocalQrPoster (client-side)

Estrategia: copiar componente por componente, reemplazar fetch legacy por `useSynaxisClient()`.

### De las CAPABILITIES PHP
Todos los handlers existen. El MVP llama exactamente a las mismas acciones. El backend no se toca excepto para añadir `register_local`, `validate_slug`, `SubdomainManager`, `TimelineModel`, `ReviewModel`, `LegalGenerator` y `RevolutDriver`.

### Del sistema de autenticación
AUTH_LOCK blindado. El MVP añade solo el registro de nuevos usuarios (`LoginRegister.php`) sin tocar los archivos existentes de auth.

---

## 7. Lo que NO se hace en el MVP

- **No se despliega para probar.** Si algo no funciona en local, se arregla en local.
- **No se usa Stripe para suscripciones.** Stripe es para pagos de mesa (TPV). Las suscripciones van por Revolut.
- **No se activa el agente IA de reservas** (OpenClaude/OpenClaw disponible pero no activo por defecto)
- **No se implementa TPV completo** (pedidos en mesa, cobro en mesa) — la carta es de consulta en MVP
- **No se implementa Verifactu / TicketBAI** — Fase 3
- **No se hace multi-local por usuario** — Fase 4
- **No se añaden traducciones automáticas completas** — deseable, no bloqueante
- **No se construye el blog en producción** — existe pero es secundario al MVP
- **No se implementan roles de equipo** (editor, camarero, cocina) — Fase 4
- **No se hacen integraciones de delivery** (Glovo, Uber Eats) — Fase 6
- **No se implementa verificación de email al registro** — Fase 2

---

## 8. Dependencias externas (no de código)

| Dependencia | Para qué | Estado |
|------------|----------|--------|
| Cuenta Revolut Business + API keys | Suscripciones M8 | Confirmada de proyectos anteriores |
| Google Pay | Via Revolut hosted checkout, sin código extra | Incluido con Revolut |
| Dominio `mylocal.es` | M10 | Pendiente confirmar |
| Hosting Hostinger con PHP ≥ 8.2 | M10 | Pendiente contratar |
| Cloudflare | M10 subdominios + CDN | Pendiente configurar |
| API key Gemini | OCR avanzado (deseable) | Sin ella usa Tesseract/local |
| Servidor `ai.miaplic.com` | Gemma 4 vision (deseable) | Funciona con Gemini como fallback |
| Datos fiscales reales de MyLocal Technologies | Legales + Footer | Pendiente reunir |
| Fotografías reales del producto | Landing M9 | Pendiente |

---

## 9. Checklist de cierre del MVP

```
[x] M1  — BrowserRouter + Routes funcionando
[x] M1  — Guard RequireAuth redirige a /acceder sin sesión
[x] M2  — Dashboard layout con sidebar + topbar
[x] M2  — Todas las páginas stub sin errores
[x] M3  — SubdomainManager detecta slug por header y por dominio
[x] M3  — Seed dinámico devuelve local_id correcto
[x] M3  — validate_slug + register_local operativos
[x] M3  — Límites Demo en backend desde el primer registro
[x] M3  — test_multitenant.php: 15+ assertions PASS
[x] M4  — RegisterPage con validación de slug en vivo
[x] M4  — Onboarding 10 pasos completo en móvil y desktop
[x] M4  — OnboardingBanner con checklist en dashboard
[x] M5  — CartaPage: CRUD completo de categorías y platos
[x] M5  — QRPage: generación y descarga PNG + PDF
[x] M5  — AjustesPage: nombre, logo, tema funcionales
[x] M5  — CartaPublicaPage: datos reales del local
[x] M6  — TimelineModel + PublicarPage funcionales
[x] M6  — ReviewModel + ReseñasPage + formulario público
[x] M6  — Carta pública muestra timeline y reseñas
[x] M6  — Schema.org AggregateRating en HTML
[x] M7  — Legales generados automáticamente al registrar
[x] M7  — Botón "Generar descripción" IA en CartaPage
[x] M7  — Botón "Sugerir categorías" IA en CartaPage
[x] M7  — Dashboard: página Legales con regenerar
[x] M8  — RevolutDriver: checkout + webhook + status
[x] M8  — BillingManager: activate + downgrade + getStatus
[x] M8  — FacturacionPage: plan actual + botón upgrade + facturas
[x] M8  — Bloqueo suave al día 21 con CTA de upgrade
[ ] M9  — Lighthouse Mobile ≥ 90 en carta pública
[ ] M9  — Textos reales en landing: H1, precios, CTA, datos fiscales
[ ] M9  — 3 personas no técnicas: onboarding < 10 min sin ayuda
[ ] M10 — Cloudflare: wildcard DNS + SSL Full strict
[ ] M10 — Hostinger: release/ subido, PHP ≥ 8.2, cron activo
[ ] M10 — demo.mylocal.es/carta carga carta pública real
[ ] M10 — Registro completo desde móvil en red 4G externa
[ ] M10 — Primer pago Revolut recibido (sandbox o real)
[ ] --- LANZAMIENTO MVP ---
[ ] Primer cliente registrado de forma autónoma
[ ] Primer pago real recibido
[ ] WhatsApp de soporte operativo en horario comercial
```

---

## 10. Historial de iteraciones

### Olas 0–L del framework (2026-01 a 2026-05)
Framework completo. 208 tests PASS. Ver `claude/planes/estructura.md`.

Tests acumulados:
- `test_login.php` 75/75
- `test_citas.php` 9/9
- `test_crm.php` 15/15
- `test_notif.php` 14/14
- `test_delivery.php` 33/33
- `test_tareas.php` 17/17
- `test_openclaude.php` 19/19
- `test_openclaw.php` 26/26
- Framework SPA: 24/24
- Bootstrap CLI: 19/19

### 2026-05-04: auth bearer-only
Bearer + sessionStorage. Sin cookies. Sin CSRF. Documentado en `claude/AUTH_LOCK.md`.

### 2026-05-05: login CAPABILITIES/LOGIN + BrowserRouter
LOGIN extraído a capability blindada (8 archivos PHP, 64 assertions). HashRouter → BrowserRouter. URLs limpias.

### 2026-05-06: OCR multi-engine
Cascada `Tesseract → Gemma 4 (ai.miaplic.com) → Gemini cloud`. 9/9 platos extraídos en pruebas reales.

### 2026-05-15: integración template Google AI Studio
Landing hostelería integrada en `templates/hosteleria/`. Documentado en `claude/docs/integracion_con_google_ai_studio.md`.

### 2026-05-16: revisión y reescritura del plan MVP
- Corrección de flujo de pagos: Revolut (suscripciones) vs Stripe (TPV de mesa)
- Timeline y Reseñas promovidos a MVP (no post-lanzamiento)
- Desarrollo 100% local hasta M10 (despliegue como último paso)
- Olas reordenadas de menor a mayor dificultad técnica
- SubdomainManager testeable en local vía header `X-Local-Id`
- Límites del plan Demo movidos a M3 (registro), no a M8 (billing)
