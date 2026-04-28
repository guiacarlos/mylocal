# ROADMAP — plan de evolución

Estado a 2026-04-17 tras auditoría de seguridad / persistencia / usuarios. Las fases 1-2 y 4 están completadas; 3 en curso; 5 abierta.

## Convenciones

- ✅ **Done** — implementado y documentado.
- 🚧 **In-progress** — empezado, no completo.
- ⏳ **Next** — próximo sprint.
- 💭 **Later** — valorado pero sin prioridad definida.
- ⚠️ **Risk** — decisión abierta o posible problema técnico.

## Fase 1 — Auditoría ✅

- ✅ Mapa completo de flujos del ACIDE viejo ([SYNAXIS_MIGRATION.md](../../SYNAXIS_MIGRATION.md)).
- ✅ Clasificación por scope: local / server / hybrid ([src/synaxis/actions.ts](../src/synaxis/actions.ts)).
- ✅ Inventario de colecciones y esquemas ([DATA_MODEL.md](DATA_MODEL.md)).
- ✅ Inventario de secretos ([SECRETS.md](SECRETS.md)).

## Fase 2 — Refuerzo de seguridad ✅

- ✅ `.htaccess` endurecido (dotfiles, backups, config, handlers, data, bin).
- ✅ Headers: X-Frame-Options DENY, X-Content-Type-Options nosniff, Referrer-Policy, Permissions-Policy, CSP `default-src 'none'`.
- ✅ CORS con whitelist en `config/cors.json`.
- ✅ CSRF double-submit (cookie + `X-CSRF-Token`), `hash_equals` constant-time.
- ✅ Sesión httponly + SameSite=Strict + Secure (HTTPS) + fingerprint UA + rolling TTL.
- ✅ Argon2id con política de contraseña (≥10 chars, ≥3 clases, blacklist mínima).
- ✅ Rate limit por scope (`login`, `ai`, `payments`, `sync`, `register`).
- ✅ Upload endurecido (whitelist MIME sin SVG, nombre hash, extensión validada).
- ✅ Sanitización (`s_id`, `s_email`, `s_str`, `s_int`).
- ✅ CLI de bootstrap superadmin (`server/bin/create-admin.php`).

## Fase 3 — Mejora de arquitectura 🚧

### Sprint próximo (⏳)

- ⏳ **Auth UI en Dashboard**: gestión de usuarios y roles desde interfaz, no solo CLI. Endpoints `create_user`, `update_user`, `delete_user`, `list_users`, `update_role`. Gate `require_role(['superadmin','admin'])`.
- ⏳ **Recuperación de contraseña**: endpoints `password_reset_request` + `password_reset_confirm`, tokens one-shot con TTL 30 min, email con SMTP. Ver [USERS.md §10](USERS.md#10-recuperación-de-contraseña-pendiente).
- ⏳ **Componente `<RequireRole>`** en React para gatear rutas del Dashboard.
- ⏳ **Revalidación de sesión al arrancar** la SPA: llamar `get_current_user` antes del primer render; redirigir a `/login` si 401.
- ⏳ **Sync bidireccional**: implementar pull del server en `synaxis_sync` con log persistente (`server/data/_oplog/`). Last-write-wins por `_version` (ya presente en el esqueleto).
- ⏳ **Tests de humo**: Vitest para SynaxisCore + PHPUnit básico para handlers.

### Sprint siguiente

- 💭 Logs de auditoría estructurados en `audit_logs/YYYY-MM-DD.log` (logout ya existe; añadir login_success, login_fail, role_change, password_change).
- 💭 Bloqueo por captcha tras N fallos de login (no bloquear cuenta para evitar DoS).
- 💭 Rotación de `jwt_secret` con lista negra de sesiones activas anteriores.
- 💭 2FA TOTP para roles superadmin/admin.

### Pendientes de fase 3

- 🚧 `synaxis_sync` pull: hoy solo aplica oplog entrante, no devuelve cambios del server.
- 🚧 UI Maître en la carta pública: chat widget con vault + fallback Gemini.
- 🚧 Panel de edición del vault (`agente_restaurante/vault_carta`) en Dashboard.

## Fase 4 — Documentación ✅

Todos los documentos de [docs/](.) actualizados en esta auditoría:

- ✅ [PROJECT.md](PROJECT.md), [ROADMAP.md](ROADMAP.md)
- ✅ [SECURITY.md](SECURITY.md), [USERS.md](USERS.md), [SECRETS.md](SECRETS.md)
- ✅ [ACIDE.md](ACIDE.md), [DATA_MODEL.md](DATA_MODEL.md)
- ✅ [AGENTS.md](AGENTS.md), [PAYMENTS.md](PAYMENTS.md), [QR.md](QR.md)
- ✅ [CLAUDE.md](../CLAUDE.md) actualizado

**Pendientes** de documentación:

- ⏳ Runbook de operación: qué hacer si webhook Revolut cae, cómo restaurar backups, cómo rotar claves en caliente.
- 💭 Diagrama Mermaid oficial en `docs/` para el flujo de pago end-to-end.

## Fase 5 — Escalabilidad

Todo ⏳/💭 hasta tener tráfico real que medir.

### Cliente

- 💭 **Índices secundarios en SynaxisCore**: si `list('products')` crece >10k docs, añadir índices por `status`, `category` para filtros O(log n).
- 💭 **Workers**: mover `SynaxisCore` a un Web Worker para que queries pesadas no bloqueen el hilo de render.
- 💭 **SharedWorker** para compartir IndexedDB entre pestañas sin recargar.
- 💭 **Cifrado en reposo opcional** (AES-GCM con clave derivada de sesión) para colecciones con PII.

### Server

- 💭 **Cache opcode** (OPcache activado con timestamps=0 en prod).
- 💭 **Migración a Postgres** si JSON-files pasan de ~50k docs por colección — cuello de botella inevitable de filesystem.
- 💭 **Queue externa** (Redis BullMQ o SQS) para webhooks lentos, emails, generación de QRs batch.
- 💭 **CDN estático**: servir `dist/` desde Cloudflare Pages / S3 + Cloudflare; el server PHP solo responde a `/acide/*`.
- 💭 **Object storage** para `server/data/media/`: migrar a S3/R2 cuando los GB crezcan.

### Observabilidad

- ⏳ Structured logging (JSON lines) con `structlog`-like en PHP.
- ⏳ Métricas Prometheus / endpoint `/metrics` (requests/sec, p95 latency, rate-limit hits, auth fails).
- 💭 Traces OpenTelemetry end-to-end.

## Riesgos técnicos identificados ⚠️

### Multi-dispositivo / sync

**Riesgo**: dos camareros editan la misma mesa simultáneamente. El merge race-safe con `ext_*` sirve para QR↔TPV, pero TPV↔TPV con el mismo `_key` es **last-write-wins**.

**Mitigación**: diseñar colas explícitas (cada camarero tiene su "cart pendiente" antes de enviar a la mesa común). Decisión aplazada hasta ver tráfico.

### IndexedDB por navegador

**Riesgo**: el usuario borra el storage del navegador → pierde la comanda en curso.

**Mitigación**:
- Las comandas "en el aire" viven en `server/table_orders` (no IndexedDB), son server-side.
- El cliente solo cachea catálogo/tema/vault — re-sembrable desde `seed/bootstrap.json`.
- Riesgo residual bajo.

### API key Gemini en un solo proveedor

**Riesgo**: corte de Google → el chat Maître falla.

**Mitigación**: el flujo de tres capas (vault → catálogo → IA) ya tiene fallback sin IA. Si Gemini cae, el cliente sigue respondiendo con vault + match directo de productos. Añadir connector Groq / Ollama como fallback: está anticipado en el catálogo de acciones ([ACIDE.md](ACIDE.md)).

### Revolut webhook sin validación probada

**Riesgo**: el validador HMAC es estructuralmente correcto pero no verificado contra la firma real de Revolut v2025-12-04.

**Mitigación**: antes de `mode: "live"`, ejecutar checklist de [PAYMENTS.md §"Seguridad"](PAYMENTS.md). Probar con ngrok + sandbox. Un webhook aceptando firmas inválidas = órdenes "pagadas" sin cobro real.

### XSS residual en el frontend

**Riesgo**: React escapa por defecto, pero cualquier uso futuro de `dangerouslySetInnerHTML` rompe la garantía. Colecciones con markdown (vault, descripciones de productos, notas internas) son candidatos peligrosos.

**Mitigación**: prohibir `dangerouslySetInnerHTML` por convención del proyecto; si es necesario, usar `dompurify` con allowlist. Revisar en code review.

### Filesystem como DB

**Riesgo**: `data_put` usa `flock` pero si el FS es NFS / EFS / un hosting raro, el lock puede no ser atómico.

**Mitigación**: validar en deploy que el hosting soporta flock real (ext4, APFS, NTFS → sí; NFS antiguo → no). Alternativa: migración a SQLite con `journal_mode=WAL` (cambio mínimo en `lib.php`).

### Rate limit evadible por IP rotation

**Riesgo**: un atacante con botnet evita los 5/min de login.

**Mitigación**: complementar con captcha tras N fallos por email + bloqueo de cuenta suave. Ya identificado en Fase 3 siguiente sprint.

### Dependencia de `.htaccess`

**Riesgo**: si alguien despliega en nginx / Caddy / IIS, las reglas de Apache no aplican → filtrado de archivos sensibles perdido.

**Mitigación**: publicar equivalentes de las reglas para nginx (`location ~` con `return 403`) cuando sea necesario. Por ahora, documentado: Apache es el target soportado.

### Pérdida de seed de IA

**Riesgo**: el vault es auto-poblado por Gemini. Si alguien borra `agente_restaurante/vault_carta.json` en server, se pierde el aprendizaje acumulado.

**Mitigación**: backup periódico (cron + rsync) de `server/data/`. Añadir `server/bin/backup.sh` en Fase 3.

## Prioridades para las próximas 2 semanas

En orden de impacto:

1. **Recuperación de contraseña** (bloquea usabilidad real).
2. **Revalidación de sesión al arrancar SPA** + `<RequireRole>` (cierra agujero UX actual).
3. **Tests de humo** — mínimo para SynaxisCore y handlers críticos.
4. **Probar webhook Revolut con sandbox real** y validar firma antes de cualquier `mode: live`.
5. **UI Maître + editor de vault en Dashboard** — es lo que hace el producto diferencial.
6. **Backup script** (`server/bin/backup.sh`) + documentar en runbook.

## Pregunta abierta

Antes de escalar a producción real, una decisión arquitectónica queda pendiente:

> ¿Multi-tenant (un server, varios restaurantes) o single-tenant (un deploy por restaurante)?

El diseño actual soporta ambos (hay `tenantId` en el user, pero no se filtra aún). Si vamos multi-tenant habrá que:

- Scoping de `data_put/get` por tenant.
- Separación de `config/*.json` por tenant.
- Roles per-tenant (un mismo email puede ser admin en un tenant y cliente en otro).

La decisión afecta a Fase 3. Pausarla hasta tener el primer cliente real confirmado.
