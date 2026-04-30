---
name: skilldashboard
description: >
  Normas y arquitectura para diseñar e implementar dashboards de administración internos.
  USAR SIEMPRE que el usuario pida crear, modificar o ampliar un dashboard, panel de control,
  área de administración, backoffice, vista de gestión, tabla de datos, KPIs, formularios de
  administración o cualquier interfaz interna de gestión de un proyecto. Si hay un dashboard
  que diseñar o un componente de administración que construir — esta skill debe activarse.
---

# Skill de Diseño — Dashboard de Administración

## Filosofía Central

Un dashboard de administración es una **herramienta de trabajo de alta densidad**, no una página de marketing. El diseño prioriza: máximo aprovechamiento del espacio, elementos compactos y sofisticados, mobile-first real y velocidad de lectura. El aspecto se construye con precisión, no se improvisa.

### Los 5 principios — NO NEGOCIABLES

**1. Densidad máxima de información**
Cada píxel cuenta. Los elementos ocupan el mínimo espacio necesario para ser legibles y funcionales. El espacio en blanco solo existe cuando tiene función estructural (separar grupos semánticos distintos). Nunca por estética decorativa.

**2. Compacto por defecto**
Padding y margin al mínimo funcional. Los elementos conviven juntos. Regla: si se puede reducir sin perder legibilidad, se reduce.

```
❌ padding: 24px 32px   →  diseño de landing page
❌ gap: 24px            →  desperdicio de pantalla
✅ padding: 4px 10px    →  herramienta profesional compacta
✅ gap: 4px             →  elementos que viven juntos
```

**3. Mobile-first real — no como afterthought**
El diseño SE CONSTRUYE primero para 375px. Cada componente debe funcionar y verse bien en móvil antes de pensar en desktop. Desktop es una amplificación progresiva, no la base.

```css
/* ✅ CORRECTO: base móvil → ampliar desktop */
.db-kpi-grid { grid-template-columns: 1fr 1fr; }
@media (min-width: 768px) { .db-kpi-grid { grid-template-columns: repeat(4, 1fr); } }

/* ❌ INCORRECTO: base desktop → "adaptar" móvil */
.db-kpi-grid { grid-template-columns: repeat(4, 1fr); }
@media (max-width: 768px) { .db-kpi-grid { grid-template-columns: 1fr; } }
```

**4. Atractivo y sofisticado**
La densidad no es fealdad. Lo compacto es elegante cuando la tipografía es precisa, el color controlado, las alineaciones perfectas y la jerarquía visual inmediata. Sofisticación = precisión, no espacio en blanco.

**5. Sin aire decorativo**
Prohibido añadir padding, margin o gap solo para que "parezca más limpio". Si el elemento puede estar más cerca sin perder legibilidad, debe estarlo.

---

## 1. Separación de Concernimientos — Regla Absoluta

### 1.1 Estructura del proyecto de UI

```
./src/
├── components/         # Componentes React (.tsx) — solo estructura JSX
├── styles/
│   └── dashboard.css   # UNA hoja de estilos propia y exclusiva
├── assets/
│   └── fonts/          # Tipografías descargadas localmente (woff2)
└── App.tsx
```

### 1.2 Prohibiciones absolutas de CSS

- **PROHIBIDO** `style={{}}` en línea en JSX
- **PROHIBIDO** CSS-in-JS: `styled-components`, `emotion`, `@stitches`
- **PROHIBIDO** mezclar clases de utilidad externas (Tailwind, Bootstrap) con CSS propio
- **PROHIBIDO** `@import url("https://fonts.googleapis.com/...")` o cualquier CDN de fuentes
- **TODO** el diseño vive en `dashboard.css`. Si no está ahí, no existe

```tsx
// ❌ MAL
<div style={{ backgroundColor: '#1E1F22', padding: '12px' }}>

// ✅ BIEN
<div className="db-panel">
```

---

## 2. Variables CSS — Sistema de Diseño Completo

Todo vive en variables CSS. Cero valores literales en las reglas.

```css
:root {
  /* ── Fondos ── */
  --db-bg-app:        #F4F5F7;
  --db-bg-surface:    #FFFFFF;
  --db-bg-raised:     #F0F1F3;
  --db-bg-sidebar:    #1A1B1E;   /* sidebar oscura siempre */

  /* ── Bordes ── */
  --db-border:        #E2E4E9;
  --db-border-strong: #C8CBD4;
  --db-border-focus:  #3B82F6;

  /* ── Texto ── */
  --db-text-main:     #1A1B1E;
  --db-text-dim:      #6B7280;
  --db-text-muted:    #9CA3AF;
  --db-text-inverse:  #F9FAFB;

  /* ── Acento y estados ── */
  --db-accent:        #3B82F6;
  --db-accent-hover:  #2563EB;
  --db-success:       #10B981;
  --db-success-bg:    #ECFDF5;
  --db-warning:       #F59E0B;
  --db-warning-bg:    #FFFBEB;
  --db-danger:        #EF4444;
  --db-danger-bg:     #FEF2F2;

  /* ── Espaciado compacto ── */
  --db-gap-xs:   2px;
  --db-gap-sm:   4px;
  --db-gap-md:   8px;
  --db-gap-lg:   14px;
  --db-gap-xl:   20px;

  /* ── Padding de componentes — COMPACTO ── */
  --db-pad-cell:   4px 10px;    /* celdas de tabla */
  --db-pad-btn:    4px 12px;    /* botones */
  --db-pad-kpi:    8px 12px;    /* bloques KPI */
  --db-pad-panel:  10px 12px;   /* paneles */
  --db-pad-modal:  12px 16px;   /* modales */

  /* ── Tipografía ── */
  --db-font-ui:   'GeistVariable', 'InterVariable', sans-serif;
  --db-font-data: 'JetBrains Mono', 'IBM Plex Mono', monospace;
  --db-size-xs:   10px;
  --db-size-sm:   11px;
  --db-size-md:   12px;
  --db-size-lg:   14px;
  --db-size-xl:   18px;

  /* ── Geometría ── */
  --db-radius-sm:   2px;
  --db-radius-md:   4px;
  --db-radius-lg:   6px;
  --db-header-h:    40px;    /* header ultra-compacto */
  --db-sidebar-w:   44px;    /* sidebar de iconos */
  --db-row-h:       28px;    /* altura de fila de tabla */
}

/* ── Modo oscuro — activar con body.dark ── */
body.dark {
  --db-bg-app:        #111214;
  --db-bg-surface:    #1C1D20;
  --db-bg-raised:     #26272B;
  --db-bg-sidebar:    #111214;
  --db-border:        #2A2B2F;
  --db-border-strong: #3A3B40;
  --db-text-main:     #E4E5E9;
  --db-text-dim:      #8B8FA8;
  --db-text-muted:    #5C5F72;
}
```

### Fuentes locales — sin CDN

```css
@font-face {
  font-family: 'GeistVariable';
  src: url('../assets/fonts/GeistVariableVF.woff2') format('woff2');
  font-weight: 100 900;
  font-display: swap;
}
@font-face {
  font-family: 'JetBrains Mono';
  src: url('../assets/fonts/JetBrainsMono-Regular.woff2') format('woff2');
  font-weight: 400;
  font-display: swap;
}
```

Fuentes recomendadas: **Geist** (Vercel), **Inter**, **IBM Plex Sans**, **Outfit**. Para datos numéricos: **JetBrains Mono**, **IBM Plex Mono**.

---

## 3. Iconos — Librería instalada, sin CDN

```bash
npm install lucide-react
```

```tsx
import { Truck, BarChart2, Settings, Users, AlertCircle, CheckCircle } from 'lucide-react';

// Tamaños compactos para dashboard denso
<AlertCircle className="db-icon db-icon--danger" size={12} />
```

```css
.db-icon         { vertical-align: middle; flex-shrink: 0; }
.db-icon--danger { color: var(--db-danger); }
.db-icon--success{ color: var(--db-success); }
.db-icon--dim    { color: var(--db-text-dim); }
.db-icon--accent { color: var(--db-accent); }
```

---

## 4. Nomenclatura — Prefijo `db-` + BEM

Todas las clases llevan prefijo `db-`. Cero clases genéricas. Cero etiquetas HTML estiladas directamente.

```
db-layout / db-header / db-sidebar / db-sidebar__item / db-sidebar__item--active
db-content / db-panel / db-panel__header
db-kpi-grid / db-kpi / db-kpi__label / db-kpi__value / db-kpi__value--positive
db-tabs / db-tab / db-tab--active
db-table-wrap / db-table / db-table__thead / db-table__th / db-table__td
db-table__row / db-table__row--error / db-table__row--success / db-table__row--selected
db-table__td--num   (columnas numéricas monoespaciadas)
db-modal-overlay / db-modal / db-modal__header / db-modal__body / db-modal__footer
db-btn / db-btn--primary / db-btn--ghost / db-btn--danger / db-btn--full-mobile
db-badge / db-badge--success / db-badge--warning / db-badge--danger
db-input / db-search
```

```css
/* ❌ MAL — etiquetas desnudas */
table { }  h1 { }  p { }  button { }

/* ✅ BIEN — clases propias */
.db-table { }  .db-section-title { }  .db-btn { }
```

---

## 5. Layout Mobile-First

```css
/* BASE MÓVIL (375px) — columna única */
.db-layout {
  display: grid;
  grid-template-rows: var(--db-header-h) 1fr;
  grid-template-columns: 1fr;
  height: 100dvh;
  background: var(--db-bg-app);
  overflow: hidden;
}

.db-header {
  height: var(--db-header-h);
  background: var(--db-bg-surface);
  border-bottom: 1px solid var(--db-border);
  display: flex;
  align-items: center;
  padding: 0 var(--db-gap-md);
  gap: var(--db-gap-md);
  position: sticky;
  top: 0;
  z-index: 100;
}

.db-content {
  overflow-y: auto;
  padding: var(--db-gap-md);
  display: flex;
  flex-direction: column;
  gap: var(--db-gap-md);
}

.db-sidebar { display: none; }  /* oculta en móvil */

/* DESKTOP ≥768px — sidebar aparece */
@media (min-width: 768px) {
  .db-layout {
    grid-template-columns: var(--db-sidebar-w) 1fr;
    grid-template-rows: var(--db-header-h) 1fr;
  }
  .db-header  { grid-column: 1 / -1; }
  .db-sidebar { display: flex; grid-row: 2; }
  .db-content { grid-row: 2; grid-column: 2; }
}

/* Sidebar ultra-compacta */
.db-sidebar {
  width: var(--db-sidebar-w);
  background: var(--db-bg-sidebar);
  border-right: 1px solid var(--db-border);
  flex-direction: column;
  align-items: center;
  padding: var(--db-gap-sm) 0;
  gap: var(--db-gap-xs);
}

.db-sidebar__item {
  width: 32px; height: 32px;
  border-radius: var(--db-radius-md);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  color: var(--db-text-muted);
  transition: background 0.12s, color 0.12s;
}
.db-sidebar__item:hover   { background: rgba(255,255,255,0.07); color: var(--db-text-inverse); }
.db-sidebar__item--active { background: var(--db-accent); color: #fff; }
```

---

## 6. KPIs Compactos

```css
/* Móvil: 2 columnas */
.db-kpi-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--db-gap-sm);
}
@media (min-width: 768px) {
  .db-kpi-grid { grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); }
}

.db-kpi {
  background: var(--db-bg-surface);
  border: 1px solid var(--db-border);
  border-radius: var(--db-radius-md);
  padding: var(--db-pad-kpi);
  display: flex; flex-direction: column; gap: 2px;
}

.db-kpi__label {
  font-family: var(--db-font-ui);
  font-size: var(--db-size-xs);
  font-weight: 500;
  color: var(--db-text-dim);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.db-kpi__value {
  font-family: var(--db-font-data);
  font-size: var(--db-size-xl);
  font-weight: 700;
  color: var(--db-text-main);
  line-height: 1;
}
.db-kpi__value--positive { color: var(--db-success); }
.db-kpi__value--negative { color: var(--db-danger); }

.db-kpi__sub {
  font-size: var(--db-size-xs);
  color: var(--db-text-muted);
  font-family: var(--db-font-ui);
}
```

---

## 7. Tablas Alta Densidad

```css
.db-table-wrap {
  overflow-x: auto;
  border: 1px solid var(--db-border);
  border-radius: var(--db-radius-md);
  -webkit-overflow-scrolling: touch;
}

.db-table {
  width: 100%;
  border-collapse: collapse;
  font-family: var(--db-font-data);
  font-size: var(--db-size-sm);
  white-space: nowrap;
}

.db-table__thead {
  background: var(--db-bg-raised);
  position: sticky; top: 0; z-index: 1;
}

.db-table__th {
  padding: var(--db-pad-cell);
  text-align: left;
  font-family: var(--db-font-ui);
  font-size: var(--db-size-xs);
  font-weight: 600;
  color: var(--db-text-dim);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid var(--db-border);
}

.db-table__td {
  padding: var(--db-pad-cell);
  border-bottom: 1px solid var(--db-border);
  color: var(--db-text-main);
  height: var(--db-row-h);
  vertical-align: middle;
}

.db-table__row:hover .db-table__td     { background: var(--db-bg-raised); cursor: pointer; }
.db-table__row--error .db-table__td    { background: var(--db-danger-bg); }
.db-table__row--success .db-table__td  { background: var(--db-success-bg); }
.db-table__row--warning .db-table__td  { background: var(--db-warning-bg); }
.db-table__td--num {
  font-family: var(--db-font-data);
  text-align: right;
  font-variant-numeric: tabular-nums;
}
```

---

## 8. Tabs

```css
.db-tabs {
  display: flex;
  border-bottom: 1px solid var(--db-border);
  overflow-x: auto;
  scrollbar-width: none;
  background: var(--db-bg-surface);
}
.db-tabs::-webkit-scrollbar { display: none; }

.db-tab {
  flex-shrink: 0;
  padding: 6px 12px;
  font-family: var(--db-font-ui);
  font-size: var(--db-size-md);
  font-weight: 500;
  color: var(--db-text-dim);
  cursor: pointer;
  border-bottom: 2px solid transparent;
  white-space: nowrap;
  transition: color 0.1s, border-color 0.1s;
}
.db-tab:hover    { color: var(--db-text-main); }
.db-tab--active  { color: var(--db-accent); border-bottom-color: var(--db-accent); }
```

---

## 9. Modales — Bottom-sheet en móvil, centrado en desktop

```css
.db-modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: flex-end;       /* móvil: sube desde abajo */
  justify-content: center;
  z-index: 1000;
}

.db-modal {
  background: var(--db-bg-surface);
  border: 1px solid var(--db-border);
  border-radius: var(--db-radius-lg) var(--db-radius-lg) 0 0;
  width: 100%;
  max-height: 90dvh;
  overflow-y: auto;
  box-shadow: none;
}

@media (min-width: 768px) {
  .db-modal-overlay { align-items: center; }
  .db-modal {
    border-radius: var(--db-radius-lg);
    width: auto; min-width: 320px; max-width: 460px; max-height: 80dvh;
  }
}

.db-modal__header {
  padding: var(--db-pad-modal);
  border-bottom: 1px solid var(--db-border);
  display: flex; justify-content: space-between; align-items: center;
  position: sticky; top: 0;
  background: var(--db-bg-surface);
  z-index: 1;
}
.db-modal__title {
  font-family: var(--db-font-ui);
  font-size: var(--db-size-lg);
  font-weight: 600;
  color: var(--db-text-main);
}
.db-modal__body   { padding: var(--db-pad-modal); }
.db-modal__footer {
  padding: var(--db-gap-sm) var(--db-gap-lg);
  border-top: 1px solid var(--db-border);
  display: flex; justify-content: flex-end; gap: var(--db-gap-sm);
  background: var(--db-bg-raised);
}
```

---

## 10. Botones, Badges e Inputs

```css
/* Botones */
.db-btn {
  font-family: var(--db-font-ui);
  font-size: var(--db-size-md);
  font-weight: 500;
  padding: var(--db-pad-btn);
  border-radius: var(--db-radius-sm);
  border: 1px solid transparent;
  cursor: pointer;
  display: inline-flex; align-items: center; gap: var(--db-gap-sm);
  transition: background 0.1s, border-color 0.1s;
  white-space: nowrap; line-height: 1;
}
.db-btn--primary { background: var(--db-accent); color: #fff; }
.db-btn--primary:hover { background: var(--db-accent-hover); }
.db-btn--ghost { background: transparent; color: var(--db-text-main); border-color: var(--db-border); }
.db-btn--ghost:hover { background: var(--db-bg-raised); }
.db-btn--danger { background: var(--db-danger); color: #fff; }
@media (max-width: 767px) {
  .db-btn--full-mobile { width: 100%; justify-content: center; }
}

/* Badges */
.db-badge {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 1px 6px;
  border-radius: var(--db-radius-sm);
  font-family: var(--db-font-ui);
  font-size: var(--db-size-xs);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.db-badge--success { background: var(--db-success-bg); color: var(--db-success); }
.db-badge--warning { background: var(--db-warning-bg); color: var(--db-warning); }
.db-badge--danger  { background: var(--db-danger-bg);  color: var(--db-danger); }
.db-badge--neutral { background: var(--db-bg-raised);  color: var(--db-text-dim); }

/* Inputs */
.db-input {
  font-family: var(--db-font-ui);
  font-size: var(--db-size-md);
  color: var(--db-text-main);
  background: var(--db-bg-surface);
  border: 1px solid var(--db-border);
  border-radius: var(--db-radius-md);
  padding: 4px 8px; outline: none; width: 100%;
  transition: border-color 0.1s;
}
.db-input:focus { border-color: var(--db-border-focus); }
.db-input::placeholder { color: var(--db-text-muted); }

.db-search {
  display: flex; align-items: center; gap: var(--db-gap-sm);
  background: var(--db-bg-raised);
  border: 1px solid var(--db-border);
  border-radius: var(--db-radius-md);
  padding: 3px 8px;
}
.db-search .db-input { border: none; background: transparent; padding: 0; }
```

---

## 11. Checklist de entrega

- [ ] ¿El diseño se construyó primero para 375px con `@media (min-width: ...)` para desktop?
- [ ] ¿Padding y gap siguen variables compactas? (`--db-pad-*` ≤ 12px vertical, `--db-gap-*` ≤ 8px)
- [ ] ¿Hay espacio en blanco decorativo que se pueda eliminar sin perder legibilidad? → Eliminar
- [ ] ¿Todos los estilos están en `dashboard.css`? ¿Cero `style={{}}` en JSX?
- [ ] ¿Las fuentes son locales (`./assets/fonts/`)? ¿Cero CDN?
- [ ] ¿Los iconos vienen de `lucide-react` u otra librería instalada? ¿Cero CDN?
- [ ] ¿La paleta usa variables CSS de `:root`? ¿Cero colores literales en reglas?
- [ ] ¿Todas las clases llevan prefijo `db-`? ¿Cero etiquetas HTML estiladas directamente?
- [ ] ¿El modo oscuro funciona solo añadiendo `.dark` al `body`?
- [ ] ¿Las tablas tienen densidad alta (row-height ~28px, padding vertical ≤4px)?
- [ ] ¿El modal aparece como bottom-sheet en móvil y centrado en desktop?

---

## 12. Anti-patrones

| Anti-patrón | Problema | Corrección |
|-------------|----------|------------|
| `style={{ padding: '24px' }}` en JSX | Rompe separación CSS/JSX | `.db-panel` con `--db-pad-panel` |
| `<table>`, `<h2>`, `<p>` sin clase | Colisiones, sin contexto | `.db-table`, `.db-section-title` |
| `@media (max-width: 768px)` como único breakpoint | Desktop-first: móvil roto | Base móvil + `@media (min-width: ...)` |
| `padding: 32px` en paneles | Desperdicio de pantalla | `var(--db-pad-panel)` → `10px 12px` |
| `gap: 24px` en grids | Elementos demasiado separados | `var(--db-gap-sm)` → `4px` |
| `@import url("fonts.google...")` | CDN externo, GDPR, offline | Fuentes en `./assets/fonts/` |
| `<i class="fa fa-icon">` CDN | Dependencia externa en runtime | `<Icon />` de lucide-react |
| `box-shadow` decorativo | Rompe estética flat | `1px solid var(--db-border)` |
| Colores literales (`#3B82F6`) en reglas CSS | Imposible mantener tema | `var(--db-accent)` |
| Espacio en blanco entre elementos relacionados | Separa lo que debería estar junto | Reducir gap, agrupar por contenedor |

---

## Resumen

> **Denso**: cada píxel trabaja. Sin espacio decorativo.
> **Compacto**: padding mínimo funcional, elementos juntos, jerarquía por tipografía no por espacio.
> **Mobile-first real**: construir en 375px, desktop es amplificación progresiva.
> **Sofisticado**: precisión tipográfica, color controlado, alineaciones exactas — elegancia sin aire.
> **Separación total**: React estructura, CSS diseña. Nunca mezclados.
> **Local siempre**: fuentes e iconos en el proyecto, sin dependencias externas en runtime.
> **Variables para todo**: colores, espaciados, tamaños — siempre variables, nunca literales.
> **Modo oscuro incluido**: `body.dark` cambia el tema completo sin tocar componentes.
