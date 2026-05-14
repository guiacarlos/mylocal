import type { SynaxisDoc } from './synaxis';

export interface AppUser extends SynaxisDoc {
    id: string;
    email: string;
    name?: string;
    role: 'superadmin' | 'administrador' | 'admin' | 'editor' | 'maestro' | 'sala' | 'cocina' | 'camarero' | 'estudiante' | 'cliente' | string;
    tenantId?: string;
}

export interface Session extends SynaxisDoc {
    id: string;
    userId: string;
    expiresAt: string;
}

export interface LocalInfo extends SynaxisDoc {
    id: string;
    nombre?: string;
    slug?: string;
    logo?: string;
    color_acento?: string;
    descripcion?: string;
    direccion?: string;
    telefono?: string;
    email?: string;
    web?: string;
    created_at?: string;
}
