/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL AUTH LOCK - load-bearing                                 ║
   ║ Cliente HTTP. Bearer en header Authorization, credentials:'omit'.║
   ╚══════════════════════════════════════════════════════════════════╝ */

import { SynaxisCore } from './SynaxisCore';
import { getActionScope } from './actions';
import type { SynaxisDoc, SynaxisRequest, SynaxisResponse } from './types';

export interface SynaxisClientOptions {
    namespace?: string;
    project?: string | null;
    apiUrl?: string;
    token?: string | null;
    overrides?: Record<string, 'local' | 'server' | 'hybrid'>;
}

export class SynaxisClient {
    readonly core: SynaxisCore;
    apiUrl: string;
    private token: string | null;
    private overrides: Map<string, 'local' | 'server' | 'hybrid'>;
    private unauthorized = false;

    constructor(opts: SynaxisClientOptions = {}) {
        this.core = new SynaxisCore({
            namespace: opts.namespace ?? 'socola',
            project: opts.project ?? null,
        });
        this.apiUrl = opts.apiUrl ?? '/acide/index.php';
        this.token = opts.token ?? null;
        this.overrides = new Map(Object.entries(opts.overrides ?? {}));
    }

    setToken(token: string | null): void {
        this.token = token;
        this.unauthorized = false;
    }

    private scopeFor(action: string) {
        return this.overrides.get(action) ?? getActionScope(action);
    }

    async execute<T = unknown>(req: SynaxisRequest): Promise<SynaxisResponse<T>> {
        const scope = this.scopeFor(req.action);

        if (scope === 'local') return this.core.execute<T>(req);

        const isPublicAction = req.action.startsWith('public_') || req.action === 'csrf_token' || req.action === 'health_check';
        const isAuthAction = req.action.startsWith('auth_');

        if (this.unauthorized && !isPublicAction && !isAuthAction) {
            return { success: false, data: null, error: 'Unauthorized (silenced)', code: 401 };
        }

        if (scope === 'server') return this.http<T>(req);

        const SERVER_SEED_ACTIONS = new Set(['list_products']);
        const local = await this.core.execute<T>(req);
        if (local.success) {
            if (SERVER_SEED_ACTIONS.has(req.action) && this.localIsEmpty(local)) {
                if (this.unauthorized && !isPublicAction) return local;
                const remote = await this.http<T>(req);
                if (remote.success) {
                    await this.cacheRemote(req, remote);
                    return remote;
                }
                return local;
            }
            return local;
        }

        if (this.unauthorized && !isPublicAction) return local;
        const remote = await this.http<T>(req);
        await this.cacheRemote(req, remote);
        return remote;
    }

    private localIsEmpty<T>(res: SynaxisResponse<T>): boolean {
        const d = res.data as any;
        if (!d) return true;
        if (Array.isArray(d) && d.length === 0) return true;
        if (d.items && Array.isArray(d.items) && d.items.length === 0) return true;
        return false;
    }

    private async cacheRemote<T>(req: SynaxisRequest, res: SynaxisResponse<T>): Promise<void> {
        if (!res.success || !res.data || !req.collection) return;
        try {
            if (req.action === 'list' || req.action === 'list_products') {
                const arr = res.data as unknown as SynaxisDoc[];
                if (Array.isArray(arr)) {
                    for (const doc of arr) {
                        if (doc?.id) await this.core.update(req.collection, doc.id, doc as Partial<SynaxisDoc>);
                    }
                }
            } else if (req.action === 'read' || req.action === 'get') {
                const doc = res.data as unknown as SynaxisDoc;
                if (doc?.id) await this.core.update(req.collection, doc.id, doc as Partial<SynaxisDoc>);
            }
        } catch { /* ignorar errores de cache */ }
    }

    private async http<T>(req: SynaxisRequest): Promise<SynaxisResponse<T>> {
        if (this.unauthorized && !req.action.startsWith('auth_') && req.action !== 'csrf_token') {
            return { success: false, data: null, error: 'Unauthorized (silenced)', code: 401 };
        }

        const headers: Record<string, string> = { 'Content-Type': 'application/json' };
        if (!this.token) {
            try {
                const saved = sessionStorage.getItem('mylocal_token');
                if (saved) this.token = saved;
            } catch (_) { /* incognito */ }
        }
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        try {
            const res = await fetch(this.apiUrl, {
                method: 'POST',
                headers,
                body: JSON.stringify(req),
                credentials: 'omit',
            });

            if (res.status === 401) {
                this.unauthorized = true;
                return { success: false, data: null, error: 'Unauthorized', code: 401 };
            }
            if (res.status === 419) {
                this.token = null;
                return { success: false, data: null, error: 'CSRF Expired', code: 419 };
            }
            const text = await res.text();
            try {
                const json = JSON.parse(text) as SynaxisResponse<T>;
                if (typeof json === 'object' && json !== null && 'success' in json) {
                    if (json.success) this.unauthorized = false;
                    return json;
                }
            } catch (_) { /* respuesta no es JSON */ }

            if (!res.ok) {
                return { success: false, data: null, error: `HTTP ${res.status}: ${text.slice(0, 100)}`, code: res.status };
            }
            return { success: false, data: null, error: 'Respuesta no JSON', code: res.status };
        } catch (e) {
            return {
                success: false,
                data: null,
                error: e instanceof Error ? e.message : String(e),
                code: 500,
            };
        }
    }

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
