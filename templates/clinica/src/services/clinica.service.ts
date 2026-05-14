import type { SynaxisClient } from '@mylocal/sdk';

export interface Cita {
    id: string;
    local_id: string;
    recurso_id: string;
    cliente: string;
    telefono?: string;
    inicio: string;
    fin: string;
    estado: 'pendiente' | 'confirmada' | 'cancelada' | 'completada';
    notas?: string;
    created_at: string;
}

export interface Paciente {
    id: string;
    local_id: string;
    nombre: string;
    email?: string;
    telefono?: string;
    etiquetas?: string[];
    notas?: string;
    fuente?: string;
    created_at: string;
    duplicate_of?: string;
}

export interface Interaccion {
    id: string;
    contacto_id: string;
    tipo: 'llamada' | 'email' | 'whatsapp' | 'nota' | 'visita';
    nota: string;
    autor_id: string;
    ts: string;
}

export interface NotifLog {
    id: string;
    driver: string;
    destinatario: string;
    asunto: string;
    enviado: boolean;
    ts: string;
    meta?: Record<string, string>;
}

async function call<T>(client: SynaxisClient, action: string, data: Record<string, unknown> = {}): Promise<T> {
    const res = await client.execute<T>({ action, data });
    if (!res.success) throw new Error(res.error ?? `Error en ${action}`);
    return res.data as T;
}

export async function listCitas(client: SynaxisClient, localId: string, desde?: string, hasta?: string): Promise<Cita[]> {
    return call<Cita[]>(client, 'cita_list', { local_id: localId, desde, hasta });
}

export async function createCita(client: SynaxisClient, data: Partial<Cita> & { local_id: string }): Promise<Cita> {
    return call<Cita>(client, 'cita_create', data as Record<string, unknown>);
}

export async function cancelCita(client: SynaxisClient, id: string): Promise<Cita> {
    return call<Cita>(client, 'cita_cancel', { id });
}

export async function listPacientes(client: SynaxisClient, localId: string): Promise<Paciente[]> {
    const res = await call<{ items: Paciente[] }>(client, 'crm_contacto_list', { local_id: localId });
    return res.items ?? (res as unknown as Paciente[]);
}

export async function createPaciente(client: SynaxisClient, localId: string, data: Partial<Paciente>): Promise<Paciente> {
    return call<Paciente>(client, 'crm_contacto_create', { local_id: localId, ...data });
}

export async function getPaciente(client: SynaxisClient, id: string): Promise<Paciente> {
    return call<Paciente>(client, 'crm_contacto_get', { id });
}

export async function listInteracciones(client: SynaxisClient, contactoId: string): Promise<Interaccion[]> {
    const res = await call<{ items: Interaccion[] }>(client, 'crm_interaccion_list', { contacto_id: contactoId });
    return res.items ?? (res as unknown as Interaccion[]);
}

export async function addInteraccion(client: SynaxisClient, contactoId: string, tipo: string, nota: string): Promise<Interaccion> {
    return call<Interaccion>(client, 'crm_interaccion_add', { contacto_id: contactoId, tipo, nota });
}

export async function listNotifLog(client: SynaxisClient, localId: string): Promise<NotifLog[]> {
    const res = await call<{ items: NotifLog[] }>(client, 'notif_list', { local_id: localId });
    return res.items ?? (res as unknown as NotifLog[]);
}
