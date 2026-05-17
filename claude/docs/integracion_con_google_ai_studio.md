# Integración de templates externos en MyLocal
## Caso de estudio: hosteleria traída desde Google AI Studio

**Fecha:** 2026-05-15  
**Template integrado:** `templates/hosteleria/`  
**Origen:** Google AI Studio (React + Tailwind v4 + motion/react)

---

## El patrón de integración correcto

Cuando se trae una aplicación React de un generador externo (Google AI Studio, Lovable, v0, Bolt…), la regla es:

> **El CSS y los componentes del template se traen TAL CUAL.  
> Solo se añaden los puntos de conexión con el SDK de MyLocal.**

Lo que NO se hace:
- No se modifica el framework ni el SDK
- No se reescribe el diseño del template
- No se mezclan estilos del framework con los del template

Lo que SÍ se hace:
1. Copiar todo el `src/` del template a `templates/<nombre>/src/`
2. Reemplazar `package.json` y `vite.config.ts` por las versiones del framework
3. Envolver con `<SynaxisProvider>` en `main.tsx`
4. Añadir `login()` / `useSynaxisClient()` donde el template necesite autenticación
5. Crear los archivos de infraestructura que el framework requiere

---

## Archivos de infraestructura a crear siempre

Al integrar un template nuevo, estos archivos deben existir o crearse:

| Archivo | Contenido mínimo | Por qué |
|---------|-----------------|---------|
| `package.json` | Ver plantilla del framework | Workspaces pnpm, dependencias correctas |
| `vite.config.ts` | Ver plantilla del framework | Proxy `/acide`, alias `@mylocal/sdk`, plugin media |
| `tsconfig.json` | Ver plantilla del framework | `paths` para `@mylocal/sdk`, `moduleResolution: bundler` |
| `tsconfig.node.json` | `composite: true` | Necesario para project references de Vite |
| `src/vite-env.d.ts` | Interface `ImportMetaEnv` | Tipado de variables de entorno Vite |
| `src/main.tsx` | `SynaxisProvider` + `App` | Punto de entrada con SDK |
| `public/seed/bootstrap.json` | `{}` | Evita 404 en la carga inicial de SynaxisProvider |
| `public/favicon.png` | Copiar de la raíz | Evita 404 del navegador |
| `public/config.json` | `{ "modulo": "...", ... }` | Configuración básica del local |

---

## package.json — plantilla base

```json
{
  "name": "hosteleria",
  "private": true,
  "version": "1.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build --emptyOutDir",
    "preview": "vite preview",
    "typecheck": "tsc -b --noEmit"
  },
  "dependencies": {
    "@mylocal/sdk": "workspace:*",
    "clsx": "^2.1.1",
    "framer-motion": "^12.0.0",
    "lucide-react": "^0.546.0",
    "motion": "^12.0.0",
    "qrcode.react": "^4.2.0",
    "react": "^19.0.0",
    "react-dom": "^19.0.0",
    "react-router-dom": "^6.27.0",
    "tailwind-merge": "^3.0.0"
  },
  "devDependencies": {
    "@tailwindcss/vite": "^4.0.0",
    "@types/node": "^25.0.0",
    "@types/react": "^19.0.0",
    "@types/react-dom": "^19.0.0",
    "@vitejs/plugin-react": "^4.3.3",
    "tailwindcss": "^4.0.0",
    "typescript": "~5.8.0",
    "vite": "^5.4.0"
  }
}
```

**IMPORTANTE:** `framer-motion` debe estar como dependencia directa (ver error #5 más abajo).

---

## main.tsx — plantilla base

```tsx
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { SynaxisProvider } from '@mylocal/sdk';
import App from './App';
import './index.css';

const API_URL = import.meta.env.VITE_API_URL ?? '/acide/index.php';

createRoot(document.getElementById('root')!).render(
    <StrictMode>
        <SynaxisProvider apiUrl={API_URL} seedUrls={['/seed/bootstrap.json']}>
            <App />
        </SynaxisProvider>
    </StrictMode>
);
```

---

## index.css — Tailwind v4 correcto

```css
@import url('https://fonts.googleapis.com/...');
@import "tailwindcss";

@theme {
  --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
  --font-display: "Space Grotesk", sans-serif;
}

@layer base {
  body {
    @apply font-sans bg-[#F9F9F7] text-gray-900;
  }
}

/* Clases personalizadas del template */
.grid-bg { ... }
.glass { ... }
```

**NO usar** `@tailwind utilities;` (directiva de Tailwind v3). En v4 basta con `@import "tailwindcss";`.

---

## Errores encontrados y soluciones

### Error 1: Página sin estilos — Tailwind v4 no generaba utilities

**Síntoma:** La página cargaba pero sin ningún estilo Tailwind. Todo aparecía en texto plano.

**Causa:** El CSS del template tenía `@tailwind utilities;` (sintaxis v3) en lugar de `@import "tailwindcss"` (v4). El plugin `@tailwindcss/vite` (oxide) no expandía las clases porque no reconocía la directiva.

**Solución:** Cambiar el `index.css` a la sintaxis v4:
```css
/* MAL (v3) */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* BIEN (v4) */
@import "tailwindcss";
```

---

### Error 2: CSS global aplastaba Tailwind — `* { margin: 0; padding: 0 }`

**Síntoma:** Tailwind generaba las utilities pero los estilos no se aplicaban correctamente. Márgenes y paddings ignorados.

**Causa:** El template o un archivo CSS externo tenía una regla sin layer:
```css
*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;  /* esta línea mataba todo */
}
```
En el sistema de CSS Cascade Layers, el CSS sin `@layer` tiene mayor especificidad que `@layer utilities`. Por tanto, el reset global aplastaba todas las clases de Tailwind.

**Solución:** Eliminar `margin: 0; padding: 0` del selector universal. O moverlo dentro de un `@layer base {}`.

---

### Error 3: Alias de importación roto — `@/src/lib/utils`

**Síntoma:** `tsc` reportaba errores de módulo no encontrado en varios componentes:
```
Cannot find module '@/src/lib/utils'
```

**Causa:** El template de Google AI Studio usaba el alias `@/src/lib/utils` asumiendo que `@` apuntaba al directorio padre del proyecto. En el `vite.config.ts` del framework, `@` apunta al directorio del template (`templates/hosteleria/`), por lo que `@/src/lib/utils` se resolvía incorrectamente.

**Archivos afectados:** `PricingSection.tsx`, `QRSection.tsx`, `ProductsSection.tsx`, `ImportSection.tsx`, `WebPreviewSection.tsx`.

**Solución:** Cambiar a ruta relativa en todos los componentes afectados:
```ts
// MAL
import { cn } from '@/src/lib/utils';

// BIEN
import { cn } from '../lib/utils';
```

---

### Error 4: Importaciones con extensión `.tsx` prohibidas — ts5097

**Síntoma:**
```
ts5097: An import path can only end with a '.tsx' extension when
'allowImportingTsExtensions' is enabled.
```

**Causa:** El template generado incluía extensiones explícitas en los imports:
```ts
import Footer from './components/Footer.tsx';
```
El `tsconfig.json` del framework tiene `"allowImportingTsExtensions": false` por coherencia con el modo bundler.

**Solución:** Quitar la extensión de todos los imports:
```ts
import Footer from './components/Footer';
```

---

### Error 5: `motion/react` — módulo no encontrado ts2307 (IDE)

**Síntoma:** El IDE mostraba el error en todos los componentes que importaban de `motion/react`:
```
No se encuentra el módulo 'motion/react' ni sus declaraciones de tipos correspondientes. ts(2307)
```
`tsc --noEmit` pasaba sin errores, pero el servidor de lenguaje TypeScript del IDE seguía mostrándolo.

**Causa raíz:** `motion@12` tiene sus tipos en `dist/react.d.ts`, que contiene:
```ts
export * from 'framer-motion';
export { m, motion } from 'framer-motion';
```
Es decir, los tipos de `motion/react` dependen de `framer-motion`. Pero `framer-motion` no era una dependencia directa del template. En pnpm con modo estricto, solo las dependencias directas están accesibles en el `node_modules/` del paquete. Aunque `framer-motion` estaba en el store de pnpm (instalado por otra vertical), no estaba enlazado al `node_modules/` de hosteleria.

El motivo por el que `tsc` pasaba sin errores era `skipLibCheck: true` — TypeScript saltaba la verificación de `node_modules/**/*.d.ts`. El IDE (VS Code TypeScript Language Server) era más estricto en la resolución.

**Intento fallido:** Se creó `src/declarations.d.ts` con:
```ts
declare module 'framer-motion' {
    export * from 'motion';
    export { motion, m, AnimatePresence, ... } from 'motion';
}
```
Esto tampoco funcionó en el IDE porque creaba una cadena circular: `motion/react` → `framer-motion` → `motion` → `motion/react`. TypeScript la detectaba y seguía sin resolver los tipos correctamente.

**Solución definitiva:** Añadir `framer-motion` como dependencia directa en `package.json`:
```json
"framer-motion": "^12.0.0"
```
Y ejecutar `pnpm install` desde la raíz. Como `framer-motion@12.38.0` ya estaba en el store de pnpm (otra vertical lo usaba), la instalación fue instantánea (0 descargas). A partir de ahí, el IDE resuelve `framer-motion` → `dist/types/index.d.ts` que importa de `motion-dom` (sin circularity).

**Lección:** Cuando el template usa `motion/react`, siempre añadir AMBAS dependencias:
```json
"motion": "^12.0.0",
"framer-motion": "^12.0.0"
```

---

### Error 6: `Footer` y `LoginModal` sin exportación predeterminada — ts2613 (IDE)

**Síntoma:** El IDE marcaba en `App.tsx`:
```
El módulo 'Footer' no tiene ninguna exportación predeterminada. ts(2613)
El módulo 'LoginModal' no tiene ninguna exportación predeterminada. ts(2613)
```
Pero `Footer.tsx` y `LoginModal.tsx` tenían claramente `export default function Footer()` y `export default function LoginModal()`. `tsc --noEmit` pasaba sin errores.

**Causa:** Error en cascada del error #5. Cuando TypeScript no puede resolver `motion/react` en `App.tsx`, el análisis del fichero falla parcialmente y los imports que dependen de él (`Footer`, `LoginModal`) se marcan incorrectamente. No era un error real en esos archivos.

**Solución:** Al resolver el error #5 (instalar `framer-motion`), estos errores desaparecieron automáticamente.

---

### Error 7: `seed/bootstrap.json` — SyntaxError en consola

**Síntoma:**
```
SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
```

**Causa:** `SynaxisProvider` carga por defecto el archivo `/seed/bootstrap.json` al arrancar. Como el archivo no existía en `public/`, Vite devolvía el `index.html` de la SPA (el fallback del SPA routing), que el SDK intentaba parsear como JSON.

**Solución:** Crear `public/seed/bootstrap.json` con contenido mínimo válido:
```json
{}
```

---

### Error 8: `favicon.ico` — 404 en consola

**Síntoma:** El navegador hacía petición automática a `/favicon.ico` y recibía 404.

**Causa:** Los navegadores siempre piden favicon automáticamente. El template no tenía favicon en `public/` ni referencia en `index.html`.

**Solución (dos pasos):**
1. Copiar `favicon.png` de la raíz del proyecto a `templates/hosteleria/public/favicon.png`
2. Añadir el `<link>` en `index.html`:
```html
<link rel="icon" type="image/png" href="/favicon.png" />
```

---

### Error 9: Variables de entorno sin tipar — IDE warnings

**Síntoma:** `import.meta.env.VITE_API_URL` mostraba tipo `any` o el IDE no ofrecía autocompletado.

**Causa:** Sin `src/vite-env.d.ts`, TypeScript no sabe qué variables de entorno expone Vite.

**Solución:** Crear `src/vite-env.d.ts`:
```ts
/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_API_URL?: string;
    readonly VITE_LOCAL_ID?: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
```

---

### Error 10: IDE usa TypeScript diferente al de `tsc`

**Síntoma:** `tsc --noEmit` pasaba sin errores pero el IDE mostraba errores. Dos versiones de TypeScript en el workspace: `5.8.x` (local del template) y `5.9.x` (raíz del monorepo).

**Solución:** Crear `.vscode/settings.json` en la raíz del proyecto apuntando al TypeScript local:
```json
{
  "typescript.tsdk": "templates/hosteleria/node_modules/typescript/lib"
}
```
Esto garantiza que el Language Server del IDE use exactamente el mismo TypeScript que se usa al compilar. Después hay que hacer **Ctrl+Shift+P → "TypeScript: Restart TS Server"** para que VS Code lo cargue.

---

## Checklist de integración para futuros templates

```
[ ] Copiar src/ del template a templates/<nombre>/src/
[ ] Crear package.json desde plantilla (incluir framer-motion si usa motion/react)
[ ] Crear vite.config.ts desde plantilla de framework
[ ] Crear tsconfig.json + tsconfig.node.json desde plantilla
[ ] Crear src/vite-env.d.ts con tipado de ImportMetaEnv
[ ] Crear src/main.tsx con SynaxisProvider
[ ] Crear public/seed/bootstrap.json con {}
[ ] Copiar public/favicon.png desde la raíz
[ ] Crear public/config.json con datos básicos del local
[ ] Verificar que index.css usa @import "tailwindcss" (v4), no @tailwind utilities
[ ] Cambiar imports @/src/lib/utils → ../lib/utils (o la ruta relativa correcta)
[ ] Quitar extensiones .tsx de todos los imports
[ ] Ejecutar pnpm install desde la raíz
[ ] Ejecutar tsc --noEmit para verificar cero errores
[ ] Comprobar consola del navegador: cero errores, cero 404
[ ] Verificar .vscode/settings.json apunta al TypeScript local
```

---

## Puntos de conexión SDK — referencia rápida

```tsx
// main.tsx — envolver toda la app
import { SynaxisProvider } from '@mylocal/sdk';
<SynaxisProvider apiUrl={API_URL} seedUrls={['/seed/bootstrap.json']}>
    <App />
</SynaxisProvider>

// LoginModal.tsx — autenticación
import { useSynaxisClient, login } from '@mylocal/sdk';
const client = useSynaxisClient();
const res = await login(client, email, password);
if (res.success) window.location.href = '/dashboard';
else setError(res.error ?? 'Credenciales incorrectas');

// Cualquier componente — acceso al cliente
import { useSynaxisClient } from '@mylocal/sdk';
const client = useSynaxisClient();
// client.post('ACCION', datos) → Promise<{ success, data, error }>
```

---

## Exports disponibles en `@mylocal/sdk`

```ts
SynaxisProvider     // Provider principal — envuelve la app
useSynaxisClient    // Hook — devuelve el cliente HTTP
useSynaxis          // Hook — alias de useSynaxisClient
login               // (client, email, password) → Promise<{ success, error }>
logout              // (client) → Promise<void>
getCurrentUser      // (client) → Promise<User | null>
getCachedUser       // () → User | null (síncrono, desde caché)
SynaxisClient       // Tipo TypeScript del cliente
```
