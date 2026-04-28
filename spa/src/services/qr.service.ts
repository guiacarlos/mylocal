/**
 * Servicio QR + mesas.
 *
 * La generación del QR en sí es `local`: solo construye las URLs
 * `/mesa/<zona-slug>-<numero>` a partir de `restaurant_zones`. Lo que
 * pinta el bitmap en pantalla es una lib JS sin dependencia de red.
 *
 * Las operaciones de mesa (cart, requests, clear) son `server` porque
 * varios dispositivos (cliente móvil, TPV, cocina) comparten estado en
 * tiempo real. Synaxis local NO puede resolverlas sin sync.
 */

import type { SynaxisClient } from '../synaxis';
import type {
    OrderItem,
    RestaurantZone,
    SentOrder,
    TableCart,
    TableRequest,
} from '../types/domain';

/* ══════════════════════ generación QR (local) ══════════════════════ */

export interface QREntry {
    tableId: string;
    zoneName: string;
    number: number;
    slug: string; // ej 'salon-5'
    url: string; // URL absoluta para el QR
}

export async function generateQRList(
    client: SynaxisClient,
    baseUrl: string,
): Promise<QREntry[]> {
    const res = await client.execute<RestaurantZone[]>({
        action: 'list',
        collection: 'restaurant_zones',
    });
    if (!res.success || !res.data) return [];
    const zones = res.data as RestaurantZone[];
    const out: QREntry[] = [];
    for (const zone of zones) {
        if (!zone.tables) continue;
        for (const t of zone.tables) {
            const slug = `${slugify(zone.name)}-${t.number}`;
            out.push({
                tableId: t.id,
                zoneName: zone.name,
                number: t.number,
                slug,
                url: `${baseUrl.replace(/\/$/, '')}/mesa/${slug}`,
            });
        }
    }
    return out;
}

/* ══════════════════════ comanda de la mesa (server) ══════════════════════ */

export async function getTableOrder(
    client: SynaxisClient,
    tableId: string,
): Promise<TableCart | null> {
    const res = await client.execute<TableCart>({
        action: 'get_table_order',
        data: { table_id: tableId },
    });
    return res.success ? res.data : null;
}

export async function updateTableCart(
    client: SynaxisClient,
    tableId: string,
    cart: OrderItem[],
    source: 'TPV' | 'QR_CUSTOMER',
): Promise<TableCart | null> {
    const res = await client.execute<TableCart>({
        action: 'update_table_cart',
        data: { table_id: tableId, cart, source },
    });
    if (!res.success) throw new Error(res.error ?? 'No se pudo actualizar la mesa');
    return res.data;
}

export async function processExternalOrder(
    client: SynaxisClient,
    tableId: string,
    items: OrderItem[],
): Promise<SentOrder | null> {
    const res = await client.execute<SentOrder>({
        action: 'process_external_order',
        data: { table_id: tableId, items },
    });
    if (!res.success) throw new Error(res.error ?? 'No se pudo enviar el pedido');
    return res.data;
}

export async function clearTable(client: SynaxisClient, tableId: string): Promise<boolean> {
    const res = await client.execute<{ cleared: boolean }>({
        action: 'clear_table',
        data: { table_id: tableId },
    });
    return Boolean(res.success && res.data?.cleared);
}

/* ══════════════════════ peticiones de sala (server) ══════════════════════ */

export async function requestWaiter(
    client: SynaxisClient,
    tableId: string,
    message?: string,
): Promise<TableRequest | null> {
    return sendTableRequest(client, tableId, 'waiter', message);
}

export async function requestBill(
    client: SynaxisClient,
    tableId: string,
    message?: string,
): Promise<TableRequest | null> {
    return sendTableRequest(client, tableId, 'bill', message);
}

async function sendTableRequest(
    client: SynaxisClient,
    tableId: string,
    type: 'waiter' | 'bill',
    message?: string,
): Promise<TableRequest | null> {
    const res = await client.execute<TableRequest>({
        action: 'table_request',
        data: { table_id: tableId, type, message },
    });
    if (!res.success) throw new Error(res.error ?? 'No se pudo enviar la petición');
    return res.data;
}

export async function listPendingRequests(
    client: SynaxisClient,
): Promise<TableRequest[]> {
    const res = await client.execute<TableRequest[]>({
        action: 'get_table_requests',
        data: { only_pending: true },
    });
    return res.success && res.data ? (res.data as TableRequest[]) : [];
}

export async function acknowledgeRequest(
    client: SynaxisClient,
    requestId: string,
): Promise<boolean> {
    const res = await client.execute<{ acknowledged: boolean }>({
        action: 'acknowledge_request',
        data: { request_id: requestId },
    });
    return Boolean(res.success && res.data?.acknowledged);
}

/* ══════════════════════ helpers ══════════════════════ */

function slugify(s: string): string {
    return s
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}
