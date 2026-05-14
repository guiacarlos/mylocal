# Template: Logística

Gestión de pedidos, flota de reparto y seguimiento público de entregas.

**Puerto dev:** 5175 | **CSS prefix:** `lg-*` | **Build:** `.\build.ps1 -Template logistica`

## Levantar en desarrollo

```powershell
run.bat logistica   # → http://localhost:5175
```

## Páginas

| Ruta | Página | Auth | Descripción |
|------|--------|------|-------------|
| `/pedidos` | PedidosPage | si | Listado con filtro por estado + nuevo pedido |
| `/flota` | FlotaPage | si | Vehículos/conductores con toggle activo/inactivo |
| `/entregas` | EntregasPage | si | Vista del día + asignación pedido → vehículo |
| `/incidencias` | IncidenciasPage | si | Registro de incidencias por tipo |
| `/seguimiento/:codigo` | SeguimientoPublicoPage | **no** | Tracking público por código de 8 chars |

La ruta `/seguimiento/:codigo` es pública — no requiere login y usa `useSynaxisClient()` directamente.

## Capabilities

```json
["LOGIN","OPTIONS","CRM","NOTIFICACIONES","DELIVERY"]
```

## Estados de pedido

```
recibido → preparando → en_ruta → entregado
                                ↘ incidencia
```

## Contexto

```tsx
import { useLogistica } from './context/LogisticaContext';

const { client, localId } = useLogistica();
```

## Servicios disponibles

```ts
import {
    listPedidos, createPedido, cambiarEstadoPedido, getPedidoByCodigo,
    listVehiculos, createVehiculo, updateVehiculo,
    asignarEntrega, listEntregasDia,
    addIncidencia,
} from './services/delivery.service';
```

## Eventos EventBus emitidos

| Evento | Cuándo |
|--------|--------|
| `pedido.creado` | Al crear un nuevo pedido — push a OpenClaw si está configurado |

## Variables de entorno

```env
VITE_API_URL=http://localhost:8091/acide/index.php
VITE_LOCAL_ID=local_logistica
```
