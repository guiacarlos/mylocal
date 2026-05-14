import { SynaxisStorage } from './SynaxisStorage';
import { runQuery } from './SynaxisQuery';
import type { QueryParams, SynaxisDoc, SynaxisRequest, SynaxisResponse } from './types';

const MASTER_COLLECTIONS = new Set(['users', 'roles', 'projects', 'system_logs']);
const MAX_VERSIONS = 5;
const OPLOG_COLLECTION = '__oplog__';

export interface SynaxisCoreOptions {
    namespace?: string;
    project?: string | null;
    writeOplog?: boolean;
}

type OpType = 'put' | 'delete';

interface OpLogEntry extends SynaxisDoc {
    op: OpType;
    collection: string;
    targetId: string;
    version: number;
    ts: string;
}

const nowIso = (): string => new Date().toISOString();

export const genId = (prefix = 'doc'): string =>
    `${prefix}_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;

function ok<T>(data: T): SynaxisResponse<T> {
    return { success: true, data, error: null };
}
function fail(error: unknown): SynaxisResponse<null> {
    const msg = error instanceof Error ? error.message : String(error);
    return { success: false, data: null, error: msg };
}

export class SynaxisCore {
    readonly namespace: string;
    readonly project: string | null;
    private scoped: SynaxisStorage;
    private global: SynaxisStorage;
    readonly writeOplog: boolean;
    readonly ready: Promise<SynaxisCore>;

    constructor({ namespace = 'synaxis', project = null, writeOplog = true }: SynaxisCoreOptions = {}) {
        this.namespace = namespace;
        this.project = project;
        this.writeOplog = writeOplog;
        this.scoped = new SynaxisStorage({
            dbName: project ? `${namespace}__${project}` : `${namespace}__global`,
        });
        this.global = new SynaxisStorage({ dbName: `${namespace}__master` });
        this.ready = Promise.all([this.scoped.open(), this.global.open()]).then(() => this);
    }

    private storageFor(collection: string): SynaxisStorage {
        return MASTER_COLLECTIONS.has(collection) ? this.global : this.scoped;
    }

    private normalize(req: SynaxisRequest): {
        action: string;
        collection: string | null;
        id: string | null;
        params: QueryParams;
        payload: Record<string, unknown>;
    } {
        const action = req.action;
        const inner = (req.data && typeof req.data === 'object' ? (req.data as Record<string, unknown>) : {}) as Record<string, unknown>;
        const collection = (req.collection as string) ?? (inner.collection as string) ?? null;
        const id = (req.id as string) ?? (inner.id as string) ?? null;
        const params = (req.params as QueryParams) ?? (inner.params as QueryParams) ?? {};

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

    async execute<T = unknown>(request: SynaxisRequest): Promise<SynaxisResponse<T>> {
        await this.ready;
        if (!request || !request.action) {
            return fail('action is required') as SynaxisResponse<T>;
        }
        const { action, collection, id, params, payload } = this.normalize(request);

        try {
            switch (action) {
                case 'health_check':
                    return ok({ status: 'healthy', namespace: this.namespace, project: this.project, ts: nowIso() }) as SynaxisResponse<T>;
                case 'read':
                case 'get':
                    return ok(await this.read(collection!, id!)) as SynaxisResponse<T>;
                case 'list':
                    return ok(await this.list(collection!)) as SynaxisResponse<T>;
                case 'query':
                    return ok(await this.query(collection!, params)) as SynaxisResponse<T>;
                case 'create':
                case 'update':
                    return ok(await this.update(collection!, id, payload as Partial<SynaxisDoc>)) as SynaxisResponse<T>;
                case 'delete':
                    return ok(await this.delete(collection!, id!)) as SynaxisResponse<T>;
                case '_debug_reset':
                    await this.reset();
                    return ok(true) as SynaxisResponse<T>;
                default:
                    return fail(`Acción no reconocida por SynaxisCore: ${action}`) as SynaxisResponse<T>;
            }
        } catch (e) {
            return fail(e) as SynaxisResponse<T>;
        }
    }

    async read<T extends SynaxisDoc = SynaxisDoc>(collection: string, id: string): Promise<T | null> {
        if (!collection || !id) throw new Error('read requiere collection e id');
        return this.storageFor(collection).get<T>(collection, id);
    }

    async list<T extends SynaxisDoc = SynaxisDoc>(collection: string): Promise<T[]> {
        if (!collection) throw new Error('list requiere collection');
        return this.storageFor(collection).all<T>(collection);
    }

    async query<T extends SynaxisDoc = SynaxisDoc>(collection: string, params: QueryParams = {}) {
        if (!collection) throw new Error('query requiere collection');
        const items = await this.storageFor(collection).all<T>(collection);
        return runQuery<T>(items, params);
    }

    async update<T extends SynaxisDoc = SynaxisDoc>(
        collection: string,
        id: string | null,
        data: Partial<T> & { _REPLACE_?: boolean } = {} as Partial<T>,
    ): Promise<T> {
        if (!collection) throw new Error('update requiere collection');
        const storage = this.storageFor(collection);
        const effectiveId = id || (data.id as string) || genId(collection);
        const existing = await storage.get<T>(collection, effectiveId);
        const replace = Boolean(data._REPLACE_);
        const incoming = { ...data } as Record<string, unknown>;
        delete incoming._REPLACE_;

        const merged: Record<string, unknown> =
            existing && !replace ? { ...(existing as unknown as Record<string, unknown>), ...incoming } : { ...incoming };

        merged.id = effectiveId;
        merged._updatedAt = nowIso();
        merged._version = (existing && typeof existing._version === 'number' ? existing._version : 0) + 1;
        if (!merged._createdAt) merged._createdAt = existing?._createdAt ?? nowIso();

        if (existing) await this.snapshotVersion(collection, existing);
        const final = await storage.put(collection, merged as unknown as T);
        if (this.writeOplog) await this.appendOp('put', collection, effectiveId, final);
        return final;
    }

    async delete(collection: string, id: string): Promise<boolean> {
        if (!collection || !id) throw new Error('delete requiere collection e id');
        const res = await this.storageFor(collection).remove(collection, id);
        if (this.writeOplog && res) await this.appendOp('delete', collection, id, null);
        return res;
    }

    private async snapshotVersion(collection: string, doc: SynaxisDoc): Promise<void> {
        if (!doc?.id) return;
        const storage = this.storageFor(collection);
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
        } catch { /* best-effort */ }
    }

    async listVersions(collection: string, id: string): Promise<SynaxisDoc[]> {
        const storage = this.storageFor(collection);
        const vCol = `${collection}__versions`;
        const all = await storage.all<SynaxisDoc>(vCol);
        return all
            .filter((v) => String(v.id).startsWith(`${id}@`))
            .sort((a, b) => (Number(a._version) > Number(b._version) ? 1 : -1));
    }

    private async appendOp(op: OpType, collection: string, targetId: string, payload: unknown): Promise<void> {
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
            await this.scoped.put(OPLOG_COLLECTION, entry);
        } catch { /* non-blocking */ }
    }

    async drainOplog(limit = 100): Promise<OpLogEntry[]> {
        const all = await this.scoped.all<OpLogEntry>(OPLOG_COLLECTION);
        all.sort((a, b) => (a.ts < b.ts ? -1 : 1));
        return all.slice(0, limit);
    }

    async clearOplog(ids: string[]): Promise<void> {
        for (const id of ids) await this.scoped.remove(OPLOG_COLLECTION, id);
    }

    async reset(): Promise<void> {
        await this.scoped.dropDatabase();
        await this.global.dropDatabase();
        this.scoped = new SynaxisStorage({
            dbName: this.project ? `${this.namespace}__${this.project}` : `${this.namespace}__global`,
        });
        this.global = new SynaxisStorage({ dbName: `${this.namespace}__master` });
        await Promise.all([this.scoped.open(), this.global.open()]);
    }

    async importSnapshot(snapshot: Record<string, SynaxisDoc[]>): Promise<boolean> {
        for (const [collection, docs] of Object.entries(snapshot)) {
            if (!Array.isArray(docs)) continue;
            for (const doc of docs) {
                if (!doc?.id) continue;
                await this.storageFor(collection).put(collection, doc);
            }
        }
        return true;
    }

    async exportSnapshot(): Promise<Record<string, SynaxisDoc[]>> {
        const out: Record<string, SynaxisDoc[]> = {};
        for (const storage of [this.scoped, this.global]) {
            for (const col of await storage.listCollections()) {
                if (col.endsWith('__versions')) continue;
                if (col === OPLOG_COLLECTION) continue;
                out[col] = await storage.all<SynaxisDoc>(col);
            }
        }
        return out;
    }
}

export { MASTER_COLLECTIONS, OPLOG_COLLECTION };
