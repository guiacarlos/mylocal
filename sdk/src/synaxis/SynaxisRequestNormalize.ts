/**
 * Normalizacion del SynaxisRequest.
 *
 * El cliente puede enviar la request en multiples formas:
 *   { action, collection, id, data, params }                   ← forma canonica
 *   { action, data: { collection, id, data: {...}, params } }  ← anidado
 *   { action, ...campos sueltos }                              ← legacy
 *
 * Aqui devolvemos siempre la misma forma plana para que execute() no
 * tenga que distinguir entre variantes.
 */

import type { QueryParams, SynaxisRequest } from './types';

export interface NormalizedRequest {
    action: string;
    collection: string | null;
    id: string | null;
    params: QueryParams;
    payload: Record<string, unknown>;
}

export function normalizeRequest(req: SynaxisRequest): NormalizedRequest {
    const action = req.action;
    const inner = (req.data && typeof req.data === 'object'
        ? (req.data as Record<string, unknown>)
        : {}) as Record<string, unknown>;

    const collection = (req.collection as string) ?? (inner.collection as string) ?? null;
    const id         = (req.id as string)         ?? (inner.id as string)         ?? null;
    const params     = (req.params as QueryParams) ?? (inner.params as QueryParams) ?? {};

    let payload: Record<string, unknown>;
    if (inner && typeof inner.data === 'object' && inner.data !== null) {
        payload = { ...(inner.data as Record<string, unknown>) };
    } else if (req.data && typeof req.data === 'object') {
        payload = { ...(req.data as Record<string, unknown>) };
        delete payload.collection;
        delete payload.id;
        delete payload.params;
    } else {
        payload = { ...(req as Record<string, unknown>) };
        delete payload.action;
        delete payload.collection;
        delete payload.id;
        delete payload.params;
    }
    return { action, collection, id, params, payload };
}
