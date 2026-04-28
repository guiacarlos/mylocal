# Pagos — Revolut Merchant API

Integración con Revolut Business. Docs oficiales: https://developer.revolut.com/docs/merchant/

## Flujo completo

```
SPA                    Server                   Revolut
 │                      │                         │
 │──create_payment─────▶│                         │
 │                      │──POST /orders──────────▶│
 │                      │◀──{id, token, state}────│
 │◀──{orderId, ...}─────│                         │
 │                                                │
 │──(iframe/redirect con token)──────────────────▶│
 │                                                │
 │    (cliente paga en el widget Revolut)         │
 │                                                │
 │──check_payment──────▶│──GET /orders/{id}──────▶│
 │◀─{state: PENDING}────│◀────────────────────────│
 │                                                │
 │   (polling cada 3s)                            │
 │                                                │
 │                     Revolut──webhook──────────▶│
 │                      │◀─POST /acide/?action=   │
 │                      │   revolut_webhook       │
 │                      │                         │
 │──check_payment──────▶│ (ya tiene el estado     │
 │◀─{state: COMPLETED}──│  cacheado)              │
```

## Acciones

| Acción | Scope | Servicio |
| :-- | :-- | :-- |
| `create_revolut_payment` | server | `createPayment()` |
| `check_revolut_payment` | server | `checkPayment()` / `pollPayment()` |
| `revolut_webhook` | server | *entrada directa de Revolut* |
| `validate_coupon` | hybrid | `validateCoupon()` |

Servicio cliente: [`src/services/payments.service.ts`](../src/services/payments.service.ts).
Handler servidor: [`server/handlers/payments.php`](../server/handlers/payments.php).

## Configuración

Archivo `server/config/revolut.json` (copiar de [`revolut.json.example`](../server/config/revolut.json.example)):

```json
{
    "api_key": "sk_...",
    "mode": "sandbox",
    "api_version": "2025-12-04",
    "endpoints": {
        "sandbox": "https://sandbox-merchant.revolut.com/api",
        "live": "https://merchant.revolut.com/api"
    },
    "webhook_secret": "...",
    "currency_default": "EUR",
    "capture_mode": "automatic",
    "timeout_seconds": 15
}
```

- `mode`: `sandbox` para desarrollo, `live` para producción. El handler elige `endpoints[mode]` automáticamente.
- `api_version`: cabecera `Revolut-Api-Version` obligatoria.
- `webhook_secret`: compartido entre Revolut y el server para validar firma HMAC-SHA256 en el webhook.
- `capture_mode: automatic` captura el importe al confirmar el pago. `manual` requiere un `capture` explícito después.

## Crear un pago

```ts
import { useSynaxisClient } from '@/hooks/useSynaxis';
import { createPayment, pollPayment } from '@/services/payments.service';

const client = useSynaxisClient();
const p = await createPayment(client, {
  amount: 3.80,           // euros, se convierten a céntimos en el server
  currency: 'EUR',
  description: 'Mesa 4 — comanda',
  orderId: localOrderId,  // metadata
  tableId: 't_4',
});
// p.publicId → pasárselo al widget Revolut Checkout

// Esperar resolución
const result = await pollPayment(client, p.orderId, { intervalMs: 3000 });
if (result.state === 'COMPLETED') { /* confirmado */ }
```

## Campos que se envían a Revolut

El handler construye:

```json
{
    "amount": 380,
    "currency": "EUR",
    "capture_mode": "automatic",
    "description": "Mesa 4 — comanda",
    "metadata": {
        "order_id": "local_order_id_interno",
        "table_id": "t_4"
    }
}
```

Con cabeceras:

```
Authorization: Bearer <api_key>
Revolut-Api-Version: 2025-12-04
Content-Type: application/json
```

Respuesta de Revolut (HTTP 201):

```json
{
    "id": "6c...",
    "token": "tkn_...",
    "state": "PENDING",
    "amount": 380,
    "currency": "EUR"
}
```

El handler devuelve al cliente:

```json
{
    "orderId": "6c...",
    "publicId": "tkn_...",
    "state": "PENDING",
    "mode": "sandbox"
}
```

## Estados

Revolut v2025-12-04:

| State | Significado | Acción SPA |
| :-- | :-- | :-- |
| `PENDING` | En proceso (widget abierto / 3DS / etc.) | seguir polling |
| `COMPLETED` | Pagado | marcar pedido como paid, limpiar mesa |
| `FAILED` | Rechazado | reintentar u ofrecer otro método |
| `CANCELLED` | Usuario canceló | permitir reintento |

## Webhook

Revolut llama a `POST /acide/?action=revolut_webhook` cuando el estado cambia (completado, fallido).

El handler [`revolut_webhook`](../server/handlers/payments.php):

1. Lee el body crudo (`php://input`) — **no** usa `$_POST`.
2. Valida la firma `Revolut-Signature: v1=<hex-hmac>` con `webhook_secret` (HMAC-SHA256).
3. Extrae `order_id` y `state` / `event`.
4. Actualiza `server/data/orders/<orderId>.json` con `revolut_state` y `webhook_at`.
5. Devuelve `{success:true}` rápido — Revolut no acepta respuestas lentas.

**Importante**: la ruta del webhook debe ser pública (sin auth). El `.htaccess` del server debe permitirla explícitamente.

## Almacenamiento server-side

`server/data/orders/<orderId>.json`:

```json
{
    "id": "6c...",
    "revolut_state": "COMPLETED",
    "amount": 380,
    "currency": "EUR",
    "webhook_at": "2026-04-17T11:00:00Z",
    "_version": 2,
    "_updatedAt": "..."
}
```

Esto sirve de **cache del estado** consultable por la SPA sin pegar a Revolut cada vez.

## Otros métodos de pago

- **Cash / Card (local)**: se marca la comanda como paid sin llamada externa — es presencial.
- **Bizum**: se muestra el número (`payment_settings.bizumPhone`) y se espera confirmación manual.
- **Transferencia**: se da el IBAN, no pasa por el server.

Todos viven en `payment_settings.enabled: ['cash', 'card', 'revolut', 'bizum', 'transfer']`. Solo Revolut necesita integración real.

## Testing en sandbox

1. Crear cuenta Business en https://business.revolut.com y activar **Merchant** en modo sandbox.
2. Obtener API key de sandbox.
3. Dejar `mode: "sandbox"` en `revolut.json`.
4. Usar las tarjetas de prueba que provee Revolut en su dashboard.
5. Para probar el webhook localmente, exponer el server con ngrok/cloudflare tunnel y configurar la URL en el panel Revolut.

## Seguridad

- **Nunca** devuelvas la `api_key` al cliente.
- **Nunca** aceptes un `orderId` arbitrario del cliente sin validar que pertenece a una orden que hizo este usuario / mesa.
- El webhook **debe** validar la firma HMAC antes de modificar datos, o cualquiera puede marcar órdenes como pagadas.
- Loguea intentos de webhook con firma inválida.

## Lo que falta

- Validar exactamente el formato de firma del webhook (el stub actual es razonable pero no probado contra Revolut real).
- Widget de Revolut Checkout integrado en `src/pages/Checkout.tsx`.
- Reintentos idempotentes si `create_payment` recibe 5xx transitorio.
- Rate-limit específico para `create_payment` además del de IA.
- Tests contra las respuestas reales del sandbox.
