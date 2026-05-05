/**
 * sala.service - configuracion de sala (zonas + mesas + QRs).
 *
 * Todas las acciones son scope:'server'. Pasan via SynaxisClient que
 * inyecta el Bearer token automaticamente.
 */

import type { SynaxisClient } from '../synaxis';

export interface Zona {
    id: string;
    local_id: string;
    nombre: string;
    icono: string;
    orden: number;
    activa: boolean;
}

export interface Mesa {
    id: string;
    local_id: string;
    zone_id: string;
    numero: string;
    capacidad: number;
    qr_token: string;
    estado: 'libre' | 'pidiendo' | 'esperando' | 'pagada';
    activa: boolean;
}

export interface SalaResumen {
    zonas: Zona[];
    mesas_total: number;
    mesas_por_zona: Record<string, number>;
}

export type Preset = 'barra' | 'salon' | 'salon_terraza' | 'completo';

async function call<T>(client: SynaxisClient, action: string, data: Record<string, unknown> = {}): Promise<T> {
    const res = await client.execute<T>({ action, data });
    if (!res.success) throw new Error(res.error ?? `Error en ${action}`);
    return res.data as T;
}

// ── Resumen ──────────────────────────────────────────────────────────

export async function getSalaResumen(client: SynaxisClient, localId = 'default'): Promise<SalaResumen> {
    return call<SalaResumen>(client, 'sala_resumen', { local_id: localId });
}

// ── Zonas ────────────────────────────────────────────────────────────

export async function listZonas(client: SynaxisClient, localId = 'default'): Promise<Zona[]> {
    return call<Zona[]>(client, 'list_zonas', { local_id: localId });
}

export async function createZona(client: SynaxisClient, payload: Partial<Zona>): Promise<Zona> {
    return call<Zona>(client, 'create_zona', payload as Record<string, unknown>);
}

export async function updateZona(client: SynaxisClient, id: string, patch: Partial<Zona>): Promise<Zona> {
    return call<Zona>(client, 'update_zona', { id, ...patch });
}

export async function deleteZona(client: SynaxisClient, id: string): Promise<void> {
    await call(client, 'delete_zona', { id });
}

export async function createZonasPreset(client: SynaxisClient, preset: Preset, localId = 'default'): Promise<Zona[]> {
    return call<Zona[]>(client, 'create_zonas_preset', { local_id: localId, preset });
}

export async function reorderZonas(client: SynaxisClient, orderedIds: string[], localId = 'default'): Promise<number> {
    return call<number>(client, 'reorder_zonas', { local_id: localId, ordered_ids: orderedIds });
}

// ── Mesas ────────────────────────────────────────────────────────────

export async function listMesas(client: SynaxisClient, opts: { localId?: string; zoneId?: string } = {}): Promise<Mesa[]> {
    return call<Mesa[]>(client, 'list_mesas', {
        local_id: opts.localId ?? 'default',
        zone_id: opts.zoneId ?? '',
    });
}

export async function createMesa(client: SynaxisClient, payload: Partial<Mesa>): Promise<Mesa> {
    return call<Mesa>(client, 'create_mesa', payload as Record<string, unknown>);
}

export async function updateMesa(client: SynaxisClient, id: string, patch: Partial<Mesa>): Promise<Mesa> {
    return call<Mesa>(client, 'update_mesa', { id, ...patch });
}

export async function deleteMesa(client: SynaxisClient, id: string): Promise<void> {
    await call(client, 'delete_mesa', { id });
}

export async function createMesasBatch(
    client: SynaxisClient,
    opts: { zoneId: string; cantidad: number; startNumero?: number; capacidad?: number; localId?: string }
): Promise<Mesa[]> {
    return call<Mesa[]>(client, 'create_mesas_batch', {
        local_id: opts.localId ?? 'default',
        zone_id: opts.zoneId,
        cantidad: opts.cantidad,
        start_numero: opts.startNumero ?? 1,
        capacidad: opts.capacidad ?? 4,
    });
}

export async function regenerateMesaQr(client: SynaxisClient, id: string): Promise<Mesa> {
    return call<Mesa>(client, 'regenerate_mesa_qr', { id });
}

// ── URLs publicas: friendly + agnosticas de dominio ──────────────────

/**
 * Convierte un nombre humano en slug seguro para URL.
 * "Terraza Sur" -> "terraza-sur", "Sala VIP" -> "sala-vip"
 * Quita acentos, baja a minusculas, sustituye no-alfanumerico por '-'.
 */
export function slugify(s: string): string {
    return (s ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '') || 'sin-nombre';
}

/**
 * URL del QR default del local (caso "publicar carta online").
 * Misma para todas las mesas: lleva a la carta digital del local.
 */
export function buildLocalCartaUrl(): string {
    if (typeof window === 'undefined') return '/carta';
    return `${window.location.protocol}//${window.location.host}/carta`;
}

/**
 * URL especifica de una mesa: /carta/<zona-slug>/mesa-<numero>
 * Identificativa y amigable. Si no se pasa zona, cae en "sin-zona" como
 * placeholder seguro (no deberia ocurrir en flujos normales).
 */
export function buildMesaUrl(mesa: Mesa, zonaNombre?: string): string {
    const zonaSlug = slugify(zonaNombre || 'sin-zona');
    const mesaSlug = `mesa-${slugify(mesa.numero)}`;
    const path = `/carta/${zonaSlug}/${mesaSlug}`;
    if (typeof window === 'undefined') return path;
    return `${window.location.protocol}//${window.location.host}${path}`;
}
