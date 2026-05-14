# @mylocal/sdk — Referencia

Paquete compartido entre todos los templates. Exporta el cliente de datos, auth, hooks y tipos.

```ts
import { SynaxisProvider, useSynaxisClient, login, logout } from '@mylocal/sdk';
```

---

## SynaxisProvider

Envuelve la app y crea el cliente de datos. Obligatorio en `main.tsx` de cada template.

```tsx
const API_URL = import.meta.env.VITE_API_URL ?? '/acide/index.php';

createRoot(document.getElementById('root')!).render(
    <StrictMode>
        <SynaxisProvider apiUrl={API_URL}>
            <App />
        </SynaxisProvider>
    </StrictMode>
);
```

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `apiUrl` | `string` | `/acide/index.php` | URL del backend PHP |
| `namespace` | `string` | `'socola'` | Namespace de IndexedDB |
| `project` | `string \| null` | `null` | Sub-namespace opcional |
| `seedUrls` | `string[]` | `['/seed/bootstrap.json']` | JSON de datos iniciales |

---

## useSynaxisClient

Hook principal. Devuelve el cliente configurado con el token de sesión.

```ts
const client = useSynaxisClient();
```

### `client.execute<T>(req)`

Llama a una acción del backend. Toda la lógica de datos pasa por aquí.

```ts
const res = await client.execute<MiTipo>({
    action: 'tarea_create',
    data: { local_id: 'l_123', titulo: 'Revisar contrato', prioridad: 'alta' },
});

if (res.success && res.data) {
    // res.data es MiTipo
}
```

| Campo de respuesta | Tipo | Descripción |
|-------------------|------|-------------|
| `success` | `boolean` | `true` si la operación fue exitosa |
| `data` | `T \| null` | Payload de respuesta |
| `error` | `string \| null` | Mensaje de error si `success = false` |

### Scope de acciones

Cada acción tiene un `scope` en `sdk/src/synaxis/actions.ts`:

| Scope | Transporte | Cuándo usarlo |
|-------|-----------|--------------|
| `server` | POST `/acide/index.php` | Lógica de negocio, auth, escritura |
| `local` | IndexedDB (sin red) | Datos temporales, caché |
| `hybrid` | Local primero, fallback servidor | Lectura con caché |

---

## useSynaxis

Hook avanzado. Devuelve el contexto completo (client + estado del seed).

```ts
const { client, ready, seedState } = useSynaxis();
```

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `client` | `SynaxisClient` | Cliente de datos |
| `ready` | `boolean` | `true` cuando IndexedDB está inicializado |
| `seedState` | `'idle' \| 'loading' \| 'done' \| 'skipped' \| 'error'` | Estado del seed inicial |

---

## Auth

```ts
import { login, logout, getCurrentUser, getCachedUser } from '@mylocal/sdk';
```

### `login(client, email, password)`

```ts
const res = await login(client, 'admin@negocio.es', 'contraseña');
if (res.success) {
    // res.user: AppUser
}
```

### `logout(client)`

```ts
await logout(client);
// Borra token de sessionStorage y limpia caché
```

### `getCurrentUser(client)`

Obtiene el usuario actual del servidor (requiere token activo).

### `getCachedUser()`

Devuelve el usuario cacheado en `sessionStorage` sin llamada al servidor.

---

## Tipos comunes

```ts
import type { AppUser, LocalInfo, UserInfo } from '@mylocal/sdk';
```

```ts
interface AppUser {
    id: string;
    email: string;
    role: 'superadmin' | 'administrador' | 'admin' | 'editor' | 'viewer';
    local_id?: string;
    nombre?: string;
}

interface LocalInfo {
    id: string;
    nombre: string;
    slug: string;
    tipo?: string;
}
```

---

## Patrón de servicio recomendado

Cada template wrappea `client.execute()` en funciones tipadas. Nunca llames `execute()` directamente desde una página.

```ts
// src/services/mi-vertical.service.ts
import type { SynaxisClient } from '@mylocal/sdk';

export interface Tarea {
    id: string;
    titulo: string;
    estado: 'pendiente' | 'en_curso' | 'hecho';
    prioridad: 'alta' | 'media' | 'baja';
}

export async function listTareas(
    client: SynaxisClient,
    localId: string,
    estado?: string,
): Promise<Tarea[]> {
    const res = await client.execute<Tarea[]>({
        action: 'tarea_list',
        data: { local_id: localId, ...(estado && { estado }) },
    });
    return res.data ?? [];
}
```

---

## Patrón de contexto recomendado

```tsx
// src/context/MiVerticalContext.tsx
import { createContext, useContext } from 'react';
import { useSynaxisClient } from '@mylocal/sdk';

const LOCAL_ID = import.meta.env.VITE_LOCAL_ID ?? 'local_default';

interface Ctx { client: ReturnType<typeof useSynaxisClient>; localId: string; }
const Ctx = createContext<Ctx | null>(null);

export function MiVerticalProvider({ children }: { children: React.ReactNode }) {
    const client = useSynaxisClient();
    return <Ctx.Provider value={{ client, localId: LOCAL_ID }}>{children}</Ctx.Provider>;
}

export function useMiVertical() {
    const ctx = useContext(Ctx);
    if (!ctx) throw new Error('useMiVertical debe usarse dentro de <MiVerticalProvider>');
    return ctx;
}
```
