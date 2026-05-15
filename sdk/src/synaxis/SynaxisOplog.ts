/**
 * SynaxisOplog — registro append-only de operaciones para sync futuro.
 *
 * Cada write (put/delete) que pasa por SynaxisCore queda registrado aqui.
 * El consumidor de Fase 3 (sync) leera con drainOplog y confirmara con
 * clearOplog cuando el servidor le devuelva acuse de recibo.
 *
 * Errores en appendOp son non-blocking: si el oplog falla, el write
 * principal NO debe caerse. Es un log eventualmente consistente.
 */

import type { SynaxisStorage } from './SynaxisStorage';
import { genId, nowIso, OPLOG_COLLECTION, type OpLogEntry, type OpType } from './SynaxisCoreHelpers';

export async function appendOp(
    storage: SynaxisStorage,
    op: OpType,
    collection: string,
    targetId: string,
    payload: unknown,
): Promise<void> {
    try {
        const entry: OpLogEntry = {
            id: genId('op'),
            op,
            collection,
            targetId,
            version: 1,
            ts: nowIso(),
            payload: payload as unknown as never,
        };
        await storage.put(OPLOG_COLLECTION, entry);
    } catch {
        /* non-blocking: el sync se reconcilia en la siguiente pasada */
    }
}

export async function drainOplog(
    storage: SynaxisStorage,
    limit = 100,
): Promise<OpLogEntry[]> {
    const all = await storage.all<OpLogEntry>(OPLOG_COLLECTION);
    all.sort((a, b) => (a.ts < b.ts ? -1 : 1));
    return all.slice(0, limit);
}

export async function clearOplog(
    storage: SynaxisStorage,
    ids: string[],
): Promise<void> {
    for (const id of ids) await storage.remove(OPLOG_COLLECTION, id);
}
