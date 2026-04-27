/**
 * SynaxisCore — base de datos JSON en navegador con contrato ACIDE.
 *
 * Uso:
 *   const core = new SynaxisCore({ namespace: 'socola' });
 *   await core.ready;
 *   const res = await core.execute({ action: 'list', collection: 'products' });
 *   // { success: true, data: [...], error: null }
 *
 * Contrato de acciones (Fase 1): read, list, query, create, update, delete,
 * health_check, _debug_reset.
 *
 * Metadatos de documento: _createdAt, _updatedAt, _version (espejo PHP).
 * Flag _REPLACE_: si está presente en el payload de update, sustituye en
 * vez de mezclar.
 *
 * Master collections: users, roles, projects, system_logs → DB global
 * separada para no contaminar datos per-proyecto (espejo PHP).
 */
(function (root, factory) {
    const deps = () => {
        const SynaxisStorage = root.SynaxisStorage || (typeof require === 'function' ? require('./synaxis-storage.js') : null);
        const SynaxisQuery = root.SynaxisQuery || (typeof require === 'function' ? require('./synaxis-query.js') : null);
        if (!SynaxisStorage || !SynaxisQuery) {
            throw new Error('SynaxisCore: faltan dependencias (SynaxisStorage, SynaxisQuery)');
        }
        return { SynaxisStorage, SynaxisQuery };
    };
    if (typeof module === 'object' && module.exports) {
        module.exports = factory(deps());
    } else {
        root.SynaxisCore = factory(deps());
    }
})(typeof self !== 'undefined' ? self : this, function ({ SynaxisStorage, SynaxisQuery }) {
    'use strict';

    const MASTER_COLLECTIONS = new Set(['users', 'roles', 'projects', 'system_logs']);
    const MAX_VERSIONS = 5;

    const nowIso = () => new Date().toISOString();

    /**
     * Genera un id estilo PHP: `<prefix>_<unix_ms>_<rand>`.
     */
    const genId = (prefix = 'doc') =>
        `${prefix}_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;

    function okResponse(data, extra = {}) {
        return { success: true, data, error: null, ...extra };
    }

    function errResponse(error, extra = {}) {
        return { success: false, data: null, error: String(error && error.message ? error.message : error), ...extra };
    }

    class SynaxisCore {
        /**
         * @param {object} opts
         * @param {string} [opts.namespace='synaxis'] — prefijo para ambas DBs.
         * @param {string} [opts.project] — slug del proyecto activo. Si se
         *   pasa, las colecciones no-master van a la DB per-proyecto.
         */
        constructor({ namespace = 'synaxis', project = null } = {}) {
            this.namespace = namespace;
            this.project = project;

            this.scoped = new SynaxisStorage({
                dbName: project ? `${namespace}__${project}` : `${namespace}__global`,
            });
            this.global = new SynaxisStorage({
                dbName: `${namespace}__master`,
            });

            this.ready = Promise.all([this.scoped.open(), this.global.open()]).then(() => this);
        }

        _storageFor(collection) {
            return MASTER_COLLECTIONS.has(collection) ? this.global : this.scoped;
        }

        /**
         * Punto de entrada único. Acepta `{ action, ... }` plano o
         * `{ action, data: {...} }` como el dispatcher PHP. Devuelve
         * siempre `{ success, data, error }`.
         */
        async execute(request) {
            await this.ready;
            const action = request && request.action;
            if (!action) return errResponse('action is required');

            // Normaliza el payload: soporta tanto plano como anidado en `data`.
            const collection = request.collection ?? request?.data?.collection ?? null;
            const id = request.id ?? request?.data?.id ?? null;
            const params = request.params ?? request?.data?.params ?? {};
            let payload;
            if (request.data && typeof request.data === 'object' && request.data.data) {
                payload = request.data.data;
            } else if (request.data && typeof request.data === 'object') {
                payload = { ...request.data };
                delete payload.collection; delete payload.id; delete payload.params;
            } else {
                payload = { ...request };
                delete payload.action; delete payload.collection; delete payload.id; delete payload.params;
            }

            try {
                switch (action) {
                    case 'health_check':
                        return okResponse({ status: 'healthy', namespace: this.namespace, project: this.project, ts: nowIso() });
                    case 'read':
                    case 'get':
                        return okResponse(await this.read(collection, id));
                    case 'list':
                        return okResponse(await this.list(collection));
                    case 'query':
                        return okResponse(await this.query(collection, params));
                    case 'create':
                    case 'update':
                        return okResponse(await this.update(collection, id, payload));
                    case 'delete':
                        return okResponse(await this.delete(collection, id));
                    case '_debug_reset':
                        await this.reset();
                        return okResponse(true);
                    default:
                        return errResponse(`Acción no reconocida por SynaxisCore: ${action}`);
                }
            } catch (e) {
                return errResponse(e);
            }
        }

        /* ─────────────────────────── CRUD ─────────────────────────── */

        async read(collection, id) {
            if (!collection || !id) throw new Error('read requiere collection e id');
            return await this._storageFor(collection).get(collection, id);
        }

        async list(collection) {
            if (!collection) throw new Error('list requiere collection');
            return await this._storageFor(collection).all(collection);
        }

        async query(collection, params = {}) {
            if (!collection) throw new Error('query requiere collection');
            const items = await this._storageFor(collection).all(collection);
            return SynaxisQuery.run(items, params);
        }

        /**
         * Upsert. Si `id` es null, se genera uno. Aplica merge con el doc
         * existente salvo que `_REPLACE_` venga en `data`.
         * Mantiene versiones (hasta MAX_VERSIONS) en colección paralela
         * `<collection>__versions` con clave `<id>@<version>`.
         */
        async update(collection, id, data = {}) {
            if (!collection) throw new Error('update requiere collection');
            const storage = this._storageFor(collection);
            const effectiveId = id || data.id || genId(collection);

            const existing = await storage.get(collection, effectiveId);
            const replace = Boolean(data && data._REPLACE_);
            const incoming = { ...data };
            delete incoming._REPLACE_;

            const merged = (existing && !replace) ? { ...existing, ...incoming } : { ...incoming };
            merged.id = effectiveId;
            merged._updatedAt = nowIso();
            merged._version = (existing && typeof existing._version === 'number' ? existing._version : 0) + 1;
            if (!merged._createdAt) merged._createdAt = existing?._createdAt || nowIso();

            if (existing) {
                await this._snapshotVersion(collection, existing);
            }

            return await storage.put(collection, merged);
        }

        async delete(collection, id) {
            if (!collection || !id) throw new Error('delete requiere collection e id');
            return await this._storageFor(collection).remove(collection, id);
        }

        /* ───────────────────── versiones ───────────────────── */

        async _snapshotVersion(collection, doc) {
            if (!doc || !doc.id) return;
            const storage = this._storageFor(collection);
            const vCol = `${collection}__versions`;
            const snap = { ...doc, id: `${doc.id}@${doc._version || 0}` };
            try {
                await storage.put(vCol, snap);
                const all = await storage.all(vCol);
                const mine = all.filter((v) => String(v.id).startsWith(`${doc.id}@`))
                    .sort((a, b) => (a._updatedAt < b._updatedAt ? -1 : 1));
                const excess = mine.length - MAX_VERSIONS;
                for (let i = 0; i < excess; i++) {
                    await storage.remove(vCol, mine[i].id);
                }
            } catch (_) { /* versioning es best-effort */ }
        }

        async listVersions(collection, id) {
            const storage = this._storageFor(collection);
            const vCol = `${collection}__versions`;
            const all = await storage.all(vCol);
            return all
                .filter((v) => String(v.id).startsWith(`${id}@`))
                .sort((a, b) => (a._version > b._version ? 1 : -1));
        }

        /* ───────────────────── mantenimiento ───────────────────── */

        async reset() {
            await this.scoped.dropDatabase();
            await this.global.dropDatabase();
            this.scoped = new SynaxisStorage({
                dbName: this.project ? `${this.namespace}__${this.project}` : `${this.namespace}__global`,
            });
            this.global = new SynaxisStorage({ dbName: `${this.namespace}__master` });
            this.ready = Promise.all([this.scoped.open(), this.global.open()]).then(() => this);
            await this.ready;
        }

        async importSnapshot(snapshot) {
            if (!snapshot || typeof snapshot !== 'object') throw new Error('snapshot inválido');
            for (const [collection, docs] of Object.entries(snapshot)) {
                if (!Array.isArray(docs)) continue;
                for (const doc of docs) {
                    if (!doc || !doc.id) continue;
                    await this._storageFor(collection).put(collection, doc);
                }
            }
            return true;
        }

        async exportSnapshot() {
            const out = {};
            for (const storage of [this.scoped, this.global]) {
                for (const col of await storage.listCollections()) {
                    if (col.endsWith('__versions')) continue;
                    out[col] = await storage.all(col);
                }
            }
            return out;
        }
    }

    SynaxisCore.MASTER_COLLECTIONS = MASTER_COLLECTIONS;
    SynaxisCore.genId = genId;
    return SynaxisCore;
});
