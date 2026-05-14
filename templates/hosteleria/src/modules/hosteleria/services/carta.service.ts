/**
 * carta.service — carta hostelera + motores IA invisibles.
 *
 * CRUD (categorías, productos): acciones genéricas de Synaxis con
 * collections 'carta_categorias' y 'carta_productos'.
 *
 * Acciones IA (scope: server): OCR, enhance, alérgenos, descripciones,
 * promociones, traducción, importación en lote, generación de PDF.
 *
 * Las funciones IA leen la API key de Gemini del servidor; sin ella
 * devuelven error explícito, nunca datos inventados.
 */

import type { SynaxisClient } from '../../../synaxis';

// ── Tipos ─────────────────────────────────────────────────────────────────

export interface Product {
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

export interface CartaCategoria {
    id: string;
    local_id: string;
    nombre: string;
    orden: number;
    disponible: boolean;
}

export interface CartaProducto {
    id: string;
    local_id: string;
    categoria_id: string;
    nombre: string;
    descripcion: string;
    precio: number;
    alergenos: string[];
    disponible: boolean;
    imagen_url?: string;
    es_especialidad?: boolean;
    texto_promocional?: string;
    origen_import?: string;
}

export interface CartaStructured {
    categorias: Array<{
        nombre: string;
        productos: Array<{ nombre: string; descripcion: string; precio: number }>;
    }>;
}

// ── Productos legacy (carta pública) ─────────────────────────────────────

export async function listPublishedProducts(client: SynaxisClient): Promise<Product[]> {
    const res = await client.execute<Product[]>({ action: 'list_products', collection: 'products' });
    if (!res.success || !res.data) return [];
    return (res.data as Product[]).filter((p) => p.status === 'publish');
}

export async function queryProducts(client: SynaxisClient, category?: string): Promise<Product[]> {
    const where = [['status', '=', 'publish']] as [string, string, unknown][];
    if (category) where.push(['category', '=', category]);
    const res = await client.execute<{ items: Product[] }>({
        action: 'query',
        collection: 'products',
        params: { where: where as never, orderBy: { field: 'price', direction: 'asc' } },
    });
    return res.success && res.data ? res.data.items : [];
}

// ── CRUD server-side (AxiDB persistente, no IndexedDB) ───────────────────
// Toda la carta vive en spa/server/data/{cartas, carta_categorias, carta_productos}/<id>.json
// El cliente NUNCA persiste localmente — fuente unica de verdad en server.

async function callServer<T>(client: SynaxisClient, action: string, data: Record<string, unknown>): Promise<T> {
    const res = await client.execute({ action, data });
    if (!res.success) throw new Error(res.error ?? `Error en ${action}`);
    return res.data as T;
}

// Cartas
export interface CartaDoc {
    id: string;
    local_id: string;
    nombre: string;
    tipo: string;
    tema: string;
    activa: boolean;
    categorias_orden: string[];
}

export async function listCartas(client: SynaxisClient, local_id: string): Promise<CartaDoc[]> {
    const res = await callServer<{ items: CartaDoc[] }>(client, 'list_cartas', { local_id });
    return res.items ?? [];
}

export async function createCarta(client: SynaxisClient, data: Partial<CartaDoc>): Promise<CartaDoc> {
    return callServer<CartaDoc>(client, 'create_carta', data as Record<string, unknown>);
}

// Categorias
export async function listCategorias(client: SynaxisClient, local_id: string, carta_id?: string): Promise<CartaCategoria[]> {
    const res = await callServer<{ items: CartaCategoria[] }>(client, 'list_categorias', {
        local_id,
        ...(carta_id ? { carta_id } : {}),
    });
    return res.items ?? [];
}

export async function createCategoria(client: SynaxisClient, data: Partial<CartaCategoria> & { carta_id: string; local_id: string }) {
    return callServer<CartaCategoria>(client, 'create_categoria', data as Record<string, unknown>);
}

export async function updateCategoria(client: SynaxisClient, id: string, data: Partial<CartaCategoria>) {
    return callServer<CartaCategoria>(client, 'update_categoria', { id, ...data });
}

export async function deleteCategoria(client: SynaxisClient, id: string) {
    return callServer<{ ok: boolean }>(client, 'delete_categoria', { id });
}

// Productos
export async function listProductos(client: SynaxisClient, local_id: string, carta_id?: string, categoria_id?: string): Promise<CartaProducto[]> {
    const res = await callServer<{ items: CartaProducto[] }>(client, 'list_productos', {
        local_id,
        ...(carta_id ? { carta_id } : {}),
        ...(categoria_id ? { categoria_id } : {}),
    });
    return res.items ?? [];
}

export async function createProducto(client: SynaxisClient, data: Partial<CartaProducto> & { carta_id: string; categoria_id: string; local_id: string; nombre: string }) {
    return callServer<CartaProducto>(client, 'create_producto', data as Record<string, unknown>);
}

export async function updateProducto(client: SynaxisClient, id: string, data: Partial<CartaProducto>) {
    return callServer<CartaProducto>(client, 'update_producto', { id, ...data });
}

export async function deleteProducto(client: SynaxisClient, id: string) {
    return callServer<{ ok: boolean }>(client, 'delete_producto', { id });
}

// ── Bootstrap del local (idempotente: crea l_default + carta principal) ──

export interface BootstrapLocalResp {
    local: { id: string; nombre: string; default_carta_id: string };
    created: boolean;
}

export async function bootstrapLocal(client: SynaxisClient): Promise<BootstrapLocalResp> {
    return callServer<BootstrapLocalResp>(client, 'bootstrap_local', {});
}

// ── Import atomico de carta tras OCR ─────────────────────────────────────
// Persiste TODO en server (spa/server/data/cartas, carta_categorias, carta_productos)
// con la jerarquia carta_id → categoria_id → producto.

export interface ImportCartaResp {
    carta_id: string;
    local_id: string;
    categorias: number;
    productos: number;
}

export async function importCartaStructured(
    client: SynaxisClient,
    data: { local_id: string; carta_id?: string; carta_nombre?: string; categorias: CartaStructured['categorias'] }
): Promise<ImportCartaResp> {
    return callServer<ImportCartaResp>(client, 'importar_carta_estructurada', data as Record<string, unknown>);
}

// ── IA — Upload para OCR (multipart, fuera de SynaxisClient) ─────────────

export async function uploadCartaSource(file: File): Promise<{ file_path: string; filename: string; ext: string }> {
    // Bearer token desde sessionStorage. Sin cookies (AUTH_LOCK).
    let token = '';
    try { token = sessionStorage.getItem('mylocal_token') ?? ''; } catch (_) { /* incognito */ }
    if (!token) throw new Error('No hay sesion activa. Inicia sesion primero.');

    const form = new FormData();
    form.append('action', 'upload_carta_source');
    form.append('file', file);
    const res = await fetch('/acide/index.php', {
        method: 'POST',
        credentials: 'omit',
        headers: { 'Authorization': `Bearer ${token}` },
        body: form,
    });

    // Lee el body como texto y parsea: el backend devuelve HTTP 200 incluso
    // en errores de negocio (AUTH_LOCK), asi que res.ok no basta.
    const text = await res.text();
    let json: { success: boolean; data?: { file_path: string; filename: string; ext: string }; error?: string };
    try { json = JSON.parse(text); }
    catch { throw new Error(`Respuesta no JSON del servidor (HTTP ${res.status})`); }
    if (!json.success || !json.data) throw new Error(json.error ?? 'Error subiendo archivo');
    return json.data;
}

// ── IA — OCR all-in-one ───────────────────────────────────────────────────

export async function importCartaFromFile(file: File): Promise<CartaStructured> {
    let token = '';
    try { token = sessionStorage.getItem('mylocal_token') ?? ''; } catch (_) { /* incognito */ }
    if (!token) throw new Error('No hay sesion activa. Inicia sesion primero.');

    const form = new FormData();
    form.append('action', 'ocr_import_carta');
    form.append('file', file);
    const res = await fetch('/acide/index.php', {
        method: 'POST',
        credentials: 'omit',
        headers: { 'Authorization': `Bearer ${token}` },
        body: form,
    });
    const text = await res.text();
    let json: { success: boolean; data?: CartaStructured; error?: string };
    try { json = JSON.parse(text); }
    catch { throw new Error(`Respuesta no JSON del servidor (HTTP ${res.status})`); }
    if (!json.success || !json.data) throw new Error(json.error ?? 'Error procesando carta');
    return json.data as CartaStructured;
}

// ── IA — OCR pasos individuales (uso interno / diagnóstico) ───────────────

export async function ocrExtract(client: SynaxisClient, file_path: string) {
    return client.execute({ action: 'ocr_extract', data: { file_path } });
}

export async function ocrParse(client: SynaxisClient, raw_text: string): Promise<CartaStructured | null> {
    const res = await client.execute<CartaStructured>({ action: 'ocr_parse', data: { raw_text } });
    return res.success ? (res.data as CartaStructured) : null;
}

// ── IA — Enhancer de imagen ───────────────────────────────────────────────

export async function enhanceImage(client: SynaxisClient, file_path: string): Promise<string | null> {
    const res = await client.execute<{ url: string }>({ action: 'enhance_image_sync', data: { file_path } });
    return res.success ? (res.data as { url: string }).url : null;
}

// ── IA — Menu Engineer ────────────────────────────────────────────────────

export async function sugerirAlergenos(client: SynaxisClient, nombre: string, ingredientes: string[]) {
    return client.execute({ action: 'ai_sugerir_alergenos', data: { nombre, ingredientes } });
}

export async function generarDescripcion(client: SynaxisClient, nombre: string, ingredientes: string[]): Promise<string | null> {
    const res = await client.execute<{ descripcion: string }>({ action: 'ai_generar_descripcion', data: { nombre, ingredientes } });
    return res.success ? (res.data as { descripcion: string }).descripcion : null;
}

export async function generarPromocion(client: SynaxisClient, nombre: string, descripcion: string): Promise<string | null> {
    const res = await client.execute<{ promocion: string }>({ action: 'ai_generar_promocion', data: { nombre, descripcion } });
    return res.success ? (res.data as { promocion: string }).promocion : null;
}

export async function traducirTexto(client: SynaxisClient, texto: string, idioma: string): Promise<string | null> {
    const res = await client.execute<{ texto: string }>({ action: 'ai_traducir', data: { texto, idioma } });
    return res.success ? (res.data as { texto: string }).texto : null;
}

// ── Importar carta en lote ────────────────────────────────────────────────

export async function importarCarta(client: SynaxisClient, local_id: string, categorias: CartaStructured['categorias']) {
    return client.execute({ action: 'importar_carta_estructurada', data: { local_id, categorias } });
}

// ── Generar PDF ───────────────────────────────────────────────────────────

export interface CartaPdfOpts {
    plantilla: 'minimalista' | 'clasica' | 'moderna';
    local?: { nombre: string; [key: string]: unknown };
    categorias: Array<{ nombre: string; productos: CartaProducto[] }>;
}

export async function generarPdfCarta(client: SynaxisClient, opts: CartaPdfOpts): Promise<void> {
    const res = await client.execute<{ pdf_base64: string }>({
        action: 'generate_pdf_carta',
        data: opts,
    });
    if (!res.success || !res.data) throw new Error(res.error ?? 'Error generando PDF');
    const bytes = Uint8Array.from(atob((res.data as { pdf_base64: string }).pdf_base64), c => c.charCodeAt(0));
    const blob = new Blob([bytes], { type: 'application/pdf' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `carta_${opts.plantilla}.pdf`;
    a.click();
    URL.revokeObjectURL(url);
}
