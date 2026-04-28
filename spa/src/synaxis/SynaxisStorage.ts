/**
 * SynaxisStorage — adaptador IndexedDB puro (sin lógica de negocio).
 *
 * Port TS de js/synaxis/synaxis-storage.js (repo padre). Cambios:
 *   - ESM, clases, tipos.
 *   - `_serialize` es un mutex promise-chain para evitar VersionError en
 *     llamadas paralelas a ensureStore (IndexedDB sólo permite crear
 *     object stores dentro de onupgradeneeded y no admite requests
 *     concurrentes de versión distinta).
 */

const INDEX_STORE = '__synaxis_index__';
const META_STORE = '__synaxis_meta__';

export interface SynaxisStorageOptions {
    dbName?: string;
    version?: number;
}

export class SynaxisStorage {
    readonly dbName: string;
    private version: number;
    private db: IDBDatabase | null = null;
    private lock: Promise<unknown> = Promise.resolve();

    constructor({ dbName = 'synaxis', version = 1 }: SynaxisStorageOptions = {}) {
        this.dbName = dbName;
        this.version = version;
    }

    private serialize<T>(fn: () => Promise<T>): Promise<T> {
        const next = this.lock.then(fn, fn) as Promise<T>;
        this.lock = next.catch(() => undefined);
        return next;
    }

    private openWithStores(stores: string[]): Promise<IDBDatabase> {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(this.dbName, this.version);
            req.onupgradeneeded = () => {
                const db = req.result;
                for (const name of stores) {
                    if (!db.objectStoreNames.contains(name)) {
                        db.createObjectStore(name, { keyPath: 'id' });
                    }
                }
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
            req.onblocked = () =>
                reject(new Error('SynaxisStorage: open bloqueado por otra pestaña'));
        });
    }

    private async openInternal(): Promise<IDBDatabase> {
        if (this.db) return this.db;
        this.db = await this.openWithStores([INDEX_STORE, META_STORE]);
        return this.db;
    }

    open(): Promise<IDBDatabase> {
        return this.serialize(() => this.openInternal());
    }

    ensureStore(collection: string): Promise<void> {
        return this.serialize(async () => {
            if (!this.db) await this.openInternal();
            if (this.db!.objectStoreNames.contains(collection)) return;
            this.db!.close();
            this.version += 1;
            this.db = null;
            await this.openWithStores([collection, INDEX_STORE, META_STORE]);
        });
    }

    private tx(name: string, mode: IDBTransactionMode): { tx: IDBTransaction; store: IDBObjectStore } {
        const tx = this.db!.transaction(name, mode);
        return { tx, store: tx.objectStore(name) };
    }

    async put<T extends { id: string }>(collection: string, doc: T): Promise<T> {
        await this.ensureStore(collection);
        return new Promise((resolve, reject) => {
            const { tx, store } = this.tx(collection, 'readwrite');
            const req = store.put(doc);
            req.onsuccess = () => resolve(doc);
            tx.onerror = () => reject(tx.error);
        });
    }

    async get<T>(collection: string, id: string): Promise<T | null> {
        await this.ensureStore(collection);
        return new Promise((resolve, reject) => {
            const { tx, store } = this.tx(collection, 'readonly');
            const req = store.get(id);
            req.onsuccess = () => resolve((req.result as T) ?? null);
            tx.onerror = () => reject(tx.error);
        });
    }

    async remove(collection: string, id: string): Promise<boolean> {
        await this.ensureStore(collection);
        return new Promise((resolve, reject) => {
            const { tx, store } = this.tx(collection, 'readwrite');
            const req = store.delete(id);
            req.onsuccess = () => resolve(true);
            tx.onerror = () => reject(tx.error);
        });
    }

    async all<T>(collection: string): Promise<T[]> {
        await this.ensureStore(collection);
        return new Promise((resolve, reject) => {
            const { tx, store } = this.tx(collection, 'readonly');
            const req = store.getAll();
            req.onsuccess = () => resolve((req.result as T[]) ?? []);
            tx.onerror = () => reject(tx.error);
        });
    }

    async clear(collection: string): Promise<boolean> {
        await this.ensureStore(collection);
        return new Promise((resolve, reject) => {
            const { tx, store } = this.tx(collection, 'readwrite');
            const req = store.clear();
            req.onsuccess = () => resolve(true);
            tx.onerror = () => reject(tx.error);
        });
    }

    async listCollections(): Promise<string[]> {
        await this.open();
        return Array.from(this.db!.objectStoreNames).filter(
            (n) => n !== INDEX_STORE && n !== META_STORE,
        );
    }

    async dropDatabase(): Promise<boolean> {
        if (this.db) {
            this.db.close();
            this.db = null;
        }
        return new Promise((resolve, reject) => {
            const req = indexedDB.deleteDatabase(this.dbName);
            req.onsuccess = () => resolve(true);
            req.onerror = () => reject(req.error);
            req.onblocked = () => reject(new Error('SynaxisStorage: drop bloqueado'));
        });
    }
}

export const SYNAXIS_INDEX_STORE = INDEX_STORE;
export const SYNAXIS_META_STORE = META_STORE;
