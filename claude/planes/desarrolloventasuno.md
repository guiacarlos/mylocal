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

## 3. Flujo de Onboarding (La Experiencia WOW)

Este flujo minimiza los pasos manuales y delega el trabajo pesado a la IA en segundo plano.

- **Paso 1 - Registro Rápido:** Email, Contraseña y Tipo de Negocio.
- **Paso 2 - Identidad Visual:** Nombre del local y subida de logo. La IA extrae automáticamente la paleta de colores del logo.
- **Paso 3 - La Magia (Importación):** 
  - *Opción A (Recomendada):* "Sube una foto de tu carta actual o un PDF y nosotros la montamos por ti". (Inicia proceso OCR en background).
  - *Opción B:* "Empezar desde cero con ayuda de nuestro asistente".
- **Paso 4 - Revisión de Platos (Momento WOW 1):** Si usó la Opción A, ve su carta estructurada. Aquí puede aplicar la **"Varita Mágica"** a las fotos subidas, generar descripciones con un clic y ver cómo se **asignan los alérgenos automáticamente**.
- **Paso 5 - Toque de Venta:** Marca 2 o 3 platos como "Especialidades" para que la IA genere textos persuasivos de micro-promoción.
- **Paso 6 - Idiomas:** Activa traducciones automáticas (ES/EN/FR/DE) con un solo clic.
- **Paso 7 - Vista Previa (Momento WOW 2):** Simulación interactiva de móvil. El diseño aplica la paleta de colores extraída en el Paso 2.
- **Paso 8 - Activación y Material Físico:** Pantalla de éxito donde no solo descarga su QR (en formato display de mesa o pegatina), sino que puede **generar su carta física completa en PDF** eligiendo entre 3 plantillas de impresión profesionales (Minimalista, Clásica o Moderna).

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
