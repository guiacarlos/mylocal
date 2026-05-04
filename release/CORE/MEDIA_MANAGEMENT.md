# Gestión de Media en Socolá / ACIDE

## Visión general

El sistema de media está dividido en dos capas:

| Capa | Qué hace | Archivos implicados |
|------|----------|---------------------|
| **Backend** | Subida, listado y borrado de archivos | `CORE/core/handlers/SystemHandler.php` |
| **API** | Dispatcher que expone las acciones | `CORE/core/ActionDispatcher.php` |
| **Panel /admin** | UI standalone para administración completa | `admin.html` + `js/admin.js` |
| **TPV injector** | Selector de imagen dentro del modal de producto React | `js/tpv-media-injector.js` |

---

## Estructura de carpetas de media

```
/MEDIA/                              ← librería principal (subidas nuevas)
    *.jpg, *.webp, *.png…            ← imágenes de producto subidas vía admin/injector
    videos/
        *.mp4, *.webm                ← vídeos del sitio
    academia/
        *.jpg, *.png…                ← imágenes de cursos/academia

/themes/socola/assets/productos/     ← catálogo de producto antiguo (legacy)
    *.webp                           ← imágenes de producto por slug
```

**Regla**: las subidas nuevas van siempre a `/MEDIA/` (raíz o subcarpeta temática).  
El directorio `themes/socola/assets/productos/` se incluye en el listado por compatibilidad con imágenes antiguas y se puede elegir como destino del panel `/admin`, pero las nuevas subidas desde el injector del TPV van a `/MEDIA/`.

---

## Acciones de API (endpoint único: `POST /acide/index.php`)

Todas las acciones requieren sesión autenticada (cookie `acide_session` o `Authorization: Bearer <token>`).  
Las acciones de escritura (`upload`, `delete_media`) requieren rol `admin`, `superadmin` o `administrador`.

### `upload` — Subir un archivo

**Request** — multipart/form-data:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `action` | string | `"upload"` |
| `file` | File | El archivo a subir |
| `folder` | string (opt.) | Destino: `""` (MEDIA raíz), `"videos"`, `"academia"` |
| `slug` | string (opt.) | Base del nombre de archivo generado (e.g. `"batido_temporada"`) |

**Respuesta** (un archivo):
```json
{
  "success": true,
  "id": "batido_temporada-123456",
  "url": "/media/batido_temporada-123456.webp",
  "filename": "batido_temporada-123456.webp",
  "folder": "",
  "ext": "webp",
  "size": 45312
}
```

**Lógica interna** (`SystemHandler::upload`):
1. Valida extensión contra la whitelist (`jpg`, `jpeg`, `png`, `webp`, `gif`, `mp4`, `webm`). SVG rechazado.
2. Genera un ID único: `<slug-sanitizado>-<últimos 6 dígitos del timestamp>`.
3. Mueve el archivo con `move_uploaded_file()` al directorio destino.
4. Devuelve la URL pública absoluta.

---

### `list_media` — Listar archivos de la librería

**Request** — JSON:
```json
{ "action": "list_media", "data": { "folder": "" } }
```

| `folder` | Qué devuelve |
|----------|-------------|
| `""` (vacío) | Todas las carpetas agrupadas |
| `"media"` | Solo `/MEDIA/` raíz |
| `"videos"` | Solo `/MEDIA/videos/` |
| `"academia"` | Solo `/MEDIA/academia/` |
| `"productos"` | Solo `/themes/socola/assets/productos/` (legacy) |

**Respuesta**:
```json
{
  "success": true,
  "data": [
    {
      "folder": "media",
      "id": "batido_temporada-123456",
      "name": "batido_temporada-123456.webp",
      "url": "/media/batido_temporada-123456.webp",
      "size": 45312,
      "ext": "webp",
      "modified": 1713654321
    }
  ],
  "formats": ["jpg","jpeg","png","webp","gif","mp4","webm"]
}
```

Ordenados por `modified` descendente (más recientes primero).

---

### `delete_media` — Borrar un archivo

**Request** — JSON:
```json
{ "action": "delete_media", "data": { "url": "/media/batido_temporada-123456.webp" } }
```

**Lógica de seguridad** (`SystemHandler::deleteMedia`):
1. Comprueba que la URL empieza por uno de los prefijos permitidos.
2. No permite subdirectorios en el nombre del archivo (`/`, `\`, `..`).
3. Verifica con `realpath()` que el archivo resuelto esté dentro del directorio permitido (protección anti path-traversal).
4. Llama a `unlink()`.

---

### `get_media_formats` — Formatos admitidos

```json
{ "action": "get_media_formats", "data": {} }
```

Devuelve `{ "image": [...], "video": [...] }` sin autenticación especial.  
Formatos fijos en `SystemHandler::allowedFormats()`. **SVG nunca se incluye** (riesgo XSS).

---

## Panel admin standalone `/admin`

Acceso: rol `admin`, `superadmin`, `administrador`, `maestro` o `editor`.  
El gate está en `gateway.php` (líneas 47-62) — el HTML nunca se entrega a usuarios sin rol válido.

### Archivos
- `admin.html` — estructura HTML (tabs Productos / Media, modales editor y picker).
- `js/admin.js` — toda la lógica: CRUD productos, librería de media, upload/delete/select.
- `css/app.css` — estilos bajo el selector `body.page-admin` (prefijo `adm-*`).

### Flujo de gestión de imágenes de producto (panel /admin)

```
Usuario abre /admin
    → Tab "Productos" carga list_products
    → Click "Editar" abre openProductModal(product)
        → Muestra imagen actual (imageUrlInput.value = product.image)
        → Botón "Subir": uploadFile(file) → upload → imageUrlInput.value = r.url
        → Botón "Elegir de librería": openMediaPicker → list_media → click tile → imageUrlInput.value = url
        → Botón "Quitar": imageUrlInput.value = ''
    → Click "Guardar":
        → Lee fd.get('image') del FormData (campo <input name="image">)
        → call('update_product', { ...payload, image: url })
        → loadProducts() — recarga la tabla completa
```

**Funciones clave en `js/admin.js`**:

| Función | Qué hace |
|---------|----------|
| `loadProducts()` | `list_products` → renderiza tabla |
| `openProductModal(product)` | Rellena el formulario con datos del producto, incluida la imagen |
| `setPreview(url)` | Actualiza el `<img>` de preview en el modal |
| `uploadFile(file, options)` | Multipart POST a `upload` → devuelve `{ url }` |
| `openMediaPicker(folder, callback)` | Abre modal de librería → `list_media` → al seleccionar llama `callback(url)` |
| `loadMedia(folder)` | `list_media` → `renderMedia()` |
| `renderMediaTile(m)` | Genera el HTML de cada tile de media |

---

## TPV Media Injector (`js/tpv-media-injector.js`)

Cargado automáticamente por `gateway.php` en el SPA del TPV (React). Añade controles de imagen al campo "IMAGEN DEL PRODUCTO" del modal de edición de React sin necesitar reconstruir el bundle.

### Por qué existe este injector

El bundle React compilado (`dashboard/assets/*.js`) no puede reconstruirse (falta el pipeline de fuentes). La acción `update_product` de React usa **axios → XHR**, no `window.fetch`. El injector parchea ambos para interceptar y completar el guardado de imagen.

### Flujo completo de edición desde el TPV

```
Usuario abre "PRODS" en el TPV
    → React carga ProductsAdmin → list_products (XHR/axios)
        → Interceptor de respuesta XHR actualiza productsCache

Usuario hace click en "Editar" en un producto
    → React abre modal con ProductForm
        → MutationObserver detecta label "IMAGEN DEL PRODUCTO"
        → injectFor(label):
            - Espera 300ms (React termina de rellenar el form)
            - Si productsCache está vacío → list_products (fetch)
            - extractFormContext() lee name/sku/id del formulario React
            - findMatch() localiza el producto en la cache
            - Renderiza el widget (preview, URL input, Librería, Subir, Borrar)
            - Registra activeInitialImage (URL de partida)

Usuario elige/sube/borra imagen
    → saveImage(url):
        1. Actualiza activeOverride (valor a inyectar en el próximo save de React)
        2. Llama apiPost('update_product', { id, image }) → fetch → backend
        3. srcReplacements[oldUrl] = newUrl (para corregir el DOM después)
        4. applyProductImagesToDOM() → actualiza imgs visibles inmediatamente

Usuario pulsa "Guardar Producto" (botón de React)
    → React llama axios.post('/acide/index.php', { action:'update_product', data:{...formData} })
        → Interceptor XHR.send() detecta la acción y reemplaza data.image con activeOverride
        → Backend recibe el payload completo con la imagen correcta
        → Respuesta XHR interceptada → productsCache actualizado

Usuario cierra modal / vuelve al TPV
    → React re-renderiza el grid con datos de su estado interno (imagen antigua)
    → MutationObserver (childList, debounce 120ms) dispara applyProductImagesToDOM():
        - Estrategia 1: para cada <img>, busca su src en srcReplacements → aplica nuevo src
        - Estrategia 2: busca <img alt="nombre"> → compara con productsCache → aplica
    → El grid muestra la imagen actualizada sin recargar la página
```

### Interceptores de red

```
window.fetch (llamadas del injector)
    request:  si action == update_product|create_product → inyecta activeOverride en data.image
    response: si action == list_products     → productsCache = data
              si action == update_product    → actualiza entrada en productsCache
                                              si es el producto activo → refreshPreview()

XMLHttpRequest.send (llamadas de axios/acideService de React)
    request:  si URL contiene /acide/index.php y action == update_product|create_product
              → inyecta activeOverride en data.image antes de enviar
    response: igual que fetch — actualiza productsCache
```

### Mecanismo de sincronización del DOM (`srcReplacements`)

```
Problema: React re-renderiza el grid con su estado interno (imagen vieja).
          Nuestros cambios directos en el DOM son sobreescritos.

Solución: mapa de sesión srcReplacements = { oldUrl: newUrl, ... }

Cada saveImage exitoso:
    srcReplacements[activeInitialImage] = finalUrl
    activeInitialImage = finalUrl  // para encadenar saves sucesivos (A→B→C)

resolveReplacement(src):
    Recorre la cadena hasta encontrar el valor final (evita bucles con set de vistos)
    Ejemplo: srcReplacements = { A:B, B:C } → resolveReplacement(A) == C

applyProductImagesToDOM():
    Para cada <img> fuera del injector:
        1. resolved = resolveReplacement(img.src)
           Si resolved ≠ img.src → img.src = resolved  (cubre el TPV grid)
        2. Si alt coincide con nombre en productsCache → aplica url del cache
           (cubre la tabla de la vista Productos/Stock)
```

### Funciones clave del injector

| Función | Descripción |
|---------|-------------|
| `injectFor(label)` | Detecta el producto en edición, renderiza el widget de imagen |
| `extractFormContext(form)` | Lee `name`, `sku`, `id`/`slug` de los inputs de React |
| `findMatch(products, ctx)` | Busca el producto en cache por id → sku → nombre |
| `saveImage(url)` | Guarda la imagen vía `update_product` directo + actualiza DOM |
| `applyProductImagesToDOM()` | Sincroniza todos los `<img>` del DOM con `productsCache` |
| `resolveReplacement(src)` | Resuelve cadenas de reemplazos `A→B→C` |
| `openPicker(cb, ctx)` / `buildPicker()` | Modal de librería de media |
| `apiPost(action, data)` | Helper fetch para llamadas JSON al API |
| `apiUpload(file, slug)` | Helper multipart para `upload` |

---

## Seguridad

| Punto | Medida |
|-------|--------|
| Gate de autenticación | `gateway.php` exige sesión válida antes de servir `admin.html` o el SPA |
| Extensiones de archivo | Whitelist estricta en backend: `jpg jpeg png webp gif mp4 webm`. SVG rechazado |
| Path traversal | `deleteMedia` valida con `realpath()` que el archivo esté dentro del directorio permitido; rechaza `/`, `\`, `..` en el nombre |
| Roles | `upload`, `delete_media`, `update_product` requieren rol admin en `StoreHandler` y `SystemHandler` |
| SVG | Nunca admitido en subidas (riesgo de XSS en `<img>` o uso como HTML) |

---

## Cómo añadir una nueva carpeta de media

1. **Backend** — en `SystemHandler::upload()`, añadir la clave a `$allowedFolders` y `$urlPrefixes`.
2. **Backend** — en `SystemHandler::listMedia()`, añadir la clave al array `$folders`.
3. **Backend** — en `SystemHandler::deleteMedia()`, añadir el prefijo de URL a `$allowedPrefixes`.
4. **Admin panel** — en `admin.html`, añadir `<option value="nuevacarpeta">` al `<select id="adm-media-folder">`.
5. **TPV injector** — en `buildPicker()`, añadir `<option value="nuevacarpeta">` al `<select data-role="folder">`.

No se necesita ningún cambio en el dispatcher ni en la autenticación.
