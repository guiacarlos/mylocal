/**
 * local.service.ts - configuracion del establecimiento.
 *
 * Wrappers tipados sobre las acciones get_local / update_local.
 */

import type { SynaxisClient } from '../synaxis/SynaxisClient';

export interface LocalInfo {
    id: string;
    nombre: string;
    telefono: string;
    direccion?: string;
    email?: string;
    web?: string;
    instagram?: string;
    tagline?: string;
    updated_at?: string;
}

async function call<T>(client: SynaxisClient, action: string, data: Record<string, unknown>): Promise<T> {
    const res = await client.execute({ action, data });
    if (!res.success) throw new Error(res.error ?? `Error en ${action}`);
    return res.data as T;
}

export async function getLocal(client: SynaxisClient, id = 'default'): Promise<LocalInfo> {
    return call<LocalInfo>(client, 'get_local', { id });
}

export async function updateLocal(
    client: SynaxisClient,
    id: string,
    patch: Partial<LocalInfo>,
): Promise<LocalInfo> {
    return call<LocalInfo>(client, 'update_local', { id, ...patch });
}

/**
 * Devuelve un nombre legible del local. Si no se ha configurado todavia,
 * devuelve fallback "Mi Local" para que las vistas no muestren strings vacios.
 */
export function localDisplayName(info?: LocalInfo | null): string {
    return (info?.nombre || '').trim() || 'Mi Local';
}
