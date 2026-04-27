# Migracion Socola → AxiDB (Caso C, Fase 5)

**Estado**: Fase 5 cerrada con coexistencia funcional. El **flip atomico**
del gateway lo aplica tu (ver §3 de este documento) cuando hayas validado
los gates §8 del plan en staging.

**Filosofia**: Socola producion **no se rompe** durante la migracion.
AxiDB y ACIDE conviven en el mismo proceso PHP, comparten el mismo
storage, y ambos endpoints (`/acide/index.php` y `/axidb/api/axi.php`)
sirven las mismas acciones — verificado por `parity_test.php`.

---

## 1. Estado actual (post-Fase 5)

```
┌─────────────────────────────────────────────────────────────┐
│  Socola (Apache + PHP)                                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  /acide/index.php  ──>  CORE/index.php  ──>  ACIDE legacy   │
│                                                             │
│  /axidb/api/axi.php ──>  Axi\Engine\Axi  ──┬─> Op model     │
│                                            │   (CRUD,       │
│                                            │    AxiSQL,     │
│                                            │    Vault,      │
│                                            │    Backup,     │
│                                            │    LegacyAct.) │
│                                            │                │
│                                            └─> ACIDE legacy │
│                                                (delegacion  │
│                                                 transparente│
│                                                 si llega    │
│                                                 {action:..})│
│                                                             │
│  STORAGE/    ←  ambos endpoints leen/escriben aqui          │
│  CAPABILITIES/  ←  cargadas tal cual por ambos              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Verificacion** ([axidb/tests/parity_test.php](../../tests/parity_test.php)):
las acciones de negocio criticas (`list_products`, `get_mesa_settings`,
`get_payment_settings`) devuelven shape **identica** byte-a-byte en
ambos endpoints. La SPA de Socola, el TPV, el QR, etc. siguen
funcionando contra `/acide/` sin cambios.

---

## 2. Que se entrega ya en Fase 5

| Asset | Donde | Que hace |
| :-- | :-- | :-- |
| Test paridad | `axidb/tests/parity_test.php` | Verifica que las acciones legacy criticas dan misma respuesta en `/acide/` y `/axidb/api/`. |
| Op `legacy.action` | `axidb/engine/Op/System/LegacyAction.php` | Wrapper formal del action ACIDE para clientes Op model. |
| Adapter de release | `axidb/migration/release_adapter.php` | Tras `build_site`, post-procesa `release/` para que el SPA llame a `/axidb/api/`. Idempotente. |
| Patch htaccess | `axidb/migration/htaccess.patch` | Bloque de cambios propuesto para el `.htaccess`. **NO se aplica automaticamente**. |
| Empaquetador | `axidb/migration/build-axidb-zip.{sh,ps1}` | Genera `axidb-vX.Y.zip` distribuible. |
| Plan migration | este documento | El paso a paso del swap atomico cuando estes listo. |

---

## 3. El swap atomico (cuando lo apliques tu)

### 3.1 Pre-requisitos

Antes de tocar nada en producion:

- [ ] Pasa los **gates §8 del plan** en staging (Socola corriendo en pie).
- [ ] Periodo de coexistencia >= 7 dias en staging sin errores reportados.
- [ ] Backup completo del FS:
      `tar czf socola-backup-$(date +%F).tgz STORAGE/ CORE/ .htaccess`
- [ ] Snapshot AxiDB:
      `axi backup create pre-flip-$(date +%F)`
- [ ] Verifica el rollback: prueba en staging que copiar `.htaccess.bak`
      vuelta a `/acide/` directo funciona.
- [ ] Avisa a usuarios (TPV/camareros) de ventana de mantenimiento de 5 min.

### 3.2 Aplicar el patch del htaccess

**Recomendado**: usa el runbook automatizado.

```bash
# 1. Dry-run: ve exactamente lo que va a hacer sin tocar nada
bash axidb/migration/flip-runbook.sh

# 2. Aplica de verdad (cronometrado, snapshot incluido)
bash axidb/migration/flip-runbook.sh --apply

# 3. (opcional) si la raiz del proyecto no esta donde esta el script
bash axidb/migration/flip-runbook.sh --apply --dir /var/www/socola
```

El runbook hace, en orden, y cronometra cada paso:

1. `axi backup create pre-flip-<fecha>` — snapshot pre-vuelo.
2. `cp .htaccess .htaccess.bak.<fecha>` — copia de seguridad.
3. Inserta el bloque AxiDB tras la regla `^acide/`. Idempotente: si ya
   esta presente, no duplica.
4. `curl` a `/acide/index.php` y `/axidb/api/axi.php` para confirmar que
   ambos responden.
5. Imprime tiempo total (SLO: <300 s).

**Manual** (si prefieres aplicar a mano sin el runbook):

```bash
cp .htaccess .htaccess.bak.$(date +%F)
# Edita .htaccess y anade tras la linea con `^acide/(.*)$ CORE/$1 [END,QSA]`:
#     RewriteRule ^axidb/api/(.*)$ axidb/api/$1 [END,QSA]

curl -sI http://socola/acide/index.php | head -1
curl -sX POST http://socola/axidb/api/axi.php \
    -H 'Content-Type: application/json' -d '{"op":"ping"}' | grep success
```

Tras esto, **ambos endpoints funcionan en paralelo**. Ningun cliente se
rompe.

### 3.3 Migrar los clientes (al ritmo que quieras)

Mientras `/acide/` sigue activo, ve cambiando los clientes uno a uno:

- [ ] **Socola SPA** (dashboard React): cambiar URL del axios baseURL a
      `/axidb/api/axi.php`. Verificar en staging.
- [ ] **TPV** (`/sistema/tpv`): mismo cambio — el archivo `js/socola-carta.js`
      hace `fetch('/acide/index.php', ...)`. Cambiar a `/axidb/api/axi.php`.
- [ ] **Webhooks Revolut**: documentar que ahora `/axidb/api/axi.php`
      tambien acepta `process_external_order`, `revolut_webhook`. Cambiar
      la URL configurada en Revolut dashboard.
- [ ] **Generador estatico**: ejecutar `php axidb/migration/release_adapter.php`
      tras cada `build_site`. Anadir al pipeline del CI si lo hay.

### 3.4 Hard flip (opcional, en el futuro)

Despues de >= 1 mes sin que nadie llame a `/acide/`:

```bash
# Cambiar la regla original para que /acide/ tambien apunte a axidb:
#     RewriteRule ^acide/(.*)$ axidb/api/$1 [END,QSA]
#
# (Dejar la regla original comentada por si necesitas revivirla.)
```

A partir de aqui, todo el trafico va al motor nuevo. `CORE/` queda como
codigo de referencia historica pero no se ejecuta nunca.

### 3.5 Rollback (<5 min)

**Recomendado**: usa el script automatizado.

```bash
# Auto-detecta el ultimo .htaccess.bak.* y restaura
bash axidb/migration/rollback.sh

# O explicito con backup + snapshot a restaurar
bash axidb/migration/rollback.sh /ruta/.htaccess.bak.YYYY-MM-DD pre-flip-YYYY-MM-DD
```

El script:

1. Si no se pasa `.bak`, busca el mas reciente automaticamente.
2. `cp <bak> .htaccess` (Apache recoge el cambio sin restart).
3. `curl` a `/acide/` (debe responder) y `/axidb/api/` (debe dar 404).
4. Si se pasa snapshot: `axi backup restore <name>`.
5. Cronometra y avisa si supera el SLO de 5 min.

**Manual**:

```bash
cp .htaccess.bak.<fecha> .htaccess
# (apache recarga htaccess automaticamente; /acide/ vuelve a CORE/)
axi backup restore pre-flip-<fecha>   # solo si necesitas descartar cambios en STORAGE
```

### 3.6 Pre-vuelo y verificacion automatizada

Antes y despues del flip, ejecuta el suite de staging:

```bash
# Cubre gates §8 + 9 capacidades + paridad + rollback rehearsal en sandbox.
php -c axidb/tests/php.ini axidb/tests/socola_staging_test.php
# 36 checks, ~3 s. Cero failures = listo para flip.

# Paridad legacy/AxiDB byte-a-byte
php -c axidb/tests/php.ini axidb/tests/parity_test.php
# 14 checks. Cero failures = los dos endpoints son intercambiables.
```

---

## 4. Capacidades Socola — verificacion

Las 4 capabilities activas en `STORAGE/system/active_plugins.json`
funcionan **idempotente** en ambos endpoints. La carga sigue siendo
responsabilidad del ACIDE legacy (`loadCapacities()` en
`axidb/engine/ACIDE.php`):

| Capability | Engine | Activa | Probada en parity_test |
| :-- | :-- | :--: | :--: |
| STORE                | `StoreEngine.php`                | ✅ | ✅ (`list_products`) |
| QR                   | `QREngine.php`                   | ✅ | ✅ (`get_mesa_settings`) |
| AGENTE_RESTAURANTE   | `Agente_restauranteEngine.php`   | ✅ | (no en test) |
| RESTAURANT_ORGANIZER | `Restaurant_organizerEngine.php` | ✅ | (no en test) |
| ACADEMY              | `AcademyEngine.php`              | ❌ deshabilitada | — |
| GEMINI               | `GeminiEngine.php`               | ❌ | — |
| RESERVAS             | `ReservasEngine.php`             | ❌ | — |
| FSE                  | `FseEngine.php`                  | (siempre) | — |

Para extender el test: anadir mas pares al array `$actions` en
`parity_test.php`.

---

## 5. build_site (release/) tras la migracion

`build_site` (action de Socola que genera `release/` agnostico) sigue
funcionando intacto. Tras un build, ejecutar:

```bash
php axidb/migration/release_adapter.php release/
```

Esto:
1. Anade alias `/axidb/api/` al `release/.htaccess`.
2. Sustituye `'/acide/index.php'` por `'/axidb/api/axi.php'` en HTML
   canonicos y JS canonicos del release.
3. Copia `axidb/` al `release/axidb/` para que el zip sea autocontenido.

Idempotente: ejecutar varias veces no rompe.

Si quieres mantener el legacy en el release tambien (por seguridad
durante el periodo de coexistencia), no se borra `CORE/` — el adapter
solo anade `axidb/`. El release sigue teniendo ambos paths.

---

## 6. Empaquetar AxiDB para distribuir a otros proyectos

Cuando otra app quiera usar AxiDB sin Socola:

```bash
# Genera axidb-v1.0.zip en el directorio actual
bash axidb/migration/build-axidb-zip.sh v1.0
# o en Windows:
pwsh axidb/migration/build-axidb-zip.ps1 v1.0
```

El zip pesa ~1.4 MB e incluye motor + SDK + docs + ejemplos. Cualquier
hosting PHP basico lo acepta unzip+listo. La app destino solo hace
`require 'axidb/axi.php'`.

---

## 7. Limites conocidos de la migracion

- **`health_check` shape**: el Op `ping` de AxiDB y la action
  `health_check` legacy tienen estructura diferente (cada motor reporta
  su propio meta). Ambos devuelven `success:true` y son funcionalmente
  equivalentes, pero el shape `data` no es identico. Documentado en
  `parity_test.php` seccion [B] (excluido del set).
- **`get_media_formats`**: depende de auth/system precondiciones que en
  CLI test no se cumplen. Verificar manualmente en navegador con sesion
  activa.
- **Cookies**: ambos gateways setean `acide_session` con el mismo
  formato. La sesion creada via `/acide/` funciona en `/axidb/api/` y
  viceversa.

---

## 8. Checklist final (gates §8 + Fase 5)

Antes de declarar la migracion completa:

- [ ] Gates §8 del plan en staging: home, /carta, /checkout, /login,
      /dashboard, /sistema/tpv, /mesa/:slug, /academy/, build_site.
- [ ] `parity_test.php` verde sobre staging.
- [ ] Periodo de coexistencia >= 7 dias.
- [ ] Snapshot pre-flip creado (`axi backup create pre-flip`).
- [ ] Patch htaccess aplicado en producion (paso 3.2).
- [ ] Una semana mas sin errores con coexistencia.
- [ ] Migrar SPA/TPV/webhooks (paso 3.3).
- [ ] Otra semana sin trafico residual a `/acide/`.
- [ ] Hard flip (paso 3.4) — opcional, solo si quieres consolidar.
