/**
 * SynaxisCore — orquestador del motor de datos.
 *
 * Dispatcher de acciones (`execute`) + CRUD basico contra IndexedDB,
 * delegando versioning y oplog a modulos auxiliares (SynaxisVersioning,
 * SynaxisOplog) y primitivas puras a SynaxisCoreHelpers.
 *
 * Reglas:
 *   - Las colecciones master (users/roles/projects/system_logs) viven en
 *     la DB <namespace>__master, compartida entre proyectos.
 *   - El resto va en la DB scoped <namespace>__<project>.
 *   - Cada write incrementa _version, refresca _updatedAt y guarda
 *     snapshot del doc PREVIO.
 *   - El oplog es append-only y best-effort.
 */

import { SynaxisStorage } from './SynaxisStorage';
import { runQuery } from './SynaxisQuery';
import type { QueryParams, SynaxisDoc, SynaxisRequest, SynaxisResponse } from './types';
import {
    MASTER_COLLECTIONS,
    OPLOG_COLLECTION,
    fail,
    genId,
    nowIso,
    ok,
    type OpLogEntry,
} from './SynaxisCoreHelpers';
import { listVersions as listVersionsImpl, snapshotVersion } from './SynaxisVersioning';
import { appendOp, clearOplog as clearOplogImpl, drainOplog as drainOplogImpl } from './SynaxisOplog';
import { normalizeRequest } from './SynaxisRequestNormalize';

export interface SynaxisCoreOptions {
    namespace?: string;
    project?: string | null;
    writeOplog?: boolean;
}

export { genId };

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

    async execute<T = unknown>(request: SynaxisRequest): Promise<SynaxisResponse<T>> {
        await this.ready;
        if (!request || !request.action) {
            return fail('action is required') as SynaxisResponse<T>;
        }
        const { action, collection, id, params, payload } = normalizeRequest(request);

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

        if (existing) await snapshotVersion(storage, collection, existing);
        const final = await storage.put(collection, merged as unknown as T);
        if (this.writeOplog) await appendOp(this.scoped, 'put', collection, effectiveId, final);
        return final;
    }

    async delete(collection: string, id: string): Promise<boolean> {
        if (!collection || !id) throw new Error('delete requiere collection e id');
        const res = await this.storageFor(collection).remove(collection, id);
        if (this.writeOplog && res) await appendOp(this.scoped, 'delete', collection, id, null);
        return res;
    }

    listVersions(collection: string, id: string): Promise<SynaxisDoc[]> {
        return listVersionsImpl(this.storageFor(collection), collection, id);
    }

    drainOplog(limit = 100): Promise<OpLogEntry[]> {
        return drainOplogImpl(this.scoped, limit);
    }

    clearOplog(ids: string[]): Promise<void> {
        return clearOplogImpl(this.scoped, ids);
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
