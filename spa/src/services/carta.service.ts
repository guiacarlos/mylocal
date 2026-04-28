/**
 * Servicio de carta — leer productos via SynaxisClient.
 * Mantiene el contrato `list_products` del backend original.
 */

import type { SynaxisClient, SynaxisDoc } from '../synaxis';

export interface Product extends SynaxisDoc {
    id: string;
    slug?: string;
    name: string;
    sku?: string;
    price: number;
    currency?: string;
    stock?: number;
    status: 'publish' | 'draft' | string;
    category?: string;
    description?: string;
    image?: string;
    allergens?: string[];
    badges?: string[];
}

export async function listPublishedProducts(client: SynaxisClient): Promise<Product[]> {
    const res = await client.execute<Product[]>({
        action: 'list_products',
        collection: 'products',
    });
    if (!res.success || !res.data) return [];
    return (res.data as Product[]).filter((p) => p.status === 'publish');
}

export async function queryProducts(
    client: SynaxisClient,
    category?: string,
): Promise<Product[]> {
    const where = [['status', '=', 'publish']] as [string, string, unknown][];
    if (category) where.push(['category', '=', category]);

    const res = await client.execute<{ items: Product[] }>({
        action: 'query',
        collection: 'products',
        params: {
            where: where as never,
            orderBy: { field: 'price', direction: 'asc' },
        },
    });
    return res.success && res.data ? res.data.items : [];
}
