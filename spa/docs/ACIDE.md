# ACIDE / SynaxisCore — arquitectura

> ACIDE nació como backend PHP monolítico. En esta nueva encarnación se convierte en **SynaxisCore**: una base de datos JSON que corre en el navegador, documentada para ser usada como librería por cualquier SPA y con la filosofía de "IA-first database" — todos los datos son documentos, todas las consultas son declarativas, la IA puede leer/escribir como un usuario más.

## Filosofía

| Antes (ACIDE PHP) | Ahora (SynaxisCore JS) |
| :-- | :-- |
| Monolito en servidor | Librería en el navegador |
| JSON en `STORAGE/*.json` | Documentos en IndexedDB |
| `POST /acide/index.php` | `client.execute({action, ...})` |
| `CRUDOperations.php` + `QueryEngine.php` | `SynaxisCore.ts` + `SynaxisQuery.ts` |
| Dispatcher gigante con un `switch` | Dispatcher con un `switch` + catálogo tipado de acciones |
| Todo requiere servidor | Solo lo imprescindible requiere servidor |

**Regla invariante**: el contrato `{action, data} → {success, data, error}` no cambia. La única diferencia es dónde se resuelve.

## Las tres capas

```
┌─────────────── React pages/components ────────────────┐
│  Solo llaman a SynaxisClient. Cero fetch, cero Core.  │
└────────────────────────┬──────────────────────────────┘
                         │ client.execute(req)
                         ▼
┌───────────── SynaxisClient — router de scope ─────────┐
│  local   → SynaxisCore (IndexedDB)                    │
│  server  → HTTP POST /acide/index.php                 │
│  hybrid  → local primero; si vacío, HTTP + cache      │
└──────────┬────────────────────────────┬───────────────┘
           ▼                            ▼
    SynaxisCore                  server/ (PHP thin)
    (browser)                    (solo lo forzoso)
```

Ver código: [src/synaxis/SynaxisClient.ts](../src/synaxis/SynaxisClient.ts), [src/synaxis/SynaxisCore.ts](../src/synaxis/SynaxisCore.ts), [src/synaxis/actions.ts](../src/synaxis/actions.ts).

## Componentes de SynaxisCore

### `SynaxisStorage`

Adaptador IndexedDB puro. Una DB por scope:

- `socola__socola` — datos del proyecto (products, orders, coupons, agente_restaurante, etc.).
- `socola__master` — "master collections" globales (users, roles, projects, system_logs) que no se deben mezclar con scope de proyecto. Espejo de la regla de [`CRUDOperations.php:11-22`](../../CORE/core/CRUDOperations.php#L11-L22) del ACIDE viejo.

Cada **colección** (p.ej. `products`) es un `IDBObjectStore` con `keyPath: 'id'`. Nuevas colecciones se crean bajo demanda subiendo la versión del schema (con un mutex interno para evitar `VersionError`).

### `SynaxisQuery`

Motor de filtros. Soporta el mismo lenguaje que [`QueryEngine.php`](../../CORE/core/QueryEngine.php):

```ts
{
  where: [
    ['status', '=', 'publish'],
    ['price', '<=', 3.0],
    ['tags', 'contains', 'sin-gluten'],
    ['id', 'IN', ['prod_1', 'prod_2']],
  ],
  search: 'espresso',       // full-text naïve sobre campos string
  orderBy: { field: 'price', direction: 'asc' },
  limit: 20,
  offset: 0,
}
```

Operadores: `=`, `==`, `!=`, `>`, `<`, `>=`, `<=`, `IN`, `contains`.

### `SynaxisCore`

Orquestador de CRUD. Cada `update` añade/actualiza `_version`, `_createdAt`, `_updatedAt`. Flag `_REPLACE_` fuerza sustitución en lugar de merge. Versionado interno en `<collection>__versions` (últimas 5).

Además mantiene un **oplog** append-only en `__oplog__`, pensando en Fase 3 (sync con server para multi-dispositivo). Cada `put`/`delete` añade una entrada; `drainOplog()` y `clearOplog(ids)` permiten empaquetar batches y enviarlos al server vía `synaxis_sync`.

### `SynaxisClient`

Fachada única que expone el resto de la app consume. Lee el `scope` de cada acción desde [`actions.ts`](../src/synaxis/actions.ts) y decide si la resuelve contra IndexedDB, contra HTTP, o hybrid (local primero + HTTP fallback + cache on success).

Overrides opcionales al construir:

```ts
new SynaxisClient({
  namespace: 'socola',
  project: 'socola',
  overrides: { list_products: 'local' }, // forzar offline durante debug
});
```

## Cómo añadir una acción nueva

1. **Decide el scope**: ¿necesita servidor? Si no, es `local`. Si es IO pero se puede cachear, `hybrid`. Si requiere secreto / webhook / coordinación multi-dispositivo, `server`.
2. **Añádela al catálogo** en [`src/synaxis/actions.ts`](../src/synaxis/actions.ts).
3. Si es `local`/`hybrid` y es CRUD simple → no hay que escribir código, `SynaxisCore` ya la resuelve.
4. Si es `server` → implementa el handler en `server/handlers/<domain>.php` y regístralo en el `switch` de [`server/index.php`](../server/index.php).
5. Crea un **service** en `src/services/<domain>.service.ts` con la firma tipada. Las pages usan el service, nunca `client.execute` directo.
6. Documenta en [DATA_MODEL.md](DATA_MODEL.md) si introduce una colección nueva.

## Diferencias con el ACIDE viejo

- **No hay "capabilities" como plugins**. En ACIDE viejo, STORE, ACADEMY, QR etc. eran motores cargados según `active_plugins.json`. En SynaxisCore todo es una colección más; lo que varía es si el frontend tiene UI para ella.
- **No hay "projects"** con selector de proyecto activo. El `project` se fija al construir el `SynaxisClient`. Si en el futuro hace falta multi-proyecto, se construyen clientes distintos.
- **No hay `GlandManager` / `MCP Bridge`**. Si hace falta comunicar con un MCP server, se añade un adapter en `server/handlers/` y se expone como acción.
- **No hay tema modificable "live"**. El theme es CSS tokens en [`src/theme/tokens.css`](../src/theme/tokens.css); cambiarlo es editar esos valores, no tocar un JSON.

## Ver también

- [DATA_MODEL.md](DATA_MODEL.md) — todas las colecciones y su esquema.
- [AGENTS.md](AGENTS.md) — maître/camarero IA + vault.
- [PAYMENTS.md](PAYMENTS.md) — flujo Revolut.
- [QR.md](QR.md) — mesas y pedidos QR.
- [SECRETS.md](SECRETS.md) — dónde van las claves.
