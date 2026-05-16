# Plan de Desarrollo: MyLocal — Fase 1 MVP
**Documento:** `claude/planes/desarrolloventasuno.md`  
**Última revisión:** 2026-05-15  
**Estado:** En ejecución — framework completo, MVP en construcción

---

## 0. Contexto: lo que existe vs lo que falta

### Lo que está construido y funciona (no reabrir)

| Bloque | Estado | Notas |
|--------|--------|-------|
| Framework multi-sector (olas 0–L) | Completo | SDK, templates, CAPABILITIES, build.ps1 |
| `@mylocal/sdk` workspaces pnpm | Completo | SynaxisProvider, login, useSynaxisClient |
| Templates: hosteleria, clinica, logistica, asesoria | Completos como apps Vite | hosteleria = landing page + SDK integrado |
| CAPABILITIES: LOGIN, OPTIONS, CARTA, QR, OCR, PDFGEN, TPV, AI, DELIVERY, CITAS, CRM, NOTIFICACIONES, TAREAS, OPENCLAW | Completos | 208 tests PASS |
| Auth (bearer-only, sin cookies, sin CSRF) | Blindado | AUTH_LOCK.md |
| OCR pipeline: Tesseract → Gemma 4 → Gemini | Completo | cascade multi-engine |
| JobQueue / Worker cron | Completo | async OCR |
| Blog recetas + scraper | Completo | |
| Legales + wiki | Completo | 6 páginas legales, 10 artículos |
| PDFGEN (3 plantillas carta + QR sheet + table tents) | Completo | |
| Dashboard SPA hostelería (spa/) | Completo (legacy) | carta, mesas, sala, QR, importación OCR |
| Carta pública (/carta, /carta/:zona/:mesa) | Completo (legacy) | BrowserRouter, rutas amigables |
| Integración de templates externos (skill) | Nuevo | `.claude/commands/integrar-template.md` |

### Lo que NO existía en el plan anterior y existe ahora

1. **SDK compartido `@mylocal/sdk`** — no contemplado en el plan original. Cambia cómo los templates se conectan al backend.
2. **Arquitectura de templates independientes** — cada vertical es una SPA Vite autocontenida, no un módulo dentro de una SPA monolítica.
3. **Landing page de hostelería** (`templates/hosteleria/`) — traída de Google AI Studio, integrada con el SDK. Es la cara pública de MyLocal para captación.
4. **Skill de integración** — proceso documentado y automatizable para traer cualquier template externo al framework.
5. **pnpm workspaces** — gestión unificada de dependencias en monorepo; `framer-motion` como dependencia directa requerida cuando se usa `motion/react`.

### La brecha crítica para el MVP

El framework está completo. El producto no está desplegado. Las piezas existen pero no están unidas en un flujo de usuario de extremo a extremo:

```
Usuario llega → Landing mylocal.es → Se registra → Onboarding → Dashboard
→ Configura carta → Genera QR → Cliente escanea QR → Ve carta → VENTA HECHA
```

Cada flecha de ese flujo tiene trabajo pendiente.

---

## 1. Arquitectura objetivo del MVP

### Una sola SPA, tres experiencias

```
templates/hosteleria/src/
  App.tsx                     ← Router raíz
  pages/
    LandingPage.tsx           ← / (marketing, captación)
    RegisterPage.tsx          ← /registro (alta de nuevo local)
    DashboardPage.tsx         ← /dashboard/* (hostelero autenticado)
    CartaPublicaPage.tsx      ← /carta, /carta/:zona/:mesa (QR clientes)
    LoginPage.tsx             ← /acceder (opcional, el modal sirve en landing)
```

### Despliegue único

```
build.ps1 → release/ → Hostinger public_html
                          ↓
            mylocal.es → SPA (Landing + Dashboard + Carta)
            *.mylocal.es → misma SPA; PHP detecta el subdominio y carga el local
```

### Cómo se carga el contexto del local

```
Router → PHP extrae HOST → define CURRENT_LOCAL_SLUG
       → Carga STORAGE/locales/<slug>.json
       → SynaxisProvider recibe local_id via seed/bootstrap.json del subdominio
       → Todos los componentes usan useSynaxisClient() ya contextualizado
```

---

## 2. Definición exacta del MVP

**MVP = primer hostelero puede pagar y usar el producto de manera autónoma.**

No es necesario que todo funcione. Sí es necesario que esto funcione sin intervención manual:

| Función | Obligatorio para MVP |
|---------|---------------------|
| Landing mylocal.es con propuesta de valor clara | Sí |
| Registro de nuevo local (slug, email, contraseña) | Sí |
| Login y acceso al dashboard | Sí |
| Gestión de carta (categorías, platos, precios, fotos) | Sí |
| Carta pública accesible via QR | Sí |
| Generación de QR descargable | Sí |
| Configuración básica del local (nombre, logo, tema) | Sí |
| Pago con tarjeta (Stripe — al menos sandbox) | Sí |
| Despliegue en Hostinger + Cloudflare (subdominios) | Sí |
| OCR de carta (PDF/foto) | Deseable — no bloqueante |
| Micro-Timeline (Publicar estado/foto del local) | Sí (Nuevo Estándar) |
| Sistema de Reseñas internas (Schema.org) | Sí (Nuevo Estándar) |
| Generación automática de Legales (Privacidad/Cookies) | Sí (Incluido) |
| Agente IA (OpenClaude/OpenClaw) | No (Fase 2) |
| TPV completo (pedidos, pagos en mesa) | No (Fase 2) |
| Multi-local por usuario | No (Fase 2) |

---

## 3. Principios de ejecución (no negociables)

1. **Atómico.** Cada archivo nuevo se valida por sí solo. No existe el commit "WIP".
2. **≤ 250 LOC.** Si un archivo va a superarlo, se parte antes.
3. **Sin hardcodeos.** Todo configurable via OPTIONS, config.json o AxiDB.
4. **Sin datos ficticios.** Empty states con CTA. Nunca Lorem Ipsum.
5. **Sin funciones a medias.** Existe completa o no existe.
6. **AUTH_LOCK intacto.** Cualquier cambio que toque auth, login, fetch, /acide, SynaxisClient: leer AUTH_LOCK.md primero.
7. **Test antes de marcar hecho.** Sin verde no se cierra.
8. **El framework no se modifica para el template.** El template se adapta al framework.

---

## 4. Olas MVP (lo que queda por hacer)

### Ola M1 — Routing y estructura SPA completa

**Objetivo:** `templates/hosteleria/` maneja las tres experiencias (landing, dashboard, carta pública) con react-router-dom. La landing actual queda como la ruta `/`.

**Estado:** Pendiente

**M1.1 — Routing principal**
- [ ] `src/App.tsx` → añadir `BrowserRouter` + `Routes`
- [ ] Ruta `/` → `LandingPage` (componentes actuales del template de Google AI Studio)
- [ ] Ruta `/acceder` → `LoginPage` (o modal sobre la landing)
- [ ] Ruta `/registro` → `RegisterPage`
- [ ] Ruta `/dashboard/*` → `DashboardPage` (requiere auth, redirect a /acceder si no)
- [ ] Ruta `/carta` → `CartaPublicaPage`
- [ ] Ruta `/carta/:zona/:mesa` → `CartaPublicaPage` con contexto de mesa
- [ ] Guard de autenticación: HOC `<RequireAuth>` que lee `getCachedUser()` del SDK
- [ ] La landing actual (`HeroSection`, `QRSection`, etc.) se mueve a `pages/LandingPage.tsx`

**M1.2 — Layout de dashboard**
- [ ] `src/pages/dashboard/DashboardLayout.tsx` — sidebar + header, envuelve todas las sub-rutas
- [ ] Sidebar: Carta / Mesas / Configuración / Facturación / Cuenta
- [ ] Header: nombre del local + botón "Ver mi carta pública" + avatar usuario
- [ ] Indicador de plan (Demo X días / Pro)
- [ ] Mobile: sidebar hamburguesa

**M1.3 — Migración del dashboard legacy**
El dashboard completo existe en `spa/src/`. La estrategia es portarlo a `templates/hosteleria/src/pages/dashboard/` conectando vía SDK.

- [ ] `pages/dashboard/CartaPage.tsx` — gestión de carta (migrado de spa/src/)
- [ ] `pages/dashboard/MesasPage.tsx` — gestión de sala y QR
- [ ] `pages/dashboard/ConfigPage.tsx` — datos del local, logo, tema visual
- [ ] `pages/dashboard/FacturacionPage.tsx` — plan, facturas, métodos de pago
- [ ] `pages/dashboard/CuentaPage.tsx` — perfil, contraseña, sesiones

**Gate M1:** `npx tsc --noEmit` pasa; Vite arranca; ruta `/dashboard` redirige a `/acceder` si no hay sesión; ruta `/carta` muestra carta pública.

---

### Ola M2 — Registro y onboarding

**Objetivo:** un usuario nuevo puede crear su cuenta y tener su carta online en < 10 minutos de forma autónoma.

**Estado:** Pendiente

**M2.1 — Backend: registro de nuevos locales**
- [ ] `CAPABILITIES/LOGIN/LoginRegister.php` — flujo de alta: valida email, slug, crea usuario, crea local en STORAGE/locales/<slug>.json, bootstrap de carta vacía
- [ ] Validador de slug: regex `^[a-z][a-z0-9-]{2,30}$` + lista palabras reservadas (`admin`, `dashboard`, `api`, `www`, `mail`, `ftp`, `cdn`, `static`, `acide`, `mylocal`, `demo`, `test`, `staging`, `dev`, `panel`, `support`, `help`, `docs`, `blog`, `shop`)
- [ ] Acción `validate_slug` — `{available: bool, reason: string}` — sin auth requerida
- [ ] Acción `register_local` — crea cuenta + local + bootstraps → devuelve token de sesión
- [ ] Añadir ambas a `ALLOWED_ACTIONS` del dispatcher

**M2.2 — Frontend: RegisterPage**
- [ ] `src/pages/RegisterPage.tsx` — form: nombre del negocio, slug (con validación en vivo), email, contraseña
- [ ] Feedback visual slug: verde/rojo mientras escribe, preview URL `<slug>.mylocal.es`
- [ ] Al registrar: llama a `register_local` → si success, guarda token, redirige a `/dashboard?onboarding=1`
- [ ] Sin verificación de email en MVP (añadir en Fase 2)

**M2.3 — Onboarding post-registro**
- [ ] `src/components/OnboardingBanner.tsx` — banner contextual en el dashboard los primeros 7 días
- [ ] Checklist: ① Sube tu logo ② Añade tu primer plato ③ Configura tus mesas ④ Descarga tu QR
- [ ] Cada item se marca automáticamente cuando se completa (polling del estado del local)
- [ ] El banner se oculta cuando el checklist está al 100% o el usuario lo cierra

**Gate M2:** usuario nuevo en una URL fresca puede registrarse, ver el dashboard vacío con el onboarding, añadir un plato y tener su carta online en `<slug>.mylocal.es/carta`.

---

### Ola M3 — Multi-tenancy: subdominios en producción

**Objetivo:** cada hostelero tiene su propio subdominio. El backend carga su contexto automáticamente.

**Estado:** Diseñado, pendiente de implementar

**M3.1 — Backend: detección de subdominio**
- [ ] `CORE/SubdomainManager.php` (existe en notas, verificar si está implementado)
  - Lee `$_SERVER['HTTP_HOST']`
  - Extrae slug: `preg_match('/^([a-z0-9\-]+)\.mylocal\.es$/i', $host, $m)`
  - Define `CURRENT_LOCAL_SLUG = $m[1]`
  - Si es `www` o raíz → `CURRENT_LOCAL_SLUG = 'mylocal'` (landing corporativa)
- [ ] `router.php` llama a `SubdomainManager::detect()` al inicio de cada request
- [ ] `spa/server/index.php` igual
- [ ] Función global `get_current_local_id()` → combina subdominio + header `X-Local-Id` (override para dashboard admin)

**M3.2 — Seed por subdominio**
- [ ] `public/seed/bootstrap.json` se genera dinámicamente por PHP con el local_id del subdominio
- [ ] El endpoint `/seed/bootstrap.json` devuelve `{ "local_id": "<slug>", "plan": "demo" }`
- [ ] `SynaxisProvider` recibe este seed y lo inyecta en todas las llamadas al SDK

**M3.3 — Aislamiento de datos**
- [ ] Todos los modelos en CAPABILITIES filtran por `local_id` en lecturas
- [ ] `get_current_local_id()` disponible en todos los handlers
- [ ] Verificar: usuario de local A no puede leer/escribir datos de local B (test de aislamiento)

**M3.4 — Test de subdominio**
- [ ] `spa/server/tests/test_subdomain.php` — 10+ assertions
- [ ] `curl -H "Host: elbar.mylocal.es" http://localhost:8091/seed/bootstrap.json` → `{"local_id":"elbar"}`
- [ ] Añadir al gate de `build.ps1`

**Gate M3:** con el release desplegado en Hostinger, `elbar.mylocal.es` carga el dashboard del local "elbar" y `otra.mylocal.es` carga el de "otra", sin que haya colisión de datos.

---

### Ola M4 — Pago (Stripe)

**Objetivo:** el hostelero puede introducir su tarjeta y activar el plan Pro.

**Estado:** Diseñado (mock), pendiente conexión real

**M4.1 — Backend Stripe**
- [ ] `CAPABILITIES/PAYMENT/StripeAdapter.php` — cliente HTTP contra la API de Stripe (sin SDK de Stripe, peticiones `curl` directas)
- [ ] Acciones: `create_checkout_session`, `stripe_webhook` (verifica firma), `get_subscription_status`, `cancel_subscription`
- [ ] Persistencia en `STORAGE/billing/<local_id>/subscription.json`, `invoices/<id>.json`
- [ ] Webhook: en `payment_intent.succeeded` → actualiza plan a Pro, genera factura
- [ ] En `customer.subscription.deleted` → downgrade a Demo, notificación al hostelero
- [ ] Registrar en `ALLOWED_ACTIONS` (webhook sin auth, resto con auth)

**M4.2 — Frontend: FacturacionPage**
- [ ] `src/pages/dashboard/FacturacionPage.tsx`
- [ ] Card "Tu plan": Demo (X días restantes) / Pro Mensual (27€/mes) / Pro Anual (260€/año)
- [ ] Botón "Activar Pro" → llama a `create_checkout_session` → redirect a Stripe hosted checkout
- [ ] Regreso desde Stripe → URL de éxito `/dashboard/facturacion?success=1`
- [ ] Histórico de facturas: tabla con fecha, importe, botón descarga PDF
- [ ] Comparativa Mensual vs Anual con ahorro calculado (20%)

**M4.3 — Plan Demo con límites**
- [ ] Demo dura 14 días desde el registro
- [ ] Demo permite: máximo 20 platos, 1 zona, 5 mesas
- [ ] Dashboard muestra cuenta atrás y CTA de upgrade siempre visible en Demo
- [ ] Límites verificados en backend: si está en Demo y supera el límite → error con `error: "PLAN_LIMIT"` + enlace a upgrade

**Gate M4:** en sandbox de Stripe, el hostelero puede pagar 27€ con la tarjeta de test `4242 4242 4242 4242`, recibe confirmación, su plan pasa a Pro y los límites se eliminan.

---

### Ola M5 — Despliegue en producción

**Objetivo:** el sistema funciona en `mylocal.es` con todos los subdominios operativos.

**Estado:** Parcialmente documentado, pendiente ejecución

**M5.1 — Cloudflare (una sola vez)**
- [ ] Añadir `mylocal.es` a Cloudflare como sitio
- [ ] Cambiar nameservers en el registrador a los de Cloudflare
- [ ] Esperar propagación DNS (status "Active" en Cloudflare)
- [ ] Registro `A @` → IP de Hostinger (proxied, naranja)
- [ ] Registro `A *` → misma IP (proxied, naranja) — cubre todos los subdominios
- [ ] SSL/TLS → modo **Full (strict)**
- [ ] Generar Origin Certificate desde Cloudflare → SSL/TLS → Origin Server → instalar en Hostinger
- [ ] Page Rule: `*.mylocal.es/MEDIA/*` → cache 1 mes
- [ ] Page Rule: `*.mylocal.es/assets/*` → cache 1 año (assets versionados por hash)
- [ ] Page Rule: `*.mylocal.es/acide/*` → bypass cache (API siempre al origen)
- [ ] Page Rule: `*.mylocal.es/seed/*` → bypass cache (seed dinámico por subdominio)

**M5.2 — Hostinger (una sola vez)**
- [ ] Subir `release/` a `public_html` (FTP o panel)
- [ ] Instalar el Origin Certificate de Cloudflare en el panel de Hostinger
- [ ] PHP ≥ 8.2 activo con extensiones: `openssl`, `curl`, `fileinfo`, `gd`, `mbstring`, `intl`
- [ ] Verificar permisos: `STORAGE/` y `MEDIA/` con 755, propiedad del usuario PHP
- [ ] Cron a 1 minuto: `php /home/<user>/public_html/axidb/plugins/jobs/worker_run.php`
- [ ] Health check: `curl https://mylocal.es/acide/index.php` → `{"success":true,"action":"health_check"}`

**M5.3 — Build para producción**
- [ ] `.\build.ps1 -Template hosteleria` genera release/ completo
- [ ] Verificar que el `index.html` generado tiene base `/` (absoluta, no `./`)
- [ ] Verificar que `.htaccess` tiene `RewriteRule ^ /index.html [L]` para SPA routing
- [ ] El PHP backend usa `__DIR__` en todos los includes (no paths absolutos)

**M5.4 — Verificación post-despliegue**
- [ ] `https://mylocal.es` → carga la landing sin errores de consola
- [ ] `https://demo.mylocal.es/carta` → carga la carta pública del local "demo"
- [ ] `https://demo.mylocal.es/dashboard` → redirige a `/acceder` (no autenticado)
- [ ] Login desde `https://demo.mylocal.es` con credenciales → accede al dashboard
- [ ] Favicon, fuentes, assets → todos en 200
- [ ] Lighthouse mobile ≥ 90

**Gate M5:** sistema en producción, accesible desde cualquier red. Un hostelero puede registrarse, configurar su carta y tener su QR operativo desde su teléfono.

---

### Ola M6 — Calidad y textos para vender

**Objetivo:** el producto no solo funciona, sino que convence. Auditoría de UX y contenidos.

**Estado:** Pendiente

**M6.1 — Landing page pulida**
La landing de `templates/hosteleria/` tiene el diseño de Google AI Studio. Necesita contenido real.

- [ ] Textos reales en todas las secciones (no placeholder)
- [ ] H1: "Tu Menú Digital en 2 Minutos — desde una Foto o PDF"
- [ ] Propuesta de valor clara vs competidores (NordQR, Bakarta): precio, OCR, sin hardware
- [ ] Precios reales en `PricingSection.tsx`: Demo (gratis 14 días) / Pro Mensual (27€) / Pro Anual (260€, −20%)
- [ ] CTA principal: "Empieza gratis — sin tarjeta" → `/registro`
- [ ] Screenshots o video demo del producto real (no mockups)
- [ ] Footer: datos fiscales reales de MyLocal Technologies

**M6.2 — UX del dashboard**
- [ ] Auditoría pantalla por pantalla: comparar con Last.app, Qamarero, Honei
- [ ] Ningún botón dice "Submit", "OK" o "Guardar" genérico — verbo + objeto
- [ ] Errores en castellano humano (sin "Error 500")
- [ ] Skeleton screens en listas (no spinners genéricos)
- [ ] Optimistic updates en CRUD de carta (plato aparece antes de confirmar el server)
- [ ] Mensajes de éxito: breves y celebrativos

**M6.3 — Responsive**
- [ ] Dashboard probado en 375px (iPhone SE), 768px (iPad), 1280px (laptop)
- [ ] Sin scroll horizontal en ningún tamaño
- [ ] Botones ≥ 44px en móvil
- [ ] Sidebar como bottom-nav en móvil (o hamburguesa)
- [ ] Carta pública testeada con 3-4 personas no técnicas escaneando QR real

**M6.4 — SEO básico**
- [ ] `<title>` dinámico por subdominio: "Carta de [Nombre Local] — MyLocal"
- [ ] `<meta description>` con descripción del local
- [ ] Schema.org `Restaurant` + `Menu` en la carta pública
- [ ] `sitemap.xml` con todos los locales activos
- [ ] `robots.txt` permite `/carta/*`, bloquea `/dashboard/*`, `/acide/*`

**Gate M6:** 3 personas no técnicas hacen el onboarding completo en < 10 minutos sin ayuda. NPS ≥ 8/10.

---

## 5. Orden de ejecución recomendado

```
Semana 1: M1 (routing + dashboard layout) + M3 (subdominios backend)
Semana 2: M2 (registro + onboarding)
Semana 3: M5 (despliegue Hostinger + Cloudflare)
Semana 4: M4 (Stripe sandbox) + M6.1 (textos landing)
Semana 5: M6 completo (UX, responsive, SEO)
→ MVP listo para primer cliente real
```

---

## 6. Lo que hereda el MVP del trabajo anterior

Estos bloques existen y funcionan. El MVP los usa directamente:

### Del dashboard legacy (`spa/src/`)
- Gestión de carta: categorías, productos, precios, fotos, alérgenos
- Importación OCR (flujo `ocr_import_carta`)
- Gestión de sala: zonas, mesas, QR tokens
- Carta pública (BrowserRouter, rutas `/carta/:zona/:mesa`)
- GeneradorQR y LocalQrPoster (client-side, sin tokens en red)

Estrategia de migración: **copiar componente por componente** de `spa/src/` a `templates/hosteleria/src/pages/dashboard/`, reemplazando las llamadas legacy por `useSynaxisClient()` del SDK donde sea necesario.

### De las CAPABILITIES PHP
Todos los handlers existen. El MVP llama exactamente a las mismas acciones que ya están en `ALLOWED_ACTIONS`. No hay que tocar el backend PHP excepto para añadir `register_local`, `validate_slug` y el sistema de subdominios.

### Del sistema de autenticación
AUTH_LOCK está blindado. El login funciona. El MVP añade solo el registro de nuevos usuarios en `LoginRegister.php`, sin tocar los archivos de la capability existente.

---

## 7. Lo que NO se hace en el MVP

Para mantener el foco:

- No se construye el agente IA para hosteleros (OpenClaude/OpenClaw) — existe pero no se activa por defecto
- No se implementa TPV completo (pedidos en mesa, cobro en mesa) — la carta es solo de consulta
- No se añaden traducciones automáticas
- No se hace el blog de recetas en producción (existe pero es secundario)
- No se implementa multi-local por usuario (un usuario = un local en MVP)
- No se hace verificación de email en registro
- No se conectan notificaciones push (WhatsApp, email transaccional) — pueden esperar
- No se implementa el sistema de roles de equipo (editor, camarero, cocina)

---

## 8. Dependencias externas que hay que tener antes del lanzamiento

| Dependencia | Para qué | Estado |
|------------|----------|--------|
| Stripe account + API keys live | Cobros | ❌ Pendiente |
| Dominio `mylocal.es` en Cloudflare | Subdominios | ❌ Pendiente |
| Hosting Hostinger con PHP ≥ 8.2 | Despliegue | ❌ Pendiente |
| API key Gemini | OCR avanzado | ❌ Pendiente (sin ella el OCR usa solo Tesseract/local) |
| Servidor `ai.miaplic.com` activo | Gemma 4 vision | ❌ Pendiente (funciona con Gemini como fallback) |
| Datos fiscales reales de MyLocal Technologies | Páginas legales | ❌ Pendiente |
| Fotografías/capturas del producto real | Landing | ❌ Pendiente |

---

## 9. Historial de iteraciones (registro técnico)

### Olas 0–L del framework (2026-01 a 2026-05)

Framework completo. 208 tests PASS. Ver `claude/planes/estructura.md` para el detalle completo de cada ola.

Acumulado de tests:
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

### Iteración 2026-05-04: auth bearer-only

Bearer + sessionStorage. Sin cookies. Sin CSRF. Documentado en `claude/AUTH_LOCK.md`.

### Iteración 2026-05-05: login CAPABILITIES/LOGIN + BrowserRouter

LOGIN extraído a capability blindada (8 archivos PHP, 64 assertions). HashRouter → BrowserRouter. URLs limpias (`/dashboard`, `/carta/:zona/:mesa`).

### Iteración 2026-05-06: OCR multi-engine

Cascada `Tesseract → Gemma 4 vision (ai.miaplic.com) → Gemini cloud`. Acción unificada `ocr_import_carta`. 9/9 platos extraídos en pruebas reales.

### Iteración 2026-05-07: UX review OCR

Botones de acción en header del paso de revisión (siempre visibles sin scroll). "Importar carta" → "Guardar".

### Iteración 2026-05-15: integración template Google AI Studio

Template de hostelería traído de Google AI Studio integrado en `templates/hosteleria/` con el SDK. Errores y soluciones documentados en `claude/docs/integracion_con_google_ai_studio.md`. Skills creadas: `.claude/commands/integrar-template.md` + `claude/docs/gemini_skill_integracion_templates.md`.

Lecciones clave:
- `framer-motion` siempre como dependencia directa cuando se usa `motion/react`
- `public/seed/bootstrap.json` con `{}` para evitar SyntaxError en SynaxisProvider
- `.vscode/settings.json` → `typescript.tsdk` para que IDE y `tsc` usen el mismo TypeScript

---

## 10. Checklist de cierre del MVP

```
[ ] M1 — Routing SPA completo (landing / dashboard / carta)
[ ] M1 — Guard de autenticación funcional
[ ] M1 — Dashboard layout con sidebar y navegación
[ ] M1 — Carta page migrada del legacy
[ ] M1 — Mesas page migrada del legacy
[ ] M1 — Config page (logo, nombre, tema)
[ ] M2 — Backend: validate_slug + register_local
[ ] M2 — RegisterPage con validación de slug en vivo
[ ] M2 — OnboardingBanner con checklist
[ ] M3 — SubdomainManager en router.php y index.php
[ ] M3 — Seed dinámico por subdominio
[ ] M3 — Test de aislamiento multi-tenancy
[ ] M4 — StripeAdapter (checkout, webhook, status)
[ ] M4 — FacturacionPage con plan y facturas
[ ] M4 — Límites del plan Demo (20 platos, 5 mesas)
[ ] M5 — Cloudflare: wildcard DNS + SSL Full strict
[ ] M5 — Hostinger: release/ subido, PHP ≥ 8.2, cron
[ ] M5 — Health check verde en producción
[ ] M6 — Textos reales en landing (precios, H1, CTA)
[ ] M6 — Screenshots/video del producto en landing
[ ] M6 — Dashboard UX auditado vs competidores
[ ] M6 — Responsive: 375px, 768px, 1280px
[ ] M6 — Carta pública testeada con QR real en móvil
[ ] M6 — SEO básico: title, description, schema.org
[ ] M7 — Ola Reputación y Micro-contenido (Nuevo)
[ ] M7 — Backend: ReviewModel + TimelineModel (AxiDB)
[ ] M7 — Dashboard: Sección "Publicar" (Foto + Título)
[ ] M7 — Landing Pública: Feed de Timeline + Sección Reseñas
[ ] M7 — Generación automática de Avisos Legales al registrarse
[ ] --- LANZAMIENTO ---
[ ] Datos fiscales reales en páginas legales
[ ] Primer cliente registrado de forma autónoma
[ ] Primer pago Stripe recibido
