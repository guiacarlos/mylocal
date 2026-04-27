/**
 * SynaxisStorage — adaptador IndexedDB para colecciones JSON.
 *
 * Cada colección es un object store con keyPath "id". Un store aparte
 * ("__index__") guarda los _index.json por colección, espejando la
 * estructura del backend PHP.
 *
 * API: openDB, put, get, delete, all, clear, tx.
 */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.SynaxisStorage = factory();
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    const INDEX_STORE = '__synaxis_index__';
    const META_STORE = '__synaxis_meta__';

    class SynaxisStorage {
        constructor({ dbName = 'synaxis', version = 1 } = {}) {
            this.dbName = dbName;
            this.version = version;
            this._db = null;
            this._knownStores = new Set([INDEX_STORE, META_STORE]);
            // Serializa ensureStore para evitar VersionError en llamadas paralelas.
            this._lock = Promise.resolve();
        }

        _serialize(fn) {
            const next = this._lock.then(fn, fn);
            this._lock = next.catch(() => { });
            return next;
        }

        /**
         * Abrir (o crear) la DB. Si se pide una colección que no existe como
         * object store, hay que elevar `version` y recrear — lo hace `ensureStore`.
         */
        async open() {
            return this._serialize(() => this._openInternal());
        }

        _openWithStores(stores) {
            return new Promise((resolve, reject) => {
                const req = indexedDB.open(this.dbName, this.version);
                req.onupgradeneeded = () => {
                    const db = req.result;
                    for (const name of stores) {
                        if (!db.objectStoreNames.contains(name)) {
                            const isKV = name === INDEX_STORE || name === META_STORE;
                            db.createObjectStore(name, isKV ? { keyPath: 'id' } : { keyPath: 'id' });
                        }
                    }
                };
                req.onsuccess = () => {
                    for (const n of Array.from(req.result.objectStoreNames)) this._knownStores.add(n);
                    resolve(req.result);
                };
                req.onerror = () => reject(req.error);
                req.onblocked = () => reject(new Error('SynaxisStorage: open bloqueado por otra pestaña'));
            });
        }

        /**
         * Garantiza que el object store para `collection` existe. Si no,
         * cierra la DB y la reabre con version+1 añadiendo el store.
         */
        async ensureStore(collection) {
            return this._serialize(async () => {
                if (!this._db) await this._openInternal();
                if (this._db.objectStoreNames.contains(collection)) return;
                this._db.close();
                this.version += 1;
                this._db = null;
                await this._openWithStores([collection, INDEX_STORE, META_STORE]);
            });
        }

        async _openInternal() {
            if (this._db) return this._db;
            this._db = await this._openWithStores([INDEX_STORE, META_STORE]);
            return this._db;
        }

        _tx(storeName, mode = 'readonly') {
            const tx = this._db.transaction(storeName, mode);
            return { tx, store: tx.objectStore(storeName) };
        }

        async put(collection, doc) {
            await this.ensureStore(collection);
            return new Promise((resolve, reject) => {
                const { tx, store } = this._tx(collection, 'readwrite');
                const req = store.put(doc);
                req.onsuccess = () => resolve(doc);
                tx.onerror = () => reject(tx.error);
            });
        }

        async get(collection, id) {
            await this.ensureStore(collection);
            return new Promise((resolve, reject) => {
                const { tx, store } = this._tx(collection, 'readonly');
                const req = store.get(id);
                req.onsuccess = () => resolve(req.result || null);
                tx.onerror = () => reject(tx.error);
            });
        }

        async remove(collection, id) {
            await this.ensureStore(collection);
            return new Promise((resolve, reject) => {
                const { tx, store } = this._tx(collection, 'readwrite');
                const req = store.delete(id);
                req.onsuccess = () => resolve(true);
                tx.onerror = () => reject(tx.error);
            });
        }

        async all(collection) {
            await this.ensureStore(collection);
            return new Promise((resolve, reject) => {
                const { tx, store } = this._tx(collection, 'readonly');
                const req = store.getAll();
                req.onsuccess = () => resolve(req.result || []);
                tx.onerror = () => reject(tx.error);
            });
        }

        async clear(collection) {
            await this.ensureStore(collection);
            return new Promise((resolve, reject) => {
                const { tx, store } = this._tx(collection, 'readwrite');
                const req = store.clear();
                req.onsuccess = () => resolve(true);
                tx.onerror = () => reject(tx.error);
            });
        }

        async listCollections() {
            await this.open();
            return Array.from(this._db.objectStoreNames).filter(
                (n) => n !== INDEX_STORE && n !== META_STORE
            );
        }

        async dropDatabase() {
            if (this._db) { this._db.close(); this._db = null; }
            return new Promise((resolve, reject) => {
                const req = indexedDB.deleteDatabase(this.dbName);
                req.onsuccess = () => resolve(true);
                req.onerror = () => reject(req.error);
                req.onblocked = () => reject(new Error('SynaxisStorage: drop bloqueado'));
            });
        }
    }

    SynaxisStorage.INDEX_STORE = INDEX_STORE;
    SynaxisStorage.META_STORE = META_STORE;
    return SynaxisStorage;
});
