---
name: skilldiseno
description: >
  Normas, arquitectura y patrones de diseño para crear SPAs web orientadas al cliente:
  webs de producto, landing pages, portfolios, tiendas, webs corporativas — cualquier
  interfaz que sea la "vitrina del negocio". USAR SIEMPRE que el usuario pida crear o
  mejorar una web pública, una SPA de presentación, una landing, una home, secciones
  de producto, formularios de contacto/newsletter, header, footer o cualquier elemento
  visual que vea el cliente final. NO usar para dashboards de administración internos
  (eso es skilldashboard).
---

# Skill de Diseño — SPA Web Cliente (Vitrina del Negocio)

## Filosofía Central

La web del cliente es la **vitrina del negocio**. Cada decisión de diseño comunica valor, genera confianza y conduce a la conversión. El diseño es sofisticado, moderno y elegante — con elementos compactos que aprovechan el espacio sin resultar asfixiantes. La experiencia empieza en móvil.

### Los 6 principios — NO NEGOCIABLES

**1. Mobile-first absoluto**
Se diseña y construye para 375px primero. Todo elemento, sección, tipografía y espaciado se define para móvil. Desktop amplifica progresivamente con `@media (min-width: ...)`. Jamás al revés.

**2. Cajas apilables desde la base**
El sistema de layout se construye sobre columnas apilables. En móvil: una columna, elementos centrados, flujo vertical. En tablet: 2 columnas. En desktop: hasta 3-4 columnas o layouts asimétricos. Cada sección es un bloque autónomo que funciona solo.

```css
/* BASE MÓVIL: apilado, centrado */
.sp-section-grid { display: grid; grid-template-columns: 1fr; gap: var(--sp-gap-md); }
.sp-section-grid > * { text-align: center; }

/* TABLET */
@media (min-width: 640px)  { .sp-section-grid { grid-template-columns: 1fr 1fr; } }

/* DESKTOP */
@media (min-width: 1024px) { .sp-section-grid { grid-template-columns: repeat(3, 1fr); } }
```

**3. Compacto y sofisticado**
Secciones con padding reducido. Elementos próximos pero con jerarquía clara. La densidad se consigue con tipografía precisa, no con espacio generoso. Referencia de padding de sección: `48px 20px` en móvil, `80px 40px` en desktop — nunca más.

**4. Modo claro por defecto, modo oscuro opcional**
El tema base es claro (blanco/gris muy claro). El modo oscuro se activa con `body.dark` o `[data-theme="dark"]`. Las variables CSS cubren ambos casos desde el inicio.

**5. Sin llamadas externas en runtime**
Fuentes descargadas y en `./assets/fonts/`. Iconos desde librería React instalada (`lucide-react`). Cero Google Fonts, cero CDN de iconos, cero dependencias externas que puedan fallar o ralentizar.

**6. Un solo archivo CSS — `styles.css`**
Todo el diseño vive en un único archivo de estilos. Sin CSS-in-JS, sin `style={{}}` en JSX, sin Tailwind mezclado. Clases semánticas con prefijo `sp-` (SPA).

---

## 1. Estructura del Proyecto

```
./src/
├── components/
│   ├── Header/
│   │   ├── Header.tsx
│   ├── sections/
│   │   ├── Hero.tsx
│   │   ├── Features.tsx
│   │   ├── Newsletter.tsx
│   │   └── ...
│   ├── Footer/
│   │   └── Footer.tsx
│   └── ui/
│       ├── Button.tsx
│       ├── Modal.tsx
│       └── ...
├── styles/
│   └── styles.css          # ÚNICO archivo CSS del proyecto
├── assets/
│   └── fonts/              # Fuentes woff2 locales
└── App.tsx
```

---

## 2. Variables CSS — Sistema Visual Completo

```css
/* ═══════════════════════════════════════════
   MODO CLARO (por defecto)
═══════════════════════════════════════════ */
:root {
  /* ── Fondos ── */
  --sp-bg:            #FFFFFF;
  --sp-bg-soft:       #F8F8F6;      /* secciones alternas */
  --sp-bg-muted:      #F0F0EC;      /* cards, inputs */
  --sp-bg-dark:       #111111;      /* secciones inversas, footer */
  --sp-bg-overlay:    rgba(0,0,0,0.48);

  /* ── Texto ── */
  --sp-text:          #0F0F0F;
  --sp-text-soft:     #3A3A3A;
  --sp-text-muted:    #717171;
  --sp-text-inverse:  #F5F5F3;
  --sp-text-inverse-soft: #BBBBBB;

  /* ── Acento ── */
  --sp-accent:        #0F0F0F;      /* acento oscuro en modo claro */
  --sp-accent-hover:  #333333;
  --sp-accent-alt:    #C8A96E;      /* dorado/cobre — lujo, acción */
  --sp-accent-alt-hover: #B8944A;

  /* ── Bordes ── */
  --sp-border:        #E5E5E3;
  --sp-border-strong: #C0C0BC;

  /* ── Tipografía ── */
  --sp-font-display:  'EditorialNew', 'PP Editorial New', Georgia, serif;
  --sp-font-body:     'GeistVariable', 'InterVariable', sans-serif;
  --sp-font-mono:     'JetBrains Mono', monospace;

  /* ── Escala tipográfica ── */
  --sp-text-xs:   11px;
  --sp-text-sm:   13px;
  --sp-text-base: 15px;
  --sp-text-md:   17px;
  --sp-text-lg:   22px;
  --sp-text-xl:   32px;
  --sp-text-2xl:  44px;
  --sp-text-3xl:  64px;

  /* ── Espaciado ── */
  --sp-gap-xs:    6px;
  --sp-gap-sm:    12px;
  --sp-gap-md:    20px;
  --sp-gap-lg:    32px;
  --sp-gap-xl:    48px;
  --sp-gap-2xl:   80px;

  /* ── Padding de sección ── */
  --sp-section-py:    48px;         /* móvil */
  --sp-section-px:    20px;         /* móvil */

  /* ── Header ── */
  --sp-header-h:      56px;

  /* ── Geometría ── */
  --sp-radius-sm:     4px;
  --sp-radius-md:     8px;
  --sp-radius-lg:     16px;
  --sp-radius-pill:   999px;

  /* ── Transiciones ── */
  --sp-transition:    0.2s ease;
  --sp-transition-slow: 0.4s ease;
}

/* ═══════════════════════════════════════════
   MODO OSCURO — activar con body.dark o [data-theme="dark"]
═══════════════════════════════════════════ */
body.dark, [data-theme="dark"] {
  --sp-bg:            #0C0C0C;
  --sp-bg-soft:       #141414;
  --sp-bg-muted:      #1C1C1C;
  --sp-bg-dark:       #080808;
  --sp-text:          #F0F0EE;
  --sp-text-soft:     #C8C8C4;
  --sp-text-muted:    #717171;
  --sp-text-inverse:  #0F0F0F;
  --sp-accent:        #F0F0EE;
  --sp-accent-hover:  #FFFFFF;
  --sp-border:        #242424;
  --sp-border-strong: #3A3A3A;
}
```

### Fuentes locales

```css
@font-face {
  font-family: 'EditorialNew';
  src: url('../assets/fonts/PPEditorialNew-Regular.woff2') format('woff2');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}
@font-face {
  font-family: 'EditorialNew';
  src: url('../assets/fonts/PPEditorialNew-Italic.woff2') format('woff2');
  font-weight: 400;
  font-style: italic;
  font-display: swap;
}
@font-face {
  font-family: 'GeistVariable';
  src: url('../assets/fonts/GeistVariableVF.woff2') format('woff2');
  font-weight: 100 900;
  font-display: swap;
}
```

**Combinaciones tipográficas recomendadas (elegantes, no genéricas):**
- Display serif + sans body: `PP Editorial New` + `Geist` → lujo, editorial
- Display sans + mono accent: `Neue Haas Grotesk` + `JetBrains Mono` → tecnología, precisión
- Serif clásico + sans moderno: `Freight Display` + `Inter` → corporativo sofisticado
- **PROHIBIDO**: Arial, Roboto, system fonts como elección de diseño

---

## 3. Iconos — Librería instalada

```bash
npm install lucide-react
```

```tsx
import { ArrowRight, Menu, X, ChevronDown, Mail, Instagram, Youtube } from 'lucide-react';

<ArrowRight className="sp-icon" size={16} />
```

```css
.sp-icon { vertical-align: middle; flex-shrink: 0; }
.sp-icon--sm  { width: 14px; height: 14px; }
.sp-icon--md  { width: 18px; height: 18px; }
.sp-icon--lg  { width: 24px; height: 24px; }
```

---

## 4. Reset y Base

```css
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html {
  scroll-behavior: smooth;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

body {
  font-family: var(--sp-font-body);
  font-size: var(--sp-text-base);
  color: var(--sp-text);
  background: var(--sp-bg);
  transition: background var(--sp-transition), color var(--sp-transition);
  overflow-x: hidden;
}

img, video { max-width: 100%; display: block; }
a { color: inherit; text-decoration: none; }
button { cursor: pointer; font-family: inherit; border: none; background: none; }
```

---

## 5. Header — Fijo, Compacto, Minimalista

### 5.1 Principio de navegación móvil

**En móvil NO se usa hamburger**. La navegación es una barra horizontal con scroll, pegada bajo el logo. Ocupa el mínimo espacio posible. El header completo (logo + nav scroll) nunca supera los 88px en móvil.

- **Logo**: altura máxima 44px, solo logo/nombre, sin elementos extra.
- **Nav móvil**: fila horizontal con scroll suave, sin flechas, sin indicadores. Los ítems se deslizan hacia la derecha con el dedo.
- **Desktop**: nav centrada visible, logo izquierda, acciones derecha.

```css
/* ── Header base — móvil primero ── */
.sp-header {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: var(--z-header);          /* sistema z-index — ver §30 */
  background: var(--sp-bg);
  border-bottom: 1px solid var(--sp-border);
  transition: background var(--sp-transition), border-color var(--sp-transition);
  /* Dos filas en móvil: logo + nav-scroll */
  display: flex;
  flex-direction: column;
}

/* Fila 1: logo + acciones */
.sp-header__top {
  height: 44px;                       /* compacto: 44px en móvil */
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--sp-section-px);
  flex-shrink: 0;
}

.sp-header__logo {
  font-family: var(--sp-font-display);
  font-size: var(--sp-text-md);
  font-style: italic;
  color: var(--sp-text);
  letter-spacing: -0.02em;
  flex-shrink: 0;
  line-height: 1;
}

.sp-header__actions {
  display: flex;
  align-items: center;
  gap: var(--sp-gap-sm);
  flex-shrink: 0;
}

/* Fila 2: nav horizontal con scroll — SOLO MÓVIL */
.sp-header__nav-scroll {
  height: 40px;
  display: flex;
  align-items: center;
  overflow-x: auto;
  overflow-y: hidden;
  scroll-behavior: smooth;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
  padding: 0 var(--sp-section-px);
  gap: 0;
  border-top: 1px solid var(--sp-border);
}
.sp-header__nav-scroll::-webkit-scrollbar { display: none; }

.sp-header__nav-scroll .sp-header__nav-link {
  flex-shrink: 0;
  padding: 0 var(--sp-gap-md) 0 0;
  font-size: var(--sp-text-sm);
  font-weight: 500;
  color: var(--sp-text-muted);
  letter-spacing: 0.04em;
  white-space: nowrap;
  transition: color var(--sp-transition);
  position: relative;
  height: 100%;
  display: flex;
  align-items: center;
}
.sp-header__nav-scroll .sp-header__nav-link:hover { color: var(--sp-text); }

/* Indicador activo — línea inferior */
.sp-header__nav-scroll .sp-header__nav-link--active {
  color: var(--sp-text);
}
.sp-header__nav-scroll .sp-header__nav-link--active::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0;
  right: var(--sp-gap-md);
  height: 2px;
  background: var(--sp-text);
  border-radius: 1px 1px 0 0;
}

/* Desvanecimiento derecho — indica que hay más ítems */
.sp-header__nav-fade {
  position: absolute;
  top: 44px; right: 0;
  width: 40px;
  height: 40px;
  background: linear-gradient(to right, transparent, var(--sp-bg));
  pointer-events: none;
  z-index: 1;
}

/* ── DESKTOP: estructura de una sola fila centrada ── */
@media (min-width: 768px) {
  .sp-header {
    flex-direction: row;
    height: var(--sp-header-h);        /* 56px en desktop */
    align-items: center;
    padding: 0 var(--sp-gap-xl);
  }

  /* Ocultar fila 2 de scroll */
  .sp-header__top {
    height: auto;
    padding: 0;
    flex: 0 0 auto;
    border: none;
  }
  .sp-header__nav-scroll {
    display: none;                     /* reemplazada por nav centrada desktop */
  }
  .sp-header__nav-fade { display: none; }

  /* Nav centrada absoluta */
  .sp-header__nav-desktop {
    display: flex;
    align-items: center;
    gap: var(--sp-gap-lg);
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
  }
  .sp-header__nav-desktop .sp-header__nav-link {
    font-size: var(--sp-text-sm);
    font-weight: 500;
    color: var(--sp-text-muted);
    letter-spacing: 0.06em;
    text-transform: uppercase;
    transition: color var(--sp-transition);
    white-space: nowrap;
  }
  .sp-header__nav-desktop .sp-header__nav-link:hover { color: var(--sp-text); }
}

/* Móvil: nav desktop oculta */
.sp-header__nav-desktop { display: none; }

/* Header transparente sobre hero */
.sp-header--transparent {
  background: transparent;
  border-bottom-color: transparent;
}
.sp-header--transparent .sp-header__nav-scroll {
  border-top-color: transparent;
}
.sp-header--transparent.sp-header--scrolled {
  background: var(--sp-bg);
  border-bottom-color: var(--sp-border);
}
.sp-header--transparent.sp-header--scrolled .sp-header__nav-scroll {
  border-top-color: var(--sp-border);
}

/* Offset de página por header de dos filas en móvil */
.sp-page {
  padding-top: 84px;                   /* 44px top + 40px nav-scroll */
}
@media (min-width: 768px) {
  .sp-page { padding-top: var(--sp-header-h); }  /* 56px */
}
```

### 5.2 JSX — Estructura del Header

```tsx
// Header.tsx — estructura correcta mobile-first
<header className={`sp-header ${scrolled ? 'sp-header--scrolled' : ''}`}>

  {/* Fila 1: logo + acciones (siempre visible) */}
  <div className="sp-header__top">
    <a href="/" className="sp-header__logo">Marca</a>

    {/* Nav centrada — solo desktop */}
    <nav className="sp-header__nav-desktop">
      <a className="sp-header__nav-link" href="/productos">Productos</a>
      <a className="sp-header__nav-link" href="/servicios">Servicios</a>
      <a className="sp-header__nav-link" href="/nosotros">Nosotros</a>
      <a className="sp-header__nav-link" href="/contacto">Contacto</a>
    </nav>

    <div className="sp-header__actions">
      <Search className="sp-icon" size={18} />
    </div>
  </div>

  {/* Fila 2: nav scroll — solo móvil */}
  <nav className="sp-header__nav-scroll">
    <a className="sp-header__nav-link sp-header__nav-link--active" href="/productos">Productos</a>
    <a className="sp-header__nav-link" href="/servicios">Servicios</a>
    <a className="sp-header__nav-link" href="/nosotros">Nosotros</a>
    <a className="sp-header__nav-link" href="/blog">Blog</a>
    <a className="sp-header__nav-link" href="/contacto">Contacto</a>
  </nav>

  {/* Degradado fade — indica scroll disponible */}
  <div className="sp-header__nav-fade" aria-hidden="true" />
</header>
```

---

## 6. Sistema de Secciones — Cajas Apilables

Cada sección es un bloque autónomo. El contenido siempre dentro de un `sp-container`.

```css
/* Offset por header fijo */
.sp-page { padding-top: var(--sp-header-h); }

/* Contenedor centrado */
.sp-container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 var(--sp-section-px);
}

/* Sección base */
.sp-section {
  padding: var(--sp-section-py) 0;
}

/* Sección con fondo alternado */
.sp-section--soft   { background: var(--sp-bg-soft); }
.sp-section--dark   { background: var(--sp-bg-dark); color: var(--sp-text-inverse); }
.sp-section--muted  { background: var(--sp-bg-muted); }

/* Ampliación desktop */
@media (min-width: 1024px) {
  .sp-container      { padding: 0 var(--sp-gap-xl); }
  .sp-section        { padding: var(--sp-gap-2xl) 0; }
}

/* Grid apilable — base móvil */
.sp-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--sp-gap-md);
}
@media (min-width: 640px)  { .sp-grid--2  { grid-template-columns: 1fr 1fr; } }
@media (min-width: 1024px) {
  .sp-grid--3  { grid-template-columns: repeat(3, 1fr); }
  .sp-grid--4  { grid-template-columns: repeat(4, 1fr); }
  .sp-grid--asymmetric { grid-template-columns: 1fr 1.6fr; }
}

/* Centrado de contenido en sección */
.sp-section-center {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--sp-gap-md);
}
.sp-section-center .sp-section__text { max-width: 560px; }
```

---

## 7. Hero — Impacto Visual Inmediato

```css
.sp-hero {
  position: relative;
  min-height: 100svh;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  overflow: hidden;
  background: var(--sp-bg-dark);
  color: var(--sp-text-inverse);
}

/* Con imagen/video de fondo */
.sp-hero__media {
  position: absolute;
  inset: 0;
  object-fit: cover;
  width: 100%; height: 100%;
  opacity: 0.55;
}

.sp-hero__content {
  position: relative;
  z-index: 1;
  padding: var(--sp-gap-xl) var(--sp-section-px);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--sp-gap-md);
  max-width: 800px;
}

.sp-hero__eyebrow {
  font-size: var(--sp-text-xs);
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--sp-text-inverse-soft);
}

.sp-hero__title {
  font-family: var(--sp-font-display);
  font-size: clamp(36px, 8vw, 72px);
  font-weight: 400;
  line-height: 1.0;
  letter-spacing: -0.03em;
  color: var(--sp-text-inverse);
}
/* Cursiva editorial en parte del título */
.sp-hero__title em {
  font-style: italic;
  color: var(--sp-accent-alt);
}

.sp-hero__subtitle {
  font-size: var(--sp-text-base);
  color: var(--sp-text-inverse-soft);
  max-width: 480px;
  line-height: 1.6;
}

/* Indicador de scroll — cruces decorativas estilo referencia */
.sp-hero__scroll {
  position: absolute;
  bottom: var(--sp-gap-lg);
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--sp-gap-xs);
  font-size: var(--sp-text-xs);
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--sp-text-inverse-soft);
  animation: sp-bounce 2s ease-in-out infinite;
}

/* Cruces decorativas en esquinas (patrón de referencia) */
.sp-hero__corner {
  position: absolute;
  font-size: 18px;
  color: rgba(255,255,255,0.3);
  line-height: 1;
  user-select: none;
}
.sp-hero__corner--tl { top: var(--sp-gap-md);  left: var(--sp-gap-md); }
.sp-hero__corner--tr { top: var(--sp-gap-md);  right: var(--sp-gap-md); }
.sp-hero__corner--bl { bottom: var(--sp-gap-md); left: var(--sp-gap-md); }
.sp-hero__corner--br { bottom: var(--sp-gap-md); right: var(--sp-gap-md); }

/* Navegación lateral numerada (patrón de referencia) */
.sp-hero__sidenav {
  position: absolute;
  left: var(--sp-gap-md);
  top: 50%;
  transform: translateY(-50%);
  display: none;
  flex-direction: column;
  gap: var(--sp-gap-lg);
}
.sp-hero__sidenav-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.sp-hero__sidenav-num {
  font-size: var(--sp-text-xs);
  color: rgba(255,255,255,0.35);
}
.sp-hero__sidenav-label {
  font-size: var(--sp-text-sm);
  font-weight: 600;
  color: rgba(255,255,255,0.7);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
@media (min-width: 1024px) {
  .sp-hero__sidenav { display: flex; }
}

@keyframes sp-bounce {
  0%, 100% { transform: translateX(-50%) translateY(0); }
  50% { transform: translateX(-50%) translateY(6px); }
}
```

---

## 8. Tipografía de Sección

```css
.sp-eyebrow {
  font-size: var(--sp-text-xs);
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--sp-text-muted);
}

.sp-title {
  font-family: var(--sp-font-display);
  font-size: clamp(28px, 5vw, 52px);
  font-weight: 400;
  line-height: 1.05;
  letter-spacing: -0.025em;
  color: var(--sp-text);
}
.sp-title em { font-style: italic; color: var(--sp-accent-alt); }

.sp-subtitle {
  font-size: var(--sp-text-md);
  color: var(--sp-text-soft);
  line-height: 1.6;
  max-width: 540px;
}

.sp-body {
  font-size: var(--sp-text-base);
  color: var(--sp-text-muted);
  line-height: 1.7;
}

.sp-label {
  font-size: var(--sp-text-sm);
  font-weight: 600;
  letter-spacing: 0.04em;
  color: var(--sp-text-soft);
}

/* Texto en secciones inversas (fondo oscuro) */
.sp-section--dark .sp-title    { color: var(--sp-text-inverse); }
.sp-section--dark .sp-subtitle { color: var(--sp-text-inverse-soft); }
.sp-section--dark .sp-eyebrow  { color: rgba(255,255,255,0.4); }
```

---

## 9. Botones

```css
.sp-btn {
  display: inline-flex;
  align-items: center;
  gap: var(--sp-gap-xs);
  font-family: var(--sp-font-body);
  font-size: var(--sp-text-sm);
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  padding: 12px 24px;
  border-radius: var(--sp-radius-pill);
  border: 1px solid transparent;
  transition: background var(--sp-transition), color var(--sp-transition),
              border-color var(--sp-transition), transform var(--sp-transition);
  cursor: pointer;
  white-space: nowrap;
}
.sp-btn:active { transform: scale(0.97); }

/* Primario — sólido oscuro */
.sp-btn--primary {
  background: var(--sp-accent);
  color: var(--sp-bg);
  border-color: var(--sp-accent);
}
.sp-btn--primary:hover {
  background: var(--sp-accent-hover);
  border-color: var(--sp-accent-hover);
}

/* Secundario — outline */
.sp-btn--outline {
  background: transparent;
  color: var(--sp-text);
  border-color: var(--sp-border-strong);
}
.sp-btn--outline:hover {
  background: var(--sp-bg-muted);
}

/* Ghost — sin borde */
.sp-btn--ghost {
  background: transparent;
  color: var(--sp-text-muted);
  padding-left: 0; padding-right: 0;
}
.sp-btn--ghost:hover { color: var(--sp-text); }

/* Dorado — acción de lujo */
.sp-btn--gold {
  background: var(--sp-accent-alt);
  color: #0F0F0F;
  border-color: var(--sp-accent-alt);
}
.sp-btn--gold:hover { background: var(--sp-accent-alt-hover); }

/* Inverso — sobre fondo oscuro */
.sp-btn--inverse {
  background: var(--sp-text-inverse);
  color: var(--sp-bg-dark);
}
.sp-btn--inverse:hover { background: #E8E8E6; }

/* Full width en móvil */
@media (max-width: 639px) {
  .sp-btn--full-mobile { width: 100%; justify-content: center; }
}
```

---

## 10. Cards de Producto / Feature

```css
.sp-card {
  background: var(--sp-bg-soft);
  border: 1px solid var(--sp-border);
  border-radius: var(--sp-radius-md);
  overflow: hidden;
  transition: border-color var(--sp-transition), transform var(--sp-transition);
}
.sp-card:hover {
  border-color: var(--sp-border-strong);
  transform: translateY(-2px);
}

.sp-card__media {
  aspect-ratio: 4/3;
  overflow: hidden;
}
.sp-card__media img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform var(--sp-transition-slow);
}
.sp-card:hover .sp-card__media img { transform: scale(1.04); }

.sp-card__body {
  padding: var(--sp-gap-md);
  display: flex;
  flex-direction: column;
  gap: var(--sp-gap-xs);
}

.sp-card__tag {
  font-size: var(--sp-text-xs);
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--sp-text-muted);
}

.sp-card__title {
  font-family: var(--sp-font-display);
  font-size: var(--sp-text-lg);
  line-height: 1.2;
  color: var(--sp-text);
}

.sp-card__desc {
  font-size: var(--sp-text-sm);
  color: var(--sp-text-muted);
  line-height: 1.6;
}

/* Card horizontal — imagen + texto lado a lado (patrón referencia No.22) */
.sp-card--horizontal {
  display: grid;
  grid-template-columns: 1fr;
}
@media (min-width: 768px) {
  .sp-card--horizontal {
    grid-template-columns: 320px 1fr;
    align-items: stretch;
  }
  .sp-card--horizontal .sp-card__media {
    aspect-ratio: auto;
    height: 100%;
    min-height: 240px;
  }
}

/* Card con fondo oscuro */
.sp-card--dark {
  background: var(--sp-bg-muted);
  border-color: #2A2A2A;
  color: var(--sp-text-inverse);
}
.sp-section--dark .sp-card--dark .sp-card__title  { color: var(--sp-text-inverse); }
.sp-section--dark .sp-card--dark .sp-card__desc   { color: var(--sp-text-inverse-soft); }
```

---

## 11. Newsletter / Formulario de Captura

Diseño compacto sobre imagen/fondo, con input prominente. Patrón de referencia.

```css
.sp-newsletter {
  position: relative;
  overflow: hidden;
  background: var(--sp-bg-muted);
}

/* Con fondo de imagen */
.sp-newsletter__bg {
  position: absolute;
  inset: 0;
  object-fit: cover;
  width: 100%; height: 100%;
  opacity: 0.4;
}

.sp-newsletter__content {
  position: relative;
  z-index: 1;
  padding: var(--sp-gap-xl) var(--sp-section-px);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--sp-gap-md);
  text-align: center;
  max-width: 640px;
  margin: 0 auto;
}

.sp-newsletter__form {
  display: flex;
  flex-direction: column;
  gap: var(--sp-gap-sm);
  width: 100%;
}

@media (min-width: 640px) {
  .sp-newsletter__form {
    flex-direction: row;
    gap: 0;
  }
}

.sp-newsletter__input {
  flex: 1;
  font-family: var(--sp-font-body);
  font-size: var(--sp-text-base);
  color: var(--sp-text);
  background: rgba(255,255,255,0.85);
  border: 1px solid var(--sp-border);
  border-radius: var(--sp-radius-pill);
  padding: 13px 22px;
  outline: none;
  transition: border-color var(--sp-transition), background var(--sp-transition);
  backdrop-filter: blur(8px);
}
.sp-newsletter__input:focus {
  background: rgba(255,255,255,0.98);
  border-color: var(--sp-border-strong);
}
.sp-newsletter__input::placeholder { color: var(--sp-text-muted); }

@media (min-width: 640px) {
  .sp-newsletter__input {
    border-radius: var(--sp-radius-pill) 0 0 var(--sp-radius-pill);
    border-right: none;
  }
  .sp-newsletter__form .sp-btn {
    border-radius: 0 var(--sp-radius-pill) var(--sp-radius-pill) 0;
  }
}

.sp-newsletter__consent {
  display: flex;
  align-items: flex-start;
  gap: var(--sp-gap-xs);
  font-size: var(--sp-text-xs);
  color: var(--sp-text-muted);
  text-align: left;
}
.sp-newsletter__consent a { text-decoration: underline; }
```

---

## 12. Footer — Por Secciones + Pie con Copyright

```css
/* ── Footer principal — columnas ── */
.sp-footer {
  background: var(--sp-bg-dark);
  color: var(--sp-text-inverse);
  padding: var(--sp-gap-xl) 0 0;
}

.sp-footer__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--sp-gap-lg);
  padding: 0 var(--sp-section-px) var(--sp-gap-xl);
  max-width: 1200px;
  margin: 0 auto;
}

@media (min-width: 640px) {
  .sp-footer__grid { grid-template-columns: repeat(2, 1fr); }
}
@media (min-width: 1024px) {
  .sp-footer__grid {
    grid-template-columns: 2fr repeat(4, 1fr);
    padding: 0 var(--sp-gap-xl) var(--sp-gap-xl);
  }
}

.sp-footer__brand {
  display: flex;
  flex-direction: column;
  gap: var(--sp-gap-sm);
}
.sp-footer__logo {
  font-family: var(--sp-font-display);
  font-size: var(--sp-text-md);
  font-style: italic;
  color: var(--sp-text-inverse);
}
.sp-footer__tagline {
  font-size: var(--sp-text-sm);
  color: var(--sp-text-inverse-soft);
  line-height: 1.5;
  max-width: 220px;
}

.sp-footer__col-title {
  font-size: var(--sp-text-xs);
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--sp-text-inverse);
  margin-bottom: var(--sp-gap-sm);
}

.sp-footer__links {
  display: flex;
  flex-direction: column;
  gap: var(--sp-gap-xs);
  list-style: none;
}

.sp-footer__link {
  font-size: var(--sp-text-sm);
  color: var(--sp-text-inverse-soft);
  transition: color var(--sp-transition);
}
.sp-footer__link:hover { color: var(--sp-text-inverse); }

/* Iconos sociales en footer */
.sp-footer__social {
  display: flex;
  gap: var(--sp-gap-sm);
  margin-top: var(--sp-gap-sm);
}
.sp-footer__social-link {
  color: var(--sp-text-inverse-soft);
  transition: color var(--sp-transition);
}
.sp-footer__social-link:hover { color: var(--sp-text-inverse); }

/* ── Pie del footer — copyright ── */
.sp-footer__bottom {
  border-top: 1px solid rgba(255,255,255,0.08);
  padding: var(--sp-gap-md) var(--sp-section-px);
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--sp-gap-xs);
  text-align: center;
}

@media (min-width: 768px) {
  .sp-footer__bottom {
    flex-direction: row;
    justify-content: space-between;
    padding: var(--sp-gap-md) var(--sp-gap-xl);
    text-align: left;
  }
}

.sp-footer__copyright {
  font-size: var(--sp-text-xs);
  color: var(--sp-text-inverse-soft);
  letter-spacing: 0.02em;
}

.sp-footer__made-by {
  font-size: var(--sp-text-xs);
  color: rgba(255,255,255,0.3);
  letter-spacing: 0.04em;
  text-transform: uppercase;
}
.sp-footer__made-by span { color: var(--sp-accent-alt); }
```

El componente Footer en React genera el año automáticamente:

```tsx
// Footer.tsx
const currentYear = new Date().getFullYear();

// En el JSX:
<div className="sp-footer__copyright">
  © {currentYear} NombreEmpresa. Todos los derechos reservados.
</div>
<div className="sp-footer__made-by">
  <span>Gestas AI</span> — Desarrollo de soluciones con IA
</div>
```

---

## 13. Modal

```css
.sp-modal-overlay {
  position: fixed; inset: 0;
  background: var(--sp-bg-overlay);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 2000;
  opacity: 0;
  pointer-events: none;
  transition: opacity var(--sp-transition);
}
.sp-modal-overlay--open {
  opacity: 1;
  pointer-events: all;
}

.sp-modal {
  background: var(--sp-bg);
  border-radius: var(--sp-radius-lg) var(--sp-radius-lg) 0 0;
  border: 1px solid var(--sp-border);
  width: 100%;
  max-height: 92svh;
  overflow-y: auto;
  transform: translateY(20px);
  transition: transform var(--sp-transition);
}
.sp-modal-overlay--open .sp-modal { transform: translateY(0); }

@media (min-width: 640px) {
  .sp-modal-overlay { align-items: center; }
  .sp-modal {
    border-radius: var(--sp-radius-lg);
    max-width: 520px;
    max-height: 85svh;
  }
}

.sp-modal__header {
  display: flex; justify-content: space-between; align-items: center;
  padding: var(--sp-gap-md) var(--sp-gap-lg);
  border-bottom: 1px solid var(--sp-border);
  position: sticky; top: 0;
  background: var(--sp-bg);
}
.sp-modal__title {
  font-family: var(--sp-font-display);
  font-size: var(--sp-text-lg);
  color: var(--sp-text);
}
.sp-modal__body   { padding: var(--sp-gap-lg); }
.sp-modal__footer {
  padding: var(--sp-gap-md) var(--sp-gap-lg);
  border-top: 1px solid var(--sp-border);
  display: flex; gap: var(--sp-gap-sm); justify-content: flex-end;
  background: var(--sp-bg-soft);
}
```

---

## 14. Nomenclatura — Prefijo `sp-` + BEM

```
sp-header / sp-header__logo / sp-header__nav / sp-header__nav-link
sp-mobile-menu / sp-mobile-menu__link / sp-mobile-menu--open
sp-hero / sp-hero__content / sp-hero__title / sp-hero__subtitle / sp-hero__scroll
sp-hero__corner / sp-hero__sidenav / sp-hero__sidenav-item
sp-page / sp-container / sp-section / sp-section--soft / sp-section--dark
sp-grid / sp-grid--2 / sp-grid--3 / sp-grid--4 / sp-grid--asymmetric
sp-section-center / sp-eyebrow / sp-title / sp-subtitle / sp-body / sp-label
sp-btn / sp-btn--primary / sp-btn--outline / sp-btn--ghost / sp-btn--gold / sp-btn--inverse
sp-card / sp-card__media / sp-card__body / sp-card__title / sp-card--horizontal / sp-card--dark
sp-newsletter / sp-newsletter__input / sp-newsletter__form / sp-newsletter__consent
sp-footer / sp-footer__grid / sp-footer__col-title / sp-footer__links / sp-footer__link
sp-footer__social / sp-footer__bottom / sp-footer__copyright / sp-footer__made-by
sp-modal-overlay / sp-modal / sp-modal__header / sp-modal__title / sp-modal__body
sp-icon / sp-icon--sm / sp-icon--md / sp-icon--lg
```

---

## 15. Animaciones y Micro-interacciones

```css
/* Entrada de elementos al hacer scroll */
.sp-reveal {
  opacity: 0;
  transform: translateY(16px);
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.sp-reveal--visible {
  opacity: 1;
  transform: translateY(0);
}

/* Stagger para listas de cards */
.sp-reveal:nth-child(2) { transition-delay: 0.1s; }
.sp-reveal:nth-child(3) { transition-delay: 0.2s; }
.sp-reveal:nth-child(4) { transition-delay: 0.3s; }

/* Separador decorativo */
.sp-divider {
  border: none;
  border-top: 1px solid var(--sp-border);
  margin: 0;
}

/* Badge/etiqueta de sección */
.sp-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: var(--sp-text-xs);
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 4px 12px;
  border: 1px solid var(--sp-border);
  border-radius: var(--sp-radius-pill);
  color: var(--sp-text-muted);
  background: var(--sp-bg-soft);
}
```

---

## 16. Checklist de entrega — SPA Web Cliente

- [ ] ¿El diseño se construyó primero para 375px? ¿Breakpoints van hacia arriba `(min-width)`?
- [ ] ¿Los elementos están centrados y apilados en móvil?
- [ ] ¿El header es sticky, minimalista y con `nav` centrada en desktop?
- [ ] ¿El footer tiene columnas temáticas + pie con copyright + año automático + "Gestas AI — Desarrollo de soluciones con IA"?
- [ ] ¿Todos los estilos están en `styles.css`? ¿Cero `style={{}}` en JSX?
- [ ] ¿Las fuentes son locales (`./assets/fonts/`)? ¿Cero CDN?
- [ ] ¿Los iconos vienen de `lucide-react` instalado? ¿Cero CDN?
- [ ] ¿La paleta usa variables CSS de `:root`? ¿Cero colores literales en reglas?
- [ ] ¿Todas las clases llevan prefijo `sp-`? ¿Cero etiquetas HTML estiladas directamente?
- [ ] ¿El modo oscuro funciona con `body.dark` o `[data-theme="dark"]`?
- [ ] ¿Las secciones alternas usan `sp-section--soft` o `sp-section--dark`?
- [ ] ¿El padding de sección es ≤ 48px en móvil y ≤ 80px en desktop?
- [ ] ¿El modal es bottom-sheet en móvil y centrado en desktop?
- [ ] ¿Los elementos tienen micro-interacciones (`hover`, `transition`, `sp-reveal`)?

---

## 17. Anti-patrones

| Anti-patrón | Problema | Corrección |
|-------------|----------|------------|
| `style={{ padding: '60px' }}` en JSX | Rompe separación | `.sp-section` con `--sp-section-py` |
| `@media (max-width: ...)` como base | Desktop-first, móvil roto | Base móvil + `@media (min-width: ...)` |
| `@import url("fonts.google...")` | CDN externo | Fuentes en `./assets/fonts/` |
| `<i class="fa fa-icon">` o CDN de iconos | Dependencia runtime | `<Icon />` de `lucide-react` |
| Colores literales en CSS | Imposible cambiar tema | `var(--sp-accent)` |
| `padding: 120px` en secciones | Desperdicio de espacio | Máx `80px` desktop, `48px` móvil |
| Cards sin hover state | Sin feedback visual | `transform: translateY(-2px)` en hover |
| Footer en una sola línea | Sin estructura | Columnas temáticas + pie separado |
| Año hardcodeado en copyright | Se desactualiza | `new Date().getFullYear()` en JSX |
| Fuente genérica (Inter, Roboto) como display | Sin carácter visual | Serif editorial o sans distintivo |
| Clases genéricas `.card`, `.btn` sin prefijo | Colisiones de estilo | `.sp-card`, `.sp-btn` |

---

## Resumen

> **Mobile-first absoluto**: 375px primero, desktop es amplificación progresiva.
> **Cajas apilables**: grid de 1 columna centrada en móvil, expande en tablet y desktop.
> **Compacto y sofisticado**: padding reducido, jerarquía por tipografía, densidad elegante.
> **Modo claro por defecto**: oscuro opcional con `body.dark` — variables cubren ambos.
> **Sin dependencias externas**: fuentes e iconos en el proyecto, cero CDN en runtime.
> **Un archivo CSS**: `styles.css` con prefijo `sp-` en todo. Cero CSS-in-JS.
> **Footer estructurado**: columnas temáticas + pie con año automático + firma Gestas AI.
> **Estética editorial**: serif de display + sans body, tipografía como elemento de diseño.

---

## 18. Identidad Visual Unificada — Sistema de Diseño Coherente

El principio más importante cuando se trabaja con múltiples secciones, módulos o páginas de un mismo proyecto: **todo debe sentirse parte del mismo universo visual**. No se mezclan estilos entre secciones. Los colores corporativos, la tipografía, los bordes, las sombras y las animaciones se definen una vez y se reutilizan siempre.

### 18.1 Sensación Premium — Los 5 atributos

**Bordes suaves**: `border-radius` generoso en cards (12–20px). Nunca esquinas en ángulo recto en elementos de contenido. Sí en separadores y líneas estructurales.

**Texturas sutiles**: fondos con micro-textura noise cuando el contexto lo permite. Nunca patrones obvios. La textura es casi imperceptible pero da profundidad.

**Sombras muy suaves**: sombras difusas y de largo alcance, nunca las típicas sombras de bootstrap. La sombra dice "este elemento flota sobre la superficie".

```css
/* ── Sistema de sombras premium ── */
--sp-shadow-xs:  0 1px 2px rgba(0,0,0,0.04);
--sp-shadow-sm:  0 2px 8px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
--sp-shadow-md:  0 4px 16px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04);
--sp-shadow-lg:  0 8px 32px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.06);
--sp-shadow-xl:  0 16px 48px rgba(0,0,0,0.12), 0 4px 12px rgba(0,0,0,0.06);
--sp-shadow-hover: 0 12px 40px rgba(0,0,0,0.14), 0 4px 12px rgba(0,0,0,0.06);
```

**Animaciones elegantes**: curvas de easing personalizadas que dan sensación de física real. Nada de `ease-in-out` genérico.

```css
/* ── Easings premium ── */
--sp-ease-out:    cubic-bezier(0.16, 1, 0.3, 1);     /* salida suave, natural */
--sp-ease-in-out: cubic-bezier(0.45, 0, 0.55, 1);    /* entrada/salida simétrica */
--sp-ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1); /* ligero rebote, vivo */
--sp-ease-apple:  cubic-bezier(0.25, 0.46, 0.45, 0.94); /* easing Apple */
```

**Hover states con micro-movimiento**: los elementos reaccionan al cursor con desplazamientos de 2–4px y sombra aumentada. Nunca cambios bruscos.

```css
/* Patrón de hover premium */
.sp-card {
  transition: transform 0.3s var(--sp-ease-out),
              box-shadow 0.3s var(--sp-ease-out);
}
.sp-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--sp-shadow-hover);
}
```

### 18.2 Añadir al `:root` — Variables de sombra y easing

```css
:root {
  /* ── Sombras premium (añadir a variables existentes) ── */
  --sp-shadow-xs:   0 1px 2px rgba(0,0,0,0.04);
  --sp-shadow-sm:   0 2px 8px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  --sp-shadow-md:   0 4px 16px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04);
  --sp-shadow-lg:   0 8px 32px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.06);
  --sp-shadow-xl:   0 16px 48px rgba(0,0,0,0.12), 0 4px 12px rgba(0,0,0,0.06);
  --sp-shadow-hover:0 12px 40px rgba(0,0,0,0.14), 0 4px 12px rgba(0,0,0,0.06);

  /* ── Easings ── */
  --sp-ease-out:    cubic-bezier(0.16, 1, 0.3, 1);
  --sp-ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
  --sp-ease-apple:  cubic-bezier(0.25, 0.46, 0.45, 0.94);

  /* ── Border radius premium ── */
  --sp-radius-card:   16px;
  --sp-radius-xl:     24px;
  --sp-radius-2xl:    32px;
}
```

---

## 19. Cards de Producto — Variantes Premium

### 19.1 Card producto con texto encima de imagen (patrón Apple)

Texto en la parte superior izquierda sobre fondo oscuro, imagen en la parte inferior. CTA flotante. Ratio fijo.

```css
.sp-card-product {
  position: relative;
  border-radius: var(--sp-radius-card);
  overflow: hidden;
  background: var(--sp-bg-muted);
  aspect-ratio: 3/4;         /* vertical, tipo póster */
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: transform 0.35s var(--sp-ease-out),
              box-shadow 0.35s var(--sp-ease-out);
  box-shadow: var(--sp-shadow-sm);
}
.sp-card-product:hover {
  transform: translateY(-4px);
  box-shadow: var(--sp-shadow-hover);
}

.sp-card-product__header {
  padding: var(--sp-gap-md) var(--sp-gap-md) 0;
  z-index: 1;
}
.sp-card-product__eyebrow {
  font-size: var(--sp-text-xs);
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--sp-text-muted);
  margin-bottom: 4px;
}
.sp-card-product__title {
  font-family: var(--sp-font-display);
  font-size: clamp(18px, 3vw, 26px);
  line-height: 1.15;
  letter-spacing: -0.02em;
  color: var(--sp-text);
}
.sp-card-product__desc {
  font-size: var(--sp-text-sm);
  color: var(--sp-text-muted);
  margin-top: 6px;
  line-height: 1.5;
}

.sp-card-product__media {
  flex: 1;
  position: relative;
  overflow: hidden;
}
.sp-card-product__media img {
  width: 100%; height: 100%;
  object-fit: contain;        /* producto centrado, sin recorte */
  padding: var(--sp-gap-sm);
  transition: transform 0.5s var(--sp-ease-out);
}
.sp-card-product:hover .sp-card-product__media img {
  transform: scale(1.04);
}

.sp-card-product__cta {
  position: absolute;
  bottom: var(--sp-gap-sm);
  right: var(--sp-gap-sm);
  width: 36px; height: 36px;
  border-radius: 50%;
  background: var(--sp-text);
  color: var(--sp-bg);
  display: flex; align-items: center; justify-content: center;
  transition: transform 0.2s var(--sp-ease-spring),
              background 0.2s ease;
}
.sp-card-product:hover .sp-card-product__cta {
  transform: scale(1.1);
  background: var(--sp-accent-alt);
  color: #fff;
}

/* Card oscura — sobre fondo negro */
.sp-card-product--dark {
  background: #1A1A1A;
}
.sp-card-product--dark .sp-card-product__title { color: #FFFFFF; }
.sp-card-product--dark .sp-card-product__desc  { color: rgba(255,255,255,0.55); }
.sp-card-product--dark .sp-card-product__cta   { background: #FFFFFF; color: #000; }
```

### 19.2 Card hero de producto — texto sobre imagen full-bleed (patrón AirPods)

Imagen de fondo, texto abajo izquierda, precio, CTAs abajo derecha.

```css
.sp-card-hero {
  position: relative;
  border-radius: var(--sp-radius-card);
  overflow: hidden;
  aspect-ratio: 16/7;
  display: flex;
  align-items: flex-end;
  background: var(--sp-bg-dark);
  box-shadow: var(--sp-shadow-md);
}

.sp-card-hero__bg {
  position: absolute; inset: 0;
  object-fit: cover; width: 100%; height: 100%;
  transition: transform 0.6s var(--sp-ease-out);
}
.sp-card-hero:hover .sp-card-hero__bg { transform: scale(1.03); }

/* Gradiente inferior para legibilidad del texto */
.sp-card-hero::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(
    to top,
    rgba(0,0,0,0.55) 0%,
    rgba(0,0,0,0.15) 40%,
    transparent 70%
  );
}

.sp-card-hero__content {
  position: relative; z-index: 1;
  width: 100%;
  padding: var(--sp-gap-lg);
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: var(--sp-gap-md);
}

.sp-card-hero__info { display: flex; flex-direction: column; gap: 4px; }

.sp-card-hero__title {
  font-family: var(--sp-font-display);
  font-size: clamp(22px, 4vw, 40px);
  font-weight: 400;
  color: #FFFFFF;
  line-height: 1.1;
  letter-spacing: -0.02em;
}
.sp-card-hero__tagline {
  font-size: var(--sp-text-sm);
  color: rgba(255,255,255,0.75);
}
.sp-card-hero__price {
  font-size: var(--sp-text-sm);
  color: rgba(255,255,255,0.6);
  margin-top: 4px;
}

.sp-card-hero__actions {
  display: flex;
  gap: var(--sp-gap-xs);
  flex-shrink: 0;
}

/* Botón pill transparente sobre oscuro */
.sp-btn--glass {
  background: rgba(255,255,255,0.15);
  color: #FFFFFF;
  border: 1px solid rgba(255,255,255,0.3);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: var(--sp-radius-pill);
  padding: 8px 18px;
  font-size: var(--sp-text-sm);
  font-weight: 500;
  transition: background 0.2s ease, border-color 0.2s ease;
}
.sp-btn--glass:hover {
  background: rgba(255,255,255,0.25);
  border-color: rgba(255,255,255,0.5);
}

/* Móvil: acciones apiladas */
@media (max-width: 639px) {
  .sp-card-hero__content  { flex-direction: column; align-items: flex-start; }
  .sp-card-hero__actions  { width: 100%; }
  .sp-card-hero           { aspect-ratio: 4/5; }
}
```

### 19.3 Card con título gigante superpuesto al producto (patrón AirPods Max)

Fondo blanco, tipografía masiva detrás del producto. Efecto de profundidad visual.

```css
.sp-card-type {
  position: relative;
  border-radius: var(--sp-radius-card);
  overflow: hidden;
  background: var(--sp-bg-soft);
  padding: var(--sp-gap-xl) var(--sp-gap-lg) var(--sp-gap-lg);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  min-height: 360px;
  box-shadow: var(--sp-shadow-sm);
}

/* Tipografía gigante de fondo */
.sp-card-type__bg-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-family: var(--sp-font-display);
  font-size: clamp(60px, 15vw, 140px);
  font-weight: 700;
  letter-spacing: -0.04em;
  color: var(--sp-text);
  opacity: 0.06;
  white-space: nowrap;
  pointer-events: none;
  user-select: none;
  z-index: 0;
}

.sp-card-type__media {
  position: relative; z-index: 1;
  flex: 1;
  display: flex; align-items: center; justify-content: center;
}
.sp-card-type__media img {
  max-height: 240px;
  object-fit: contain;
  transition: transform 0.4s var(--sp-ease-out);
}
.sp-card-type:hover .sp-card-type__media img { transform: translateY(-6px); }

.sp-card-type__footer {
  position: relative; z-index: 1;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  width: 100%;
  padding-top: var(--sp-gap-sm);
}
.sp-card-type__info { text-align: left; }
.sp-card-type__name {
  font-size: var(--sp-text-md);
  font-weight: 600;
  color: var(--sp-text);
}
.sp-card-type__price {
  font-size: var(--sp-text-sm);
  color: var(--sp-text-muted);
}
```

---

## 20. Sección Carrusel Horizontal — Cards Deslizables

Patrón Apple "Descubre el Mac" / "Por qué comprar en Apple" — grid de cards con scroll horizontal suave, flechas de navegación.

```css
.sp-carousel {
  position: relative;
}

.sp-carousel__header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding: 0 var(--sp-section-px);
  margin-bottom: var(--sp-gap-md);
}
.sp-carousel__title {
  font-family: var(--sp-font-display);
  font-size: clamp(22px, 4vw, 38px);
  font-weight: 400;
  letter-spacing: -0.025em;
  color: var(--sp-text);
}
.sp-carousel__link {
  font-size: var(--sp-text-sm);
  color: var(--sp-accent);
  white-space: nowrap;
  transition: opacity 0.2s ease;
}
.sp-carousel__link:hover { opacity: 0.7; }

.sp-carousel__track-wrap {
  overflow-x: auto;
  scroll-behavior: smooth;
  scrollbar-width: none;
  padding: var(--sp-gap-sm) var(--sp-section-px) var(--sp-gap-lg);
  cursor: grab;
}
.sp-carousel__track-wrap:active { cursor: grabbing; }
.sp-carousel__track-wrap::-webkit-scrollbar { display: none; }

.sp-carousel__track {
  display: flex;
  gap: var(--sp-gap-sm);
  width: max-content;
}

/* Item del carrusel — ancho fijo, snap */
.sp-carousel__item {
  width: clamp(240px, 40vw, 300px);
  flex-shrink: 0;
  scroll-snap-align: start;
}
.sp-carousel__track-wrap { scroll-snap-type: x mandatory; }

/* Flechas de navegación */
.sp-carousel__nav {
  display: flex;
  gap: var(--sp-gap-xs);
  padding: 0 var(--sp-section-px);
  margin-top: var(--sp-gap-sm);
  justify-content: flex-end;
}
.sp-carousel__arrow {
  width: 32px; height: 32px;
  border-radius: 50%;
  border: 1px solid var(--sp-border-strong);
  background: var(--sp-bg);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  color: var(--sp-text);
  transition: background 0.2s ease, border-color 0.2s ease;
  box-shadow: var(--sp-shadow-xs);
}
.sp-carousel__arrow:hover {
  background: var(--sp-bg-muted);
  border-color: var(--sp-text);
}
.sp-carousel__arrow:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

@media (min-width: 1024px) {
  .sp-carousel__item { width: clamp(260px, 22vw, 320px); }
}
```

---

## 21. Sección Acordeón — Contenido Expandible con Media Lateral

Patrón "Descubre el mundo de Apple" — lista de items en la izquierda, imagen dinámica a la derecha que cambia según el item activo.

```css
.sp-accordion-section {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--sp-gap-lg);
  background: var(--sp-bg-soft);
  border-radius: var(--sp-radius-xl);
  padding: var(--sp-gap-xl) var(--sp-gap-lg);
}

@media (min-width: 768px) {
  .sp-accordion-section {
    grid-template-columns: 1fr 1fr;
    align-items: start;
    padding: var(--sp-gap-xl);
  }
}

.sp-accordion__list { display: flex; flex-direction: column; }

.sp-accordion__item {
  border-bottom: 1px solid var(--sp-border);
}
.sp-accordion__item:first-child { border-top: 1px solid var(--sp-border); }

.sp-accordion__trigger {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--sp-gap-md) 0;
  cursor: pointer;
  width: 100%;
  text-align: left;
}
.sp-accordion__trigger-label {
  font-family: var(--sp-font-display);
  font-size: var(--sp-text-lg);
  font-weight: 400;
  color: var(--sp-text);
  transition: color 0.2s ease;
}
.sp-accordion__item--active .sp-accordion__trigger-label {
  color: var(--sp-accent);
}
.sp-accordion__icon {
  transition: transform 0.3s var(--sp-ease-out), color 0.2s ease;
  color: var(--sp-text-muted);
  flex-shrink: 0;
}
.sp-accordion__item--active .sp-accordion__icon {
  transform: rotate(180deg);
  color: var(--sp-accent);
}

.sp-accordion__body {
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.4s var(--sp-ease-out), opacity 0.3s ease;
  opacity: 0;
}
.sp-accordion__item--active .sp-accordion__body {
  max-height: 400px;
  opacity: 1;
}
.sp-accordion__body-inner {
  padding: 0 0 var(--sp-gap-md);
  font-size: var(--sp-text-base);
  color: var(--sp-text-muted);
  line-height: 1.7;
}

/* Media lateral — imagen que cambia con el item activo */
.sp-accordion__media {
  position: sticky;
  top: calc(var(--sp-header-h) + var(--sp-gap-md));
  border-radius: var(--sp-radius-lg);
  overflow: hidden;
  aspect-ratio: 4/3;
  background: var(--sp-bg-muted);
}
.sp-accordion__media img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: opacity 0.4s ease;
}
.sp-accordion__media img.sp-fade-out { opacity: 0; }
.sp-accordion__media img.sp-fade-in  { opacity: 1; }
```

---

## 22. Sección de Comparación — Grid de Features por Columna

Patrón tabla de comparación de productos (AirPods) — icono + texto centrado, columnas por producto.

```css
.sp-compare {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.sp-compare::-webkit-scrollbar { display: none; }

.sp-compare__table {
  display: grid;
  min-width: 600px;
  border-collapse: collapse;
}

/* Header de columnas — nombre del producto */
.sp-compare__header {
  display: grid;
  grid-template-columns: 1fr repeat(var(--cols, 4), 1fr);
  padding-bottom: var(--sp-gap-md);
  border-bottom: 1px solid var(--sp-border);
}
.sp-compare__col-name {
  font-size: var(--sp-text-sm);
  font-weight: 600;
  color: var(--sp-text-soft);
  text-align: center;
}

/* Fila de feature */
.sp-compare__row {
  display: grid;
  grid-template-columns: 1fr repeat(var(--cols, 4), 1fr);
  padding: var(--sp-gap-md) 0;
  border-bottom: 1px solid var(--sp-border);
  align-items: center;
}

/* Celda de feature */
.sp-compare__cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  text-align: center;
  padding: 0 var(--sp-gap-sm);
}
.sp-compare__cell-icon {
  color: var(--sp-text-soft);
  opacity: 0.7;
}
.sp-compare__cell-text {
  font-size: var(--sp-text-xs);
  color: var(--sp-text-muted);
  line-height: 1.4;
}

/* Celda vacía = guión */
.sp-compare__cell--empty::before {
  content: '—';
  color: var(--sp-border-strong);
  font-size: var(--sp-text-md);
}
```

---

## 23. Sección Bento Grid — Mosaico de Contenido

Mosaico de tarjetas de distintos tamaños en un grid. Algunas cards ocupan 2 columnas o 2 filas. Efecto moderno y visual.

```css
.sp-bento {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--sp-gap-sm);
}

@media (min-width: 640px) {
  .sp-bento {
    grid-template-columns: repeat(2, 1fr);
    grid-auto-rows: minmax(240px, auto);
  }
}
@media (min-width: 1024px) {
  .sp-bento {
    grid-template-columns: repeat(4, 1fr);
    grid-auto-rows: minmax(200px, auto);
  }
}

.sp-bento__item {
  border-radius: var(--sp-radius-card);
  overflow: hidden;
  background: var(--sp-bg-soft);
  border: 1px solid var(--sp-border);
  padding: var(--sp-gap-lg);
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  position: relative;
  transition: transform 0.3s var(--sp-ease-out),
              box-shadow 0.3s var(--sp-ease-out);
  box-shadow: var(--sp-shadow-xs);
}
.sp-bento__item:hover {
  transform: translateY(-2px);
  box-shadow: var(--sp-shadow-md);
}

/* Variantes de tamaño */
.sp-bento__item--wide  { grid-column: span 2; }
.sp-bento__item--tall  { grid-row: span 2; }
.sp-bento__item--large { grid-column: span 2; grid-row: span 2; }

/* Fondo oscuro para items destacados */
.sp-bento__item--dark {
  background: var(--sp-bg-dark);
  border-color: transparent;
  color: var(--sp-text-inverse);
}

/* Imagen de fondo en bento */
.sp-bento__item--media {
  padding: 0;
  background: none;
}
.sp-bento__item--media .sp-bento__bg {
  position: absolute; inset: 0;
  object-fit: cover; width: 100%; height: 100%;
  transition: transform 0.5s var(--sp-ease-out);
}
.sp-bento__item--media:hover .sp-bento__bg { transform: scale(1.04); }
.sp-bento__item--media .sp-bento__content {
  position: relative; z-index: 1;
  padding: var(--sp-gap-lg);
  background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 60%);
  height: 100%;
  display: flex; flex-direction: column; justify-content: flex-end;
}

.sp-bento__eyebrow {
  font-size: var(--sp-text-xs);
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--sp-text-muted);
  margin-bottom: 6px;
}
.sp-bento__item--dark .sp-bento__eyebrow { color: rgba(255,255,255,0.5); }

.sp-bento__title {
  font-family: var(--sp-font-display);
  font-size: clamp(18px, 2.5vw, 28px);
  line-height: 1.15;
  letter-spacing: -0.02em;
  color: var(--sp-text);
}
.sp-bento__item--dark .sp-bento__title,
.sp-bento__item--media .sp-bento__title { color: #FFFFFF; }
```

---

## 24. Sección Showcase — Imagen Grande + Info Lateral

Patrón "Pásate al Mac" — dos cards grandes lado a lado, cada una con texto arriba e imagen abajo. Equilibrio visual.

```css
.sp-showcase {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--sp-gap-sm);
}
@media (min-width: 768px) {
  .sp-showcase { grid-template-columns: 1fr 1fr; }
}

.sp-showcase__item {
  border-radius: var(--sp-radius-xl);
  background: var(--sp-bg-soft);
  border: 1px solid var(--sp-border);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  padding: var(--sp-gap-xl) var(--sp-gap-lg) 0;
  gap: var(--sp-gap-md);
  transition: box-shadow 0.3s var(--sp-ease-out);
  box-shadow: var(--sp-shadow-xs);
}
.sp-showcase__item:hover { box-shadow: var(--sp-shadow-md); }

.sp-showcase__header { text-align: center; }
.sp-showcase__title {
  font-family: var(--sp-font-display);
  font-size: clamp(22px, 3.5vw, 34px);
  line-height: 1.1;
  letter-spacing: -0.025em;
  color: var(--sp-text);
}
.sp-showcase__desc {
  font-size: var(--sp-text-sm);
  color: var(--sp-text-muted);
  line-height: 1.6;
  margin-top: 8px;
}
.sp-showcase__link {
  font-size: var(--sp-text-sm);
  color: var(--sp-accent);
  margin-top: 6px;
  display: inline-block;
}
.sp-showcase__link:hover { text-decoration: underline; }

.sp-showcase__media {
  flex: 1;
  overflow: hidden;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  min-height: 200px;
}
.sp-showcase__media img {
  max-width: 100%;
  object-fit: contain;
  transition: transform 0.4s var(--sp-ease-out);
}
.sp-showcase__item:hover .sp-showcase__media img { transform: translateY(-4px); }
```

---

## 25. Sección Specs / Features en Franja de Color

Banner ancho con fondo de color o imagen, texto izquierda, imagen/producto derecha. Versión compacta de hero para secciones internas.

```css
.sp-feature-band {
  border-radius: var(--sp-radius-xl);
  overflow: hidden;
  display: grid;
  grid-template-columns: 1fr;
  background: var(--sp-bg-muted);
  border: 1px solid var(--sp-border);
  box-shadow: var(--sp-shadow-sm);
}
@media (min-width: 768px) {
  .sp-feature-band {
    grid-template-columns: 1fr 1fr;
    min-height: 280px;
  }
}

.sp-feature-band__content {
  padding: var(--sp-gap-xl) var(--sp-gap-lg);
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: var(--sp-gap-sm);
}
.sp-feature-band__title {
  font-family: var(--sp-font-display);
  font-size: clamp(20px, 3vw, 32px);
  line-height: 1.1;
  letter-spacing: -0.02em;
  color: var(--sp-text);
}
.sp-feature-band__desc {
  font-size: var(--sp-text-base);
  color: var(--sp-text-muted);
  line-height: 1.6;
}
.sp-feature-band__actions {
  display: flex; gap: var(--sp-gap-sm);
  flex-wrap: wrap; margin-top: var(--sp-gap-xs);
}

.sp-feature-band__media {
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--sp-bg-soft);
  min-height: 200px;
}
.sp-feature-band__media img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.5s var(--sp-ease-out);
}
.sp-feature-band:hover .sp-feature-band__media img { transform: scale(1.04); }
```

---

## 26. Sección Stats / Números Impactantes

Fila de métricas grandes con etiqueta debajo. Muy eficaz para transmitir credibilidad.

```css
.sp-stats {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1px;                          /* separadores por gap entre celdas */
  background: var(--sp-border);      /* el background del grid hace de separador */
  border: 1px solid var(--sp-border);
  border-radius: var(--sp-radius-card);
  overflow: hidden;
}
@media (min-width: 768px) {
  .sp-stats { grid-template-columns: repeat(4, 1fr); }
}

.sp-stat {
  background: var(--sp-bg);
  padding: var(--sp-gap-lg) var(--sp-gap-md);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  text-align: center;
  transition: background 0.2s ease;
}
.sp-stat:hover { background: var(--sp-bg-soft); }

.sp-stat__value {
  font-family: var(--sp-font-display);
  font-size: clamp(32px, 6vw, 56px);
  font-weight: 400;
  line-height: 1;
  letter-spacing: -0.03em;
  color: var(--sp-text);
}
.sp-stat__value em {
  font-style: italic;
  color: var(--sp-accent-alt);
}
.sp-stat__label {
  font-size: var(--sp-text-sm);
  color: var(--sp-text-muted);
  line-height: 1.4;
}
```

---

## 27. Tabla de Datos en Contexto Web (no dashboard)

Para webs de producto que muestran especificaciones técnicas, comparativas, precios, horarios, etc.

```css
.sp-table-wrap {
  border-radius: var(--sp-radius-card);
  border: 1px solid var(--sp-border);
  overflow: hidden;
  box-shadow: var(--sp-shadow-sm);
}

.sp-table {
  width: 100%;
  border-collapse: collapse;
  font-family: var(--sp-font-body);
  font-size: var(--sp-text-sm);
}

.sp-table__thead { background: var(--sp-bg-soft); }

.sp-table__th {
  padding: 12px 16px;
  text-align: left;
  font-size: var(--sp-text-xs);
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--sp-text-muted);
  border-bottom: 1px solid var(--sp-border);
  white-space: nowrap;
}

.sp-table__td {
  padding: 12px 16px;
  border-bottom: 1px solid var(--sp-border);
  color: var(--sp-text-soft);
  vertical-align: middle;
}

.sp-table__row:last-child .sp-table__td { border-bottom: none; }

.sp-table__row {
  transition: background 0.15s ease;
}
.sp-table__row:hover .sp-table__td { background: var(--sp-bg-soft); }

/* Estado destacado — fila con badge FULL / FEATURED */
.sp-table__row--featured .sp-table__td {
  background: color-mix(in srgb, var(--sp-accent) 6%, transparent);
  font-weight: 500;
  color: var(--sp-text);
}

/* Badge inline en celda */
.sp-table__badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: var(--sp-radius-pill);
  font-size: var(--sp-text-xs);
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}
.sp-table__badge--full    { background: #0F0F0F; color: #FFFFFF; }
.sp-table__badge--active  { background: #ECFDF5; color: #10B981; }
.sp-table__badge--pending { background: #FFFBEB; color: #F59E0B; }

/* Scroll horizontal en móvil */
.sp-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
```

---

## 28. Checklist Ampliado — Coherencia Visual entre Secciones

Cuando se crean múltiples secciones o módulos de un mismo proyecto, verificar además:

- [ ] ¿Todas las cards usan el mismo `--sp-radius-card` (16px)?
- [ ] ¿Los hover states son consistentes (siempre `translateY(-3px)` + `shadow-hover`)?
- [ ] ¿Las sombras usan exclusivamente las variables `--sp-shadow-*`?
- [ ] ¿Las animaciones usan los easings `--sp-ease-*` definidos?
- [ ] ¿Los títulos de sección tienen el mismo patrón: eyebrow → título serif → subtítulo?
- [ ] ¿Las secciones alternas usan `sp-section--soft` y `sp-section--dark` de forma rítmica?
- [ ] ¿El botón CTA principal es siempre el mismo estilo en todo el proyecto?
- [ ] ¿Los border-radius son consistentes (cards: 16px, botones: pill, secciones: 24px)?
- [ ] ¿Los colores corporativos solo se modifican en `:root`, nunca en clases específicas?
- [ ] ¿Las tablas web usan `.sp-table` (no `.db-table` del dashboard)?
- [ ] ¿El carrusel tiene scroll snap y flechas de navegación?
- [ ] ¿El acordeón tiene transición suave de max-height y fade?

---

## 29. Anti-patrones Ampliados

| Anti-patrón | Problema | Corrección |
|-------------|----------|------------|
| Cards con `border-radius: 4px` en web cliente | Se siente dashboard, no producto | `var(--sp-radius-card)` → 16px |
| `box-shadow: 0 2px 4px rgba(0,0,0,0.3)` | Sombra dura y genérica | `var(--sp-shadow-md)` difusa |
| `transition: all 0.3s ease` | Afecta propiedades no deseadas | `transition: transform 0.3s var(--sp-ease-out), box-shadow 0.3s ...` |
| `transition: ease-in-out` | Easing genérico, sin vida | `var(--sp-ease-out)` o `var(--sp-ease-apple)` |
| Cards sin sombra ni borde | Planas, sin profundidad | `box-shadow: var(--sp-shadow-sm)` + `border: 1px solid var(--sp-border)` |
| Hover que cambia layout (gap, padding) | Efecto de salto visual | Solo `transform` y `box-shadow` en hover |
| Mezclar estilos sp- y db- en el mismo componente | Incoherencia visual | sp- para web cliente, db- para admin |
| Distintos `border-radius` por sección sin razón | Diseño inconsistente | Variables definidas, nunca literales |
| Texto blanco directo sobre imagen sin gradiente | Ilegible según imagen | Gradiente `linear-gradient(to top, rgba(0,0,0,0.55), transparent)` |
| Stats/números sin `font-variant-numeric: tabular-nums` | Números se mueven al actualizar | Siempre en columnas numéricas |

---

## 30. Sistema de Capas — z-index Arquitectónico

El z-index no se escribe nunca como número literal en las clases. **Siempre a través de variables CSS**. El sistema de capas es una decisión de arquitectura que se define una sola vez en `:root` y se respeta en todo el proyecto.

### 30.1 Variables de z-index — añadir a `:root`

```css
:root {
  /* ── Capas del sistema (de abajo hacia arriba) ── */
  --z-base:        0;       /* flujo normal del documento */
  --z-raised:      1;       /* cards con hover, elementos interactivos */
  --z-float:       10;      /* tooltips locales, dropdowns pequeños */
  --z-sticky:      100;     /* elementos sticky dentro de secciones */
  --z-header:      200;     /* header fijo — siempre sobre el contenido */
  --z-dropdown:    300;     /* menús desplegables del header */
  --z-overlay:     400;     /* fondos oscuros de modal/drawer */
  --z-modal:       500;     /* modales, drawers, sheets */
  --z-toast:       600;     /* notificaciones, toasts — encima de modales */
  --z-tooltip:     700;     /* tooltips globales — siempre visibles */
  --z-cursor:      800;     /* cursores personalizados */
  --z-dev:         9999;    /* elementos de debug — nunca en producción */
}
```

### 30.2 Mapa visual de capas

```
z: 800  ── Cursor personalizado
z: 700  ── Tooltips globales
z: 600  ── Toasts / Notificaciones
z: 500  ── Modales / Drawers / Bottom-sheets
z: 400  ── Overlay oscuro (fondo de modal)
z: 300  ── Dropdowns del header / Submenús
z: 200  ── Header fijo (sp-header)
z: 100  ── Elementos sticky internos (tabla thead, filtros)
z: 10   ── Tooltips locales, badges flotantes
z: 1    ── Cards con hover elevado
z: 0    ── Flujo normal del documento
z: -1   ── Pseudo-elementos de fondo (::before, ::after decorativos)
```

### 30.3 Uso correcto en cada componente

```css
/* ── Header ── */
.sp-header {
  position: fixed;
  z-index: var(--z-header);   /* 200 */
}

/* ── Dropdown de navegación ── */
.sp-dropdown {
  position: absolute;
  z-index: var(--z-dropdown); /* 300 — encima del header */
}

/* ── Overlay de modal ── */
.sp-modal-overlay {
  position: fixed;
  z-index: var(--z-overlay);  /* 400 */
}

/* ── Modal / Drawer / Bottom-sheet ── */
.sp-modal {
  position: fixed;
  z-index: var(--z-modal);    /* 500 — encima del overlay */
}

/* ── Toast / Notificación ── */
.sp-toast {
  position: fixed;
  z-index: var(--z-toast);    /* 600 — encima de modales */
}

/* ── Thead sticky de tabla ── */
.sp-table__thead {
  position: sticky;
  top: 0;
  z-index: var(--z-sticky);   /* 100 */
}

/* ── Card elevada en hover ── */
.sp-card {
  position: relative;
  z-index: var(--z-base);     /* 0 en reposo */
  transition: z-index 0s, transform 0.3s var(--sp-ease-out);
}
.sp-card:hover {
  z-index: var(--z-raised);   /* 1 — sobre cards adyacentes */
}

/* ── Contenido sobre imagen en hero ── */
.sp-hero__content {
  position: relative;
  z-index: var(--z-raised);   /* 1 — sobre el media de fondo */
}
.sp-hero__media {
  position: absolute;
  z-index: var(--z-base);     /* 0 */
}

/* ── Pseudo-elemento decorativo de fondo ── */
.sp-section::before {
  content: '';
  position: absolute;
  z-index: -1;                /* ÚNICO caso donde z-index negativo es correcto */
}
```

### 30.4 Reglas de uso — NO NEGOCIABLES

**Prohibido** escribir z-index como número literal en ninguna clase de componente:
```css
/* ❌ MAL — número mágico, imposible de mantener */
.mi-componente { z-index: 9999; }
.mi-overlay    { z-index: 100; }
.mi-modal      { z-index: 101; }

/* ✅ BIEN — sistema semántico */
.mi-componente { z-index: var(--z-modal); }
.mi-overlay    { z-index: var(--z-overlay); }
```

**Prohibido** el `z-index: 9999` como solución a problemas de capas — indica arquitectura rota. Revisar la jerarquía de `position` y el stacking context.

**Stacking context**: un elemento crea un nuevo stacking context cuando tiene `position` distinto de `static` + `z-index` distinto de `auto`, o cuando tiene `transform`, `opacity < 1`, `filter`, `will-change`, `isolation: isolate`. Esto aísla sus hijos del z-index global.

```css
/* Crear stacking context explícito para aislar capas internas */
.sp-section--isolated {
  isolation: isolate;  /* los z-index internos no compiten con el global */
}
```

### 30.5 Dropdown del header — capa correcta

El dropdown es un caso crítico: debe aparecer encima del header (`z-header: 200`) pero también encima de cualquier contenido de la página.

```css
.sp-header {
  position: fixed;
  z-index: var(--z-header);    /* 200 */
  /* IMPORTANTE: no usar overflow: hidden — bloquearía el dropdown */
}

.sp-header__nav-desktop {
  position: relative;          /* crea contexto para el dropdown */
}

.sp-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  z-index: var(--z-dropdown);  /* 300 — encima del header */
  background: var(--sp-bg);
  border: 1px solid var(--sp-border);
  border-radius: 0 0 var(--sp-radius-md) var(--sp-radius-md);
  min-width: 200px;
  box-shadow: var(--sp-shadow-lg);
  opacity: 0;
  transform: translateY(-8px);
  pointer-events: none;
  transition: opacity 0.2s var(--sp-ease-out),
              transform 0.2s var(--sp-ease-out);
}
.sp-header__nav-link:hover + .sp-dropdown,
.sp-dropdown:hover {
  opacity: 1;
  transform: translateY(0);
  pointer-events: all;
}
```

### 30.6 Toast — Siempre visible, esquina fija

```css
.sp-toast-container {
  position: fixed;
  bottom: var(--sp-gap-lg);
  right: var(--sp-gap-lg);
  z-index: var(--z-toast);      /* 600 */
  display: flex;
  flex-direction: column;
  gap: var(--sp-gap-sm);
  pointer-events: none;
  max-width: 360px;
  width: calc(100vw - var(--sp-gap-xl));
}

.sp-toast {
  background: var(--sp-bg-dark);
  color: var(--sp-text-inverse);
  border-radius: var(--sp-radius-md);
  padding: var(--sp-gap-sm) var(--sp-gap-md);
  display: flex;
  align-items: center;
  gap: var(--sp-gap-sm);
  font-size: var(--sp-text-sm);
  box-shadow: var(--sp-shadow-xl);
  pointer-events: all;
  animation: sp-toast-in 0.35s var(--sp-ease-out);
}

@keyframes sp-toast-in {
  from { opacity: 0; transform: translateY(12px) scale(0.96); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

.sp-toast--success { border-left: 3px solid var(--sp-success); }
.sp-toast--error   { border-left: 3px solid var(--sp-danger); }
.sp-toast--info    { border-left: 3px solid var(--sp-accent); }

/* Móvil: toasts centrados en bottom */
@media (max-width: 639px) {
  .sp-toast-container {
    bottom: var(--sp-gap-md);
    right: var(--sp-gap-md);
    left: var(--sp-gap-md);
    width: auto;
  }
}
```

---

## 31. Checklist de Capas y Header Móvil

- [ ] ¿El z-index usa exclusivamente variables `var(--z-*)` — cero números literales?
- [ ] ¿El header móvil tiene nav de scroll horizontal en lugar de hamburger?
- [ ] ¿La altura total del header en móvil es ≤ 88px (44px top + 40px nav)?
- [ ] ¿El header desktop tiene nav centrada con `position: absolute; left: 50%`?
- [ ] ¿El overlay del modal usa `var(--z-overlay)` (400) y el modal `var(--z-modal)` (500)?
- [ ] ¿Los toasts están en `var(--z-toast)` (600) — encima de todo?
- [ ] ¿Ningún componente usa `z-index: 9999`? → Si existe, arquitectura a revisar
- [ ] ¿El header no tiene `overflow: hidden`? → Bloquearía dropdowns
- [ ] ¿Los elementos con `transform`/`opacity`/`filter` crean stacking context? → Planificado
- [ ] ¿Las secciones con capas internas complejas usan `isolation: isolate`?
- [ ] ¿El nav-scroll del header tiene `sp-header__nav-fade` para indicar scroll disponible?
- [ ] ¿El `.sp-page` tiene `padding-top: 84px` en móvil y `padding-top: var(--sp-header-h)` en desktop?

---

## 32. Anti-patrones de Capas

| Anti-patrón | Problema | Corrección |
|-------------|----------|------------|
| `z-index: 9999` en cualquier elemento | Número mágico, escala rota | `var(--z-modal)` o `var(--z-toast)` |
| `z-index: 100` en el modal y `z-index: 101` en el overlay | Overlay debajo del modal | Overlay: `var(--z-overlay)` (400), Modal: `var(--z-modal)` (500) |
| Header con `overflow: hidden` | Bloquea dropdowns y tooltips | Nunca `overflow: hidden` en header |
| Hamburger en mobile como único patrón de nav | UX pobre, interacción extra | Nav con scroll horizontal en fila propia |
| Nav móvil que ocupa pantalla completa | Pierde contexto, sensación heavy | Scroll horizontal compacto bajo el logo |
| Card con `z-index: 999` en hover | Rompe el sistema de capas | `z-index: var(--z-raised)` (1) es suficiente |
| `transform` en elemento padre de modal | Crea stacking context, modal queda atrapado | Mover modal fuera del elemento con transform |
| `opacity: 0.99` en un wrapper | Crea stacking context sin querer | Solo aplicar opacity en elementos que lo necesitan |
| Dos modales apilados sin gestión de capas | Se solapan incorrectamente | Sistema de gestión de z-index dinámico en React |
