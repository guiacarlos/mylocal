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
    private opening: Promise<IDBDatabase> | null = null;
    private lock: Promise<unknown> = Promise.resolve();
    private knownStores: Set<string>;

    constructor({ dbName = 'synaxis', version = 1 }: SynaxisStorageOptions = {}) {
        this.dbName = dbName;
        this.version = version;
        this.knownStores = new Set([INDEX_STORE, META_STORE]);
    }

    private serialize<T>(fn: () => Promise<T>): Promise<T> {
        const next = this.lock.then(async () => {
            try {
                return await fn();
            } catch (e) {
                if (e instanceof Error && e.message.includes('null') && this.db === null) {
                    this.opening = null;
                }
                throw e;
            }
        });
        this.lock = next.then(
            () => undefined,
            () => undefined,
        );
        return next;
    }

    private openWithStores(stores: string[]): Promise<IDBDatabase> {
        if (this.opening) return this.opening;

        this.opening = new Promise((resolve, reject) => {
            const req = indexedDB.open(this.dbName, this.version);

            req.onupgradeneeded = () => {
                const db = req.result;
                for (const name of stores) {
                    if (!db.objectStoreNames.contains(name)) {
                        db.createObjectStore(name, { keyPath: 'id' });
                    }
                }
            };

            req.onsuccess = () => {
                this.db = req.result;
                this.db.onversionchange = () => {
                    if (this.db) {
                        this.db.close();
                        this.db = null;
                        this.opening = null;
                    }
                };
                this.opening = null;
                resolve(this.db);
            };

            req.onerror = () => {
                const err = req.error;
                this.opening = null;
                if (err?.name === 'VersionError') {
                    indexedDB.deleteDatabase(this.dbName).onsuccess = () => {
                        window.location.reload();
                    };
                } else {
                    reject(err);
                }
            };

            req.onblocked = () => {
                if (this.db) this.db.close();
                this.db = null;
                setTimeout(() => {
                    this.opening = null;
                    this.getDb().then(resolve, reject);
                }, 200);
            };
        });

        return this.opening;
    }

    private async getDb(): Promise<IDBDatabase> {
        if (this.db) return this.db;
        return await this.openWithStores(Array.from(this.knownStores));
    }

    async open(): Promise<IDBDatabase> {
        return this.serialize(() => this.getDb());
    }

    private async ensureStoreInternal(collection: string): Promise<void> {
        const db = await this.getDb();
        if (db.objectStoreNames.contains(collection)) {
            this.knownStores.add(collection);
            return;
        }
        this.knownStores.add(collection);
        db.close();
        this.db = null;
        this.opening = null;
        this.version = Math.max(this.version, db.version) + 1;
        await this.getDb();
    }

    async ensureStore(collection: string): Promise<void> {
        return this.serialize(() => this.ensureStoreInternal(collection));
    }

    private async transaction(
        collection: string,
        mode: IDBTransactionMode,
    ): Promise<{ tx: IDBTransaction; store: IDBObjectStore }> {
        await this.ensureStoreInternal(collection);
        if (!this.db) {
            this.db = await this.getDb();
        }
        const tx = this.db.transaction(collection, mode);
        return { tx, store: tx.objectStore(collection) };
    }

    async put<T extends { id: string }>(collection: string, doc: T): Promise<T> {
        return this.serialize(async () => {
            const { tx, store } = await this.transaction(collection, 'readwrite');
            return new Promise((resolve, reject) => {
                const req = store.put(doc);
                req.onsuccess = () => resolve(doc);
                tx.onerror = () => reject(tx.error);
                tx.onabort = () => reject(tx.error || new Error('Transaction aborted'));
            });
        });
    }

    async get<T>(collection: string, id: string): Promise<T | null> {
        return this.serialize(async () => {
            const { tx, store } = await this.transaction(collection, 'readonly');
            return new Promise((resolve, reject) => {
                const req = store.get(id);
                req.onsuccess = () => resolve((req.result as T) ?? null);
                tx.onerror = () => reject(tx.error);
            });
        });
    }

    async remove(collection: string, id: string): Promise<boolean> {
        return this.serialize(async () => {
            const { tx, store } = await this.transaction(collection, 'readwrite');
            return new Promise((resolve, reject) => {
                const req = store.delete(id);
                req.onsuccess = () => resolve(true);
                tx.onerror = () => reject(tx.error);
            });
        });
    }

    async all<T>(collection: string): Promise<T[]> {
        return this.serialize(async () => {
            const { tx, store } = await this.transaction(collection, 'readonly');
            return new Promise((resolve, reject) => {
                const req = store.getAll();
                req.onsuccess = () => resolve((req.result as T[]) ?? []);
                tx.onerror = () => reject(tx.error);
            });
        });
    }

    async clear(collection: string): Promise<boolean> {
        return this.serialize(async () => {
            const { tx, store } = await this.transaction(collection, 'readwrite');
            return new Promise((resolve, reject) => {
                const req = store.clear();
                req.onsuccess = () => resolve(true);
                tx.onerror = () => reject(tx.error);
            });
        });
    }

    async listCollections(): Promise<string[]> {
        return this.serialize(async () => {
            const db = await this.getDb();
            return Array.from(db.objectStoreNames).filter(
                (n) => n !== INDEX_STORE && n !== META_STORE,
            );
        });
    }

    async dropDatabase(): Promise<boolean> {
        return this.serialize(async () => {
            if (this.db) {
                this.db.close();
                this.db = null;
            }
            this.opening = null;
            return new Promise((resolve, reject) => {
                const req = indexedDB.deleteDatabase(this.dbName);
                req.onsuccess = () => resolve(true);
                req.onerror = () => reject(req.error);
                req.onblocked = () => {
                    setTimeout(() => {
                        const retry = indexedDB.deleteDatabase(this.dbName);
                        retry.onsuccess = () => resolve(true);
                        retry.onerror = () => reject(retry.error);
                    }, 500);
                };
            });
        });
    }
}

export const SYNAXIS_INDEX_STORE = INDEX_STORE;
export const SYNAXIS_META_STORE = META_STORE;
