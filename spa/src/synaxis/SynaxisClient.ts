/**
 * SynaxisClient — fachada única para la SPA.
 *
 * Resuelve la acción eligiendo transporte:
 *   - scope 'local'  → siempre SynaxisCore (sin red).
 *   - scope 'server' → siempre HTTP contra `/acide/index.php`.
 *   - scope 'hybrid' → primero local; si no encuentra datos (`null` o
 *     lista vacía), cae al HTTP y cachea el resultado en IndexedDB.
 *
 * Diseñado para que el **resto de la SPA** llame solo a este cliente. Las
 * services/pages jamás hablan directamente con SynaxisCore o con fetch.
 */

import { SynaxisCore } from './SynaxisCore';
import { getActionScope } from './actions';
import type { SynaxisDoc, SynaxisRequest, SynaxisResponse } from './types';

export interface SynaxisClientOptions {
    namespace?: string;
    project?: string | null;
    apiUrl?: string;
    /**
     * Token CSRF para el patrón double-submit. El server setea la cookie
     * `socola_csrf`; el cliente la lee y la inyecta en el header
     * `X-CSRF-Token` de cada POST state-changing.
     */
    csrfToken?: string | null;
    /** Map opcional para forzar un scope concreto en acciones específicas. */
    overrides?: Record<string, 'local' | 'server' | 'hybrid'>;
}

export class SynaxisClient {
    readonly core: SynaxisCore;
    apiUrl: string;
    private csrfToken: string | null;
    private overrides: Map<string, 'local' | 'server' | 'hybrid'>;

    constructor(opts: SynaxisClientOptions = {}) {
        this.core = new SynaxisCore({
            namespace: opts.namespace ?? 'socola',
            project: opts.project ?? null,
        });
        this.apiUrl = opts.apiUrl ?? '/acide/index.php';
        this.csrfToken = opts.csrfToken ?? null;
        this.overrides = new Map(Object.entries(opts.overrides ?? {}));
    }

    setCsrfToken(token: string | null): void {
        this.csrfToken = token;
    }

    private scopeFor(action: string) {
        return this.overrides.get(action) ?? getActionScope(action);
    }

    async execute<T = unknown>(req: SynaxisRequest): Promise<SynaxisResponse<T>> {
        const scope = this.scopeFor(req.action);

        if (scope === 'local') return this.core.execute<T>(req);
        if (scope === 'server') return this.http<T>(req);

        // hybrid: intenta local, si hay "nada" va al server y cachea.
        const local = await this.core.execute<T>(req);
        if (this.isMeaningful(local)) return local;

        const remote = await this.http<T>(req);
        await this.cacheRemote(req, remote);
        return remote;
    }

    private isMeaningful<T>(res: SynaxisResponse<T>): boolean {
        if (!res.success) return false;
        const d = res.data as unknown;
        if (d === null || d === undefined) return false;
        if (Array.isArray(d) && d.length === 0) return false;
        if (typeof d === 'object' && d !== null) {
            // QueryResult vacío
            const maybe = d as { items?: unknown[] };
            if (Array.isArray(maybe.items) && maybe.items.length === 0) return false;
        }
        return true;
    }

    private async cacheRemote<T>(req: SynaxisRequest, res: SynaxisResponse<T>): Promise<void> {
        if (!res.success || !res.data || !req.collection) return;

        // Si es list → poblar colección. Si es read/get → put único.
        try {
            if (req.action === 'list' || req.action === 'list_products') {
                const arr = res.data as unknown as SynaxisDoc[];
                if (Array.isArray(arr)) {
                    for (const doc of arr) {
                        if (doc?.id) {
                            await this.core.update(req.collection, doc.id, doc as Partial<SynaxisDoc>);
                        }
                    }
                }
            } else if (req.action === 'read' || req.action === 'get') {
                const doc = res.data as unknown as SynaxisDoc;
                if (doc?.id) await this.core.update(req.collection, doc.id, doc as Partial<SynaxisDoc>);
            }
        } catch {
            // Cachear es best-effort; nunca rompe la respuesta al caller.
        }
    }

    private async http<T>(req: SynaxisRequest): Promise<SynaxisResponse<T>> {
        const headers: Record<string, string> = { 'Content-Type': 'application/json' };
        if (this.csrfToken) headers['X-CSRF-Token'] = this.csrfToken;

        try {
            const res = await fetch(this.apiUrl, {
                method: 'POST',
                headers,
                body: JSON.stringify(req),
                credentials: 'include',  // la cookie socola_session (httponly) viaja aquí
            });
            if (res.status === 419) {
                // CSRF expirado: la SPA debe re-obtener token y reintentar.
                this.csrfToken = null;
            }
            const json = (await res.json()) as SynaxisResponse<T>;
            return json;
        } catch (e) {
            return {
                success: false,
                data: null,
                error: e instanceof Error ? e.message : String(e),
            };
        }
    }

    /**
     * Carga un snapshot (típicamente `/seed/<file>.json`) y lo importa.
     * Solo ejecuta la importación si la colección está vacía, para no
     * pisar datos ya sincronizados.
     */
    async seedIfEmpty(url: string): Promise<{ imported: boolean; collections: string[] }> {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`Seed HTTP ${res.status}`);
        const snapshot = (await res.json()) as Record<string, SynaxisDoc[]>;

        let anyEmpty = false;
        for (const col of Object.keys(snapshot)) {
            const existing = await this.core.list(col);
            if (existing.length === 0) {
                anyEmpty = true;
                break;
            }
        }
        if (!anyEmpty) return { imported: false, collections: [] };

        await this.core.importSnapshot(snapshot);
        return { imported: true, collections: Object.keys(snapshot) };
    }
}
