# Landing Page — Hosteleria

Documentación de diseño, estructura y responsivo de la página principal del template `hosteleria`.

---

## Arquitectura de archivos

```
templates/hosteleria/src/
├── index.css                      # Variables de tema, fuentes, utilidades globales
├── App.tsx                        # Orquestador: SplashScreen + Header + secciones + Footer + LoginModal
├── lib/utils.ts                   # Helper cn() para classnames
└── components/
    ├── SplashScreen.tsx           # Intro animada (6.6 s), se monta sobre todo
    ├── Header.tsx                 # Navbar fija (h-16), menú hamburguesa en móvil
    ├── HeroSection.tsx            # #hero — configurador de estilos de carta
    ├── QRSection.tsx              # #qr — generador y lista de QRs
    ├── WebPreviewSection.tsx      # #web — preview del sitio con temas de color
    ├── ImportSection.tsx          # #importar — escáner IA de carta en papel
    ├── ProductsSection.tsx        # #productos — grid de platos estilo feed visual
    ├── PDFSection.tsx             # #pdf — descarga de carta en PDF
    ├── PricingSection.tsx         # #planes — tres planes de precio
    ├── Footer.tsx                 # Footer con columnas de links y redes sociales
    ├── LoginModal.tsx             # Modal de acceso (overlay global)
    ├── MockupData.ts              # Datos compartidos: PRODUCTS[], CATEGORIES[]
    ├── MockupContent.tsx          # Carta interactiva (estilos Moderna / Minimal)
    ├── MockupPremium.tsx          # Carta interactiva (estilo Premium)
    └── WebMockup.tsx              # Sitio web simulado para WebPreviewSection
```

---

## Orden de secciones

El `App.tsx` monta las secciones en este orden:

| # | Componente | Anchor | Fondo |
|---|-----------|--------|-------|
| — | SplashScreen | — | `#ffffff` |
| — | Header | — | `#F9F9F7/95` (glassmorphism) |
| 1 | HeroSection | `#hero` | `#F9F9F7` |
| 2 | QRSection | `#qr` | `#ffffff` |
| 3 | WebPreviewSection | `#web` | `#F9F9F7` |
| 4 | ImportSection | `#importar` | `#000000` |
| 5 | ProductsSection | `#productos` | `#ffffff` |
| 6 | PDFSection | `#pdf` | `#F9F9F7` |
| 7 | PricingSection | `#planes` | `#ffffff` |
| — | Footer | — | `#ffffff` |

Los fondos alternan deliberadamente entre `#F9F9F7` (crema cálido) y `#ffffff` (blanco puro) para separar visualmente las secciones sin usar bordes ni divisores. La sección ImportSection rompe el patrón usando negro total para impacto dramático.

---

## Paleta de colores

### Colores base y superficies

| Token | Valor | Uso |
|-------|-------|-----|
| `#F9F9F7` | Crema cálido | Fondo global del body, secciones alternas (Hero, WebPreview, PDF) |
| `#ffffff` | Blanco puro | Secciones alternas (QR, Productos, Planes, Footer, Header) |
| `#000000` / `#0a0a0a` | Negro | Fondo ImportSection, CTA buttons, texto principal |
| `#f3f4f6` / `gray-100` | Gris muy claro | Bordes de cards, separadores, fondo de elementos UI |
| `#f9fafb` / `gray-50` | Casi blanco | Fondos de paneles internos, filas de listas |

### Colores de texto

| Token Tailwind | Valor approx. | Uso |
|----------------|---------------|-----|
| `text-gray-900` | `#111827` | Texto principal, títulos |
| `text-gray-500` | `#6b7280` | Subtítulos, párrafos descriptivos |
| `text-gray-400` | `#9ca3af` | Eyebrow labels, spans secundarios, spans grises en H2 |
| `text-gray-300` | `#d1d5db` | Iconos de acción inactivos |
| `text-white/40` | blanco 40% | Span de contraste en H2 dentro de sección negra |
| `text-white/50` | blanco 50% | Párrafos sobre fondo negro (ImportSection) |

### Colores de acento

| Color | Valor | Uso |
|-------|-------|-----|
| `emerald-400` | `#34d399` | Línea de escaneo, iconos y chips en ImportSection |
| `cyan-500` | `#06b6d4` | Glow ambiental secundario en ImportSection |
| `amber-400` | `#fbbf24` | Badge "Más popular" en PricingSection |
| `amber-600` | `#d97706` | Precio de productos en mockup Moderna |
| `red-400` | `#f87171` | Botón eliminar QR (hover) |

### Variables CSS definidas en `index.css`

```css
--color-brand-primary:   #000000;
--color-brand-secondary: #ffffff;
```

---

## Tipografía

### Fuentes

Cargadas desde Google Fonts vía `index.css`:

| Variable CSS | Familia | Pesos | Uso |
|-------------|---------|-------|-----|
| `--font-sans` / `font-sans` | **Inter** | 300, 400, 500, 600 | Texto general, párrafos, etiquetas, botones |
| `--font-display` / `font-display` | **Space Grotesk** | 300, 400, 500, 600, 700 | Títulos H1/H2/H3, logotipo, wordmarks |

### Escala tipográfica

| Uso | Clase Tailwind | px | Fuente | Peso |
|-----|---------------|-----|--------|------|
| H1 / H2 editorial grande | `text-4xl sm:text-5xl lg:text-6xl xl:text-[4.5rem]` | 36→48→60→72 px | `font-display` | `font-bold` |
| H2 centrado (Productos, Planes) | `text-4xl` | 36 px | `font-display` | `font-bold` |
| H3 card / sección | `text-lg` o `text-xl` | 18–20 px | `font-display` | `font-semibold` |
| Eyebrow label | `text-[11px] font-mono uppercase tracking-[0.22em]` | 11 px | `font-mono` (Inter) | normal |
| Párrafo descriptivo | `text-[13px]` o `text-[14px]` | 13–14 px | Inter | normal |
| Micro label / monospace | `text-[9px] font-mono uppercase tracking-widest` | 9 px | `font-mono` | normal |
| Precio plan | `text-4xl font-display font-bold tracking-tighter` | 36 px | Space Grotesk | bold |

### Tracking (letter-spacing) habitual

- Eyebrow: `tracking-[0.22em]` a `tracking-[0.35em]`
- H2 editorial: `tracking-tighter` (–0.05em)
- Precio / monospace UI: `tracking-widest`

---

## Patrón de layout de secciones

### Contenedor estándar de sección

```tsx
<section className="min-h-screen lg:h-screen pt-16 flex items-center overflow-hidden bg-[...]">
  <div className="w-full max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-12 items-center py-10 lg:py-0">
    ...
  </div>
</section>
```

- `pt-16`: compensa el Header fijo de 64 px de alto.
- `min-h-screen lg:h-screen`: en móvil puede crecer; en desktop se fija a 100 vh.
- `max-w-7xl mx-auto px-6`: ancho máximo 1280 px con padding lateral de 24 px.
- `py-10 lg:py-0`: respiración vertical en móvil (sin ella el contenido se aplasta).

### Rejilla editorial (dos columnas)

```
[  Visual / Mockup  ]  [  Editorial (texto)  ]
```

La columna del visual lleva `order-2 lg:order-1` y la editorial `order-1 lg:order-2` para que en móvil el texto aparezca siempre primero (encima del visual).

### Excepciones al patrón de dos columnas

| Sección | Layout |
|---------|--------|
| HeroSection | `flex-col lg:flex-row` manual con panel de estilos + mockup |
| ProductsSection | `grid grid-cols-2 lg:grid-cols-4` — 4 cards |
| PricingSection | `grid grid-cols-1 md:grid-cols-3` — 3 planes |
| WebPreviewSection | `flex flex-col lg:flex-row` con panel izq fijo `w-80` |

---

## Diseño del Header

```
[  My Local  ]  [ Ver mi carta · QR · Web · Importar · Productos · PDF · Planes ]  [ Empezar gratis ]
```

- **Fijo**: `position: fixed; top: 0; height: 64px (h-16)`
- **Fondo**: `#F9F9F7/95` + `backdrop-blur-sm` — transparencia frosted-glass
- **Borde**: `border-b border-gray-100`
- **Logotipo**: `font-display font-bold text-xl tracking-tighter` → "My Local"
- **CTA**: `bg-black text-white px-6 py-2.5 rounded-full text-sm` → "Empezar gratis"
- **Móvil**: nav links ocultos, aparece icono hamburguesa (`Menu` de Lucide), el menú se despliega hacia abajo con `AnimatePresence` y fondo blanco sólido.

---

## SplashScreen

Animación de intro de 6.6 s que bloquea la vista mientras la app carga.

**Estructura visual (de arriba a abajo):**
1. Texto rotativo — emerge desde el QR con `y: 22 → 0`, sale con `y: 0 → -14`
2. Tarjeta QR 3D — 158×158 px, fondo blanco, `border-radius: 22px`, `padding: 18px`
   - Sombra multicapa: 4 capas de `box-shadow` para efecto tridimensional
   - Animación flotante: `y: [0, -6, 0]` con `repeat: Infinity`, duración 3.6 s
3. 5 iconos de integración — `w-9 h-9 rounded-xl bg-gray-50` con entrada escalonada (`delay: 0.9 + i * 0.07`)
4. Wordmark "My Local" — `font-mono tracking-[0.35em] text-gray-300 text-[10px]`

**Secuencia de textos:**

| Paso | Tiempo | Texto |
|------|--------|-------|
| 1 | 700 ms | "NEGOCIOS QUE EVOLUCIONAN" (mono caps) |
| 2 | 2700 ms | "Soluciones que hacen tu negocio cada día más fuerte" |
| 3 | 4700 ms | "Clientes contentos, tu local vende más..." |
| end | 6600 ms | `onComplete()` — fade out con `opacity: 0`, duración 1.2 s |

---

## Secciones — descripción detallada

### 1. HeroSection (`#hero`) — `bg-[#F9F9F7]`

**Concepto**: Configurador interactivo de estilos de carta. El usuario ve el resultado al instante.

**Layout desktop**: columna editorial izquierda (`w-[40%]`) + mockup central + selector de estilos vertical derecha.
**Layout móvil**: editorial arriba → pills de estilos horizontales → mockup.

**Elementos interactivos**:
- 3 estilos de carta: `Moderna` / `Minimal` / `Premium` (botones pill)
- Selector de dispositivo: `Smartphone / Tablet / Monitor` (pills con iconos Lucide), solo visible en desktop (`hidden lg:flex`)
- Mockup animado con `AnimatePresence mode="wait"` — spring `stiffness: 130, damping: 18`

**Tamaños de mockup (desktop)**:

| Dispositivo | Ancho | Alto | Radio |
|-------------|-------|------|-------|
| mobile | 220 px | 440 px | 2.5rem |
| tablet | 380 px | 480 px | 2.5rem |
| desktop | 520 px | 320 px | 2.5rem |

---

### 2. QRSection (`#qr`) — `bg-white`

**Concepto**: Generador de QR dinámico con gestión de múltiples ubicaciones.

**Layout**: Editorial a la derecha (con lista de QRs guardados), panel de previsualización a la izquierda.

**Panel de QRs**: Dos estilos en paralelo (`flex flex-col sm:flex-row`):
- **Dinámico**: QRCodeSVG 130 px, nivel H, con inicial del local sobre el QR
- **Clásico**: QRCodeSVG 120 px, nivel L, con nombre del local encima

**Input interactivo**: Escribe el nombre → actualiza ambos QRs en tiempo real.

**Lista de QRs guardados**: `AnimatePresence` con `motion.div` por item, botones editar/eliminar ocultos que aparecen con `group-hover`.

---

### 3. WebPreviewSection (`#web`) — `bg-[#F9F9F7]`

**Concepto**: Preview en tiempo real del sitio web del local con selector de tema.

**Layout**: Panel de controles izquierda (`w-80 flex-shrink-0`) + mockup derecha.

**Temas disponibles**:

| Tema | Fondo | Card | Texto |
|------|-------|------|-------|
| Claro | `#ffffff` | `#f9f9f7` | `#0a0a0a` |
| Oscuro | `#0a0a0a` | `rgba(255,255,255,0.06)` | `#ffffff` |
| Personalizado | 6 pasteles | variante card | heredado de Claro |

**Colores pastel personalizados**: Crema, Salvia, Lavanda, Arena, Polvo, Niebla.

**Selector de dispositivo**: Desktop / Tablet / Móvil, solo visible en desktop.

**Tamaños de mockup (desktop)**:

| Dispositivo | Ancho | Alto | Radio |
|-------------|-------|------|-------|
| desktop | 680 px | 440 px | 0.5rem |
| tablet | 360 px | 490 px | 2.5rem |
| mobile | 220 px | 450 px | 2.5rem |

El mockup en desktop incluye una "chrome bar" simulando el navegador (círculos rojo/amarillo/verde + barra de URL `milocal.es`).

---

### 4. ImportSection (`#importar`) — `bg-black text-white`

**Concepto**: Escáner IA que digitaliza cartas en papel. Sección oscura de alto impacto.

**Efectos de fondo**: Dos glows ambientales con `blur-[140px]` — esmeralda arriba-derecha, cian abajo-izquierda.

**Visual del escáner** (`max-w-[400px] aspect-square`):
- Foto de carta de fondo con `opacity-30 grayscale` + grid de líneas finas en verde esmeralda
- 4 esquinas de escáner: `border-emerald-400`, bordes `2px`
- Línea de escaneo animada: `animate top: ['5%', '93%', '5%']`, gradiente `transparent → #34d399 → #67e8f9 → #34d399 → transparent` con glow
- Tarjeta central: `bg-black/70 backdrop-blur-xl` con icono Upload con `animate-bounce`
- 2 chips flotantes con animación `y: [0, ±8, 0]` repeat Infinity

**Editorial**: Lista de 3 pasos (Captura → Digitaliza → Publica) con iconos esmeralda en `bg-white/8`.

---

### 5. ProductsSection (`#productos`) — `bg-white`

**Concepto**: Catálogo visual de platos estilo "feed" de redes sociales.

**Grid**: `grid-cols-2 lg:grid-cols-4` — 2 columnas en móvil, 4 en desktop.

**Cards**: `rounded-2xl border border-gray-100`, hover con `shadow-xl -translate-y-1`.
- Imagen `aspect-square` con `group-hover:scale-105` (zoom suave en 700 ms)
- Badge de alérgenos en esquina superior izquierda: `bg-white/90 backdrop-blur-sm rounded-full`
- Footer de card: iconos de acción (Heart, MessageCircle, Share2, Info) en `text-gray-300` con hover de colores

**Productos de demostración**: Burger Premium · Poke Bowl Salmón · Pizza Trufada · Tacos Al Pastor.

---

### 6. PDFSection (`#pdf`) — `bg-[#F9F9F7]`

**Concepto**: Generación de carta imprimible en PDF con diseño editorial minimalista.

**Mockup de carta** (`max-w-[300px]`):
- Card blanca con `shadow-xl rounded-3xl p-6`
- Interior en `bg-gray-50 rounded-2xl p-6` simulando una hoja
- Logo "ML" en círculo negro, título "La Carta" en mayúsculas con `tracking-[0.5em]`
- 4 categorías con items: Carnes / Bowls / Pizzas / Tacos (mismos datos que MockupData)
- Cada item en `flex justify-between` con línea punteada `border-dotted border-gray-200`
- QR placeholder en la parte inferior

**Chip de estado**: `absolute -top-4 -right-4` badge negro "PDF listo" con icono Download.

---

### 7. PricingSection (`#planes`) — `bg-white`

**Concepto**: Tres planes con el central destacado.

**Grid**: `grid-cols-1 md:grid-cols-3 gap-5` — apilado en móvil, 3 columnas en tablet+.

| Plan | Precio | Estilo | Extra |
|------|--------|--------|-------|
| Gratis | 0€ | `bg-[#F9F9F7] border-transparent` | — |
| Pro | 27€ + IVA/mes | `bg-black text-white border-black scale-105 z-10` | Badge "Más popular" en `bg-yellow-400` |
| Premium | 264€ + IVA/año | `bg-[#F9F9F7] border-transparent` | — |

El plan Pro usa `scale-105` para sobresalir físicamente de los otros dos.

---

### Footer

**Layout desktop**: `grid md:grid-cols-[1fr,2fr]` — logotipo + descripción izquierda, 3 columnas de links derecha (Producto / Compañía / Legal).
**Redes sociales**: Instagram, Twitter, Facebook — `p-3 bg-gray-50 rounded-full hover:bg-black hover:text-white`.
**Bottom bar**: copyright en mono + "HECHO EN ESPAÑA · SOPORTE@MILOCAL.COM".

---

## Sistema de mockups internos

Los mockups de carta usados en HeroSection tienen tres estilos que comparten datos de `MockupData.ts`:

| Estilo | Componente | Descripción visual |
|--------|-----------|-------------------|
| Moderna | `MockupContent → ModernaView` | Header foto con gradiente + categorías pill + lista con imagen cuadrada |
| Minimal | `MockupContent → MinimalView` | Sin imagen, tipografía elegante, lista nombre + precio con separador |
| Premium | `MockupPremium` | Foto hero amplia + lista con imagen redondeada + modal de detalle del plato |

Los nombres de productos se muestran **siempre completos** — sin `truncate`. Font `text-[9px]` con imagen `w-9 h-9 rounded-lg` para maximizar el espacio del nombre en viewports estrechos.

---

## Responsive — breakpoints y estrategia

Tailwind estándar, mobile-first:

| Breakpoint | px | Nombre |
|-----------|-----|--------|
| (base) | 0+ | móvil |
| `sm` | 640+ | phablet |
| `md` | 768+ | tablet |
| `lg` | 1024+ | desktop |
| `xl` | 1280+ | desktop XL |

### Patrones aplicados en todas las secciones editoriales

| Propiedad | Móvil | Desktop |
|-----------|-------|---------|
| Alto de sección | `min-h-screen` (crece) | `lg:h-screen` (fijo 100 vh) |
| Dirección del contenedor | `flex-col` / `grid` 1 col | `lg:flex-row` / `lg:grid-cols-2` |
| Alineación de texto | `text-center` | `lg:text-left` |
| Párrafo / max-width | `mx-auto` | `lg:mx-0` |
| Botones / flex row | `justify-center` | `lg:justify-start` |
| Orden visual | Editorial arriba (order-1) | Visual izquierda (order-1) |
| Padding vertical | `py-10` | `lg:py-0` |

### Header en móvil
- Nav links: `hidden md:flex` — desaparecen en móvil
- CTA "Empezar gratis": `hidden md:block` — desaparece en móvil
- Hamburger: `md:hidden` — solo visible en móvil
- Menú desplegable: fondo blanco sólido, links con `text-base`, CTA a ancho completo

### Elementos ocultos en móvil

| Elemento | Clase | Motivo |
|---------|-------|--------|
| Selector de dispositivo (Hero, Web) | `hidden lg:flex` | Controles avanzados no necesarios en móvil |
| Descripción del estilo de carta | `hidden lg:block` | Texto descriptivo secundario en hero |
| Párrafo intro HeroSection | `hidden sm:block` | El H1 es suficiente en pantalla muy pequeña |
| Nav links del Header | `hidden md:flex` | Se usa el menú hamburguesa |

---

## Dependencias de UI

| Librería | Versión | Uso |
|---------|---------|-----|
| `motion/react` (Framer Motion) | — | Todas las animaciones: `motion.div`, `AnimatePresence`, spring |
| `lucide-react` | — | Iconografía: Menu, X, QR, Smartphone, Tablet, Monitor, Sun, Moon, Palette, Check, etc. |
| `qrcode.react` | — | `QRCodeSVG` en QRSection y SplashScreen |
| `tailwindcss` | v4 | Estilos, tema, variables CSS |

---

## Datos de demostración

Los cuatro productos de demo son coherentes entre todas las secciones (MockupData, ProductsSection, PDFSection):

| Producto | Precio | Categoría | Alérgenos |
|---------|--------|-----------|-----------|
| Burger Premium | 14.50€ | Carnes | Gluten, Lácteos |
| Poke Bowl Salmón | 12.90€ | Bowls | Pescado, Sésamo |
| Pizza Trufada | 16.00€ | Pizzas | Gluten, Lácteos |
| Tacos Al Pastor | 9.50€ | Tacos | — |
