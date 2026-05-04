/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL AUTH LOCK - load-bearing                                 ║
   ║ Cliente HTTP. Bearer en header Authorization, credentials:'omit'.║
   ║ Lee body JSON aunque res.ok=false (errores de negocio en HTTP 200).║
   ║ Antes de modificar, leer claude/AUTH_LOCK.md y verificar que     ║
   ║ spa/server/tests/test_login.php sigue pasando despues del cambio.║
   ╚══════════════════════════════════════════════════════════════════╝ */
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
    token?: string | null;
    /** Map opcional para forzar un scope concreto en acciones específicas. */
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

        // hybrid: prueba local. Si local exito (incluso con datos vacios) ESA
        // es la respuesta - el SPA usa IndexedDB como fuente de verdad para
        // CRUD (carta_categorias, carta_productos, etc). Solo cae al server
        // si local fallo (e.g. coleccion no existe).
        // Excepciones: list_products + acciones donde el server tiene seed
        // canonico, identificadas por la action concreta.
        const SERVER_SEED_ACTIONS = new Set(['list_products']);
        const local = await this.core.execute<T>(req);
        if (local.success) {
            // local respondio bien. Si es una accion semilla server y local
            // esta vacio, intenta poblar desde server. Si no, devuelve local.
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

        // local fallo. Cae al server como ultimo recurso.
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
            // Ignorar errores de cache
        }
    }

    private async http<T>(req: SynaxisRequest): Promise<SynaxisResponse<T>> {
        if (this.unauthorized && !req.action.startsWith('auth_') && req.action !== 'csrf_token') {
            return { success: false, data: null, error: 'Unauthorized (silenced)', code: 401 };
        }

        const headers: Record<string, string> = { 'Content-Type': 'application/json' };
        // Auth bearer-only: leemos el token de sessionStorage si no esta cacheado.
        // Sin cookies httponly. Sin CSRF. Sin riesgo de cross-site (sessionStorage
        // no es accesible para origenes ajenos).
        if (!this.token) {
            try {
                const saved = sessionStorage.getItem('mylocal_token');
                if (saved) this.token = saved;
            } catch (_) { /* incognito o storage bloqueado */ }
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
            // Si el body es JSON valido con envelope (success/error), lo usamos
            // aunque res.ok sea false. Asi mensajes de negocio del servidor
            // llegan al usuario sin envoltorios "HTTP 500: ..." opacos.
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
                code: 500
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


