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

### I. Configuración de Sala (Estancias y Mesas) ✅ OLA 1 CERRADA

**I.1 Modelo de datos**
- [x] Crear `CAPABILITIES/QR/MesaModel.php` (CRUD mesa + validaciones).
- [x] Crear `CAPABILITIES/QR/ZonaModel.php` (CRUD zona/estancia).
- [x] Crear `CAPABILITIES/QR/QrTokenGenerator.php` (`bin2hex(random_bytes(8))` por mesa).
- [x] **Cambio post-feedback**: URL pública por mesa pasa a ser **`/carta/<zona-slug>/mesa-<numero>`** (identificativa y amigable, no token opaco). El token 16-hex se conserva en BD para futuro modo "pedidos por mesa".

**I.2 Bootstrap minimal en lugar de wizard de 4 pasos** (cambio de diseño)
- [x] **Decision UX post-feedback usuario**: el wizard de presets se descartó porque añadía fricción. En lugar: el server bootstrapea **1 zona "Sala" + 1 mesa "1"** automáticamente la primera vez que se llama a `sala_resumen`.
- [x] El usuario edita directo: añadir estancias, renombrar inline, borrar.
- [x] `SalaWizard.tsx` original presets rápidos sigue en código como referencia pero no se monta en el flujo activo.

**I.3 Vista de gestión continua**
- [x] Componente `spa/src/components/sala/SalaMapa.tsx` (renombrado de `MesaCanvas.jsx`) con vista por zonas.
- [x] Acciones por mesa: renombrar, capacidad, regenerar QR, eliminar.
- [x] Edición inline de zonas: click en nombre para renombrar (Enter/Escape), botón × para borrar (cascada en mesas).
- [x] Botón "+ Estancia" siempre visible.
- [ ] Estado en tiempo real (libre/pidiendo/esperando/pagada) — pendiente de TPV completo.
- [ ] Importar/exportar mesas en CSV — pendiente, no es prioridad para Ola 1.

**I.4 Acciones server**
- [x] `create_zona`, `update_zona`, `delete_zona`, `list_zonas`, `reorder_zonas`, `create_zonas_preset`.
- [x] `create_mesas_batch` para crear N mesas de una zona en una sola llamada.
- [x] `regenerate_mesa_qr` (cambia el token, útil si alguien copió un QR).
- [x] `sala_resumen` con bootstrap idempotente.

**I.5 Configuración del establecimiento** (añadido al cerrar Ola 1)
- [x] `spa/server/handlers/local.php` con CRUD del local (nombre, teléfono, dirección, web, instagram, tagline).
- [x] Acciones `get_local`, `update_local` registradas en dispatcher.
- [x] Tarjeta editable inline en `SalaMapa.tsx`: nombre + teléfono + URL pública con botón copiar.

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

### K. Centro de Impresión (Carta + QRs + Displays) — parcialmente hecho en Ola 1

**K.1 UI agrupada** — pendiente como panel unificado
- [ ] `CAPABILITIES/PDFGEN/admin/PrintCenterPanel.jsx` con 3 tarjetas
      (Carta física / QRs por mesa / Displays de mesa).
- [x] **Por separado ya existe**: botón "Imprimir QRs" + "QR principal del local" en `SalaMapa.tsx`.
- [ ] Selector de plantilla en cada tarjeta (Minimal/Clásica/Moderna).
- [ ] Botón "Descargar PDF" que llama al server y baja el archivo.
- [ ] Indicador de progreso para PDFs grandes (>2 MB).

**K.2 Acciones server / generación de QRs**
- [x] **`generate_qr_sheet` resuelto client-side**: `SalaQrSheet.tsx` con `qrcode.react` genera A4 con grid 2 columnas, agrupado por zona, 1 página por zona. Imprime con `window.print()` (PDF nativo del navegador). **No hay leak de tokens** (vs. la versión antigua que mandaba a `api.qrserver.com`).
- [x] **`LocalQrPoster.tsx` (extra al plan)**: poster A4 marketing con nombre del local enorme, QR central con frame negro, teléfono prominente, instagram/web si configurados. Pensado para escaparate/barra.
- [ ] `generate_table_tents` - PDF multi-página, un display A6 por mesa. Reusa `display_mesa.php`.
- [ ] `generate_full_carta` - ya existe `generate_pdf_carta`. Verificar que respeta el tema activo del local (paleta del logo).

**K.3 Calidad imprenta**
- [ ] Sangrado de 3mm en todos los PDFs imprimibles.
- [ ] CMYK opcional vía conversión Imagick (cuando esté disponible).
- [ ] Marcas de corte en hojas multi-elemento (pegatinas).
- [x] Densidad de QR adecuada: SalaQrSheet usa 180px, LocalQrPoster 420px (cómodo a distancia).

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

### M. Dashboard completo (zona de gestión del hostelero)

**M.1 Layout y navegación**
- [ ] `spa/src/pages/Dashboard.tsx` con sidebar fijo + header sticky.
- [ ] Tabs principales: **Carta** / **Mesas** / **Pedidos** / **Configuración** / **Facturación** / **Cuenta**.
- [ ] Breadcrumbs en cada subpágina.
- [ ] Indicador de plan activo (Demo / Pro mensual / Pro anual) siempre visible.
- [ ] Botón "Ver mi carta pública" → abre `<slug>.mylocal.es/carta` en nueva pestaña.

**M.2 Configuración del local**
- [ ] Pantalla `Configuración → General`: nombre, logo, slug, tipo de negocio, descripción corta.
- [ ] Pantalla `Configuración → Identidad`: paleta auto + tema (delegado a bloque J).
- [ ] Pantalla `Configuración → Idiomas`: switches ES/EN/FR/DE + autotraduce existente.
- [ ] Pantalla `Configuración → Horarios`: rangos por día de semana (desayuno/almuerzo/cena).
- [ ] Pantalla `Configuración → Datos fiscales`: NIF, razón social, dirección (necesario para Stripe live).
- [ ] Pantalla `Configuración → Equipo`: lista de usuarios con roles (admin/editor/sala/cocina/camarero) e invitar nuevos.

**M.3 Vista pedidos en tiempo real (sin TPV completo aún)**
- [ ] Pantalla `Pedidos`: lista de mesas con estado actual (libre/pidiendo/esperando/pagada).
- [ ] Filtros por zona y estado.
- [ ] Click en mesa → detalle del pedido con líneas y totales.
- [ ] Polling cada 3s o WebSocket si está disponible (futuro).

**M.4 Notificaciones internas**
- [ ] Componente `NotificationBell.jsx` en el header del dashboard.
- [ ] Tipos: pedido nuevo, pago recibido, alerta IA (stock bajo, plato sin foto).
- [ ] Persisten en `STORAGE/notifications/<user_id>/<id>.json`.
- [ ] Badge con contador de no leídas.

### N. Suscripción, Facturación y Mi Cuenta

**N.1 Mi cuenta**
- [ ] Pantalla `Cuenta → Perfil`: cambiar email, nombre, foto.
- [ ] Pantalla `Cuenta → Contraseña`: cambio con verificación de la actual.
- [ ] Pantalla `Cuenta → Sesiones activas`: dispositivos conectados, botón cerrar sesión remota.
- [ ] Pantalla `Cuenta → Cerrar cuenta` con doble confirmación (ya documentado en wiki art. 10).

**N.2 Suscripción**
- [ ] Pantalla `Facturación → Mi plan`: muestra plan actual, próxima renovación, botón cambiar plan.
- [ ] Comparativa visual Mensual (27€) vs Anual (260€, ahorra 20%).
- [ ] Acción "Cambiar a anual" con cálculo de prorrateo.
- [ ] Acción "Cancelar plan" con flujo de retención (motivo de cancelación, oferta de descuento, confirmar).
- [ ] Cuenta atrás visible para usuarios en demo (días restantes).

**N.3 Facturas**
- [ ] Pantalla `Facturación → Histórico`: tabla con todas las facturas emitidas.
- [ ] Botón descargar factura individual en PDF.
- [ ] Botón descargar año completo en ZIP.
- [ ] Email automático cuando se emite una factura nueva.

**N.4 Métodos de pago**
- [ ] Pantalla `Facturación → Métodos de pago`: tarjetas guardadas via Stripe.
- [ ] Añadir/quitar tarjeta sin pasar por el flujo de checkout completo.
- [ ] Tarjeta por defecto marcada con badge.

**N.5 Stripe (mock local en esta ola)**
- [ ] `CAPABILITIES/PAYMENT/StripeAdapter.php` con sandbox keys.
- [ ] Webhooks recibidos en `/acide/index.php` action `stripe_webhook`.
- [ ] Persistencia en `STORAGE/billing/<local_id>/...` con factura, plan, método.
- [ ] **NO live keys hasta la Ola 8**.

### O. Diseño profesional y UX

**O.1 Auditoría pantalla por pantalla**
- [ ] Hacer captura de cada pantalla del dashboard y compararla con
      Last.app, Qamarero y Honei. Marcar lo que estamos peor.
- [ ] Lista de mejoras priorizada por impacto (alto/medio/bajo).
- [ ] Aplicar las de impacto alto. Documentar las medias para Fase 2.

**O.2 Microcopys revisados**
- [ ] Ningún botón dice "Submit", "OK" o "Guardar" genérico — usar verbo + objeto ("Crear plato", "Confirmar pago").
- [ ] Mensajes de error en castellano humano (sin "Error 500"). Si es técnico, esconder detalle y ofrecer "Reintentar" o "Contactar soporte".
- [ ] Mensajes de éxito breves y celebrativos ("Tu carta está online" en vez de "Operación exitosa").

**O.3 Estados de carga**
- [ ] Reemplazar spinners genéricos por **skeleton screens** en listas (categorías, productos, pedidos).
- [ ] Botones con loading inline (texto del botón cambia + spinner pequeño).
- [ ] Optimistic updates en CRUD: si el usuario crea un plato, aparece inmediatamente en la lista mientras se guarda en background.

**O.4 Consistencia visual**
- [ ] Auditoría de uso de variables CSS: ningún color literal `#XXXXXX` en componentes.
- [ ] Auditoría de tipografía: 4 tamaños máximo, 3 weights máximo en todo el dashboard.
- [ ] Auditoría de espaciado: solo valores del sistema (`--db-gap-*`).

**O.5 Animaciones y micro-interacciones**
- [ ] Transiciones en tabs, modales, dropdowns (max 150ms, ease-out).
- [ ] Feedback táctil en clics importantes (botón se hunde 1px).
- [ ] Sin animaciones decorativas (no parallax, no efectos llamativos).

### P. Responsive (mobile + tablet + desktop)

**P.1 Audit por breakpoint**
- [ ] Cada pantalla del dashboard probada en **375px** (iPhone SE).
- [ ] Cada pantalla probada en **768px** (iPad portrait).
- [ ] Cada pantalla probada en **1280px** (laptop estándar).
- [ ] Sin scroll horizontal en ningún tamaño.

**P.2 Touch targets**
- [ ] Botones e iconos clicables ≥ **44px** de altura en móvil.
- [ ] Tap targets con padding suficiente (no botones pegados).
- [ ] Inputs con altura ≥ 44px en móvil para evitar zoom auto de iOS.

**P.3 Sidebar y navegación**
- [ ] En móvil el sidebar se oculta detrás de un botón hamburguesa.
- [ ] El header pasa a sticky con info crítica condensada.
- [ ] Tabs internas se hacen scroll horizontal en pantallas estrechas.

**P.4 Tablas y formularios**
- [ ] Tablas largas → cards apiladas en móvil (no scroll horizontal).
- [ ] Formularios → labels arriba (no a la izquierda) en móvil.
- [ ] Modales → bottom-sheet en móvil, centrados en desktop.

**P.5 Pruebas con clientes finales reales**
- [ ] 3-4 testers (no técnicos) escanean QR de mesa con su móvil real.
- [ ] Tiempo medio para añadir un plato al carrito y "pagar" (mock): < 60s.
- [ ] Tiempo de carga de la carta pública (red 3G simulada): < 3s.

### Q. SEO y carta pública optimizada

**Q.1 Meta tags por subdominio**
- [ ] `<title>` dinámico: "Carta de [Nombre del local] - MyLocal".
- [ ] `<meta description>` con descripción del local + 3 platos destacados.
- [ ] `<meta property="og:image">` con foto del logo o foto destacada.
- [ ] `<link rel="canonical">` apuntando a `<slug>.mylocal.es/carta`.

**Q.2 Schema.org structured data**
- [ ] `Restaurant` schema con dirección, teléfono, horarios.
- [ ] `Menu` schema con `MenuSection` por categoría y `MenuItem` por plato.
- [ ] Validar con Google Rich Results Test (debe parsear sin errores).

**Q.3 Sitemap y robots**
- [ ] `sitemap.xml` dinámico que lista todos los subdominios activos
      (uno por local).
- [ ] `robots.txt` permite indexación de cartas públicas, prohíbe
      `/dashboard`, `/sistema`, `/acide`.

**Q.4 Performance (Web Vitals)**
- [ ] **LCP** (Largest Contentful Paint) < 2.5s en mobile 3G.
- [ ] **CLS** (Cumulative Layout Shift) < 0.1.
- [ ] **INP** (Interaction to Next Paint) < 200ms.
- [ ] Lighthouse mobile score ≥ 90 en una carta real.

**Q.5 Pre-carga inteligente**
- [ ] `<link rel="preload">` para fuentes locales.
- [ ] Imágenes `loading="lazy"` excepto las del primer viewport.
- [ ] WebP/AVIF cuando el navegador los soporta.

### R. Agnosticismo del código (rutas relativas, no absolutas)

**R.1 Auditoría de rutas absolutas**
- [ ] grep `'/acide/` en todo `spa/src/` → todas via `SynaxisClient.apiUrl`
      (configurable, no hardcoded).
- [ ] grep `'/MEDIA/` en JSX → ninguna ruta absoluta directa, todas
      relativas (`./MEDIA/`) o servidas vía router.
- [ ] grep `http://` y `https://` → solo URLs externas explícitas
      (apis de Gemini, Stripe).
- [ ] No usar `window.location.origin + '/...'` para enlaces internos.

**R.2 Vite base config**
- [ ] `vite.config.ts` con `base: './'` (relativo) o configurable via
      `VITE_BASE_PATH` env var.
- [ ] Build genera `index.html` con `<script src="./assets/...">`
      (no `/assets/...`).
- [ ] Verificación: copiar `release/` a un subdirectorio aleatorio y
      comprobar que carga sin tocar nada.

**R.3 Router compatible con cualquier mount**
- [ ] HashRouter actual mantiene URLs `/#/dashboard` que funcionan en
      cualquier ruta.
- [ ] Probar montar el SPA en `/app/`, `/cliente-x/`, raíz `/`. Tres
      mounts distintos, mismo build.

**R.4 PHP backend agnóstico**
- [ ] `router.php` usa `__DIR__` en todos los includes (no rutas absolutas).
- [ ] No hay paths hardcoded a `/home/<user>/...` en ningún sitio.
- [ ] Probar mover el `release/` a otra carpeta y arrancar PHP — debe funcionar.

### S. Pre-producción (test e2e local)

**S.1 Test gate ampliado**
- [ ] `spa/server/tests/test_login.php` cubre login + OCR (ya hecho).
- [ ] Nuevo `test_dashboard.php` cubre: crear local, configurar mesas,
      cambiar tema, generar PDFs, gestionar suscripción mock.
- [ ] Total: > 80 assertions de gate.
- [ ] Build aborta si cualquiera falla.

**S.2 Pruebas con usuarios reales (en local)**
- [ ] 3 hosteleros amigos hacen el onboarding completo en local sin ayuda.
- [ ] Métrica: tiempo medio < 10 minutos del registro al QR descargado.
- [ ] Métrica: 0 momentos de "no sé qué hacer aquí".
- [ ] Recoger feedback y aplicar las 3 mejoras más impactantes.

**S.3 Datos de prueba realistas**
- [ ] Seed de un local "demo" con 30 productos, 5 categorías, 3 zonas, 12 mesas.
- [ ] Datos limpios, sin Lorem Ipsum.

**S.4 Documentación operativa**
- [ ] `docs/DEPLOY.md` con el procedimiento exacto para Ola 8.
- [ ] `docs/RUNBOOK.md` con incidencias frecuentes y cómo resolverlas.
- [ ] `docs/BACKUP.md` con cómo hacer backup de `STORAGE/` en Hostinger.

**S.5 Plan de rollback**
- [ ] Si algo falla en producción, qué pasos seguir para volver atrás.
- [ ] Versionado de releases (git tag por cada despliegue).
- [ ] Backup automático de `STORAGE/` cada 24h en Hostinger.

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

## 9.6 Iteración 2026-05-05: refactor LOGIN a CAPABILITIES/LOGIN

Tras varias regresiones de auth durante el desarrollo de Ola 1, se
extrae todo el flujo de login a una capability bloqueada para que las
features futuras lo importen en lugar de tocar `handlers/auth.php`.

**Estructura**: `CAPABILITIES/LOGIN/` con 8 archivos PHP + README +
capability.json. Configuración en `CAPABILITIES/OPTIONS/optionsLogin.php`,
`optionsLoginRoles.php`, `optionsLoginPermissions.php`.

- [x] `LoginCapability` (fachada pública: authenticate, resolveUser, requireRole, logout, rateLimit, safe*).
- [x] `LoginPasswords` (Argon2id + dummy hash + policy + needs_rehash).
- [x] `LoginSessions` (issue / resolve / revoke bearer + UA fingerprint).
- [x] `LoginRoles` (requireRole + glob match contra optionsLoginPermissions).
- [x] `LoginRateLimit` (rl_check con buckets en STORAGE/data/_rl).
- [x] `LoginVault` (findByEmail / findById / upsert / patch en data/users).
- [x] `LoginBootstrap` (auto-seed de los 4 default users).
- [x] `LoginSanitize` (s_id / s_email / s_str / s_int).
- [x] `optionsLogin*.php` con defaults no-secretos (TTL, argon2, password policy, roles, permisos).
- [x] `spa/server/handlers/auth.php` reducido a 4 wrappers de 1 línea + 2 shims CLI (165 → 50 líneas).
- [x] `spa/server/lib.php` reducido a data/resp/http_json + shims (347 → 120 líneas).
- [x] `spa/server/bin/bootstrap-users.php` reducido a wrapper de `LoginBootstrap::run()` (65 → 25 líneas).
- [x] `claude/AUTH_LOCK.md` actualizado con tabla 3.a (capability) + 3.b (dispatchers delgados).
- [x] Test gate ampliado: 50 → 64 assertions (chequeo de 11 archivos LOGIN/OPTIONS + denegación 403 por rol).

Resultado: cualquier feature nueva importa `\Login\LoginCapability` y no
toca `auth.php` ni `lib.php`. Las regresiones de login se cierran de raíz.

## 9.7 Iteración 2026-05-05: URLs friendly + BrowserRouter

Tras feedback de uso real, se cambian las URLs del SPA y de los QRs:

- [x] `HashRouter` → `BrowserRouter` en `main.tsx`. URLs limpias (`/dashboard` en vez de `/#/dashboard`).
- [x] `LoginModal.tsx`: redirección post-login con `window.location.assign` (sin hash).
- [x] Routes nuevas en App.tsx: `/carta`, `/carta/:zonaSlug`, `/carta/:zonaSlug/:mesaSlug`.
- [x] `buildLocalCartaUrl()` → `/carta` (QR default del local, todas las mesas comparten URL).
- [x] `buildMesaUrl(mesa, zonaNombre)` → `/carta/<zona-slug>/mesa-<numero>` (modo avanzado por mesa).
- [x] `slugify()` en `sala.service.ts` (quita acentos, baja a minúsculas, no-alfanumérico → `-`).
- [x] `Carta.tsx` reescrita: lee `carta_productos` + `carta_categorias` (era `products` legacy vacío). Muestra nombre del local en header. Empty state amable con link al panel.

## 9.8 Iteración 2026-05-06: pipeline OCR multi-engine + servidor IA propio

GestasAI implementa cascada `Tesseract → Gemma 4 vision → Gemini` en el
backend, con un servidor propio en `ai.miaplic.com` (llama.cpp + Gemma 4 E2B).

**Arquitectura**:
- [x] `CAPABILITIES/AI/AIClient.php` — cliente OpenAI-compatible (llama.cpp/vLLM/Ollama).
- [x] `CAPABILITIES/OCR/OCREngine.php` reescrito con cascada: Tesseract local (≥50 chars) → IA local (`ai.miaplic.com` con Gemma 4) → Gemini cloud (fallback final).
- [x] Acción server unificada `ocr_import_carta`: upload + OCR + parse en una sola llamada (antes era cadena de 3).
- [x] `CartaImportWizard.tsx` simplificado: `importCartaFromFile(file)` → backend → JSON estructurado.
- [x] `humanizeError()` ampliado: detecta "ambos motores fallan", "poppler/imagick falta", 429/quota, network, timeout, 500.
- [x] `set_time_limit(360s)` en `carta.php` para PDFs largos multi-página.
- [x] Conexión directa a `local_extract_url` (evita timeout del proxy intermedio).
- [x] OPTIONS namespace `ai.*` con `local_endpoint`, `local_api_key`, `local_model` (gitignored, en `STORAGE/options/ai.json`).

**Pruebas reales contra `ai.miaplic.com`**:
- [x] Vision simple: 7-11s por imagen.
- [x] Extracción JSON estructurada: 17-22s por página.
- [x] Calidad: 9/9 platos extraídos correctamente con PDF a 1656×2342 px.
- [x] Calidad imagen baja: 360×360 → modelo no lee (límite físico, no bug). Recomendación de validación frontend pendiente.

## 9.9 Iteración 2026-05-07: UX del review tras OCR

- [x] Botones "Guardar" / "Cancelar" movidos al header del paso review (siempre visibles, no escondidos tras scroll de la tabla).
- [x] "Importar carta" renombrado a "Guardar" (más claro y consistente).
- [x] Estilos `.db-review-head` + `.db-review-actions` en `db-styles.css`.

---

## 10. Ruta Crítica de la Fase 1 (orden de ejecución)

**Filosofía:** construir TODO en local hasta que el producto sea
profesional, agnóstico, modular y pulido. Subir a producción es la
ÚLTIMA ola. Subir antes nos retrasa porque cada bug descubierto en
producción es 10x más caro de arreglar que en local.

**Disciplina de cierre** (actualizado 2026-05-08):
- Cada ola se cierra al 100% antes de pasar a la siguiente.
- Si un ítem depende de una ola futura, se MUEVE a esa ola en el plan,
  no se deja "pendiente con asterisco".
- Si una decisión arquitectural de una ola futura ayuda a la actual,
  se aplica AHORA para no rehacer trabajo después.
- Build verde + test gate + commit + push antes de marcar una ola cerrada.

Cada ola lleva:
- **Objetivo**: para qué sirve.
- **Bloques**: secciones del checklist 7.
- **Criterios de salida**: lista exacta de lo que debe estar marcado [x]
  antes de declararla cerrada. Esto es el contrato.

---

### 🌊 OLA 1 — Sala configurable (estancias + mesas + QRs + local) ✅ CERRADA

**Objetivo:** el hostelero configura su local, zonas y mesas. Tiene QRs.

**Bloques:** I (completo), K.1+K.2 (parciales).

**Criterios de salida:** ✅
- [x] CRUD de zonas y mesas server-side (AxiDB persistente).
- [x] Bootstrap minimal automático (1 zona "Sala" + 1 mesa "1" al crear).
- [x] SalaMapa con edición inline (añadir/renombrar/borrar zonas).
- [x] Datos del local persistidos (nombre, teléfono, dirección, redes).
- [x] URLs friendly `/carta/<zona>/mesa-<n>`.
- [x] Póster A4 marketing del QR principal con logo MyLocal por defecto.
- [x] Hoja A4 con QRs por mesa imprimible.

**Movido a Olas posteriores:**
- Estado mesa en tiempo real (libre/pidiendo/...) → **Ola 3 (Pedidos)**.
- Importar/exportar mesas CSV → **Ola 3 (Configuración avanzada)** o drop.

---

### 🌊 OLA 2 — Carta digital pública (Web + PDF) ✅ CERRADA

**Objetivo:** el hostelero elige plantilla y color de su carta digital.
Lo que ve en preview es lo que sale en PDF y en /carta cuando el cliente
escanea el QR.

**Bloques:** J completo (ampliado/sustituido), K.2 cerrar, K.3 completo.

**Criterios de salida:** ✅
- [x] 3 plantillas Web (moderna/minimal/premium) × 3 colores (claro/oscuro/blanco roto).
- [x] 3 plantillas PDF (minimalista/clásica/moderna) × 5 colores.
- [x] Imagen del local subible (`/MEDIA/local/<id>/`) con defaults bonitos.
- [x] Auto-save en panel Web.
- [x] Carta pública `/carta/...` renderiza con plantilla y color elegidos.
- [x] Lecturas públicas sin sesión (`list_productos`, `get_local`, etc).
- [x] **`generate_pdf_carta` backend usa el local del server** (lee `web_template`,
      `web_color`, `imagen_hero`, telefono, tagline, copyright automáticamente).
- [x] **3 plantillas PDF reescritas** con paleta CSS por color, logo del local
      en header, footer con teléfono + copyright.
- [x] **Sangrado 3mm** vía opción `bleed_mm` en PdfRenderer + márgenes de
      seguridad en cada plantilla.
- [x] **`SalaTableTents`** — A6 por mesa client-side con `qrcode.react` +
      `window.print()` (cero leak de tokens, mismo patrón que SalaQrSheet).
- [x] **`LocalConfigCard`** — editor inline de TODOS los campos del local
      (nombre, tagline, teléfono, dirección, email, web, instagram, facebook,
      tiktok, whatsapp, copyright) con auto-save al perder foco.
- [x] Test gate **64 → 71 assertions**: bootstrap_local, web_template/web_color
      persistidos, whitelist rechaza valores inválidos, jerarquía atómica
      carta→categoría→producto, lecturas públicas sin Bearer, escrituras
      protegidas.

---

### 🌊 OLA 3 — Dashboard completo (Configuración + Pedidos + Cuenta + Facturación) ✅ CERRADA

**Objetivo:** el hostelero gestiona TODO desde el dashboard, sin pedirnos
nada por email. Plan: cobramos 27€/mes o 260€/año vía Stripe (sandbox por ahora).

**Bloques:** M completo, N completo a nivel UI (Stripe sandbox webhooks
queda como punto puente para Ola 8).

**Criterios de salida:** ✅
- [x] Layout: sidebar fijo + header sticky + breadcrumbs en cada subpágina.
- [x] Tab `Pedidos`: vista lista de mesas con estado (libre/pidiendo/esperando/pagada).
      Polling cada 3s.
- [x] `Configuración → General`: LocalConfigCard con todos los campos de contacto.
- [x] `Configuración → Identidad`: logo subible + tipo de negocio + descripción.
- [x] `Configuración → Idiomas`: switches 6 idiomas (ES/EN/FR/DE/PT/IT), ES obligatorio.
- [x] `Configuración → Horarios`: rangos multi-tramo por día semana.
- [x] `Configuración → Datos fiscales`: NIF, razón social, dirección fiscal.
- [x] `Configuración → Equipo`: lista members con roles + propietario marcado.
- [x] `Cuenta → Perfil`: email, nombre, rol (lectura).
- [x] `Cuenta → Contraseña`: form con validación local + warning backend pendiente.
- [x] `Cuenta → Sesiones activas`: sesión actual visible.
- [x] `Cuenta → Cerrar cuenta`: doble confirmación con expected string GDPR.
- [x] `Facturación → Mi plan`: estado + comparativa Mensual/Anual + cancelar.
- [x] `Facturación → Histórico`: empty state esperando primer pago.
- [x] `Facturación → Métodos de pago`: empty state Stripe sandbox pendiente.
- [x] **Cuenta atrás de demo** visible (días restantes) en panel Plan.
- [x] **Botón "Ver mi carta pública"** abre `/carta` en pestaña nueva.
- [x] **Plan activo** visible en header (cuando esté configurado).
- [x] **Sidebar fijo** con 6 entradas (Carta/Mesas/Pedidos/Config/Facturación/Cuenta).
- [x] **Breadcrumbs** derivados de la URL con etiquetas localizadas.
- [x] **DashboardContext** comparte local+productos+categorías entre sub-páginas
      (una sola carga, varios consumidores).
- [x] Backend del local ampliado: campos `idiomas`, `horarios`, `nif`,
      `razon_social`, `direccion_fiscal`, `tipo_negocio`, `descripcion` con
      sanitizers correctos para arrays y objetos.
- [x] Test gate **71 → 75 assertions** (idiomas array, horarios multi-tramo,
      datos fiscales, lectura pública del schema completo).

**Puntos diferidos a Ola 8 (despliegue):**
- `CAPABILITIES/PAYMENT/StripeAdapter.php` con sandbox keys + webhooks reales.
- Persistencia billing en `STORAGE/billing/<local_id>/...`.
- Endpoint `change_password` (la UI está, el backend espera).
- Endpoint `close_account` (UI con GDPR está, backend espera).
- Endpoint `list_sessions` (placeholder muestra sesión actual).
- Invitación de miembros vía email + token (requiere SMTP).
- Notificaciones reales (campana en header es decorativa por ahora).

---

### 🌊 OLA 4 — Diseño profesional y UX

**Objetivo:** el producto se ve profesional. Compite visualmente con
Last.app y Qamarero.

**Bloques:** O completo.

**Criterios de salida:**
- [ ] Captura de cada pantalla del dashboard comparada con Last.app/Qamarero.
- [ ] Microcopys: ningún botón "Submit/OK/Guardar" genérico. Usar verbo+objeto.
- [ ] Mensajes de error humanos en castellano (sin "Error 500").
- [ ] Mensajes de éxito breves y celebrativos.
- [ ] Skeleton screens en listas (categorías, productos, pedidos).
- [ ] Loading inline en botones (texto + spinner pequeño).
- [ ] Optimistic updates en CRUD.
- [ ] Auditoría CSS: ningún color literal `#XXXXXX` fuera de variables.
- [ ] Auditoría tipografía: 4 tamaños máximo, 3 weights máximo.
- [ ] Animaciones: transiciones <150ms ease-out, hover táctil.
- [ ] Sin animaciones decorativas (no parallax).

**Estimado:** 4-6 horas.

---

### 🌊 OLA 5 — Responsive (mobile + tablet + desktop)

**Objetivo:** funciona perfecto en móvil. La mitad de hosteleros lo usarán
solo desde el móvil.

**Bloques:** P completo.

**Criterios de salida:**
- [ ] Cada pantalla del dashboard probada en 375px, 768px, 1280px.
- [ ] Sin scroll horizontal en ningún tamaño.
- [ ] Touch targets ≥ 44px en móvil.
- [ ] Inputs altura ≥ 44px (evita zoom auto iOS).
- [ ] Sidebar oculto tras botón hamburguesa en móvil.
- [ ] Header sticky con info condensada en móvil.
- [ ] Tabs internas scroll horizontal en pantallas estrechas.
- [ ] Tablas largas → cards apiladas en móvil.
- [ ] Formularios → labels arriba en móvil (no a la izquierda).
- [ ] Modales → bottom-sheet en móvil, centrados en desktop.
- [ ] 3-4 testers reales escanean QR con su móvil: tiempo medio "ver
      carta" < 3s en red 3G; "añadir plato al carrito" < 60s.

**Estimado:** 4-6 horas.

---

### 🌊 OLA 6 — SEO + carta pública optimizada

**Objetivo:** las cartas públicas se indexan en Google. Tráfico orgánico
gratis a cada cliente que tengamos.

**Bloques:** Q completo.

**Criterios de salida:**
- [ ] `<title>` dinámico: "Carta de [Nombre] - MyLocal".
- [ ] `<meta description>` con local + 3 platos destacados.
- [ ] `<meta property="og:image">` con logo o foto destacada.
- [ ] `<link rel="canonical">` apuntando a `<slug>.mylocal.es/carta`.
- [ ] Schema.org `Restaurant` con dirección, teléfono, horarios.
- [ ] Schema.org `Menu` con `MenuSection` + `MenuItem`.
- [ ] Validar con Google Rich Results Test.
- [ ] `sitemap.xml` dinámico con subdominios activos.
- [ ] `robots.txt` permite cartas públicas, prohíbe `/dashboard|sistema|acide`.
- [ ] LCP < 2.5s en mobile 3G.
- [ ] CLS < 0.1.
- [ ] INP < 200ms.
- [ ] Lighthouse mobile ≥ 90 en una carta real.
- [ ] `<link rel="preload">` para fuentes locales.
- [ ] Imágenes `loading="lazy"` excepto primer viewport.
- [ ] WebP/AVIF cuando el navegador lo soporta.

**Estimado:** 3-5 horas.

---

### 🌊 OLA 7 — Multi-tenancy + Agnosticismo + Pre-producción

**Objetivo:** el código es agnóstico (funciona en cualquier subdominio/IP),
los datos están aislados por local, y un tester real puede onboardearse
solo en menos de 10 minutos.

**Bloques:** L completo (multi-tenancy), R completo (agnosticismo),
S completo (pre-prod).

**Criterios de salida:**

Multi-tenancy (L):
- [ ] Función global `get_current_local_id()` que combina subdomain +
      header `X-Local-Id`.
- [ ] Filtro automático por `local_id` en lecturas/escrituras.
- [ ] Bloquear cross-local: admin de A no puede tocar datos de B
      (enforce `LocalModel::userCanAccess` en cada handler).
- [ ] `LocalSwitcher` UI extendido (avatar + enlace `<slug>.mylocal.es`).
- [ ] Endpoint `list_my_locales` filtrado por usuario autenticado.

Agnosticismo (R):
- [ ] grep `/acide/` → solo en `SynaxisClient.apiUrl` (configurable).
- [ ] grep `/MEDIA/` en JSX → ninguna ruta absoluta hardcoded.
- [ ] grep `http://|https://` → solo URLs externas explícitas (Gemini, Stripe).
- [ ] No `window.location.origin + '/...'` para enlaces internos.
- [ ] `vite.config.ts` con `base: './'`.
- [ ] `router.php` con `__DIR__` en todos los includes.
- [ ] Probar mover `release/` a otra carpeta y arrancar PHP.
- [ ] Probar montar SPA en `/`, `/app/`, `/cliente-x/` — mismo build funciona.

Pre-prod (S):
- [ ] Test gate ampliado: >80 assertions cubren dashboard, no solo login+OCR.
- [ ] `test_dashboard.php` con: crear local, configurar mesas, cambiar tema,
      generar PDFs, gestionar suscripción mock.
- [ ] Build aborta si cualquiera falla.
- [ ] 3 hosteleros amigos hacen onboarding completo en local sin ayuda.
- [ ] Métrica: tiempo medio < 10 min del registro al QR descargado.
- [ ] Métrica: 0 momentos de "no sé qué hacer aquí".
- [ ] Seed de local "demo" con 30 productos, 5 categorías, 3 zonas, 12 mesas.
- [ ] `docs/DEPLOY.md` con procedimiento exacto para Ola 8.
- [ ] `docs/RUNBOOK.md` con incidencias frecuentes.
- [ ] `docs/BACKUP.md` con backup de `STORAGE/` en Hostinger.
- [ ] Plan de rollback documentado.

**Estimado:** 5-8 horas.

---

### 🌊 OLA 8 — Despliegue producción Hostinger + Cloudflare + Stripe live

**Cuándo:** SOLO cuando las olas 1-7 estén cerradas con [x].

**Bloques:** H completo (Cloudflare + Hostinger), Stripe live final.

**Criterios de salida:**

Cloudflare:
- [ ] `mylocal.es` añadido a Cloudflare como sitio.
- [ ] Nameservers en Hostinger apuntan a Cloudflare.
- [ ] Status "Active" en Cloudflare tras propagación.
- [ ] Registro `A @` → IP Hostinger (proxied).
- [ ] Registro `A *` → wildcard, misma IP (proxied).
- [ ] SSL/TLS modo Full (strict).
- [ ] Origin Certificate generado e instalado en Hostinger.
- [ ] Page Rule cache 1 mes para `*.mylocal.es/MEDIA/*`.
- [ ] Page Rule cache 1 año para `*.mylocal.es/assets/*`.
- [ ] Page Rule bypass cache para `*.mylocal.es/acide/*`.

Hostinger:
- [ ] `release/` subido a `public_html`.
- [ ] Origin Certificate instalado en el dominio.
- [ ] PHP ≥ 8.2 con `openssl`, `curl`, `fileinfo`, `gd`, `mbstring`, `intl`.
- [ ] `STORAGE/` y `MEDIA/` con permisos 755, usuario PHP.
- [ ] Cron 1 min: `php /home/<user>/public_html/axidb/plugins/jobs/worker_run.php`.
- [ ] `health_check` responde 200 desde `https://mylocal.es/acide/index.php`.

Subdominios:
- [ ] Extractor de slug en `router.php` define `CURRENT_LOCAL_SLUG`.
- [ ] Mismo extractor en `spa/server/index.php`.
- [ ] Cargar `STORAGE/locales/<slug>.json` automáticamente cada request.
- [ ] Si slug no existe, redirigir a landing pública `mylocal.es`.

Slug validator (L cierre):
- [ ] Lista reservadas: admin, dashboard, api, app, www, mail, ftp, cpanel,
      cdn, static, assets, acide, mylocal, demo, test, staging, dev, panel,
      support, help, docs, blog, shop, store.
- [ ] Regex `^[a-z][a-z0-9-]{2,30}$`.
- [ ] Endpoint `validate_slug` con `{available, reason}`.
- [ ] UI registro con feedback en vivo.

Stripe live:
- [ ] Live keys configuradas en `STORAGE/options/payment.json` (vía panel admin).
- [ ] Datos fiscales reales rellenados en páginas legales.
- [ ] Primera venta real registrada.

**Estimado:** 4-8 horas operacional + testing.

**Estado al cerrar:** Carta digital del primer cliente operativa en
`<slug>.mylocal.es` con todo lo de las olas 1-7 funcionando en producción.

---

### Anti-patrón a evitar

NO hacer despliegue prematuro a Hostinger por:
- "Quiero ver cómo se ve en un dominio real"
- "Quiero enseñárselo a alguien"
- "Quiero probar Cloudflare"

Eso son tentaciones. La forma de "verlo en un dominio real" es:
1. Levantar `php -S 0.0.0.0:8090` en local.
2. Editar `hosts` para mapear `elbar.mylocal.es` a `127.0.0.1`.
3. Probar el comportamiento de subdominio sin tocar Cloudflare.

Así desarrollamos contra el escenario real **sin desplegar**.

---

## 10.1 Estado actual de las Olas (snapshot 2026-05-08)

| Ola | Bloques | Estado | % | Notas |
|---|---|---|---|---|
| 🌊 1 — Sala + QRs + Local | I, K.1/K.2 parcial | ✅ **CERRADA** | 100% | URLs friendly, bootstrap minimal, póster QR, edición inline. |
| 🌊 2 — Carta digital Web+PDF | J ampliado, K.2/K.3 | ✅ **CERRADA** | 100% | 3+3 plantillas, auto-save, PDF lee local del server, table tents A6, LocalConfigCard, test gate 71/71. |
| 🌊 3 — Dashboard completo | M, N | ✅ **CERRADA** | 100% | Sidebar+breadcrumbs+22 sub-páginas, DashboardContext, 6 panels Config, Pedidos polling, Cuenta+Facturación UI. Stripe sandbox real diferido a Ola 8. Test gate 75/75. |
| 🌊 4 — Diseño profesional + UX | O | ⏳ **PRÓXIMA** | 0% | Auditoría visual, microcopys, skeletons, optimistic updates. |
| 🌊 5 — Responsive | P | 🔴 PENDIENTE | 0% | 3 breakpoints. |
| 🌊 6 — SEO carta pública | Q | 🔴 PENDIENTE | 0% | Meta tags + Schema.org + Lighthouse ≥90. |
| 🌊 7 — Multi-tenancy + Agnosticismo + Pre-prod | L, R, S | 🔴 PENDIENTE | 10% | BrowserRouter ya hecho, falta auditoría completa, tests con usuarios. |
| 🌊 8 — Despliegue prod | H, Stripe live | 🔴 PENDIENTE | 0% | Operacional, último. |

**Progreso total estimado:** ~50% del proyecto Fase 1 (3/8 olas cerradas).

### Disciplina aplicada en esta iteración

A partir del 2026-05-08, el plan se ejecuta con **ola-a-ola completa**:
no se considera una ola cerrada hasta que TODOS sus criterios de salida
están marcados [x]. Si un ítem depende de una ola futura, se mueve al
plan de esa ola y se queda eliminado del actual (no "[ ] depende de X").

### Trabajo extra fuera del plan original (iteración 2026-05-05/08)

Mejoras que se incorporaron mientras se cerraban las olas:

- ✅ **Refactor LOGIN a `CAPABILITIES/LOGIN/`** — login bloqueado.
- ✅ **`LocalQrPoster`** — póster A4 de marketing con nombre+teléfono.
- ✅ **URLs friendly + BrowserRouter** — `/dashboard` limpio, `/carta/terraza/mesa-10`.
- ✅ **Servidor IA propio `ai.miaplic.com`** — Gemma 4 E2B vía llama.cpp.
- ✅ **Pipeline OCR cascada** — Tesseract → Gemma 4 → Gemini fallback.
- ✅ **Persistencia AxiDB jerárquica** — `local → carta → categoría → producto` server-side.
- ✅ **Lecturas públicas** — la carta digital se sirve sin sesión.

### Métricas de progreso

- **Olas cerradas:** 3/8 (37%)
- **Ola próxima:** Ola 4 — Diseño profesional + UX (auditoría O completa).
- **Capabilities activas:** 22.
- **Test gate actual:** 75/75 PASS contra source y release.
- **Commits en origin/main desde reorganización:** 24+.
- **Sub-rutas del dashboard:** 22 (carta/mesas/pedidos/config + 6 sub-config + facturación + 3 sub-fact + cuenta + 4 sub-cuenta).
- **Persistencia:** TODO en disco (cero IndexedDB para datos de negocio).

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
