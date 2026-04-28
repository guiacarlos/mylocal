# Finalidad del proyecto — Socolá / SynaxisCore

> Documento rector. Quién somos, qué resolvemos, cómo lo abordamos. Útil como primera lectura para nuevos colaboradores y como ficha de auditoría técnica.

## Nombre del proyecto

**Socolá** (producto) + **SynaxisCore** (plataforma técnica).

- **Socolá** es la aplicación real: web pública (carta, reservas, contacto), checkout, TPV, mesas por QR, agente maître IA y academia.
- **SynaxisCore** es la "base de datos documental en el navegador" que sustenta Socolá — pensada para ser reutilizable como librería por otras apps que necesiten el mismo modelo (local-first con sync opcional).

## 🎯 Objetivo principal

Desarrollar una aplicación **segura, escalable y mantenible** para la gestión de un café/restaurante (catálogo, pedidos, TPV, QR de mesa, asistente IA, pagos con Revolut), manteniendo la información protegida, operando offline-first cuando es posible y reduciendo a lo imprescindible la dependencia del servidor.

## 🧩 Problema que resuelve

### Problemas operativos del negocio (el "qué")

- **Carta online actualizable** sin redesplegar código.
- **TPV** compartido entre varios dispositivos (caja, camareros, cocina) con merge race-safe.
- **Pedidos por QR de mesa** que llegan a la cola de cocina al instante.
- **Agente maître IA** que recomienda de la carta, filtra alérgenos y aprende de preguntas reales.
- **Pagos Revolut** con webhook real, sandbox/live, trazabilidad completa.
- **Reservas** y gestión de academia.

### Problemas técnicos del sector (el "por qué este diseño")

Muchos proyectos web carecen simultáneamente de:

1. **Configuración segura en servidor** — `.htaccess` débiles, archivos `.env` expuestos, headers inexistentes.
2. **Gestión robusta de usuarios** — passwords con MD5/SHA1, sesiones sin `httponly`, sin CSRF, sin rate limit.
3. **Persistencia de datos bien estructurada** — SQL acoplado al backend, sin esquema documentado, sin versionado.
4. **Escalabilidad** — toda operación requiere servidor, incluso leer el catálogo.
5. **Experiencia offline** — cualquier corte de red rompe la app.

**Este proyecto** ataca las 5 simultáneamente con un diseño **local-first** (los datos viven primero en el navegador) + **servidor adelgazado** (solo lo imprescindible: auth, pagos, webhooks, coordinación multi-dispositivo, IA). Más detalle en [ACIDE.md](ACIDE.md).

## 🚀 Qué se quiere conseguir

| Valor | Cómo se materializa |
| :-- | :-- |
| **Seguridad desde el diseño** (security by design) | Ver [SECURITY.md](SECURITY.md) §1-17. Headers, CORS, CSRF, Argon2id, rate limit, upload endurecido, todo vertebrado desde el arranque, no parcheado. |
| **Sistema de usuarios robusto** | Argon2id, httponly + SameSite=Strict, fingerprint de UA, rolling TTL, CLI para bootstrap, política de contraseñas. Ver [USERS.md](USERS.md). |
| **Persistencia de datos fiable** | Modelo documental JSON: cliente en IndexedDB (16 colecciones tipadas), server en `server/data/<col>/<id>.json` con `flock` + versionado. Schema en [DATA_MODEL.md](DATA_MODEL.md). |
| **Código mantenible y documentado** | TypeScript estricto en cliente, PHP 8.2 tipado en server, 8 documentos markdown actualizados en el mismo repo, [CLAUDE.md](../CLAUDE.md) explica las reglas de proyecto. |
| **Preparación para escalabilidad** | Contrato `{action, data} → {success, data, error}` invariante; scope `local`/`server`/`hybrid` permite mover carga; oplog listo para sync (Fase 3); compatible con cualquier hosting estático. |

## 🏛️ Arquitectura general

### Diagrama

```
Navegador (client)
┌─────────────────────────────────────────────────────────────┐
│  React SPA (Vite + TS)  →  React Router  →  pages/          │
│           │                                                  │
│           ▼                                                  │
│  services/ (carta, maitre, payments, qr, auth)              │
│           │                                                  │
│           ▼                                                  │
│  ┌────────────────────────────────────────┐                 │
│  │ SynaxisClient                          │                 │
│  │ decide transporte por scope de acción  │                 │
│  └─────┬──────────────────────────┬───────┘                 │
│        ▼                          ▼                         │
│   SynaxisCore                httpRequest                    │
│   (IndexedDB)                (fetch)                        │
└─────────────────────────────────────────────────────────────┘
                                     │
                                     │ POST /acide/*
                                     │ cookie socola_session (httponly)
                                     │ header X-CSRF-Token
                                     ▼
Server (PHP 8.2 thin)
┌─────────────────────────────────────────────────────────────┐
│ .htaccess  → headers, deny sensitivos, entry único          │
│    │                                                         │
│    ▼                                                         │
│ index.php  → CORS whitelist, CSRF, Auth gate, Rate limit    │
│    │                                                         │
│    ▼                                                         │
│ handlers/ (auth, ai, payments, qr, sync, upload, reservas)  │
│    │                                                         │
│    ▼                                                         │
│ data/<col>/<id>.json   config/*.json                         │
└─────────────────────────────────────────────────────────────┘
                                     │
                                     │ HTTPS outbound
                                     ▼
Servicios externos: Google Gemini · Revolut Merchant API
```

### Piezas clave

1. **SPA React + TypeScript + Vite** ([src/](../src/)): UI 100% en navegador.
2. **SynaxisCore** ([src/synaxis/](../src/synaxis/)): BD documental en IndexedDB con API tipo Mongo. Contrato idéntico al antiguo dispatcher ACIDE.
3. **SynaxisClient** ([src/synaxis/SynaxisClient.ts](../src/synaxis/SynaxisClient.ts)): fachada que decide **dónde** resolver cada acción según su `scope`.
4. **Server thin PHP** ([server/](../server/)): solo lo que el navegador no puede (auth real, webhooks, proxy IA, sync, upload).
5. **Documentación viva** ([docs/](.)): 8 documentos que cubren arquitectura, modelo de datos, seguridad, flujos de negocio y operativa.

### Principios arquitectónicos

1. **Local-first**: el cliente funciona sin red para todo lo local. La red es una mejora, no un requisito.
2. **Single entry point**: toda comunicación es `POST /acide/*` con `{action, data}`. No hay endpoints REST dispersos.
3. **Scope explícito**: cada acción declara `local`/`server`/`hybrid` en [`actions.ts`](../src/synaxis/actions.ts). No hay ambigüedad.
4. **Separación cliente/servidor por necesidad**, no por convención: algo vive en el server **solo** si (a) requiere secreto, (b) requiere webhook, (c) requiere coordinación multi-dispositivo, (d) requiere validación no-repudiable (pagos).
5. **Documentar el "por qué"** en cabeceras de archivo. Los "qué" los explica el código.

## 📦 Dependencias externas

| Servicio | Para qué | Dónde se integra |
| :-- | :-- | :-- |
| Google Gemini | Chat del agente Maître, academia | [`server/handlers/ai.php`](../server/handlers/ai.php), key en `config/gemini.json` |
| Revolut Merchant | Pagos con tarjeta | [`server/handlers/payments.php`](../server/handlers/payments.php), key en `config/revolut.json` |
| (Opcional) SMTP / SendGrid | Recuperación de contraseña (pendiente) | — |

Sin dependencias de base de datos relacional. Sin dependencias de Redis/memcached. Sin cola de mensajes.

## 🛠️ Plan de implementación

### Fase 1 — Auditoría (COMPLETADA)

- ✅ Revisión de seguridad (ver [SECURITY.md](SECURITY.md)).
- ✅ Revisión de `.htaccess` (endurecido: deny sensitivos, headers, CSP, HTTPS redirect opcional).
- ✅ Validación de persistencia (flock + versionado + sanitización de ids).
- ✅ Análisis de gestión de usuarios (Argon2id, CSRF, rate limit, rolling session).

### Fase 2 — Refuerzo de seguridad (COMPLETADA)

- ✅ Endurecimiento de `.htaccess` (dotfiles, backups, config, handlers, bin).
- ✅ Protección de endpoints (allowlist de acciones, rol por acción).
- ✅ Mejora de validaciones (`s_id`, `s_email`, `s_str`, whitelist MIME, extensión cruzada).
- ✅ Headers de seguridad (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, CSP).
- ✅ CSRF double-submit, rate limit por IP+scope, httponly + SameSite=Strict, fingerprint UA.

### Fase 3 — Mejora de arquitectura (EN CURSO)

- ✅ Separación de responsabilidades (lib.php, handlers/, config/).
- ✅ Capa DAO minimalista (`data_put/get/delete/all` con flock).
- ⏳ Refactorización de autenticación → completada en server; UI admin de gestión de usuarios pendiente.
- ⏳ Sync bidireccional cliente ↔ server (oplog ya existe en cliente, endpoint `synaxis_sync` esqueleto).

### Fase 4 — Documentación (COMPLETADA en auditoría actual)

- ✅ [CLAUDE.md](../CLAUDE.md) actualizado con reglas de proyecto.
- ✅ [ACIDE.md](ACIDE.md), [DATA_MODEL.md](DATA_MODEL.md), [SECURITY.md](SECURITY.md), [USERS.md](USERS.md), [AGENTS.md](AGENTS.md), [PAYMENTS.md](PAYMENTS.md), [QR.md](QR.md), [SECRETS.md](SECRETS.md), [PROJECT.md](PROJECT.md), [ROADMAP.md](ROADMAP.md).
- ⏳ Guía de mantenimiento (pendiente): runbook de incidentes, cómo rotar claves, cómo restaurar backups.

### Fase 5 — Escalabilidad (PENDIENTE)

- Preparación para carga: medir, no adivinar. Benchmark con `wrk` / `k6` cuando haya tráfico real.
- Optimización de consultas: índices secundarios en SynaxisCore si `list()` tiene > 10k docs.
- Migración potencial a servicios externos: CDN estático (Cloudflare Pages), object storage para media, Postgres gestionado si el JSON-file deja de escalar.

## 🔐 Reglas de seguridad (resumen)

Detalle completo en [SECURITY.md](SECURITY.md). Lo mínimo:

- `.htaccess`: listado desactivado, dotfiles denegados, config/handlers/data bloqueados, extensiones sensibles rechazadas, headers de seguridad estrictos, CSP `default-src 'none'`.
- Cookies de sesión: `httponly` + `SameSite=Strict` + `Secure` (HTTPS).
- CSRF: double-submit cookie con `hash_equals` en constant-time.
- Passwords: Argon2id, mínimo 10 chars + 3 clases, `password_needs_rehash` automático.
- Rate limit: 5/min login, 30-120/min IA, 20-120/min pagos, 60/min sync.
- Upload: whitelist MIME sin SVG, nombre reescrito a hash, extensión validada contra MIME.
- Errores: `display_errors=0`, respuestas genéricas, log interno.
- HTTPS: redirect 301 + HSTS (activar en deploy).

## 🏁 Cómo arrancar

```bash
# Cliente
cd socola
npm install
npm run dev     # http://localhost:5173

# Server (en otra terminal)
cd socola/server
cp config/auth.json.example config/auth.json     && edit
cp config/gemini.json.example config/gemini.json && edit
cp config/revolut.json.example config/revolut.json && edit
cp config/cors.json.example config/cors.json     && edit

# Primer admin
php bin/create-admin.php

# Servir (Apache + mod_php, o cualquier otro)
# (configurar DocumentRoot según hosting)
```

En dev, el `vite.config.ts` proxea `/acide/*` al server (`SOCOLA_API` env var, default `http://localhost:8090`).

## 🧪 Pruebas (pendientes)

Aún no hay runner de tests. Cuando se añada, prioridad:

1. `src/synaxis/*` — test de humo: CRUD, query operators, versioning.
2. Handlers PHP — PHPUnit básico para `auth`, `payments`, `qr`.
3. E2E Playwright: login, crear producto, generar QR, recibir pedido QR, pagar.

## 📄 Licencia y autoría

(A definir por el propietario del proyecto). La documentación y código están en este repo bajo tu control.
