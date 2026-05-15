<?php
/**
 * AxiDB - Storage\PackedDriver: stub para v2 (coleccion empaquetada).
 *
 * Subsistema: engine/storage
 * Estado:    reservado para v2+. Idea: colecciones grandes se serializan a un
 *            unico archivo binario tipo MessagePack con indice en memoria,
 *            reduciendo I/O en colecciones con >10k docs.
 * En v1:     todos los metodos lanzan NOT_IMPLEMENTED. El interface queda listo
 *            para que la transicion no requiera cambios en los Ops.
 */

namespace Axi\Engine\Storage;

use Axi\Engine\AxiException;

final class PackedDriver implements Driver
{
    public function __construct(private string $basePath)
    {
    }

    public function driverName(): string
    {
        return 'PackedDriver';
    }

    private function notImplemented(string $method): never
    {
        throw new AxiException(
            "PackedDriver::{$method}() no implementado. Reservado para v2.",
            AxiException::NOT_IMPLEMENTED
        );
    }

    public function writeDoc(string $collection, string $id, array $data): array
    {
        $this->notImplemented(__FUNCTION__);
    }

    public function readDoc(string $collection, string $id): ?array
    {
        $this->notImplemented(__FUNCTION__);
    }

    public function deleteDoc(string $collection, string $id): bool
    {
        $this->notImplemented(__FUNCTION__);
    }

    public function listIds(string $collection): array
    {
        $this->notImplemented(__FUNCTION__);
    }

    public function listDocs(string $collection): array
    {
        $this->notImplemented(__FUNCTION__);
    }

    public function collectionExists(string $collection): bool
    {
        $this->notImplemented(__FUNCTION__);
    }

    public function ensureCollection(string $collection): void
    {
        $this->notImplemented(__FUNCTION__);
    }

    public function dropCollection(string $collection): bool
    {
        $this->notImplemented(__FUNCTION__);
    }

    public function acquireLock(string $collection, int $timeoutMs = 5000): mixed
    {
        $this->notImplemented(__FUNCTION__);
    }

    public function releaseLock(mixed $handle): void
    {
        $this->notImplemented(__FUNCTION__);
    }
}
