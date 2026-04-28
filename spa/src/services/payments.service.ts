/**
 * Servicio de pagos Revolut.
 *
 * TODOS los endpoints aquí son `server` en el catálogo — la API key de
 * Revolut vive en `server/config/revolut.json` y NUNCA en el cliente.
 *
 * Flujo típico:
 *   1. Cliente pulsa "Pagar con tarjeta (Revolut)" → `createPayment()`.
 *   2. Server llama a Revolut, devuelve `{orderId, publicId, state}`.
 *   3. SPA hace polling a `checkPayment(orderId)` cada ~3s.
 *   4. Revolut llama al webhook `/acide/?action=revolut_webhook` cuando
 *      cambia el estado → server actualiza la orden.
 *   5. SPA ve `state: COMPLETED` y muestra la confirmación.
 *
 * Los estados siguen la API v2025-12-04: PENDING, COMPLETED, FAILED,
 * CANCELLED.
 */

import type { SynaxisClient } from '../synaxis';

export interface CreatePaymentInput {
    amount: number; // euros (se convierten a céntimos en el server)
    currency?: string; // default EUR
    description: string;
    orderId?: string; // id del pedido interno para metadata
    tableId?: string;
}

export interface CreatePaymentResult {
    orderId: string; // id Revolut
    publicId: string; // token público para iframe/redirect
    state: RevolutState;
    mode: 'sandbox' | 'live';
}

export type RevolutState = 'PENDING' | 'COMPLETED' | 'FAILED' | 'CANCELLED';

export interface CheckPaymentResult {
    orderId: string;
    state: RevolutState;
    updatedAt: string;
}

export async function createPayment(
    client: SynaxisClient,
    input: CreatePaymentInput,
): Promise<CreatePaymentResult> {
    const res = await client.execute<CreatePaymentResult>({
        action: 'create_revolut_payment',
        data: {
            amount: input.amount,
            currency: input.currency ?? 'EUR',
            description: input.description,
            orderId: input.orderId,
            tableId: input.tableId,
        },
    });
    if (!res.success || !res.data) {
        throw new Error(res.error ?? 'No se pudo crear el pago');
    }
    return res.data;
}

export async function checkPayment(
    client: SynaxisClient,
    orderId: string,
): Promise<CheckPaymentResult> {
    const res = await client.execute<CheckPaymentResult>({
        action: 'check_revolut_payment',
        data: { order_id: orderId },
    });
    if (!res.success || !res.data) {
        throw new Error(res.error ?? 'No se pudo consultar el pago');
    }
    return res.data;
}

/**
 * Polling simple hasta que el pago termine o falle. Útil para la UI
 * de checkout. Se cancela desde fuera con `signal`.
 */
export async function pollPayment(
    client: SynaxisClient,
    orderId: string,
    {
        intervalMs = 3000,
        timeoutMs = 5 * 60 * 1000,
        signal,
    }: { intervalMs?: number; timeoutMs?: number; signal?: AbortSignal } = {},
): Promise<CheckPaymentResult> {
    const start = Date.now();
    while (true) {
        if (signal?.aborted) throw new Error('Polling cancelado');
        const r = await checkPayment(client, orderId);
        if (r.state !== 'PENDING') return r;
        if (Date.now() - start > timeoutMs) throw new Error('Timeout pago Revolut');
        await delay(intervalMs);
    }
}

export async function validateCoupon(
    client: SynaxisClient,
    code: string,
    subtotal: number,
): Promise<{ ok: boolean; discount: number; reason?: string }> {
    const res = await client.execute<{ ok: boolean; discount: number; reason?: string }>({
        action: 'validate_coupon',
        data: { code, subtotal },
    });
    if (!res.success) return { ok: false, discount: 0, reason: res.error ?? 'Cupón inválido' };
    return res.data ?? { ok: false, discount: 0 };
}

function delay(ms: number): Promise<void> {
    return new Promise((r) => setTimeout(r, ms));
}
