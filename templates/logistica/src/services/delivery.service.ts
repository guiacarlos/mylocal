import type { SynaxisClient } from '@mylocal/sdk';

export interface Pedido {
    id: string;
    local_id: string;
    cliente: string;
    telefono?: string;
    email?: string;
    direccion: string;
    items: Array<{ nombre: string; cantidad: number }>;
    estado: 'recibido' | 'preparando' | 'en_ruta' | 'entregado' | 'incidencia';
    codigo_seguimiento: string;
    notas?: string;
    created_at: string;
}

export interface Vehiculo {
    id: string;
    local_id: string;
    matricula: string;
    conductor: string;
    modelo?: string;
    estado: 'activo' | 'inactivo';
    created_at: string;
}

export interface Entrega {
    id: string;
    pedido_id: string;
    vehiculo_id: string;
    fecha: string;
    notas?: string;
    created_at: string;
}

export interface Incidencia {
    id: string;
    pedido_id: string;
    tipo: string;
    descripcion: string;
    resuelto: boolean;
    created_at: string;
}

export interface SeguimientoResult {
    encontrado: boolean;
    codigo?: string;
    estado?: string;
    cliente?: string;
    direccion?: string;
    notas?: string;
    created_at?: string;
}

async function call<T>(client: SynaxisClient, action: string, data: Record<string, unknown> = {}): Promise<T> {
    const res = await client.execute<T>({ action, data });
    if (!res.success) throw new Error(res.error ?? `Error en ${action}`);
    return res.data as T;
}

export async function listPedidos(client: SynaxisClient, localId: string, estado?: string): Promise<Pedido[]> {
    return call<Pedido[]>(client, 'pedido_list', { local_id: localId, ...(estado ? { estado } : {}) });
}

export async function createPedido(client: SynaxisClient, localId: string, data: Partial<Pedido>): Promise<Pedido> {
    return call<Pedido>(client, 'pedido_create', { local_id: localId, ...data });
}

export async function getPedido(client: SynaxisClient, id: string): Promise<Pedido> {
    return call<Pedido>(client, 'pedido_get', { id });
}

export async function cambiarEstadoPedido(client: SynaxisClient, id: string, estado: string): Promise<Pedido> {
    return call<Pedido>(client, 'pedido_estado', { id, estado });
}

export async function listVehiculos(client: SynaxisClient, localId: string): Promise<Vehiculo[]> {
    return call<Vehiculo[]>(client, 'vehiculo_list', { local_id: localId });
}

export async function createVehiculo(client: SynaxisClient, localId: string, data: Partial<Vehiculo>): Promise<Vehiculo> {
    return call<Vehiculo>(client, 'vehiculo_create', { local_id: localId, ...data });
}

export async function updateVehiculo(client: SynaxisClient, id: string, data: Partial<Vehiculo>): Promise<Vehiculo> {
    return call<Vehiculo>(client, 'vehiculo_update', { id, ...data });
}

export async function asignarEntrega(client: SynaxisClient, pedidoId: string, vehiculoId: string, fecha: string): Promise<Entrega> {
    return call<Entrega>(client, 'entrega_asignar', { pedido_id: pedidoId, vehiculo_id: vehiculoId, fecha });
}

export async function listEntregasDia(client: SynaxisClient, localId: string, fecha: string): Promise<Entrega[]> {
    return call<Entrega[]>(client, 'entrega_list_dia', { local_id: localId, fecha });
}

export async function addIncidencia(client: SynaxisClient, pedidoId: string, tipo: string, descripcion: string): Promise<Incidencia> {
    return call<Incidencia>(client, 'incidencia_add', { pedido_id: pedidoId, tipo, descripcion });
}

export async function pedidoSeguimiento(client: SynaxisClient, codigo: string): Promise<SeguimientoResult> {
    return call<SeguimientoResult>(client, 'pedido_seguimiento', { codigo });
}
