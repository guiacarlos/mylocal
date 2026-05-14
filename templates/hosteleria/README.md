# Template: Hostelería

Gestión completa para bares y restaurantes. Carta digital QR, TPV, agente IA de sala.

**Puerto dev:** 5173 | **CSS prefix:** `sp-*` | **Build:** `.\build.ps1 -Template hosteleria`

## Páginas

| Ruta | Descripción |
|------|-------------|
| `/carta` | Carta digital pública (sin login) |
| `/carta/:mesa` | Carta con contexto de mesa para pedido QR |
| `/mesa/:slug` | Vista QR para el cliente |
| `/login` | Autenticación del hostelero |
| `/dashboard/*` | Panel de gestión autenticado |
| `/sistema/tpv/*` | Punto de venta (sala/cocina/admin) |

## Capabilities

```json
["LOGIN","OPTIONS","CARTA","QR","TPV","CRM","NOTIFICACIONES","GEMINI","AGENTE_RESTAURANTE","PRODUCTS","PAYMENT","FISCAL"]
```

## Credenciales de desarrollo

`socola@socola.es` / `socola2026` (auto-creadas en primer arranque)

## Arrancar

```bash
cd socola
npm install
npm run dev
```

Por defecto la dev server expone http://localhost:5173 y proxea `/acide/*` a `http://localhost:8090` (el PHP adelgazado). Puedes sobreescribir el target:

```bash
SOCOLA_API=http://localhost:8090 npm run dev
```

## Build para producción

```bash
npm run build
```

Salida en `dist/`. Ese directorio se sube a cualquier hosting estático (Netlify, Cloudflare Pages, S3, Apache, nginx). Asegúrate de tener el server PHP corriendo en `/acide/` — si no está, las acciones marcadas como `server` fallarán pero el resto de la SPA funciona local-first gracias a SynaxisCore.

## Estructura

```
socola/
├── public/
│   ├── seed/bootstrap.json        42 productos + tpv_settings reales
│   └── favicon.png
├── src/
│   ├── main.tsx                   boot + SynaxisProvider
│   ├── App.tsx                    router
│   ├── pages/                     Home, Carta, Checkout, Login, Dashboard,
│   │                              TPV, MesaQR, Academia, Nosotros, Contacto
│   ├── synaxis/
│   │   ├── SynaxisStorage.ts      adaptador IndexedDB + lock
│   │   ├── SynaxisQuery.ts        where/orderBy/limit/offset/search
│   │   ├── SynaxisCore.ts         CRUD + versionado + oplog
│   │   ├── SynaxisClient.ts       fachada híbrida local/HTTP
│   │   ├── actions.ts             catálogo de acciones con scope
│   │   ├── types.ts
│   │   └── index.ts
│   ├── hooks/useSynaxis.ts        provider React + siembra inicial
│   ├── services/                  carta, auth
│   └── theme/tokens.css           paleta Socolá (sage, coffee, honey)
├── server/
│   ├── index.php                  dispatcher adelgazado
│   ├── .htaccess
│   └── handlers/README.md         qué portar desde el ACIDE original
├── vite.config.ts
├── tsconfig.json
└── package.json
```

## Cómo se despacha una acción

El resto de la app nunca habla con fetch ni con SynaxisCore directamente — solo con el `SynaxisClient`:

```ts
import { useSynaxisClient } from '@/hooks/useSynaxis';
const client = useSynaxisClient();

const res = await client.execute({
  action: 'query',
  collection: 'products',
  params: { where: [['status', '=', 'publish']] },
});
```

El cliente mira el `scope` de la acción en [src/synaxis/actions.ts](src/synaxis/actions.ts):

- **local** → SynaxisCore resuelve en IndexedDB. Cero red.
- **server** → POST a `/acide/index.php`. Cero cache local.
- **hybrid** → prueba local; si está vacío, va a HTTP y cachea el resultado.

Si quieres forzar el scope de una acción concreta (ej. hacer `list_products` siempre HTTP durante un debug), pasa `overrides` al construir el client.

## Datos iniciales

La primera vez que cargas la SPA en un navegador, `SynaxisProvider` detecta que las colecciones están vacías e importa `public/seed/bootstrap.json`. A partir de ese momento los datos viven en IndexedDB (DevTools → Application → IndexedDB → `socola__socola`).

Para regenerar el seed desde el `STORAGE/` del ACIDE viejo:

```bash
cd ..   # raíz del repo padre
node -e "/* ver script en SYNAXIS_MIGRATION.md */"
```

## Limpiar la DB del navegador

En la SPA:

```ts
await client.execute({ action: '_debug_reset' });
```

O en DevTools: Application → IndexedDB → botón derecho sobre la DB → Delete.

## Estado actual

- ✅ Scaffold Vite + React + TS
- ✅ SynaxisCore completo en TS (CRUD + query + versionado + oplog + master collections)
- ✅ SynaxisClient con transporte conmutable local/HTTP/hybrid
- ✅ Carta pública funciona 100% local (lee de IndexedDB)
- ✅ Home, Login, Nosotros, Contacto funcionales
- ⏳ Checkout, TPV, MesaQR, Dashboard, Academia → stubs
- ⏳ Server PHP adelgazado → esqueleto sin handlers implementados (ver [server/handlers/README.md](server/handlers/README.md))

## Próximos pasos

1. Portar `server/handlers/auth.php` (es lo único que bloquea el flujo de login real).
2. Portar `server/handlers/ai.php` para desbloquear la Academia y el chat del restaurante.
3. Implementar pantallas del Dashboard (CRUD de productos vía SynaxisCore).
4. Implementar `synaxis_sync` (Fase 3) para multi-dispositivo.
