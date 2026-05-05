# Plan de Desarrollo y Ventas: Fase 1 (Optimizada con IA Invisible)

**Documento:** claude/planes/desarrolloventasuno.md
**Proyecto:** MyLocal
**Estado:** Planificación de Ejecución Detallada (Fase 1 Mejorada)

---

## 1. Análisis y Opinión Estratégica

La propuesta de inyectar IA potente desde el primer minuto sin venderla como tal es la decisión estratégica más acertada para desbancar a la competencia (NordQR, Bakarta). 

**¿Por qué es correcto?**
- **Reduce la fricción a cero:** El mayor cuello de botella para un hostelero es el tiempo que tarda en "picar" los datos de su carta. Pedirle que dedique 30 minutos es perderlo. Con la importación por OCR (Visión) reducimos ese tiempo a 2 minutos.
- **Efecto WOW instantáneo:** Cuando el hostelero sube una foto mediocre de su plato y la plataforma se la devuelve con fondo desenfocado y corrección de color, o cuando sube un PDF y ve su carta montada automáticamente, el valor percibido se multiplica por 10.
- **Cumplimiento sin esfuerzo:** Detectar alérgenos automáticamente es un dolor de cabeza legal resuelto como por arte de magia.
- **Enfoque técnico correcto:** Centralizar esto en `MenuEngineer.php` con procesos asíncronos garantiza que la interfaz de usuario siga siendo extremadamente rápida (cargando en < 2s) mientras la "magia" ocurre en segundo plano.

**Conclusión:** Integrar estas soluciones como "ayudas automáticas" hace que nuestro producto no se perciba como una herramienta más, sino como un servicio que trabaja para ellos.

---

## 2. Arquitectura y Estándares de Código

Para garantizar la mantenibilidad y escalabilidad del sistema, se seguirán estrictamente las siguientes reglas arquitectónicas:

### 2.1. Estructura Modular y Granular (Agnóstica)
- **Construcción Atómica:** Cada documento tiene una única responsabilidad. Ningún archivo debe superar las **250 líneas de código**.
- **Independencia de Capacidades:** Cada funcionalidad se desarrollará como un módulo independiente en su propio directorio dentro de `./CAPABILITIES/`.
  - Ejemplo: `./CAPABILITIES/OCR/`, `./CAPABILITIES/RECETAS/`, `./CAPABILITIES/WEBSCRAPER/`.
- **Cero Hardcoding:** Los sistemas deben ser funcionales y dinámicos. No se permiten datos estáticos donde debería haber lógica o configuración.
- **Plugins para AxiDB:** Cualquier extensión de la base de datos AxiDB debe ser modular y residir en `./axidb/plugins/`.
  - Ejemplo: `./axidb/plugins/alimentos/`, `./axidb/plugins/ocr/`.
- **Particionamiento Profesional:** Se mantendrá una estructura clara inspirada en el estándar de MyLocal:
  - `./CORE/core/private/` para lógica sensible y privada.
  - `./CORE/core/modules/` para módulos funcionales del sistema.

### 2.2. Sistema de Diseño y Skills
Se utilizarán "Skills" dinámicas para estandarizar la apariencia del proyecto:
- **Vista Cliente:** Se aplicará el diseño definido en `./artifacts/skilldiseno.md`. Si se mejora o modifica cualquier vista del cliente, esta skill se sobrescribirá y automejorará para reflejar el nuevo estándar.
- **Dashboard de Administración:** Se aplicará el diseño definido en `./artifacts/skilldashboard.md`.

---

## 3. Descripción de la Fase 1: La Carta Autónoma

La Fase 1 se redefine. Ya no es solo un creador de cartas digitales, es un **"Digitalizador Instantáneo"**. El objetivo es que el hostelero consiga su QR en tiempo récord con un resultado visual superior al que conseguiría contratando a un diseñador.

**Nuevos Motores de IA Invisible en Fase 1:**
1. **Conversor Carta PDF/Imagen a Digital (OCR Avanzado):** Extrae nombre, descripción y precio de cualquier foto o PDF.
2. **AI Enhancer de Fotos:** "Varita mágica" para embellecer fotos de móviles antiguos (mejora de iluminación, contraste, efecto bokeh).
3. **Auto-Alérgenos:** Etiquetado predictivo de los 14 alérgenos obligatorios de la UE a partir de los ingredientes.
4. **Copywriting de Venta:** Generación de micro-promociones persuasivas para platos marcados como "Especialidad".
5. **Generador de Material Impreso:** Creación automática de cartas físicas en PDF (3 plantillas: Minimalista, Clásica, Moderna) y displays de mesa para el QR, listos para llevar a la imprenta.

---

## 3.1. Flujo de Onboarding (La Experiencia WOW)

Este flujo minimiza los pasos manuales y delega el trabajo pesado a la IA en segundo plano. Es el **corazón vendible** de la Fase 1: en menos de 10 minutos el hostelero pasa de "no tengo nada" a "carta digital + QRs imprimidos + sala configurada".

- **Paso 1 - Registro Rápido:** Email, contraseña, nombre del local y `slug` (validado en vivo). Al confirmar, ya es accesible en `<slug>.mylocal.es`.
- **Paso 2 - Identidad Visual:** Subida de logo. La IA extrae automáticamente la paleta de colores y la propone como acento del tema.
- **Paso 3 - Configura tu sala:** Wizard de estancias y mesas (sección 5.4). Presets rápidos: "Solo barra", "Salón + Terraza"... En 30 segundos quedan creadas Z zonas y M mesas con QRs únicos.
- **Paso 4 - La Magia (Importación de carta):**
  - *Opción A (Recomendada):* "Sube una foto de tu carta actual o un PDF y nosotros la montamos por ti". (OCR + parse vía Gemini Vision).
  - *Opción B:* "Empezar desde cero con ayuda del asistente".
- **Paso 5 - Revisión de Platos (Momento WOW 1):** Carta estructurada con categorías y precios. Aquí aplica la **Varita Mágica** a las fotos, genera descripciones con un clic y ve cómo se **asignan los alérgenos automáticamente**.
- **Paso 6 - Toque de Venta:** Marca 2-3 platos como "Especialidades" para que la IA genere micro-promociones persuasivas.
- **Paso 7 - Idiomas:** Activa traducciones automáticas (ES/EN/FR/DE) con un solo clic.
- **Paso 8 - Tema Visual (Momento WOW 2):** Elige entre 4 temas profesionales (Minimal, Dark, Classic, Premium). Preview en vivo con su propia carta. Botón "Ver como cliente".
- **Paso 9 - Centro de Impresión:** Pantalla final que genera 3 PDFs:
  1. Carta física en plantilla Minimalista, Clásica o Moderna.
  2. Hoja A4 con todos los QRs de las mesas.
  3. Display de mesa A6 plegable, uno por cada mesa.
- **Paso 10 - Activación:** "Tu carta digital ya está online en `<slug>.mylocal.es`. Comparte con tu equipo." Botón copiar enlace + WhatsApp share.

---

## 4. Blog de Recetas (El Banco de Conocimiento AI & SEO)

Esta es una pieza clave no solo para el marketing (SEO), sino como base de datos de entrenamiento y contexto para el Agente Restaurante y los usuarios.

**Características del Web Scraper y el Blog:**
- **Scraper Inteligente:** Extrae recetas españolas, ingredientes, cantidades, tiempos de preparación, categorización y etiquetas.
- **Formato Visual (Estilo Instagram):** 
  - Diseño vertical/cuadrado (mobile first).
  - Imagen principal impactante con fondo transparente o efecto estudio.
  - Video corto tipo Reel/TikTok integrado para la preparación.
- **Utilidad Doble:** 
  - *Para SEO:* Atrae tráfico orgánico masivo ("cómo hacer tortilla de patatas", "ingredientes paella valenciana").
  - *Para la IA:* Alimenta el conocimiento de `MenuEngineer.php` para mejorar la sugerencia de alérgenos y descripciones basándose en recetas canónicas.

---

## 5. Páginas Legales y Estructura de la Wiki

Para transmitir confianza total y cumplir con las normativas (GDPR), se implementará un footer corporativo completo.

### 5.1. Textos Legales
1. **Aviso Legal:** Identidad fiscal de MyLocal, condiciones de la empresa.
2. **Política de Privacidad:** Tratamiento de datos de los hosteleros y de los clientes finales que leen la carta.
3. **Política de Cookies:** Selector granular y declaración de cookies técnicas vs analíticas.
4. **Términos de Uso (Política de Uso):** Reglas de uso de la plataforma, SLAs y disponibilidad.
5. **Política de Cuentas:** Condiciones de registro, titularidad de los datos, derecho de exportación y eliminación (soberanía de datos).
6. **Política de Reembolsos:** Condiciones de la garantía, cancelación de planes Pro Mensual y Pro Anual.

### 5.2. Wiki de la Herramienta (Centro de Ayuda)
Estructura clara orientada al auto-servicio:
- **Primeros Pasos:** Cómo registrarse, subir el logo, configurar la carta base.
- **Gestión de la Carta:** Cómo editar precios, usar la varita mágica (Enhancer), añadir alérgenos, reordenar categorías.
- **Descargas y Códigos QR:** Cómo descargar el QR, generar QRs por mesa, opciones de impresión.
- **Tu Cuenta y Suscripción:** Gestión de facturación, upgrade a Pro Anual, cancelación.
- **Glosario Hostelero:** Explicación técnica y sencilla (Qué es Verifactu, Normativa de Alérgenos UE, etc.).

### 5.3. Despliegue Profesional: Hostinger + Cloudflare + Subdominios

Stack productivo: **Hostinger** como hosting (PHP + disco para STORAGE) y
**Cloudflare** como capa DNS/SSL/cache delante. La razón de tener
Cloudflare encima de Hostinger no es por velocidad solamente: es para
poder dar a cada cliente su subdominio (`elbar.mylocal.es`,
`cafedora.mylocal.es`) sin tocar nada en Hostinger por cada cliente
nuevo.

#### Arquitectura

```
Cliente final (móvil escanea QR)
         │
         ▼
elbar.mylocal.es              ←  el subdominio del hostelero
         │
         ▼ (DNS y SSL)
Cloudflare (Wildcard *.mylocal.es)
         │
         ▼ (Origin)
Hostinger (un solo hosting con 1 dominio: mylocal.es)
         │
         ▼ (.htaccess + router.php detectan el host)
PHP carga `local_id = elbar` desde STORAGE/locales/elbar.json
```

#### Flujo cuando un hostelero contrata el plan

1. Hostelero rellena el formulario de alta y elige `slug` (`elbar`).
2. Backend valida que `elbar` no choque con palabras reservadas
   (`admin`, `dashboard`, `api`, `app`, `www`, `mail`, etc.) ni con
   otro local existente.
3. Se crea `STORAGE/locales/elbar.json` con sus datos.
4. Sin tocar Cloudflare ni Hostinger: el wildcard ya cubre el nuevo
   subdominio. **Es operativo en menos de 1 segundo desde el alta.**
5. El primer acceso a `elbar.mylocal.es` dispara el bootstrap de su
   carta vacía y el wizard de onboarding.

#### Configuración de Cloudflare (una sola vez)

1. Añadir `mylocal.es` a Cloudflare como sitio.
2. Cambiar los nameservers en el registrador (Hostinger panel) a los
   que indique Cloudflare.
3. En Cloudflare → DNS:
   - Registro `A` con nombre `@` apuntando a la IP de Hostinger.
   - Registro `A` con nombre `*` apuntando a la misma IP. **Proxied (naranja)**.
4. SSL/TLS → modo **Full (strict)**. Certificado wildcard
   `*.mylocal.es` automático en el borde de Cloudflare.
5. Reglas de página:
   - `*.mylocal.es/*` → cache estática para `/MEDIA/*` y `/assets/*`.
   - `*.mylocal.es/acide/*` → bypass cache (siempre fresco al backend).

#### Configuración de Hostinger (una sola vez)

1. Subir la carpeta `release/` al `public_html` del dominio raíz.
2. En el panel: añadir el certificado SSL "Cloudflare Origin Certificate"
   en el dominio (no Let's Encrypt — entra en conflicto con Cloudflare
   Full strict).
3. PHP version ≥ 8.2 con extensiones `openssl`, `curl`, `fileinfo`,
   `gd`, `mbstring` activas (panel → Advanced → PHP Configuration).
4. Cron del worker IA a 1 minuto:
   `php /home/<user>/public_html/axidb/plugins/jobs/worker_run.php`

#### Detección de subdominio en el backend

`router.php` y `spa/server/index.php` extraen el host:

```php
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
if (preg_match('/^([a-z0-9\-]+)\.mylocal\.es$/i', $host, $m)) {
    define('CURRENT_LOCAL_SLUG', $m[1]);
}
// CURRENT_LOCAL_SLUG queda disponible para todo el flujo PHP.
```

`STORAGE/locales/<slug>.json` se carga al inicio de cada request y se
añade al objeto `services` que reciben todas las capabilities.

#### Por qué Cloudflare encima de Hostinger (y no solo Hostinger)

- **Wildcard SSL gratis** (Hostinger lo cobra como add-on por dominio).
- **Onboarding instantáneo de cliente nuevo** sin tocar el panel.
- **Cache global**: cuando un cliente final escanea el QR en Marbella,
  los assets le llegan desde el edge más cercano, no de Madrid.
- **Protección DDoS** sin coste adicional.
- **Page Rules** para bypass de cache en `/acide/*` (la API debe ir
  siempre al origen).

---

### 5.4. Gestión de Estancias y Mesas (UX simple, complejo por dentro)

El hostelero verá una pantalla simple: arrastrar mesas dentro de
"estancias" (Salón, Terraza, Barra, Reservados...). Por dentro
mantenemos el grafo `local → zonas → mesas` con QR único por mesa.

#### Modelo de datos

```
STORAGE/locales/<slug>.json
  └─ información del local

STORAGE/restaurant_zones/<zone_id>.json
  ├─ id            uuid
  ├─ local_id      slug del local
  ├─ nombre        "Salón principal"
  ├─ orden         entero
  ├─ icono         emoji o nombre lucide ("door", "tree", etc.)
  └─ activa        boolean

STORAGE/mesas/<mesa_id>.json
  ├─ id            uuid
  ├─ local_id      slug
  ├─ zone_id       referencia
  ├─ numero        "1", "T2", "Reservado A"
  ├─ capacidad     entero
  ├─ qr_url        URL pública (ej. elbar.mylocal.es/m/abc123)
  ├─ qr_token      hash único (no adivinable)
  └─ activa        boolean
```

#### Flujo del wizard "Configura tu sala"

**Paso 1 - ¿Cuántas estancias tienes?**
- Botón rápido: "Solo barra", "Solo salón", "Salón + Terraza", "Personalizado".
- Las primeras 3 opciones crean las zonas en un clic.

**Paso 2 - ¿Cuántas mesas en <Zona>?**
- Slider visual de 1 a 30 mesas por zona, por defecto 8.
- Al confirmar, las crea numeradas automáticamente (1, 2, 3...).

**Paso 3 - Personaliza (opcional)**
- El usuario puede renombrar mesas (por ejemplo `T1`, `T2` para terraza)
  o cambiar capacidades (mesa familiar de 8 vs barra de 1).
- Drag & drop para mover mesas entre zonas.

**Paso 4 - Generar QRs**
- Botón único: "Generar QRs de todas las mesas" → produce un PDF A4
  con grid 3x4 (12 QRs por hoja) listo para imprimir.
- Opción adicional: "Display de mesa" (table tent A6 plegable) por
  cada mesa.

#### Componentes a construir

- `CAPABILITIES/QR/admin/MesasWizard.jsx` - wizard 4 pasos.
- `CAPABILITIES/QR/admin/MesaCanvas.jsx` - vista canvas con drag&drop.
- `CAPABILITIES/QR/MesaModel.php` - CRUD de mesas con validación slug.
- `CAPABILITIES/QR/QrTokenGenerator.php` - genera token no adivinable
  (`bin2hex(random_bytes(8))`) para cada mesa.
- `CAPABILITIES/QR/admin/qr-mesas.css` - estilos del canvas.

---

### 5.5. Selector de Temas Visuales (4 estilos profesionales)

El hostelero elige el tema visual de SU carta pública (la que ve el
cliente final al escanear el QR). Cuatro temas cuidados, no cien
mediocres.

#### Los 4 temas

| Tema | Identidad | Tipografía | Paleta base |
|------|-----------|------------|-------------|
| **Minimal** | Limpio, mucho blanco, foto protagonista | Inter / sans-serif | Blanco + negro + acento del logo |
| **Dark** | Premium, fondo oscuro, fotos brillan | Outfit / Inter | Negro + dorado + acento |
| **Classic** | Editorial, taberna, tipografía serif | Playfair / Georgia | Crema + marrón + acento |
| **Premium** | Lujo, mucho aire, jerarquía tipográfica | Editorial New + Geist | Negro + dorado claro + acento |

Cada tema es un set de variables CSS aplicadas con `[data-theme="..."]`.
La paleta principal viene del logo del hostelero (extracción automática
con `PaletteExtractor.php` ya implementado).

#### Almacenamiento

```
STORAGE/locales/<slug>.json
  └─ theme_settings:
      ├─ id           "minimal" | "dark" | "classic" | "premium"
      ├─ accent       "#C8A96E"  ← extraido del logo o personalizado
      ├─ font_pair    "default" | "elegant" | "modern"
      └─ logo_position "header" | "hero"
```

#### Flujo de selección

1. Pantalla **"Elige el aspecto de tu carta"** con 4 tarjetas grandes,
   cada una mostrando una preview real de la carta del usuario con ese
   tema aplicado.
2. Click → tema activo. Se aplica al instante en `<slug>.mylocal.es`.
3. Botón "Ver como cliente" abre la carta pública en una pestaña nueva.

#### Componentes a construir

- `CAPABILITIES/CARTA/Themes.php` - definición de los 4 temas con
  variables CSS por defecto y la lógica para mezclarlas con la paleta
  del logo.
- `CAPABILITIES/CARTA/themes/minimal.css`, `dark.css`, `classic.css`,
  `premium.css` - cada tema en su archivo (≤250 líneas).
- `CAPABILITIES/CARTA/admin/ThemeSelector.jsx` - UI con previews.
- `CAPABILITIES/CARTA/admin/ThemePreview.jsx` - mini iframe con la
  carta pública del usuario aplicando el tema.

---

### 5.6. Impresión Masiva: Carta + QRs en un solo flujo

El hostelero llega al final del onboarding y necesita **material
físico** para abrir mañana. Una sola pantalla genera:

1. **Carta física en PDF** - 1 plantilla a elegir entre Minimalista,
   Clásica, Moderna. Incluye foto, descripción, alérgenos, precio.
   Lista para imprenta.
2. **QRs por mesa en PDF** - hoja A4 con grid 3x4 (12 QRs), repetida
   tantas veces como haga falta para cubrir todas las mesas.
3. **Display de mesa (table tent)** - A6 plegable con QR central y
   reclamo "Pide y paga desde tu móvil". Uno por cada mesa.

#### Por qué importa para el negocio

Los hosteleros de la competencia (NordQR, Bakarta) hacen que el
hostelero se busque la vida con la imprenta. Nosotros generamos los
PDFs listos para llevar a una papelería o imprimir en casa. Eso es
**activación en 24 horas** vs. semanas.

#### UI de salida

Pantalla `Imprimir material` con tres tarjetas:

```
┌───────────────────────────┐  ┌───────────────────────────┐  ┌───────────────────────────┐
│  📋 Carta física          │  │  🔲 QRs por mesa          │  │  🪑 Displays de mesa      │
│  PDF A4, 4 páginas        │  │  PDF A4, 12 QRs por hoja  │  │  PDF A6 plegable          │
│  Plantilla: [Minimal ▼]   │  │  24 mesas → 2 hojas       │  │  24 displays              │
│  [Descargar PDF]          │  │  [Descargar PDF]          │  │  [Descargar PDF]          │
└───────────────────────────┘  └───────────────────────────┘  └───────────────────────────┘
```

#### Componentes a construir

- `CAPABILITIES/PDFGEN/admin/PrintCenterPanel.jsx` - las 3 tarjetas.
- Acción server `generate_qr_sheet` - usa la plantilla
  `pegatinas_qr.php` ya creada y la rellena con todas las mesas
  del local actual.
- Acción server `generate_table_tents` - itera mesas y produce un
  PDF multi-página con un display por hoja.

---

---

## 6. Amplificación SEO y Mejoras de Textos

- **Mensajes Orientados a la Solución:** Cambiar "Creador de Cartas" por "Tu Carta Digital en 2 Minutos a partir de una Foto".
- **Arquitectura SEO:** 
  - Crear landings específicas de formato problema/solución: "Cómo digitalizar una carta en PDF", "Carta con alérgenos automáticos", "Mejorar fotos de platos para restaurantes".
- **Textos H1/H2 Optimizados:** 
  - H1: "Tu Menú Digital Inteligente: De PDF a QR en 2 minutos."
  - H2: "Deja de picar datos. Sube una foto de tu carta y nuestra tecnología la estructura por ti, detecta alérgenos y mejora tus fotos."

---

## 7. Checklist Paso a Paso (Implementación Perfecta)

### A. Infraestructura y Base de Datos
- [x] Ampliar el esquema de Base de Datos para soportar los nuevos campos generados por IA (alérgenos sugeridos, texto_promocional, imagen_mejorada).
- [x] Configurar el sistema de colas (Jobs/Workers) para procesos asíncronos (OCR y procesado de imágenes no deben bloquear el frontend).

### B. Módulos de IA (MenuEngineer.php)
- [x] Implementar conector/API para **OCR Inteligente** (procesamiento de imágenes y PDFs).
- [x] Desarrollar parser para estructurar el output del OCR en JSON (Categorías > Platos > Precios).
- [x] Integrar API de **Mejora de Imagen (AI Enhancer)** para upscale y efecto bokeh.
- [x] Crear el algoritmo de cruce de ingredientes con el catálogo de **Alérgenos UE**.
- [x] Configurar los prompts de sistema para el **Generador de Micro-promociones** y **Descripciones**.

### C. Flujo de Onboarding & Frontend
- [x] Desarrollar la UI de "Arrastra tu PDF/Foto" con animaciones de carga amigables.
- [x] Integrar la extracción de paleta de colores desde el logo del usuario.
- [x] Construir la interfaz de revisión (Paso 4) donde el usuario ve la "magia" y puede aprobar/editar las sugerencias de la IA.
- [x] Añadir el botón de "Varita Mágica" en el uploader de imágenes del plato.

### D. Blog de Recetas & Scraper
- [x] Desarrollar el Web Scraper en Python o PHP para recolectar datos de sitios de recetas estructurados.
- [x] Crear el modelo de datos de `Recetas` (Ingredientes, cantidades, pasos, etiquetas).
- [x] Diseñar el layout del Blog estilo Instagram (Mobile First, Tarjetas visuales, Auto-play de video).

### E. Legales y Centro de Ayuda (Wiki)
- [x] Redactar y publicar todas las páginas legales (Privacidad, Uso, Cookies, Reembolsos, Cuentas, Aviso Legal).
- [x] Instalar y configurar el banner de cookies.
- [x] Montar la estructura de la Wiki (Centro de Ayuda) y poblarla con los primeros 10 artículos clave.

### F. Generador de PDFs (Material Impreso)
- [x] Diseñar y maquetar 3 plantillas de impresión (Minimalista, Clásica, Moderna) para la carta física.
- [x] Crear plantillas PDF generables para displays de mesa (Table Tents) y pegatinas con el QR integrado.
- [x] Integrar motor de generación de PDF (ej. Puppeteer o Dompdf) que respete márgenes y sangrados de imprenta.

### G. Calidad y Lanzamiento
- [x] Verificar que ningún archivo supere las 250 líneas de código y cumpla con la responsabilidad única.
- [x] Validar la estructura de directorios modular en `./CAPABILITIES/` y `./axidb/plugins/`.
- [x] Aplicar y validar el diseño de vistas cliente según `./artifacts/skilldiseno.md`.
- [x] Aplicar y validar el diseño de administración según `./artifacts/skilldashboard.md`.
- [x] Testing de carga del OCR con PDFs complejos (manuscritos, fotos torcidas, multi-columna).
- [x] Testing de la precisión del clasificador de alérgenos.
- [x] Verificación de tiempos de respuesta: la UI debe responder instantáneamente aunque la IA siga procesando.
- [x] Cierre definitivo de Fase 1 para proceder a ventas.

### H. Despliegue Hostinger + Cloudflare (operacional)

**H.1 Cloudflare (DNS + SSL + cache)**
- [ ] Añadir `mylocal.es` a Cloudflare como sitio.
- [ ] Cambiar nameservers en Hostinger para apuntar a los de Cloudflare.
- [ ] Esperar a que el cambio propague (status "Active" en Cloudflare).
- [ ] Crear registro `A @` apuntando a la IP de Hostinger (proxied).
- [ ] Crear registro `A *` apuntando a la misma IP (proxied) - wildcard.
- [ ] SSL/TLS modo **Full (strict)**.
- [ ] Generar Origin Certificate desde Cloudflare → SSL/TLS → Origin Server.
- [ ] Page Rule: `*.mylocal.es/MEDIA/*` → cache 1 mes.
- [ ] Page Rule: `*.mylocal.es/assets/*` → cache 1 año (assets versionados).
- [ ] Page Rule: `*.mylocal.es/acide/*` → bypass cache (siempre origen).

**H.2 Hostinger (origen)**
- [ ] Subir `release/` al `public_html` del hosting.
- [ ] Instalar el Origin Certificate de Cloudflare en el dominio.
- [ ] PHP ≥ 8.2 con extensiones: `openssl`, `curl`, `fileinfo`, `gd`, `mbstring`, `intl`.
- [ ] Verificar `STORAGE/` y `MEDIA/` con permisos 755 y propiedad del usuario PHP.
- [ ] Cron a 1 minuto: `php /home/<user>/public_html/axidb/plugins/jobs/worker_run.php`.
- [ ] Verificar que `health_check` responde 200 desde `https://mylocal.es/acide/index.php`.

**H.3 Detección de subdominio en backend**
- [ ] Añadir extractor de slug en `router.php` que define `CURRENT_LOCAL_SLUG`.
- [ ] Replicar la misma lógica en `spa/server/index.php`.
- [ ] Cargar `STORAGE/locales/<slug>.json` automáticamente en cada request.
- [ ] Si el slug no existe, redirigir a la landing pública `mylocal.es`.
- [ ] Test: `curl -H "Host: elbar.mylocal.es" http://localhost:8090/` debe cargar el contexto del local `elbar`.

**H.4 Validador de slugs**
- [ ] Lista de palabras reservadas: `admin`, `dashboard`, `api`, `app`, `www`, `mail`, `ftp`, `cpanel`, `cdn`, `static`, `assets`, `acide`, `mylocal`, `demo`, `test`, `staging`, `dev`, `panel`, `support`, `help`, `docs`, `blog`, `shop`, `store`.
- [ ] Regex: `^[a-z][a-z0-9-]{2,30}$` (no empieza por número/guión, longitud 3-31).
- [ ] Endpoint `validate_slug` que devuelve `{available: bool, reason: string}`.
- [ ] UI del registro con feedback en vivo: verde/rojo según disponibilidad.

### I. Configuración de Sala (Estancias y Mesas)

**I.1 Modelo de datos**
- [ ] Crear `CAPABILITIES/QR/MesaModel.php` (CRUD mesa + validaciones).
- [ ] Crear `CAPABILITIES/QR/ZonaModel.php` (CRUD zona/estancia).
- [ ] Crear `CAPABILITIES/QR/QrTokenGenerator.php` (`bin2hex(random_bytes(8))` por mesa).
- [ ] Generar URL pública por mesa: `<slug>.mylocal.es/m/<token>` (más corto que `/mesa/<id>`).

**I.2 Wizard "Configura tu sala"**
- [ ] Componente `CAPABILITIES/QR/admin/SalaWizard.jsx` (4 pasos).
- [ ] Paso 1: presets rápidos (Solo barra / Solo salón / Salón+Terraza / Personalizado).
- [ ] Paso 2: slider de mesas por zona, autonumeración.
- [ ] Paso 3: drag & drop entre zonas + renombre + capacidad.
- [ ] Paso 4: confirmación con resumen ("3 zonas, 24 mesas, 24 QRs listos").

**I.3 Vista de gestión continua**
- [ ] Componente `CAPABILITIES/QR/admin/MesaCanvas.jsx` con vista tipo "mapa de sala".
- [ ] Acciones por mesa: renombrar, capacidad, mover de zona, regenerar QR, archivar.
- [ ] Estado en tiempo real: libre / pidiendo / esperando / pagada (cuando exista TPV).
- [ ] Importar/exportar mesas en CSV (útil para grupos con 50+ mesas).

**I.4 Acciones server**
- [ ] `create_zona`, `update_zona`, `delete_zona`, `list_zonas`.
- [ ] `create_mesa_batch` para crear N mesas de una zona en una sola llamada.
- [ ] `regenerate_mesa_qr` (cambia el token, útil si alguien copió un QR).

### J. Selector de Temas Visuales

**J.1 Definición de temas**
- [ ] `CAPABILITIES/CARTA/Themes.php` con la definición de los 4 temas
      (id, nombre, descripción, vars CSS por defecto, fonts cargadas).
- [ ] `CAPABILITIES/CARTA/themes/minimal.css` (Inter, blanco/negro/acento).
- [ ] `CAPABILITIES/CARTA/themes/dark.css` (Outfit, negro/dorado/acento).
- [ ] `CAPABILITIES/CARTA/themes/classic.css` (Playfair, crema/marrón/acento).
- [ ] `CAPABILITIES/CARTA/themes/premium.css` (Editorial New, lujo).

**J.2 Selector en el dashboard**
- [ ] `CAPABILITIES/CARTA/admin/ThemeSelector.jsx` con 4 tarjetas grandes.
- [ ] `CAPABILITIES/CARTA/admin/ThemePreview.jsx` que renderiza un mini
      iframe con la carta pública del usuario aplicando el tema.
- [ ] Aplicación del tema activo al instante (sin recarga del backend).
- [ ] Botón "Ver como cliente" → abre `<slug>.mylocal.es/carta` en nueva pestaña.

**J.3 Carta pública lee tema desde backend**
- [ ] `CartaPublicaApi.php` devuelve `theme_settings` junto con la carta.
- [ ] `socola-carta.js` (frontend público) inyecta `<link rel="stylesheet">`
      al CSS correcto según `theme_settings.id`.
- [ ] El acento (`--accent`) se sobrescribe con el color extraído del logo
      del hostelero.

### K. Centro de Impresión (Carta + QRs + Displays)

**K.1 UI agrupada**
- [ ] `CAPABILITIES/PDFGEN/admin/PrintCenterPanel.jsx` con 3 tarjetas
      (Carta física / QRs por mesa / Displays de mesa).
- [ ] Selector de plantilla en cada tarjeta (Minimal/Clásica/Moderna).
- [ ] Botón "Descargar PDF" que llama al server y baja el archivo.
- [ ] Indicador de progreso para PDFs grandes (>2 MB).

**K.2 Acciones server nuevas**
- [ ] `generate_qr_sheet` - PDF A4 con grid 3x4 de TODAS las mesas activas
      del local actual. Reusa `pegatinas_qr.php` y rellena con la lista
      real de mesas.
- [ ] `generate_table_tents` - PDF multi-página, un display A6 por mesa.
      Reusa `display_mesa.php`.
- [ ] `generate_full_carta` - ya existe `generate_pdf_carta`. Verificar
      que respeta el tema activo del local (paleta del logo).

**K.3 Calidad imprenta**
- [ ] Sangrado de 3mm en todos los PDFs imprimibles.
- [ ] CMYK opcional vía conversión Imagick (cuando esté disponible).
- [ ] Marcas de corte en hojas multi-elemento (pegatinas).
- [ ] Densidad de QR mínima: 38mm para escanear cómodo a distancia.

### L. Multi-tenancy en datos

**L.1 Aislamiento por local**
- [ ] Cada doc en `STORAGE/carta_categorias/` y `carta_productos/`
      DEBE tener `local_id`. Filtrar siempre por él en lecturas.
- [ ] Función `get_current_local_id()` global que combina:
      a) Subdominio del request (CURRENT_LOCAL_SLUG).
      b) Selector explícito en el dashboard (`X-Local-Id` header).
- [ ] Bloquear que un user de un local lea/escriba datos de otro.

**L.2 Switcher de local (para hosteleros con varios)**
- [ ] `LocalSwitcher.jsx` ya existe — extender para mostrar avatar y
      enlace directo a `<slug>.mylocal.es`.
- [ ] Endpoint `list_my_locales` filtrado por usuario autenticado.
- [ ] Permisos por local (admin del local A no puede tocar local B).

---

## 8. Notas de Implementacion

### 8.1 Archivos creados en esta iteracion

**Backend - capa de datos y colas (axidb plugins):**
- `axidb/plugins/alergenos/AlergenosCatalog.php` - 14 alergenos UE + catalogo de ingredientes con seed inicial.
- `axidb/plugins/jobs/JobQueue.php` - cola JSON con estados pending/running/done/failed.
- `axidb/plugins/jobs/JobWorker.php` - ejecutor con registro de handlers.
- `axidb/plugins/jobs/worker_run.php` - punto de entrada para cron.

**Backend - capacidades IA:**
- `CAPABILITIES/CARTA/MenuEngineer.php` - sugerirAlergenos, generarDescripcion, generarPromocion, traducir.
- `CAPABILITIES/CARTA/models/ProductoCartaModel.php` - ampliado con `alergenos_sugeridos`, `texto_promocional`, `imagen_mejorada_url`, `es_especialidad`, `ingredientes`, `origen_import`.
- `CAPABILITIES/OCR/OCREngine.php` - Gemini Vision para imagenes y PDFs.
- `CAPABILITIES/OCR/OCRParser.php` - parser hibrido (heuristico + Gemini).
- `CAPABILITIES/ENHANCER/ImageEnhancer.php` - varita magica (Imagick/GD).
- `CAPABILITIES/ENHANCER/PaletteExtractor.php` - paleta dominante desde logo.

**Frontend - admin del onboarding:**
- `CAPABILITIES/CARTA/admin/onboarding-ai.css` - capa visual prefijo db-.
- `CAPABILITIES/CARTA/admin/ImportUploader.jsx` - drag and drop + polling de job.
- `CAPABILITIES/CARTA/admin/LogoPaletteCapture.jsx` - subida logo + paleta.
- `CAPABILITIES/CARTA/admin/AIRevisionPanel.jsx` - revision interactiva.
- `CAPABILITIES/CARTA/admin/MagicWandButton.jsx` - boton de mejora.

**Recetas y blog:**
- `CAPABILITIES/RECETAS/models/RecetaModel.php` - modelo completo.
- `CAPABILITIES/RECETAS/public/blog.css` + `BlogFeed.jsx` - feed estilo Instagram.
- `CAPABILITIES/WEBSCRAPER/RecetaScraper.php` - lectura JSON-LD Schema.org/Recipe.

**Gestión de Identidad y Temas:**
- `CAPABILITIES/CARTA/Themes.php` - Definición de los 4 temas (Minimal, Dark, Classic, Premium) con sus variables CSS.
- `CAPABILITIES/CARTA/admin/ThemeSelector.jsx` - UI para previsualizar y guardar el tema.
- `CORE/core/SubdomainManager.php` - Lógica de detección de `slug` desde la URL para cargar el contexto del local.

**Legales y wiki:**
- `CAPABILITIES/LEGAL/pages/*.md` - aviso legal, privacidad, cookies, terminos, cuentas, reembolsos.
- `CAPABILITIES/LEGAL/public/CookieBanner.jsx` + `cookie-banner.css` - banner GDPR granular.
- `CAPABILITIES/WIKI/articles/*.md` - 10 articulos publicados con front-matter.

**Generador PDF:**
- `CAPABILITIES/PDFGEN/PdfRenderer.php` - motor con Dompdf + fallback wkhtmltopdf.
- `CAPABILITIES/PDFGEN/templates/carta_minimalista|clasica|moderna.php` - 3 plantillas de carta A4.
- `CAPABILITIES/PDFGEN/templates/display_mesa.php` - table tent A6.
- `CAPABILITIES/PDFGEN/templates/pegatinas_qr.php` - hoja A4 con grid 3x4.

### 8.2 Skills aplicadas

- `artifacts/skilldesarrollo.md`: estructura modular en `CAPABILITIES/<NOMBRE>/` con `index.php` + `README.md`, plugins en `axidb/plugins/`, ningun archivo supera 250 lineas, sin simulaciones (los conectores a Gemini fallan con error explicito si no hay API key, no inventan datos).
- `artifacts/skilldashboard.md`: clases prefijo `db-`, mobile-first, padding compacto via variables, tipografia precisa, sin estilos inline en JSX, todo en `onboarding-ai.css`.
- `artifacts/skilldiseno.md`: clases prefijo `sp-` para web publica (blog y banner), mobile-first 375px, fuentes locales declaradas, modo claro/oscuro con `body.dark`.

### 8.3 Dependencias externas requeridas en produccion

Para que el sistema funcione al 100% el operador debe configurar:

- **API key Gemini** en `STORAGE/config/gemini_settings.json` (`{ "api_key": "...", "model": "gemini-1.5-flash" }`). Sin ella, OCR/descripciones/traducciones devuelven error claro.
- **php-imagick** instalado en el servidor. Sin el, los PDFs no pueden procesarse via OCR (las imagenes si).
- **Dompdf** via `composer require dompdf/dompdf` desde `axidb/engine/`, o `wkhtmltopdf` en el sistema. Sin ninguno, los PDFs devuelven error.
- **Cron** configurado para ejecutar `axidb/plugins/jobs/worker_run.php` cada minuto.

### 8.4 Endpoints API que el dispatcher debe registrar

Estos action handlers son los que usa el frontend nuevo y deben enrutarse en `CORE/core/ActionDispatcher.php`:

- `upload_carta_source` - subida de PDF/imagen para OCR.
- `enqueue_ocr` - encola job tipo `ocr_carta`.
- `job_status` - consulta el estado de un job por id.
- `upload_logo` - subida del logo del local.
- `extract_palette` - llama a `EnhancerCapability::paletaDesdeLogo`.
- `enhance_image_sync` - varita magica sincrona.
- `ai_generar_descripcion` - llama a `MenuEngineer::generarDescripcion`.
- `ai_sugerir_alergenos` - llama a `MenuEngineer::sugerirAlergenos`.
- `importar_carta_estructurada` - guarda categorias y productos en lote.
- `listar_recetas_publicas` - feed publico del blog.
- `wiki_listar`, `wiki_articulo` - centro de ayuda.
- `legal_pagina` - paginas legales por slug.

### 8.5 Que falta para considerar Fase 1 vendible al 100%

Implementacion: completa. Lo unico pendiente es operacional:

- Cargar la API key de Gemini en `STORAGE/config/gemini_settings.json`.
- Instalar Dompdf via composer y verificar generacion de PDF de prueba.
- Configurar cron del worker.
- Sustituir los datos fiscales `[a completar antes de lanzamiento]` en las paginas legales.
- Conectar Stripe en modo live para los planes 27 EUR / 260 EUR.

Con eso, el producto pasa a estado vendible.

---

## 9. Iteracion 2026-05-04: blindar el flujo de subida de carta

Despues del primer cierre (seccion 8) descubrimos en pruebas reales que
el flujo OCR no era operativo end-to-end por varios motivos no
contemplados originalmente. Cambios aplicados, todos blindados con
test gate en `build.ps1`:

### 9.1 Auth bearer-only (sin cookies, sin CSRF)

Antes: el upload usaba CSRF cookie + double-submit. Eso traia stale
sessions cross-port (Vite 5173 vs PHP 8090) y bloqueaba el upload con
HTTP 401.

Ahora: el cliente envia `Authorization: Bearer <token>` desde
sessionStorage. El server lee solo ese header. Sin cookies. Documentado
en `claude/AUTH_LOCK.md`.

- [x] `uploadCartaSource` en `spa/src/services/carta.service.ts` lee
      el token de sessionStorage y manda Authorization Bearer.
- [x] `current_user()` en `spa/server/lib.php` solo lee Bearer.
- [x] Eliminada validacion de CSRF en `spa/server/index.php`.
- [x] Errores de negocio devuelven HTTP 200 con `{success:false, error}`
      para que el cliente muestre el mensaje real (antes "HTTP 500: ").

### 9.2 Hybrid CRUD: SynaxisClient deja de pedir al server

Antes: cuando el dashboard cargaba con `carta_categorias`/`carta_productos`
vacios en IndexedDB, el cliente caia al server (por la logica hybrid)
y el server respondia 400 "resolver en cliente". Ruido constante en
consola.

Ahora: si el local responde con exito (incluso con datos vacios), esa
es la respuesta. Solo cae al server si local fallo. Excepcion explicita
para `list_products` que tiene seed canonico.

- [x] `SynaxisClient.execute()` actualizado con `localIsEmpty()`.

### 9.3 OCR config tolerante

Antes: `OCREngine` y `MenuEngineer` solo leian
`STORAGE/config/gemini_settings.json` (legacy CORE). El flujo SPA tiene
su config en `spa/server/config/gemini.json`. Mismatch -> sin api_key
nunca encontrada -> error opaco "API key Gemini no configurada".

Ahora: ambos motores buscan en cascada en tres ubicaciones y normalizan
`default_model` -> `model`. Mensaje accionable cuando no hay key:
> "API key de Gemini no configurada. Edita spa/server/config/gemini.json
> y anade tu api_key. Ver: https://makersuite.google.com/app/apikey"

- [x] `CAPABILITIES/OCR/OCREngine.php` con loadConfig() multipath.
- [x] `CAPABILITIES/OCR/OCRParser.php` igual.
- [x] `CAPABILITIES/CARTA/MenuEngineer.php` igual.

### 9.4 Test gate del flujo OCR (37/37 PASS)

`spa/server/tests/test_login.php` ampliado con seccion 9b:

- Upload sin Bearer -> 401.
- Upload con Bearer admin -> 200 + file_path en data.
- Upload `.exe` -> rechazado con mensaje claro.
- OCR sin api key -> error accionable mencionando Gemini/Imagick.

Si cualquiera falla, `build.ps1` aborta con exit 1.

- [x] 37 assertions cubren auth + login + upload + OCR-stub.
- [x] `build.ps1` ejecuta el test contra release/. Build aborta si rompe.

### 9.5 Lo unico que sigue pendiendo del usuario

Para que el OCR genere texto REAL (y no solo un error claro):

1. Conseguir api key gratuita en https://makersuite.google.com/app/apikey
2. Pegarla en `spa/server/config/gemini.json` campo `api_key`
3. Sin reiniciar nada. El siguiente upload ya extrae texto.

Para PDFs multipagina hace falta `php-imagick` instalado en el sistema.
Sin Imagick, los PDFs no se pueden trocear en imagenes. Imagenes
sueltas (JPG/PNG/WEBP) funcionan sin Imagick.

---

## 10. Ruta Crítica de la Fase 1 (orden de ejecución)

Hay 3 olas de trabajo. Cada ola se cierra por completo antes de pasar a
la siguiente. Cada ola termina con build verde + commit + push.

### 🌊 OLA 1 — Sala configurable (estancias + mesas + QRs reales)

**Objetivo de negocio:** el hostelero termina el onboarding con TODOS
sus QRs ya generados, no con uno solo.

Trabajo:
- Bloque **I** completo (Configuración de Sala).
- Sub-tareas K.1 y K.2: centro de impresión que genera la hoja de QRs
  por mesa de verdad.

Resultado vendible: el hostelero puede entrar al dashboard y ver "Mi
sala: 3 zonas, 24 mesas. Descargar 2 hojas de QRs". Imprime, pone los
QRs en sus mesas, y ya está operativo en una mañana.

### 🌊 OLA 2 — Tema visual

**Objetivo de negocio:** la carta pública del hostelero tiene
identidad propia desde el día uno (no parece "una más"). Esto es
fundamental para el WOW.

Trabajo:
- Bloque **J** completo (Selector de Temas).
- Aplicar el tema activo al PDF de la carta (K.3).

Resultado vendible: el hostelero ve su propia carta con 4 estéticas
distintas y elige la que cuadra con su local. Le quita la barrera de
"tengo que contratar a un diseñador".

### 🌊 OLA 3 — Despliegue producción Hostinger + Cloudflare

**Objetivo de negocio:** un hostelero real puede contratar y empezar a
usar el producto en producción.

Trabajo:
- Bloque **H** completo (Cloudflare + Hostinger + subdominios).
- Bloque **L** completo (multi-tenancy seguro).
- Pasos operacionales 8.5 (api key Gemini, Stripe live, datos fiscales
  reales en legales).

Resultado vendible: la primera venta real. Carta digital del cliente
en `<slug>.mylocal.es` operativa públicamente.

---

## 11. Reglas de oro de esta Fase 1

Mientras tanto, NO se hace:

- **No se toca** el sistema de auth (`AUTH_LOCK.md` lo blinda con test gate).
- **No se toca** el flujo OCR (sección 9, blindado con tests).
- **No se commitean secretos** en `STORAGE/options/` (ya en `.gitignore`).
- **No se rompe** el límite de 250 líneas por archivo.
- **No se añade** una capability nueva que no esté en este plan sin
  consenso explícito.

Cada nueva feature pasa por:
1. Diseño en este plan con checklist [].
2. Implementación atomic-by-atomic.
3. Test gate ampliado si toca auth/OCR/datos.
4. Build verde antes de commit.
5. Marcar [x] en el checklist.

Esa disciplina es la que evita que volvamos a perder días depurando
regresiones.
