import type { SynaxisClient } from '@mylocal/sdk';

// ── Tipos ─────────────────────────────────────────────────────────────────

export interface Cliente {
    id: string;
    local_id: string;
    nombre: string;
    email?: string;
    telefono?: string;
    nif?: string;
    regimen?: string;
    notas?: string;
    created_at: string;
}

export interface Tarea {
    id: string;
    local_id: string;
    titulo: string;
    descripcion?: string;
    estado: 'pendiente' | 'en_curso' | 'hecho';
    prioridad: 'alta' | 'media' | 'baja';
    cliente_id?: string;
    fecha_vencimiento?: string;
    created_at: string;
}

export interface Vencimiento {
    id: string;
    local_id: string;
    recurso_id: string;
    cliente: string;
    inicio: string;
    fin: string;
    estado: 'pendiente' | 'confirmada' | 'cancelada' | 'completada';
    notas?: string;
    created_at: string;
}

export interface OcrResult {
    texto?: string;
    nif?: string;
    total?: string;
    fecha?: string;
    proveedor?: string;
}

// ── Helper ────────────────────────────────────────────────────────────────

async function call<T>(client: SynaxisClient, action: string, data: Record<string, unknown> = {}): Promise<T> {
    const res = await client.execute<T>({ action, data });
    if (!res.success) throw new Error(res.error ?? `Error en ${action}`);
    return res.data as T;
}

// ── Clientes (CRM) ────────────────────────────────────────────────────────

export async function listClientes(client: SynaxisClient, localId: string): Promise<Cliente[]> {
    const res = await call<{ items: Cliente[] }>(client, 'crm_contacto_list', { local_id: localId });
    return (res as unknown as Cliente[]).length !== undefined ? (res as unknown as Cliente[]) : (res.items ?? []);
}

export async function createCliente(client: SynaxisClient, localId: string, data: Partial<Cliente>): Promise<Cliente> {
    return call<Cliente>(client, 'crm_contacto_create', { local_id: localId, ...data });
}

// ── Tareas ────────────────────────────────────────────────────────────────

export async function listTareas(client: SynaxisClient, localId: string): Promise<Tarea[]> {
    return call<Tarea[]>(client, 'tarea_list', { local_id: localId });
}

export async function createTarea(client: SynaxisClient, localId: string, data: Partial<Tarea>): Promise<Tarea> {
    return call<Tarea>(client, 'tarea_create', { local_id: localId, ...data });
}

export async function moverTarea(client: SynaxisClient, id: string, estado: Tarea['estado']): Promise<Tarea> {
    return call<Tarea>(client, 'tarea_update', { id, estado });
}

export async function deleteTarea(client: SynaxisClient, id: string): Promise<void> {
    await call(client, 'tarea_delete', { id });
}

// ── Vencimientos fiscales (CITAS con recurso r_fiscal) ───────────────────

export const RECURSO_FISCAL = 'r_fiscal';

export async function listVencimientos(client: SynaxisClient, localId: string): Promise<Vencimiento[]> {
    return call<Vencimiento[]>(client, 'cita_list', { local_id: localId });
}

export async function createVencimiento(client: SynaxisClient, localId: string, data: {
    cliente: string; inicio: string; fin: string; notas?: string;
}): Promise<Vencimiento> {
    return call<Vencimiento>(client, 'cita_create', {
        local_id: localId,
        recurso_id: RECURSO_FISCAL,
        ...data,
    });
}

export async function cancelarVencimiento(client: SynaxisClient, id: string): Promise<Vencimiento> {
    return call<Vencimiento>(client, 'cita_cancel', { id });
}
