# Plan de Desarrollo: MyLocal — MVP Fase 1
**Documento:** `claude/planes/desarrolloventasuno.md`
**Última revisión:** 2026-05-16
**Estado:** En ejecución — framework completo, MVP en construcción

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
- [ ] Instalar `react-router-dom` en `templates/hosteleria/`
- [ ] `App.tsx` → `BrowserRouter` + `Routes`
- [ ] Ruta `/` → `LandingPage` (mueve los componentes actuales de App.tsx)
- [ ] Ruta `/registro` → `RegisterPage` (componente stub por ahora)
- [ ] Ruta `/acceder` → `LoginPage` (o modal reutilizado de la landing)
- [ ] Ruta `/dashboard/*` → `DashboardPage` con guard de auth
- [ ] Ruta `/carta` → `CartaPublicaPage` (stub)
- [ ] Ruta `/carta/:zona/:mesa` → misma `CartaPublicaPage` con params
- [ ] `<RequireAuth>` — HOC que lee `getCachedUser()` del SDK; redirige a `/acceder` si no hay sesión

**M1.2 — Vite SPA routing**
- [ ] `vite.config.js` → confirmar que el servidor de desarrollo sirve `index.html` para todas las rutas (ya debería estar)
- [ ] `.htaccess` en `release/` → `RewriteRule ^ /index.html [L]` (ya existe, verificar)

**Gate M1:** `npx tsc --noEmit` pasa. `run.bat hosteleria` arranca. La landing carga en `/`. Navegar a `/dashboard` sin sesión redirige a `/acceder`. La URL `/carta` no da 404.

---

### Ola M2 — Dashboard layout y navegación (frontend, sin lógica de negocio)

**Dificultad: baja-media.** Estructura visual sin datos reales todavía.

**Objetivo:** El hostelero autenticado entra al dashboard y ve la navegación completa del producto.

**M2.1 — Layout del dashboard**
- [ ] `src/pages/dashboard/DashboardLayout.tsx` — sidebar + topbar, envuelve sub-rutas
- [ ] Sidebar items: Inicio / Carta / Diseño / QR / Publicar / Reseñas / Ajustes / Facturación
- [ ] Topbar: nombre del local + avatar + botón "Ver mi carta pública" (abre subdominio en nueva pestaña)
- [ ] Indicador de plan: banner "Demo — X días restantes" cuando aplica
- [ ] Móvil: sidebar como sheet lateral (hamburguesa)

**M2.2 — Páginas stub con empty states reales**
- [ ] `InicioPage.tsx` — métricas: visitas hoy, escaneos QR, plato más visto (datos vacíos con CTA)
- [ ] `CartaPage.tsx` — stub con CTA "Añade tu primer plato"
- [ ] `PublicarPage.tsx` — stub con CTA "Publica tu primera foto"
- [ ] `ReseñasPage.tsx` — stub con CTA "Comparte el enlace para recibir reseñas"
- [ ] `QRPage.tsx` — stub con preview de QR
- [ ] `AjustesPage.tsx` — stub con nombre del local y logo
- [ ] `FacturacionPage.tsx` — stub con plan actual

**Gate M2:** Iniciar sesión con credenciales de prueba locales accede al dashboard. La navegación entre todas las páginas funciona. En móvil (375px) no hay scroll horizontal.

---

### Ola M3 — Backend: multi-tenancy y registro (backend PHP, testeable local)

**Dificultad: media.** PHP puro, sin nueva dependencia externa. Testeable 100% en local con header `X-Local-Id`.

**Objetivo:** el backend sabe en todo momento qué local sirve y puede crear nuevos locales.

**M3.1 — SubdomainManager**
- [ ] `CORE/SubdomainManager.php`
  - Lee `$_SERVER['HTTP_X_LOCAL_ID']` primero (desarrollo local y override de admin)
  - Si no existe, extrae slug de `HTTP_HOST`: `preg_match('/^([a-z0-9\-]+)\.mylocal\.es$/i', $host, $m)`
  - Si es `www` o dominio raíz → slug `mylocal` (landing corporativa)
  - Define la constante global `CURRENT_LOCAL_SLUG`
- [ ] `router.php` → llama a `SubdomainManager::detect()` al inicio de cada request
- [ ] `spa/server/index.php` → igual
- [ ] Función global `get_current_local_id()` disponible en todos los handlers

**M3.2 — Seed dinámico por local**
- [ ] El endpoint `/seed/bootstrap.json` devuelve JSON dinámico: `{"local_id":"<slug>","plan":"demo","demo_days_left":21}`
- [ ] `SynaxisProvider` en el SDK ya consume este endpoint; verificar que funciona con datos dinámicos

**M3.3 — Backend: registro de nuevos locales**
- [ ] `CAPABILITIES/LOGIN/LoginRegister.php`
  - Acción `validate_slug` — sin auth — devuelve `{available: bool, reason: string}`
  - Regex: `^[a-z][a-z0-9-]{2,30}$`
  - Lista de slugs reservados: `admin, dashboard, api, www, mail, ftp, cdn, acide, mylocal, demo, test, staging, panel, support, help, docs, blog, shop, carta, registro, acceder`
  - Acción `register_local` — crea usuario + local + STORAGE inicial → devuelve token de sesión
  - Al registrar: genera legales automáticos para el local (privacidad, cookies, aviso legal) a partir de templates con datos del local
- [ ] Añadir `validate_slug` y `register_local` a `ALLOWED_ACTIONS` del dispatcher

**M3.4 — Límites del plan Demo (desde el primer día, no después)**
- [ ] Backend verifica el plan antes de writes: si Demo → máximo 20 platos, 1 zona, 5 mesas
- [ ] Si supera límite → `{"success":false,"error":"PLAN_LIMIT","upgrade_url":"/dashboard/facturacion"}`
- [ ] Función reutilizable `check_plan_limit($localId, $resource, $count)` en CORE

**M3.5 — Test de multi-tenancy local**
- [ ] `spa/server/tests/test_multitenant.php` — 15+ assertions
- [ ] `curl -H "X-Local-Id: elbar" http://localhost:8091/seed/bootstrap.json` → `{"local_id":"elbar"}`
- [ ] Usuario de local A no puede leer datos de local B
- [ ] Registro de un local nuevo crea STORAGE aislado y devuelve token válido

**Gate M3:** `test_multitenant.php` pasa. Con `X-Local-Id: elbar` en las peticiones, el sistema sirve el contexto de "elbar". Un registro nuevo crea el local y hace login automático.

---

### Ola M4 — Registro y onboarding (frontend + integración M3)

**Dificultad: media.** Depende de M3 para el backend. El flujo de 10 pasos es el corazón del producto.

**Objetivo:** usuario nuevo puede registrarse y tener su carta online en menos de 10 minutos.

**M4.1 — RegisterPage**
- [ ] `src/pages/RegisterPage.tsx` — form: tipo de negocio → nombre del local → slug → email → contraseña
- [ ] Validación de slug en tiempo real: llama a `validate_slug` con debounce 400ms
- [ ] Feedback visual: punto verde/rojo + preview URL `<slug>.mylocal.es`
- [ ] Al enviar: llama a `register_local` → guarda token en SDK → redirige a `/dashboard?onboarding=1`
- [ ] Sin verificación de email en MVP (flujo sin fricción)

**M4.2 — Onboarding 10 pasos (per ventas.md)**
- [ ] `src/components/OnboardingWizard.tsx` — wizard paso a paso, se activa cuando `?onboarding=1`
- [ ] Paso 1: Tipo de negocio (Bar / Restaurante / Cafetería / Otro) → personaliza plantillas
- [ ] Paso 2: Identidad — nombre del local + subida de logo. Preview en vivo
- [ ] Paso 3: Idiomas — ES por defecto, toggles EN/FR/DE
- [ ] Paso 4: Categorías — botón "Sugerir automáticamente" (llama a OpenClaude) o manual
- [ ] Paso 5: Primer plato — nombre, precio, foto, descripción + botón "Generar descripción" (IA)
- [ ] Paso 6: Diseño — 3 plantillas: Minimal / Elegante / Moderno
- [ ] Paso 7: Colores — autogenerados desde logo o selección manual
- [ ] Paso 8: Vista previa — simulación de móvil con scroll real + botón "Ver como cliente"
- [ ] Paso 9: QR — generar QR general + por mesa si aplica. Descarga PNG y PDF
- [ ] Paso 10: Momento WOW — "Tu carta ya está online" + enlace + QR grande + CTA "Compartir"
- [ ] Progreso guardado por paso (si cierra y vuelve, retoma donde dejó)

**M4.3 — OnboardingBanner post-wizard**
- [ ] Banner contextual en el dashboard los primeros 21 días
- [ ] Checklist: ① Sube tu logo ② Añade tu primer plato ③ Descarga tu QR ④ Publica tu primera foto ⑤ Comparte el enlace
- [ ] Cada item se marca automáticamente cuando se completa
- [ ] Se oculta al 100% o cuando el usuario lo cierra

**Gate M4:** usuario nuevo en `localhost:5173` puede registrarse, completar el wizard y tener su carta accesible en `localhost:8091/carta` con los datos del onboarding.

---

### Ola M5 — Migración del legacy: Carta y QR (frontend + SDK)

**Dificultad: media-alta.** El bloque más denso del MVP. El dashboard legacy existe pero usa patrones distintos al SDK.

**Objetivo:** el hostelero puede gestionar su carta completa desde el nuevo dashboard.

**Estrategia:** copiar componentes de `spa/src/` a `templates/hosteleria/src/pages/dashboard/`, reemplazando cada llamada fetch legacy por `useSynaxisClient()` del SDK. Un componente a la vez, con tests locales tras cada migración.

**M5.1 — CartaPage completa**
- [ ] Listado de categorías + platos (lee de CAPABILITIES/CARTA)
- [ ] Añadir / editar / eliminar categoría
- [ ] Añadir / editar / eliminar plato (nombre, precio, foto, descripción, alérgenos)
- [ ] Botón "Generar descripción" → OpenClaude → rellena el campo automáticamente
- [ ] Drag & drop para reordenar platos y categorías
- [ ] Respeta límites del plan Demo: si llega a 20 platos, muestra CTA de upgrade

**M5.2 — QRPage funcional**
- [ ] Generación de QR general del local
- [ ] Generación de QR por mesa/zona
- [ ] Descarga PNG y PDF (QR sheet con instrucciones — usa PDFGEN existente)
- [ ] Vista previa del QR antes de descargar

**M5.3 — AjustesPage funcional**
- [ ] Nombre del local, descripción corta, dirección, teléfono, horario
- [ ] Subida y recorte de logo
- [ ] Selector de tema visual (colores del local)
- [ ] Cambio de email y contraseña

**M5.4 — CartaPublicaPage**
- [ ] Migrada del legacy: carga la carta del local según `local_id` del contexto
- [ ] Categorías filtradas, foto por plato, alérgenos, precio
- [ ] Multiidioma básico: si el local tiene EN activado, botón de cambio
- [ ] Funciona en `/carta` y `/carta/:zona/:mesa`

**Gate M5:** el hostelero puede añadir, editar y eliminar platos. La carta pública en `/carta` refleja los cambios en tiempo real. El QR descargable apunta a la URL correcta.

---

### Ola M6 — Local Vivo: Timeline y Reseñas (nuevo backend + frontend)

**Dificultad: media.** Dos modelos nuevos en AxiDB + UI en el dashboard + sección en la carta pública.

**Objetivo:** el local tiene presencia viva en la web, no solo una carta estática. Esto es diferenciación directa frente a NordQR y Bakarta.

**M6.1 — Backend: TimelineModel**
- [ ] `CAPABILITIES/TIMELINE/TimelineModel.php` — AxiDB, colección `timeline/<local_id>/`
- [ ] Campos: `id`, `tipo` (foto/texto/video), `titulo`, `descripcion`, `media_url`, `publicado_at`
- [ ] Acciones: `create_post`, `list_posts`, `delete_post`
- [ ] Subida de imagen a `MEDIA/<local_id>/timeline/`
- [ ] Máximo 50 posts en Demo, ilimitado en Pro

**M6.2 — Backend: ReviewModel con Schema.org**
- [ ] `CAPABILITIES/REVIEWS/ReviewModel.php` — AxiDB, colección `reviews/<local_id>/`
- [ ] Campos: `id`, `autor`, `estrellas` (1-5), `comentario`, `fecha`, `verificado`
- [ ] Acciones: `create_review` (sin auth — pública), `list_reviews`, `delete_review` (con auth)
- [ ] Enlace de invitación firmado con token para que el cliente deje reseña
- [ ] Schema.org `AggregateRating` en la carta pública para SEO

**M6.3 — Dashboard: PublicarPage**
- [ ] `src/pages/dashboard/PublicarPage.tsx`
- [ ] Form: subir foto o video corto + título + descripción
- [ ] Lista de posts publicados con opción de eliminar
- [ ] Preview de cómo se verá en la carta pública

**M6.4 — Dashboard: ReseñasPage**
- [ ] `src/pages/dashboard/ReseñasPage.tsx`
- [ ] Lista de reseñas recibidas con estrellas y comentario
- [ ] Botón "Generar enlace de invitación" — copia URL al portapapeles
- [ ] Puntuación media visible + distribución de estrellas
- [ ] Respuesta del dueño a reseñas (visible en carta pública)

**M6.5 — Carta pública: sección Timeline y Reseñas**
- [ ] En la carta pública, debajo del menú: sección "Últimas novedades" (timeline)
- [ ] Sección "Lo que dicen nuestros clientes" (reseñas + Schema.org)
- [ ] Enlace "Deja tu reseña" → formulario público (no requiere cuenta)

**Gate M6:** el dueño publica una foto desde el dashboard y aparece en la carta pública. Un cliente puede dejar una reseña con 5 estrellas desde la carta pública. La carta pública muestra `AggregateRating` en el HTML (verificable con Google Rich Results Test).

---

### Ola M7 — Legales automáticos e IA de carta (integración con lo existente)

**Dificultad: baja-media.** Usa capacidades ya construidas (OpenClaude, PDFGEN). Solo hay que conectarlas al flujo de registro y al dashboard.

**Objetivo:** al registrarse, el hostelero tiene sus legales listos. El copiloto IA funciona en el dashboard.

**M7.1 — Generación automática de legales al registrar**
- [ ] `register_local` (M3.3) dispara la generación de legales al finalizar
- [ ] `CAPABILITIES/LEGAL/LegalGenerator.php` — toma nombre del local, email, slug, dirección → renderiza plantillas
- [ ] Documentos generados: Política de Privacidad, Aviso Legal, Política de Cookies
- [ ] Guardados en `STORAGE/<slug>/legal/` como archivos Markdown
- [ ] Accesibles en la carta pública en `/legal/privacidad`, `/legal/aviso`, `/legal/cookies`
- [ ] En los ajustes: página "Mis Legales" con botón de regenerar si cambian los datos

**M7.2 — Copiloto IA en CartaPage**
- [ ] Botón "Generar descripción" en el form de edición de plato
- [ ] Llama a `CAPABILITIES/AI` (OpenClaude) con nombre + ingredientes → devuelve descripción en < 5s
- [ ] Botón "Sugerir categorías" en el setup inicial → devuelve lista según tipo de negocio
- [ ] Botón "Traducir carta" (deseable, no bloqueante para el gate)

**Gate M7:** registrar un local nuevo crea automáticamente los 3 documentos legales. En la carta de un plato, el botón "Generar descripción" rellena el campo. Los legales son accesibles públicamente en las URLs correctas.

---

### Ola M8 — Suscripción con Revolut + Google Pay (billing)

**Dificultad: media-alta.** Primera integración con Revolut Business API. Credenciales confirmadas de proyectos anteriores.

**Objetivo:** el hostelero puede pagar su suscripción de forma autónoma con Revolut o Google Pay.

**M8.1 — RevolutDriver para suscripciones**
- [ ] `CAPABILITIES/PAYMENT/drivers/RevolutDriver.php`
- [ ] Peticiones HTTP directas a Revolut Business API (sin SDK externo, `curl` nativo)
- [ ] Acciones: `create_order` (genera checkout URL), `check_order_status`, `webhook_revolut`
- [ ] Google Pay: se activa en el checkout de Revolut sin código adicional (Revolut lo gestiona)
- [ ] Persistencia: `STORAGE/billing/<slug>/subscription.json`, `invoices/<id>.json`

**M8.2 — Gestión de suscripciones**
- [ ] `CAPABILITIES/BILLING/BillingManager.php`
- [ ] Webhook de Revolut: en `ORDER_COMPLETED` → actualiza plan en AxiDB, extiende días, elimina límites Demo
- [ ] En cancelación → downgrade a Demo con 0 días, activa bloqueo suave
- [ ] Acción `get_subscription_status` → devuelve plan actual, fecha próximo cobro, facturas
- [ ] Añadir acciones a `ALLOWED_ACTIONS`

**M8.3 — FacturacionPage funcional**
- [ ] `src/pages/dashboard/FacturacionPage.tsx`
- [ ] Card "Tu plan": Demo (X días) / Pro Mensual (27€/mes) / Pro Anual (260€/año)
- [ ] Botón "Activar Pro" → `create_order` → redirect a Revolut hosted checkout
- [ ] Regreso desde Revolut → `/dashboard/facturacion?success=1` → feedback de éxito
- [ ] Comparativa mensual vs anual: ahorro calculado en tiempo real (−20%)
- [ ] Histórico de facturas: tabla con fecha, importe, descarga PDF

**M8.4 — Flujo de conversión Demo → Pro**
- [ ] Día 7: informe automático "Así ha ido tu primera semana" (email transaccional o notificación interna)
- [ ] Día 14: aviso "te quedan 7 días" con CTA de upgrade
- [ ] Día 18: informe final "has recibido X visitas"
- [ ] Día 21: bloqueo suave del panel — overlay con "Tu período de prueba ha terminado" + botón upgrade

**Gate M8:** en sandbox de Revolut, el hostelero puede completar el pago de 27€, el webhook actualiza el plan a Pro, los límites de Demo desaparecen y aparece la factura en el histórico.

---

### Ola M9 — SEO, rendimiento y calidad (transversal)

**Dificultad: baja.** Todo técnico puro. Sin dependencias de negocio nuevas.

**Objetivo:** el producto no solo funciona, convence y posiciona.

**M9.1 — SEO en la carta pública**
- [ ] `<title>` dinámico: "Carta de [Nombre del Local] — MyLocal"
- [ ] `<meta name="description">` con descripción del local
- [ ] Schema.org `Restaurant` + `Menu` + `MenuItem` generados en el servidor PHP
- [ ] Schema.org `AggregateRating` (desde M6)
- [ ] Open Graph tags para compartir en redes (foto del local + nombre)
- [ ] `robots.txt`: permite `/carta/*`, bloquea `/dashboard/*`, `/acide/*`

**M9.2 — Rendimiento**
- [ ] Carta pública carga en < 2s en 4G (Lighthouse Mobile ≥ 90)
- [ ] Imágenes de platos en formato WebP o AVIF con lazy loading
- [ ] Assets JS/CSS con hash (ya lo hace Vite) — cache 1 año

**M9.3 — UX del dashboard**
- [ ] Ningún botón dice "Submit", "OK" o "Guardar" genérico — verbo + objeto ("Guardar carta", "Publicar foto", "Descargar QR")
- [ ] Errores en castellano humano: "No se pudo guardar el plato. Comprueba la conexión." — nunca "Error 500"
- [ ] Skeleton screens en listas (no spinners genéricos)
- [ ] Mensajes de éxito breves: "Plato añadido", "Foto publicada", "QR descargado"

**M9.4 — Landing page textos reales**
- [ ] H1: "Tu negocio en la nube en 10 minutos. Sin instalar nada."
- [ ] Propuesta de valor completa vs NordQR y Bakarta (carta + presencia + reputación + IA)
- [ ] CTA principal: "Empieza gratis — 21 días, sin tarjeta" → `/registro`
- [ ] Precios reales en PricingSection: Demo (0€) / Pro mensual (27€+IVA) / Pro anual (260€+IVA)
- [ ] Datos fiscales reales de MyLocal Technologies en el Footer

**Gate M9:** Lighthouse Mobile ≥ 90 en la carta pública de un local de prueba. Ningún texto de placeholder visible. 3 personas no técnicas hacen el onboarding completo en < 10 minutos sin ayuda.

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
[ ] M1  — BrowserRouter + Routes funcionando
[ ] M1  — Guard RequireAuth redirige a /acceder sin sesión
[ ] M2  — Dashboard layout con sidebar + topbar
[ ] M2  — Todas las páginas stub sin errores
[ ] M3  — SubdomainManager detecta slug por header y por dominio
[ ] M3  — Seed dinámico devuelve local_id correcto
[ ] M3  — validate_slug + register_local operativos
[ ] M3  — Límites Demo en backend desde el primer registro
[ ] M3  — test_multitenant.php: 15+ assertions PASS
[ ] M4  — RegisterPage con validación de slug en vivo
[ ] M4  — Onboarding 10 pasos completo en móvil y desktop
[ ] M4  — OnboardingBanner con checklist en dashboard
[ ] M5  — CartaPage: CRUD completo de categorías y platos
[ ] M5  — QRPage: generación y descarga PNG + PDF
[ ] M5  — AjustesPage: nombre, logo, tema funcionales
[ ] M5  — CartaPublicaPage: datos reales del local
[ ] M6  — TimelineModel + PublicarPage funcionales
[ ] M6  — ReviewModel + ReseñasPage + formulario público
[ ] M6  — Carta pública muestra timeline y reseñas
[ ] M6  — Schema.org AggregateRating en HTML
[ ] M7  — Legales generados automáticamente al registrar
[ ] M7  — Botón "Generar descripción" IA en CartaPage
[ ] M8  — RevolutDriver: checkout + webhook + status
[ ] M8  — FacturacionPage: plan actual + botón upgrade + facturas
[ ] M8  — Bloqueo suave al día 21 con CTA de upgrade
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
