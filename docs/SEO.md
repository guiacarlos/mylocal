# SEO — MyLocal (Ola M9.5)

Guía completa del sistema SEO estructural automático. Todo el contenido es dinámico: ningún valor está hardcodeado; procede íntegramente de los datos del local en AxiDB.

---

## Arquitectura en dos capas

### Capa 1 — Landing corporativa (`mylocal.es`)

Objetivo: posicionar MyLocal como producto SaaS.

| Componente | Archivo | Qué hace |
|---|---|---|
| `LandingSchema` | `templates/hosteleria/src/components/LandingSchema.tsx` | JSON-LD `@graph`: Organization + WebSite + WebPage + SoftwareApplication |
| `FAQSection` | `templates/hosteleria/src/components/FAQSection.tsx` | 7 FAQs con schema `FAQPage`, acordeón accesible |
| `useSeoMeta` | `templates/hosteleria/src/hooks/useSeoMeta.ts` | `<title>`, `<meta description>`, og:image, canonical |

### Capa 2 — Carta pública del hostelero (`<slug>.mylocal.es/carta`)

Objetivo: indexar el restaurante concreto con rich results en Google.

| Componente | Archivo | Qué hace |
|---|---|---|
| `SeoBuilder` | `CAPABILITIES/SEO/SeoBuilder.php` | Orquestador: construye `@graph` completo desde AxiDB |
| `SeoSchemas` | `CAPABILITIES/SEO/SeoSchemas.php` | Constructores de nodos: Menu, MenuItem, Review, Post, FAQ |
| `SeoCache` | `CAPABILITIES/SEO/SeoCache.php` | Caché 24h en colección `seo_cache` de AxiDB |
| `SeoEndpoints` | `CAPABILITIES/SEO/SeoEndpoints.php` | GET `/carta/sitemap.xml` y `/carta/llms.txt` |
| `handle_seo` | `spa/server/handlers/seo.php` | Acción `get_local_schema` (JSON-LD bajo demanda) |
| `CartaPublicaPage` | `templates/hosteleria/src/pages/CartaPublicaPage.tsx` | Inyecta schema servidor o fallback cliente |

---

## Schema.org generado por local

`SeoBuilder::buildFullPage()` genera un `@graph` con estos nodos:

```
@graph [
  Restaurant / LocalBusiness / FoodEstablishment
    ├─ address (PostalAddress)
    ├─ geo (GeoCoordinates) ← geocodificado via Nominatim automáticamente
    ├─ openingHoursSpecification[]
    ├─ aggregateRating
    ├─ servesCuisine[]      ← de tipo_cocina[] del local
    ├─ priceRange           ← de precio_medio del local (€ / €€ / €€€)
    ├─ telephone
    ├─ hasMap               ← de url_maps del local
    ├─ acceptsReservations
    ├─ sameAs[]
    └─ hasMenu
         └─ MenuSection[]
              └─ MenuItem[]
                   ├─ offers (Offer con price + EUR)
                   ├─ description
                   └─ suitableForDiet[] ← inferido de alergenos
  Review[] (máx 10 con comentario — límite de Google)
  SocialMediaPosting[] (posts con media_url)
]
```

### Campos del local necesarios para schema completo

Todos se rellenan desde **Ajustes del local** (`/dashboard/ajustes`):

| Campo AxiDB | UI | Uso en schema |
|---|---|---|
| `nombre` | Nombre del local | `Restaurant.name` |
| `descripcion` | Descripción corta | `Restaurant.description` |
| `imagen_hero` | Logo (subida) | `Restaurant.image` |
| `telefono` | Teléfono | `Restaurant.telephone` |
| `tipo_cocina[]` | Chips tipo cocina | `Restaurant.servesCuisine` |
| `precio_medio` | €/€€/€€€ | `Restaurant.priceRange` |
| `acepta_reservas` | Toggle reservas | `Restaurant.acceptsReservations` |
| `url_maps` | URL Google Maps | `Restaurant.hasMap` + `sameAs` |
| `direccion.calle/numero/cp/ciudad/provincia` | Dirección estructurada | `PostalAddress` |
| `lat` / `lng` | Auto (Nominatim) | `GeoCoordinates` |
| `horario[]` | 7 días × abre/cierra | `OpeningHoursSpecification[]` |

---

## Caché e invalidación

El schema se construye una vez y se cachea 24h en AxiDB (`seo_cache/<localId>`).

**Invalidación automática** — se llama `SeoBuilder::invalidateCache($localId)` tras cada escritura en:

| Handler | Operaciones que invalidan |
|---|---|
| `carta.php` | create/update/delete categoría, create/update/delete producto, importación en lote |
| `reviews.php` | create_review, delete_review |
| `timeline.php` | create_post, delete_post |
| `local.php` | update_local (cualquier campo) |

La próxima llamada a `get_local_schema` reconstruye el schema desde cero.

---

## Endpoints GET (sin sesión)

Servidos por `router.php` → `SeoEndpoints`:

### `/carta/sitemap.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://lacocinadeana.mylocal.es/carta</loc>
    <lastmod>2026-05-17</lastmod>   <!-- fecha del producto más reciente -->
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
  <!-- + entrada por cada post reciente con imagen -->
  <!-- + /legal/privacidad, /legal/aviso, /legal/cookies (priority 0.3) -->
</urlset>
```

### `/carta/llms.txt`

Formato estándar `llms.txt` para modelos de lenguaje (Perplexity, ChatGPT, etc.):

```
# La Cocina de Ana
> Bar de tapas tradicionales en el centro de Murcia.

## Información
Dirección: Calle Mayor 5, 30001 Murcia
Teléfono: +34 600 000 000
Horario: Lun 12:00–23:00, Mar 12:00–23:00, ...
Precio medio: €€
Tipo de cocina: Mediterránea, Tapas

## Carta
https://lacocinadeana.mylocal.es/carta — Carta completa con precios y alérgenos
42 platos en 6 categorías.
Categorías: Entrantes, Carnes, Pescados, Postres, Bebidas, Menú del día

## Reseñas
4.7 sobre 5 — 28 valoraciones verificadas.

## Últimas novedades
- Menú de temporada primavera (2026-04-01)

## Reservas
Acepta reservas. Contactar en +34 600 000 000.
```

---

## Imágenes — naming SEO (`MediaUploader`)

`CORE/MediaUploader.php` gestiona nombres y conversión de imágenes.

**Convención de naming:**
```
{slug-local}_{tipo}_{titulo-slug}_{YYYYMMDD}.webp
Ej: la-cocina-de-ana_hero_la-cocina-de-ana_20260517.webp
    la-cocina-de-ana_post_menu-temporada-primavera_20260517.webp
```

**Alt text automático** (prioridad):
1. `descripcion` del plato/post (primeros 100 chars)
2. `"{nombre} en {local}, {ciudad}"`
3. `"Imagen de {local}"`

**Procesado:** resize a 1920px máx, conversión a WebP (calidad 82), eliminación de EXIF.

---

## Geocodificación automática (Nominatim)

Cuando `update_local` recibe una dirección estructurada sin coordenadas, `local.php` llama a la API gratuita de OpenStreetMap Nominatim y almacena `lat`/`lng` en el local:

```
GET https://nominatim.openstreetmap.org/search
  ?q=Calle+Mayor+5%2C+30001+Murcia+Spain
  &format=json&limit=1
User-Agent: MyLocal/1.0 (infojuancarlosaguirre@gmail.com)
```

No requiere API key. Timeout: 5s. Si falla, `lat`/`lng` quedan vacíos y el schema omite `GeoCoordinates`.

---

## Acción API: `get_local_schema`

```json
POST /acide/index.php
{ "action": "get_local_schema", "data": { "local_id": "l_abc123" } }

→ { "success": true, "data": { "schema": "{\"@context\":\"https://schema.org\",\"@graph\":[...]}" } }
```

- **Pública** (no requiere sesión)
- Cacheable por el cliente (el schema cambia solo cuando se invalida)
- El cliente React lo inyecta como `<script type="application/ld+json">`
- Si falla, `CartaPublicaPage` hace fallback al schema cliente (`buildSchemaOrg`)

---

## Añadir un nuevo campo al schema

1. Añadir el campo en `AjustesPage.tsx` (UI) y en `update_local` (backend)
2. Leerlo en `SeoBuilder::buildRestaurant()` desde `$local['campo']`
3. Mapearlo al nodo Schema.org correspondiente
4. La invalidación de caché ya está cubierta (se invalida en cada `update_local`)
5. Actualizar este documento

---

## Validación manual

| Herramienta | URL | Qué valida |
|---|---|---|
| Rich Results Test | search.google.com/test/rich-results | Restaurant, Menu, Review, FAQ |
| Schema Validator | validator.schema.org | Estructura JSON-LD |
| llms.txt checker | llmstxt.org | Formato llms.txt |
| Sitemap validator | xml-sitemaps.com/validate-xml-sitemap | XML sitemap |
