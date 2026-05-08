/**
 * local.service.ts - configuracion del establecimiento.
 *
 * Wrappers tipados sobre las acciones get_local / update_local.
 */

import type { SynaxisClient } from '../synaxis/SynaxisClient';

export type WebTemplate = 'moderna' | 'minimal' | 'premium';
export type WebColor = 'claro' | 'oscuro' | 'blanco_roto';

export interface LocalInfo {
    id: string;
    nombre: string;
    telefono: string;
    direccion?: string;
    email?: string;
    web?: string;
    instagram?: string;
    facebook?: string;
    tiktok?: string;
    whatsapp?: string;
    tagline?: string;
    imagen_hero?: string;
    web_template?: WebTemplate;
    web_color?: WebColor;
    copyright?: string;
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

/**
 * Sube una imagen del local (logo/hero) a /MEDIA/local/<id>/. Persiste la
 * URL relativa en local.imagen_hero y la devuelve para que el cliente
 * actualice el state inmediatamente.
 *
 * Usa multipart fuera de SynaxisClient (igual que uploadCartaSource) porque
 * fetch nativo es necesario para FormData con File.
 */
export async function uploadLocalImage(file: File, localId: string): Promise<{ url: string; local_id: string }> {
    let token = '';
    try { token = sessionStorage.getItem('mylocal_token') ?? ''; } catch (_) { /* incognito */ }
    if (!token) throw new Error('No hay sesion activa.');

    const form = new FormData();
    form.append('action', 'upload_local_image');
    form.append('local_id', localId);
    form.append('file', file);

    const res = await fetch('/acide/index.php', {
        method: 'POST',
        credentials: 'omit',
        headers: { 'Authorization': `Bearer ${token}` },
        body: form,
    });
    const text = await res.text();
    let json: { success: boolean; data?: { url: string; local_id: string }; error?: string };
    try { json = JSON.parse(text); }
    catch { throw new Error(`Respuesta no JSON del servidor (HTTP ${res.status})`); }
    if (!json.success || !json.data) throw new Error(json.error ?? 'Error subiendo imagen');
    return json.data;
}
