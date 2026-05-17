# Skill: Integración de templates externos en MyLocal Framework
## Para agentes Gemini

**Proyecto:** MyLocal — Framework multi-sector para negocios españoles  
**Stack:** React 19 + TypeScript + Vite + Tailwind v4 + PHP (backend) + AxiDB (datos)  
**Gestión de paquetes:** pnpm workspaces (monorepo)  
**SDK compartido:** `@mylocal/sdk` en `sdk/` enlazado vía `workspace:*`

---

## Contexto del proyecto

MyLocal es un monorepo en `e:/mylocal/` con esta estructura crítica:

```
sdk/                        SDK TypeScript compartido (@mylocal/sdk)
templates/hosteleria/       Vertical bares/restaurantes — puerto 5173
templates/clinica/          Vertical clínicas — puerto 5174
templates/logistica/        Vertical logística — puerto 5175
templates/asesoria/         Vertical asesorías — puerto 5176
release/                    Build de producción (generada por build.ps1)
CORE/                       Backend PHP — auth, motor API
CAPABILITIES/               Módulos PHP de negocio
STORAGE/                    Datos en tiempo real (excluido de git)
```

Cuando el usuario trae una aplicación React de un generador externo (Google AI Studio, Lovable, v0, Bolt, etc.), se integra como un template nuevo en `templates/<nombre>/`.

**REGLA FUNDAMENTAL:**
El código CSS y los componentes del template se copian TAL CUAL. No se modifica el diseño. Solo se añaden los puntos de conexión con el SDK de MyLocal.

---

## Arquitectura de comunicación

```
[React SPA] ←→ [SynaxisProvider (SDK)] ←→ POST /acide/index.php ←→ [PHP Backend]
```

- Todo pasa por `POST /acide/index.php` con `{ accion: string, datos: any }`
- El SDK expone: `SynaxisProvider`, `useSynaxisClient`, `login`, `logout`, `getCurrentUser`
- No hay REST convencional. No hay MySQL. Los datos van a STORAGE/ via AxiDB.

---

## Paso a paso completo de integración

### PASO 1 — Inspección del template origen

Antes de copiar nada, analiza el template fuente:

**Checklist de inspección:**
- ¿Usa Tailwind v3 (`@tailwind utilities;`) o v4 (`@import "tailwindcss"`)?
- ¿Importa de `motion/react` o de `framer-motion`?
- ¿Usa alias `@/` que apunten a `src/`? → Busca `@/src/` en el código
- ¿Los imports tienen extensión `.tsx` explícita?
- ¿Tiene CSS global con `* { margin: 0; padding: 0 }`?
- ¿Tiene su propio sistema de auth?
- ¿Qué componente es el punto de entrada? (normalmente `App.tsx`)

### PASO 2 — Crear estructura del template

```powershell
# Crear directorio
New-Item -ItemType Directory -Path "templates/<nombre>/src/components" -Force
New-Item -ItemType Directory -Path "templates/<nombre>/public/seed" -Force
```

**Copiar el src/ del template origen:**
```powershell
Copy-Item -Path "<origen>/src/*" -Destination "templates/<nombre>/src/" -Recurse
```

### PASO 3 — Crear package.json

```json
{
  "name": "<nombre>",
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
    "react": "^19.0.0",
    "react-dom": "^19.0.0",
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

**ADVERTENCIA CRÍTICA:** Si el template usa `motion/react`, añade SIEMPRE `"framer-motion": "^12.0.0"` como dependencia directa. `motion@12` tiene sus tipos en `dist/react.d.ts` que importa de `framer-motion`. En pnpm estricto, `framer-motion` no es accesible sin ser dependencia directa, lo que provoca errores de tipo en cascada en el IDE (incluso cuando `tsc` pasa). Ver sección "Errores conocidos" más abajo.

### PASO 4 — Crear vite.config.ts

```typescript
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import fs from 'fs';
import path from 'path';
import { defineConfig } from 'vite';

const API_TARGET = process.env.SOCOLA_API || 'http://127.0.0.1:8091';
const PROJECT_ROOT = path.resolve(process.cwd(), '..', '..');
const MEDIA_ROOT   = path.join(PROJECT_ROOT, 'MEDIA');
const SDK_ROOT     = path.join(PROJECT_ROOT, 'sdk', 'index.ts');
const OUT_DIR = process.env.VITE_OUT_DIR
    ? path.resolve(process.cwd(), process.env.VITE_OUT_DIR)
    : path.join(PROJECT_ROOT, 'release');

const MIME_TYPES: Record<string, string> = {
    '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg',
    '.webp': 'image/webp', '.gif': 'image/gif', '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon',
};

function serveMediaPlugin() {
    return {
        name: 'serve-media-root',
        configureServer(server: any) {
            server.middlewares.use('/MEDIA', (req: any, res: any, next: any) => {
                const filePath = path.join(MEDIA_ROOT, req.url.split('?')[0]);
                if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
                    const ext = path.extname(filePath).toLowerCase();
                    res.setHeader('Content-Type', MIME_TYPES[ext] || 'application/octet-stream');
                    fs.createReadStream(filePath).pipe(res);
                } else { next(); }
            });
        },
    };
}

export default defineConfig({
    plugins: [react(), tailwindcss(), serveMediaPlugin()],
    base: '/',
    resolve: {
        alias: {
            '@mylocal/sdk': SDK_ROOT,
            '@': path.resolve(__dirname, '.'),
        },
    },
    server: {
        port: 5173,  // 5173 hosteleria | 5174 clinica | 5175 logistica | 5176 asesoria
        open: true,
        proxy: {
            '/acide': { target: API_TARGET, changeOrigin: true },
        },
    },
    build: {
        target: 'es2020',
        sourcemap: false,
        outDir: OUT_DIR,
        emptyOutDir: true,
    },
});
```

### PASO 5 — Crear tsconfig.json

```json
{
  "compilerOptions": {
    "target": "ES2020",
    "useDefineForClassFields": true,
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "skipLibCheck": true,
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": false,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "jsx": "react-jsx",
    "strict": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noFallthroughCasesInSwitch": true,
    "baseUrl": ".",
    "paths": {
      "@mylocal/sdk": ["../../sdk/index.ts"]
    }
  },
  "include": ["src"],
  "references": [{ "path": "./tsconfig.node.json" }]
}
```

### PASO 6 — Crear tsconfig.node.json

```json
{
  "compilerOptions": {
    "composite": true,
    "skipLibCheck": true,
    "module": "ESNext",
    "moduleResolution": "bundler",
    "allowSyntheticDefaultImports": true,
    "strict": true
  },
  "include": ["vite.config.ts"]
}
```

### PASO 7 — Crear archivos de infraestructura

**`src/vite-env.d.ts`:**
```typescript
/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_API_URL?: string;
    readonly VITE_LOCAL_ID?: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
```

**`src/declarations.d.ts`:**
```typescript
// Reserved for future ambient module declarations.
```

**`public/seed/bootstrap.json`:**
```json
{}
```
Obligatorio. Sin este archivo, SynaxisProvider lanza un SyntaxError al intentar parsear el HTML del fallback de la SPA como JSON.

**`public/favicon.png`:** Copiar desde `e:/mylocal/favicon.png`

**`public/config.json`:**
```json
{
  "modulo": "<nombre>",
  "nombre": "MyLocal",
  "slug": "mylocal",
  "color_acento": "#000000",
  "plan": "demo"
}
```

**`index.html`:** Actualizar título y añadir link favicon:
```html
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/png" href="/favicon.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <title>MyLocal — <Descripción del vertical></title>
  </head>
  <body>
    <div id="root"></div>
    <script type="module" src="/src/main.tsx"></script>
  </body>
</html>
```

### PASO 8 — Reescribir main.tsx

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

### PASO 9 — Correcciones estándar del código del template

Aplica estas correcciones siempre, en este orden:

**9.1 — Tailwind v4:**
```css
/* Eliminar si existen */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Reemplazar por */
@import "tailwindcss";
```

**9.2 — Alias `@/src/` rotos:**
Busca con grep `@/src/` en todos los `.tsx`. Cambia a ruta relativa:
```ts
// MAL
import { cn } from '@/src/lib/utils';
// BIEN  
import { cn } from '../lib/utils';
```

**9.3 — Extensiones `.tsx` en imports:**
Busca imports con extensión explícita. Quítala:
```ts
// MAL
import Header from './components/Header.tsx';
// BIEN
import Header from './components/Header';
```

**9.4 — CSS reset global:**
Si hay `* { margin: 0; padding: 0 }` sin `@layer`, el unlayered CSS aplasta las utilities de Tailwind (mayor especificidad CSS). Elimina esas propiedades del selector `*` o muévelas a `@layer base {}`.

**9.5 — Imports no usados:**
TypeScript con `noUnusedLocals: true` fallará en imports que el template generó pero no usa. Revisa y elimina los que `tsc` marque.

### PASO 10 — Crear LoginModal y conectar auth

Si el template no tiene modal de login, créalo en `src/components/LoginModal.tsx`:

```tsx
import { useState } from 'react';
import { X, Loader2 } from 'lucide-react';
import { useSynaxisClient, login } from '@mylocal/sdk';

interface Props { open: boolean; onClose: () => void; }

export default function LoginModal({ open, onClose }: Props) {
  const client = useSynaxisClient();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  if (!open) return null;

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const res = await login(client, email, password);
      if (res.success) { window.location.href = '/dashboard'; }
      else { setError(res.error ?? 'Credenciales incorrectas'); }
    } catch {
      setError('Error de conexión. Inténtalo de nuevo.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="fixed inset-0 z-[200] flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={onClose} />
      <div className="relative bg-white rounded-2xl p-8 w-full max-w-md shadow-2xl">
        <button onClick={onClose} className="absolute top-6 right-6 p-2 rounded-full hover:bg-gray-100 transition-colors">
          <X className="w-5 h-5" />
        </button>
        <h2 className="text-2xl font-bold mb-2">Acceder</h2>
        <p className="text-sm text-gray-500 mb-6">Entra en tu panel de MyLocal</p>
        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          <input type="email" value={email} onChange={e => setEmail(e.target.value)}
            required autoFocus placeholder="tu@negocio.es"
            className="px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm" />
          <input type="password" value={password} onChange={e => setPassword(e.target.value)}
            required placeholder="••••••••"
            className="px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm" />
          {error && <p className="text-sm text-red-600 bg-red-50 px-4 py-2 rounded-xl">{error}</p>}
          <button type="submit" disabled={loading}
            className="mt-2 py-3 bg-black text-white rounded-xl font-medium text-sm hover:bg-gray-800 disabled:opacity-50 flex items-center justify-center gap-2">
            {loading && <Loader2 className="w-4 h-4 animate-spin" />}
            {loading ? 'Accediendo...' : 'Entrar'}
          </button>
        </form>
      </div>
    </div>
  );
}
```

En `App.tsx` conecta el estado y pasa la prop `onLoginClick`:
```tsx
const [showLogin, setShowLogin] = useState(false);
// En Header: <Header onLoginClick={() => setShowLogin(true)} />
// Al final: <LoginModal open={showLogin} onClose={() => setShowLogin(false)} />
```

### PASO 11 — Instalar dependencias

```powershell
# Desde la raíz del monorepo
cd e:/mylocal
pnpm install
```

pnpm usa el store centralizado — si las dependencias ya están descargadas por otro template, la instalación es instantánea.

### PASO 12 — Configurar IDE (VS Code)

Verificar que existe `.vscode/settings.json` en la raíz:
```json
{
  "typescript.tsdk": "templates/<nombre>/node_modules/typescript/lib"
}
```

Después: Ctrl+Shift+P → "TypeScript: Restart TS Server"

---

## Tests de certificación — OBLIGATORIOS

**Todos deben pasar. Si alguno falla, la integración no está terminada.**

### TEST 1 — TypeScript: cero errores

```powershell
cd templates/<nombre>
npx tsc --noEmit
```
- PASS: Sin output (exit 0)
- FAIL: Cualquier error

### TEST 2 — Vite: arranque limpio

```powershell
# Desde la raíz
.\run.bat <nombre>
```
- PASS: Servidor arranca en puerto asignado sin errores en terminal
- FAIL: Module not found, plugin error, port in use

### TEST 3 — Navegador: consola limpia

Abrir `http://localhost:<puerto>`, DevTools → Console
- PASS: 0 errores rojos, 0 warnings amarillos evitables
- FAIL: Cualquier error en consola

### TEST 4 — Network: cero 404

DevTools → Network → filtrar status 404
- PASS: Ninguna petición 404
- FAIL: Cualquier recurso no encontrado

Verificar específicamente:
- `/seed/bootstrap.json` → 200
- `/favicon.png` → 200
- Assets Vite → todos 200

### TEST 5 — SDK conectado

DevTools → Network → buscar peticiones a `/@fs/` o `sdk`
- PASS: `@mylocal/sdk` resuelve a `/@fs/E:/mylocal/sdk/...`
- FAIL: 404 sobre archivos SDK

### TEST 6 — Login funcional

1. Click en botón de login del template
2. Verificar que el modal aparece con campos email/password
3. Credenciales incorrectas → mensaje de error visible
4. Sin errores JS en consola durante el flujo
- PASS: Modal abre, cierra, valida, maneja errores correctamente
- FAIL: JS error, pantalla en blanco, modal no aparece

### TEST 7 — Diseño intacto

Comparar visualmente con el template original
- PASS: Diseño idéntico o equivalente
- FAIL: Estilos rotos, layout distorsionado, componentes sin estilo

### TEST 8 — IDE sin errores

Abrir `src/App.tsx` en VS Code
- PASS: 0 subrayados rojos
- FAIL: Errores de tipo visibles (puede necesitar Restart TS Server)

---

## Errores conocidos y soluciones

### motion/react — Error ts2307 en IDE

**Síntoma:**
```
No se encuentra el módulo 'motion/react' ni sus declaraciones de tipos. ts(2307)
```

**Por qué ocurre:**
`motion@12` — `dist/react.d.ts` importa de `framer-motion`:
```ts
export * from 'framer-motion';
```
En pnpm estricto, solo dependencias directas están en `node_modules/`. `framer-motion` no estaba listado → el IDE no lo encuentra aunque `tsc` pase (por `skipLibCheck: true`).

**Efecto secundario:** Falla la resolución de tipos en `App.tsx` completo, lo que hace que el IDE marque imports no relacionados (como `Footer`, `LoginModal`) con "no tiene exportación predeterminada" ts(2613). Son falsos positivos que desaparecen al resolver el error raíz.

**Solución:**
```json
"framer-motion": "^12.0.0"
```
Añadirlo en `dependencies` de `package.json` y ejecutar `pnpm install`.

---

### seed/bootstrap.json — SyntaxError

**Síntoma:**
```
SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
```

**Por qué:** `SynaxisProvider` carga `/seed/bootstrap.json` al arrancar. Sin el archivo, Vite devuelve el `index.html` (SPA fallback) que el SDK intenta parsear como JSON.

**Solución:** Crear `public/seed/bootstrap.json` con `{}`.

---

### Tailwind v4 no aplica estilos

**Síntoma:** Página carga pero sin ningún estilo Tailwind.

**Dos causas posibles:**

1. Sintaxis v3 en `index.css`:
```css
/* Borrar */
@tailwind base;
@tailwind components;
@tailwind utilities;
/* Añadir */
@import "tailwindcss";
```

2. CSS global sin layer aplastando utilities:
```css
/* Si hay esto → eliminar margin/padding del selector * */
* { margin: 0; padding: 0; }
```
El CSS sin `@layer` tiene mayor especificidad que `@layer utilities`. Resultado: el reset aplasta todo.

---

### Alias @/src/ no resueltos

**Síntoma:** `Cannot find module '@/src/lib/utils'`

**Por qué:** El alias `@` apunta a `templates/<nombre>/` (raíz del template), no a su padre. `@/src/lib/utils` no existe.

**Solución:** Cambiar a ruta relativa en todos los componentes afectados:
```ts
import { cn } from '../lib/utils';
```

---

## Referencia: exports de @mylocal/sdk

```typescript
SynaxisProvider     // Provider — envuelve la app en main.tsx
useSynaxisClient    // Hook — devuelve el cliente HTTP autenticado
useSynaxis          // Alias de useSynaxisClient
login               // (client, email, password) → Promise<{ success: boolean, error?: string }>
logout              // (client) → Promise<void>
getCurrentUser      // (client) → Promise<User | null>
getCachedUser       // () → User | null  (síncrono, sin petición)
SynaxisClient       // Tipo TypeScript
```

---

## Referencia: puertos por template

| Template | Puerto dev | Vertical |
|----------|-----------|---------|
| hosteleria | 5173 | Bares y restaurantes |
| clinica | 5174 | Clínicas y consultas |
| logistica | 5175 | Pedidos y entregas |
| asesoria | 5176 | Gestión documental |

---

## Arranque del entorno de desarrollo

```powershell
# Desde la raíz e:/mylocal/
.\run.bat <nombre>
# → Levanta PHP en :8091 + Vite con HMR en :<puerto>
```

Para producción, usar `.\build.ps1 -Template <nombre>`. NUNCA `npm run build` directamente (borra archivos PHP de release/).
