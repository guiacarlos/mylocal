# QR · Mesas · Pedidos

El cliente escanea el QR de su mesa → abre `https://socola.xxx/mesa/<zone-slug>-<number>` → ve la carta → añade productos al "cart de la mesa" → la comanda llega a la cocina.

## Componentes

- **`restaurant_zones`** (colección) — layout del local: zonas y mesas. Es el "mapa" editable desde el Dashboard → QR.
- **Generador de QR** — JS que construye las URLs y las pinta en un `<canvas>` para imprimir. 100% local.
- **`table_orders`** (server) — comanda viva de cada mesa, compartida entre cliente QR, TPV y cocina.
- **`sent_orders`** (server) — cola de comandas enviadas a cocina.
- **`table_requests`** (server) — peticiones "llamar camarero" / "cuenta".

## Generación de los QRs

Scope: **local**. No requiere red.

```ts
import { useSynaxisClient } from '@/hooks/useSynaxis';
import { generateQRList } from '@/services/qr.service';

const client = useSynaxisClient();
const qrs = await generateQRList(client, 'https://socola.miaplic.com');
// [
//   { tableId: 't_1', zoneName: 'Salon', number: 1, slug: 'salon-1',
//     url: 'https://socola.miaplic.com/mesa/salon-1' },
//   ...
// ]
```

La URL se pasa a cualquier librería QR del lado cliente (`qrcode`, `qr-code-styling`, etc.) para pintarla. No hay librería incluida por defecto — cuando se implemente la UI de impresión, añadir `qrcode` al `package.json`.

## Esquema `restaurant_zones`

Cada zona es un documento:

```json
{
    "id": "z_1773449176187",
    "name": "Salon",
    "tables": [
        {
            "id": "t_1773449179923",
            "number": 1,
            "capacity": 2,
            "x": 50,
            "y": 50,
            "width": 70,
            "height": 70,
            "shape": "square",
            "status": "free"
        }
    ]
}
```

Los campos `x/y/width/height/shape` son para el editor visual del Dashboard que dibuja el plano del local. El QR sólo usa `id`, `number` y el `zone.name` (para el slug).

Ver tipos TS: [`RestaurantZone`, `RestaurantTable`](../src/types/domain.ts).

## Pedido desde la mesa

### 1. Cliente escanea y entra a `/mesa/salon-4`

El router en [`App.tsx`](../src/App.tsx) captura `:slug` → `MesaQR.tsx`. La página:

- Carga el `tableId` correspondiente (match `slug` → `zone + number`).
- Carga el `table_order` actual del server (`get_table_order`).
- Muestra la carta (local, desde `products`).
- Permite añadir/quitar items → sincroniza con `update_table_cart` (source `QR_CUSTOMER`).

### 2. Cliente "envía comanda"

`process_external_order({table_id, items})` → el server:

1. Merge con la comanda viva en `table_orders`, **marcando los nuevos items con `_key: ext_<productId>_<rand>`** para que el TPV sepa que vienen del QR.
2. Añade los items a `sent_orders[tableId].items` (la cola de cocina).
3. Marca `table_orders[tableId].status = "pending_confirmation"`.

### 3. TPV en cocina ve los items

La cocina hace polling a `get_table_requests` + `get_table_order`. Cuando confirma, actualiza con `update_table_cart` pasando el cart con los items ya sin marca `ext_`.

### Merge race-safe

El problema clásico: el cliente añade algo por QR en `t = 0ms`, el TPV hace un update propio en `t = 1ms` sin haber visto el cambio del QR → sobrescribe.

Solución implementada en [`update_table_cart`](../server/handlers/qr.php): si viene un update con `source: "TPV"`, **preservamos** todos los items del cart existente cuyo `_key` empiece por `ext_` y no estén en el nuevo cart. Así los items del QR nunca se pierden aunque el TPV aún no los "vea".

## Peticiones de camarero

El cliente puede pulsar "llamar camarero" o "pedir cuenta":

```ts
import { requestWaiter, requestBill } from '@/services/qr.service';

await requestWaiter(client, tableId, 'Necesitamos servilletas');
await requestBill(client, tableId);
```

El server crea un `table_requests/<id>.json`:

```json
{
    "id": "req_ab12cd34",
    "table_id": "t_1",
    "table_name": "Salón · Mesa 1",
    "type": "bill",
    "message": "",
    "status": "pending",
    "created_at": "2026-04-17T11:00:00Z"
}
```

La sala hace polling con `listPendingRequests(client)` y las marca como atendidas con `acknowledgeRequest(client, requestId)`.

## Limpieza tras pago

Cuando el TPV cobra la mesa (o el pago Revolut se completa), llamar a `clearTable(client, tableId)`:

- Borra `table_orders/<tableId>.json`.
- Borra `sent_orders/<tableId>.json`.
- **No** toca `table_requests` — esas se cierran individualmente.

## Estados de una mesa

| Estado `status` | Significado |
| :-- | :-- |
| `free` | Sin comanda viva |
| `active` | Con comanda, TPV como source |
| `pending_confirmation` | Hay items del QR sin confirmar por cocina |

El layout visual del Dashboard colorea las mesas según estos estados + `status` del propio doc de la mesa dentro de su zona.

## Limitaciones actuales

- **No hay WebSocket / SSE**: la SPA hace polling (~3-5s) a `get_table_order` y `get_table_requests`. Suficiente para un café; para volúmenes grandes, añadir un canal push.
- **No hay multi-tenant**: la estructura soporta `tenantId` pero el server todavía no lo filtra.
- **No hay control de concurrencia fino** más allá del merge `ext_`. Si dos QRs del mismo móvil hacen `update_table_cart` simultáneo con payloads distintos, el último gana.

## Lo que falta en UI

- Página `/mesa/:slug` con carta + cart.
- Canvas de impresión de QRs desde el Dashboard (`DashboardQR.tsx`).
- Panel "pedidos" en cocina con cola en tiempo real.
- Bandeja de "peticiones de sala".
