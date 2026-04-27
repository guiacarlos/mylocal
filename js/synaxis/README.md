# SynaxisCore — base de datos JSON en navegador

Librería cliente que expone el mismo contrato que el dispatcher PHP de ACIDE pero almacena los datos en IndexedDB. Ver [SYNAXIS_MIGRATION.md](../../SYNAXIS_MIGRATION.md) para el plan completo.

## Estado

**Fase 1** — CRUD + Query + Versionado. No hay sync ni transporte HTTP todavía. No está conectada a la SPA ni a `carta.html` — es una librería standalone que se puede probar con [test-synaxis.html](../../test-synaxis.html).

## Archivos

| Archivo | Rol |
| :-- | :-- |
| `synaxis-storage.js` | Adaptador IndexedDB puro (put/get/all/remove/clear). |
| `synaxis-query.js` | Motor de filtros espejo de [QueryEngine.php](../../CORE/core/QueryEngine.php). |
| `synaxis-core.js` | Clase `SynaxisCore`: `execute({action, ...})` → `{success, data, error}`. |

Se cargan como `<script>` sueltos (UMD) o como `require()`/`import`. No hay build step.

## Uso rápido

```html
<script src="/js/synaxis/synaxis-storage.js"></script>
<script src="/js/synaxis/synaxis-query.js"></script>
<script src="/js/synaxis/synaxis-core.js"></script>
<script>
  const core = new SynaxisCore({ namespace: 'socola', project: 'socolaaaaa' });
  await core.ready;

  // Insert
  await core.execute({
    action: 'update',
    collection: 'products',
    id: 'espresso',
    data: { name: 'Espresso', price: 1.8, status: 'publish' }
  });

  // Query
  const res = await core.execute({
    action: 'query',
    collection: 'products',
    params: { where: [['status', '=', 'publish']], orderBy: { field: 'price', direction: 'asc' } }
  });
  console.log(res.data.items);
</script>
```

## Contrato

Mismo que ACIDE:

```js
{ action, collection?, id?, data?, params? }   // request
{ success, data, error }                       // response
```

Acciones soportadas hoy: `read`, `get`, `list`, `query`, `create`, `update`, `delete`, `health_check`, `_debug_reset`.

### Operadores `where`

Espejo de [QueryEngine.php:44-80](../../CORE/core/QueryEngine.php#L44-L80): `=`, `==`, `!=`, `>`, `<`, `>=`, `<=`, `IN`, `contains`.

### Metadatos automáticos

Cada `update` añade/actualiza:

- `_createdAt` (ISO-8601, solo en primera inserción)
- `_updatedAt` (ISO-8601, cada escritura)
- `_version` (entero, autoincrementa)

Flag de entrada `_REPLACE_: true` → sustituye el doc entero en vez de merge.

### Master collections

`users`, `roles`, `projects`, `system_logs` van a una **DB IndexedDB separada** (`<namespace>__master`) para no mezclarse con datos per-proyecto. Espejo de [CRUDOperations.php:11-22](../../CORE/core/CRUDOperations.php#L11-L22).

## Por qué no se conecta todavía a `carta.html`

Primero hay que validar la librería en aislamiento con [test-synaxis.html](../../test-synaxis.html). El swap del transporte (Fase 2) va detrás de una decisión explícita (local / HTTP / híbrido) que no se ha tomado aún — ver `SYNAXIS_MIGRATION.md` §7.

## Versionado interno

Cada `update` guarda el doc **anterior** en una colección paralela `<collection>__versions` con clave `<id>@<version>`. Se conservan las 5 últimas. Se consulta con `core.listVersions(collection, id)`.

## Limitaciones conocidas (v1)

- `listCollections()` solo ve las que ya se han creado en esta sesión (ensureStore sube la versión de IndexedDB bajo demanda).
- Sin índices secundarios: los filtros son O(n) en memoria. Igual que el PHP actual. Optimización dejada para cuando haga falta.
- Sin cifrado en reposo.
- Sin sync. SynaxisCore por sí solo **no habla** con el servidor.
