/**
 * SynaxisCoreHelpers — primitivas puras compartidas por SynaxisCore.
 *
 * Aqui viven constantes, generadores de id, formato de respuesta y tipos
 * auxiliares. Todo es "sin estado": ninguna funcion lee/escribe storage.
 * Asi SynaxisCore queda enfocado en la orquestacion.
 */

import type { SynaxisDoc, SynaxisResponse } from './types';

/** Colecciones que viven en la base "master" (compartida entre proyectos). */
export const MASTER_COLLECTIONS = new Set(['users', 'roles', 'projects', 'system_logs']);

/** Cuantos snapshots de version por documento conserva snapshotVersion. */
export const MAX_VERSIONS = 5;

/** Nombre de la coleccion donde se guarda el oplog (push pendiente para sync). */
export const OPLOG_COLLECTION = '__oplog__';

/** ISO 8601 del momento actual. */
export const nowIso = (): string => new Date().toISOString();

/** Genera ids cortos legibles con prefijo (`doc_1715000000000_abc123`). */
export const genId = (prefix = 'doc'): string =>
    `${prefix}_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;

/** Envoltura "exito" del contrato uniforme {success, data, error}. */
export function ok<T>(data: T): SynaxisResponse<T> {
    return { success: true, data, error: null };
}

/** Envoltura "fallo" — extrae el mensaje del Error o stringifica. */
export function fail(error: unknown): SynaxisResponse<null> {
    const msg = error instanceof Error ? error.message : String(error);
    return { success: false, data: null, error: msg };
}

/** Tipo de operacion registrada en el oplog. */
export type OpType = 'put' | 'delete';

/** Estructura de cada entrada del oplog. */
export interface OpLogEntry extends SynaxisDoc {
    op: OpType;
    collection: string;
    targetId: string;
    version: number;
    ts: string;
}
