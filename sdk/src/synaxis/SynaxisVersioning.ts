/**
 * SynaxisVersioning — snapshot de versiones de documento.
 *
 * Cada update de SynaxisCore guarda un snapshot del doc PREVIO en la
 * sub-coleccion `<collection>__versions` con clave `<id>@<version>`.
 * Conserva como maximo MAX_VERSIONS snapshots por id (best-effort:
 * los errores no burbujean al caller).
 *
 * Funciones puras: reciben el storage como argumento, no tienen estado.
 */

import type { SynaxisStorage } from './SynaxisStorage';
import type { SynaxisDoc } from './types';
import { MAX_VERSIONS } from './SynaxisCoreHelpers';

/** Guarda doc actual en la sub-coleccion versions y borra los excedentes. */
export async function snapshotVersion(
    storage: SynaxisStorage,
    collection: string,
    doc: SynaxisDoc,
): Promise<void> {
    if (!doc?.id) return;
    const vCol = `${collection}__versions`;
    const snap = { ...doc, id: `${doc.id}@${doc._version ?? 0}` };
    try {
        await storage.put(vCol, snap as SynaxisDoc);
        const all = await storage.all<SynaxisDoc>(vCol);
        const mine = all
            .filter((v) => String(v.id).startsWith(`${doc.id}@`))
            .sort((a, b) => (String(a._updatedAt) < String(b._updatedAt) ? -1 : 1));
        const excess = mine.length - MAX_VERSIONS;
        for (let i = 0; i < excess; i++) {
            await storage.remove(vCol, mine[i].id);
        }
    } catch {
        /* best-effort: una version no guardada no debe romper el write principal */
    }
}

/** Lista los snapshots conservados para un documento concreto. */
export async function listVersions(
    storage: SynaxisStorage,
    collection: string,
    id: string,
): Promise<SynaxisDoc[]> {
    const vCol = `${collection}__versions`;
    const all = await storage.all<SynaxisDoc>(vCol);
    return all
        .filter((v) => String(v.id).startsWith(`${id}@`))
        .sort((a, b) => (Number(a._version) > Number(b._version) ? 1 : -1));
}
