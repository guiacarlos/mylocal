# LEGAL — Sistema de políticas en dos capas

MyLocal tiene dos conjuntos de documentos legales completamente separados. Confundirlos es un error grave: mezclan responsables, datos fiscales y contextos distintos.

---

## Las dos capas

### Capa 1 — Documentos de la empresa (GestasAI / MyLocal)

**¿Cuándo se muestran?** Cuando el visitante está en `mylocal.es` (la landing corporativa).

**¿Quién es el responsable?** GESTASAI TECNOLOGY SL, CIF E23950967.

**¿Qué cubren?** La relación entre MyLocal y los hosteleros que contratan el servicio:
- Condiciones de uso del SaaS
- Privacidad de los datos de los hosteleros como clientes de GestasAI
- Política de reembolsos y cancelación de suscripciones
- Canal de denuncias (Ley 2/2023)

**Archivos:**
```
CAPABILITIES/LEGAL/company/
  aviso.md           → Aviso legal + condiciones de contratación SaaS
  privacidad.md      → Privacidad de hosteleros como clientes de GestasAI
  cookies.md         → Cookies de mylocal.es (SPA React + SynaxisCore)
  reembolsos.md      → Política de cancelación y reembolsos de suscripciones
  canal-denuncias.md → Canal de denuncias Ley 2/2023
```

**Clase PHP:** `CAPABILITIES/LEGAL/CompanyLegal.php`
**Teléfono de contacto:** +34 611 677 577

---

### Capa 2 — Documentos del hostelero (por local)

**¿Cuándo se muestran?** Cuando el visitante está en `<slug>.mylocal.es` (la carta digital del restaurante).

**¿Quién es el responsable?** El hostelero (bar, restaurante, cafetería...) que ha contratado MyLocal. Sus datos fiscales, no los de GestasAI.

**¿Qué cubren?** La relación entre el restaurante y sus clientes (comensales que escanean el QR):
- Privacidad de los datos de los clientes del restaurante (reseñas, reservas)
- Aviso legal del establecimiento (LSSICE)
- Política de cookies de la carta digital

**Almacenamiento:** colección AxiDB `local_legales`, con IDs `{localId}_{doc}`.

**Clase PHP:** `CAPABILITIES/LEGAL/LegalGenerator.php`

**Documentos generados:** `privacidad`, `aviso`, `cookies` (3 documentos por local).

---

## Flujo de vida de los documentos del hostelero

### 1. Alta inicial (registro)

`LoginRegister::registerLocal()` → al crear el local, llama automáticamente a:

```php
LegalGenerator::generateForLocal($localId, $nombre, $email, $slug, $direccion, $telefono)
```

El hostelero tiene sus 3 documentos desde el primer minuto.

### 2. Actualización de datos

`local.php update_local` → cuando el hostelero cambia nombre, dirección, teléfono o email desde **Ajustes**, los documentos se regeneran automáticamente.

Campos que disparan regeneración: `nombre`, `direccion`, `telefono`, `email`.

### 3. Regeneración manual

Acción autenticada `regenerate_legales` (requiere rol admin/editor):

```json
POST /acide/index.php
{ "action": "regenerate_legales", "data": { "local_id": "l_abc123" } }
```

### 4. Plantillas

Las plantillas viven en `LegalGenerator::tplPrivacidad()`, `tplAviso()`, `tplCookies()`.

Variables disponibles:

| Variable | Valor |
|---|---|
| `{{nombre}}` | Nombre del local |
| `{{email}}` | Email de contacto del hostelero |
| `{{slug}}` | Slug del local (ej. `la-cocina-de-ana`) |
| `{{direccion}}` | Dirección formateada (string) |
| `{{telefono}}` | Teléfono del local |
| `{{fecha}}` | Fecha de generación (`dd/mm/yyyy`) |
| `{{anio}}` | Año actual |

Si algún campo está vacío, la variable `{{telefono}}` cae back a `{{email}}`.

---

## Resolución de contexto en el frontend

`LegalPage.tsx` determina qué capa mostrar usando **el seed del subdominio** como fuente de verdad:

```
Visitante en mylocal.es/legal/privacidad
  → seed devuelve { local_id: 'mylocal' }
  → get_legal({ local_id: 'mylocal', doc: 'privacidad' })
  → CompanyLegal::get('privacidad')
  → ✅ Documentos de GestasAI

Visitante en lacocinadeana.mylocal.es/legal/privacidad
  → seed devuelve { local_id: 'lacocinadeana' }  ← slug, no ID interno
  → get_legal({ local_id: 'lacocinadeana', doc: 'privacidad' })
  → legales_resolve_local_id('lacocinadeana') → 'l_abc123'
  → LegalGenerator::get('l_abc123', 'privacidad')
  → ✅ Documentos del hostelero
```

**Por qué seed primero (no sessionStorage):**
Si se usara sessionStorage primero, un hostelero logueado visitando `mylocal.es/legal` vería sus propios documentos en lugar de los de la empresa. El subdominio define el contexto, no la sesión.

---

## Responsabilidades legales por capa

| Aspecto | mylocal.es (GestasAI) | slug.mylocal.es (Hostelero) |
|---|---|---|
| Responsable RGPD | GESTASAI TECNOLOGY SL | El hostelero |
| Datos tratados | Datos de hosteleros clientes | Datos de comensales |
| Encargado tratamiento | — | GESTASAI TECNOLOGY SL (art. 28 RGPD) |
| Teléfono contacto | +34 611 677 577 | El del hostelero |
| DPD / Canal denuncias | Obligatorio (Ley 2/2023) | No aplica (menos de 250 empleados) |

---

## Archivos relevantes

| Archivo | Responsabilidad |
|---|---|
| `CAPABILITIES/LEGAL/CompanyLegal.php` | Sirve docs de empresa desde `/company/*.md` |
| `CAPABILITIES/LEGAL/LegalGenerator.php` | Genera y persiste docs del hostelero en AxiDB |
| `CAPABILITIES/LEGAL/company/*.md` | Fuente de verdad de los docs de empresa |
| `spa/server/handlers/legales.php` | Handler: routing por local_id + resolución slug→ID |
| `templates/hosteleria/src/pages/LegalPage.tsx` | UI: renderiza Markdown, detecta contexto por seed |
| `CAPABILITIES/LOGIN/LoginRegister.php` | Genera legales en el momento del registro |
| `spa/server/handlers/local.php` | Regenera legales tras update_local |

---

## Añadir un nuevo documento al hostelero

1. Añadir el slug a `LegalGenerator::DOCS` (ej. `'terminos'`)
2. Añadir el método `tplTerminos()` con la plantilla Markdown
3. Añadirlo al `match` de `template()` y al `titulo()`
4. Añadirlo a `LOCAL_DOCS` en `LegalPage.tsx`
5. Llamar a `regenerate_legales` para los locales existentes

## Añadir un nuevo documento de empresa

1. Crear `CAPABILITIES/LEGAL/company/<slug>.md`
2. Añadir el slug y título a `CompanyLegal::DOCS`
3. Añadirlo a `COMPANY_DOCS` en `LegalPage.tsx`

---

## Qué NO hacer

- No añadir CIF, dirección o teléfono de GestasAI en los templates del hostelero — son datos del hostelero.
- No usar los documentos de empresa como base para los del hostelero — son relaciones jurídicas distintas.
- No cachear los documentos del hostelero en el cliente más de 1h — se regeneran automáticamente y el usuario debe ver la versión actual.
- No pasar `local_id` vacío si se quiere el documento del hostelero — el servidor interpretaría eso como documentos de empresa.
