<?php
/**
 * AxiDB - Storage\Driver: interface del backend de persistencia.
 *
 * Subsistema: engine/storage
 * Responsable: abstraer como se guardan/leen documentos. Permite sustituir
 *              la impl JSON-por-archivo (v1) por otra (Packed, Redis, SQLite)
 *              en el futuro sin tocar los Ops.
 * Invariante: toda escritura es atomica a nivel de documento.
 */

namespace Axi\Engine\Storage;

interface Driver
{
    /**
     * Escribe (crea o actualiza) un documento. Garantia atomica (tmp+rename).
     * @return array el documento persistido (incluye _version, _updatedAt, _createdAt).
     */
    public function writeDoc(string $collection, string $id, array $data): array;

    /**
     * Lee un documento por id. Devuelve null si no existe.
     */
    public function readDoc(string $collection, string $id): ?array;

    /**
     * Elimina un documento fisicamente. True si existia.
     */
    public function deleteDoc(string $collection, string $id): bool;

    /**
     * Lista todos los ids (string[]) de una coleccion. Ignora docs que empiezan por "_".
     * @return string[]
     */
    public function listIds(string $collection): array;

    /**
     * Lista todos los documentos de una coleccion (sin proyeccion).
     * @return array[] lista de documentos.
     */
    public function listDocs(string $collection): array;

    /**
     * True si la coleccion existe (directorio creado).
     */
    public function collectionExists(string $collection): bool;

    /**
     * Crea la coleccion vacia si no existe. Idempotente.
     */
    public function ensureCollection(string $collection): void;

    /**
     * Elimina la coleccion completa (directorio, docs, indices). True si existia.
     */
    public function dropCollection(string $collection): bool;

    /**
     * Adquiere lock exclusivo sobre la coleccion. Para operaciones batch.
     * Devuelve handle opaco que debe pasarse a releaseLock().
     */
    public function acquireLock(string $collection, int $timeoutMs = 5000): mixed;

    /**
     * Libera el lock previamente adquirido.
     */
    public function releaseLock(mixed $handle): void;

    /**
     * Nombre del driver (para telemetria).
     */
    public function driverName(): string;
}
