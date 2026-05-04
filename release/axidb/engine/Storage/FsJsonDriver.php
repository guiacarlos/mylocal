<?php
/**
 * AxiDB - Storage\FsJsonDriver: impl default de Driver sobre JSON-por-archivo.
 *
 * Subsistema: engine/storage
 * Responsable: un documento = un archivo JSON en <basePath>/<collection>/<id>.json.
 *              Escritura atomica (tmp + rename), flock() con timeout, realpath
 *              anti-traversal. Sin _index externo: listIds escanea glob directo
 *              (suficiente para volumenes pequenos; Fase 1.4b: indice cacheable).
 * Invariantes: nunca file_put_contents sin tmp+rename. Nunca path con user-input
 *              sin pasar por validatePath().
 */

namespace Axi\Engine\Storage;

use Axi\Engine\AxiException;

final class FsJsonDriver implements Driver
{
    public function __construct(private string $basePath)
    {
        if (!\is_dir($this->basePath)) {
            @\mkdir($this->basePath, 0700, true);
        }
    }

    public function driverName(): string
    {
        return 'FsJsonDriver';
    }

    public function writeDoc(string $collection, string $id, array $data): array
    {
        $this->validateName($collection, 'collection');
        $this->validateName($id, 'id');

        $this->ensureCollection($collection);
        $path = $this->docPath($collection, $id);

        // Merge con existente si lo hay.
        $existing = \is_file($path) ? (\json_decode(\file_get_contents($path), true) ?: []) : [];
        $merged   = \array_merge($existing, $data);
        $merged['_id']        = $id;
        $merged['_updatedAt'] = \date('c');
        $merged['_version']   = (int) (($existing['_version'] ?? 0)) + 1;
        if (!isset($existing['_createdAt'])) {
            $merged['_createdAt'] = $merged['_updatedAt'];
        } else {
            $merged['_createdAt'] = $existing['_createdAt'];
        }

        $tmp = $path . '.tmp.' . \bin2hex(\random_bytes(4));
        $bytes = \json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($bytes === false) {
            throw new AxiException("FsJsonDriver: json_encode fallo.", AxiException::INTERNAL_ERROR);
        }
        if (\file_put_contents($tmp, $bytes) === false) {
            throw new AxiException("FsJsonDriver: no pude escribir tmp {$tmp}.", AxiException::INTERNAL_ERROR);
        }
        @\chmod($tmp, 0600);
        if (!\rename($tmp, $path)) {
            @\unlink($tmp);
            throw new AxiException("FsJsonDriver: rename atomico fallo para {$path}.", AxiException::INTERNAL_ERROR);
        }
        return $merged;
    }

    public function readDoc(string $collection, string $id): ?array
    {
        $this->validateName($collection, 'collection');
        $this->validateName($id, 'id');
        $path = $this->docPath($collection, $id);
        if (!\is_file($path)) {
            return null;
        }
        $raw  = \file_get_contents($path);
        $data = \json_decode($raw, true);
        return \is_array($data) ? $data : null;
    }

    public function deleteDoc(string $collection, string $id): bool
    {
        $this->validateName($collection, 'collection');
        $this->validateName($id, 'id');
        $path = $this->docPath($collection, $id);
        if (!\is_file($path)) {
            return false;
        }
        return @\unlink($path);
    }

    public function listIds(string $collection): array
    {
        $this->validateName($collection, 'collection');
        $dir = $this->collectionPath($collection);
        if (!\is_dir($dir)) {
            return [];
        }
        $ids = [];
        foreach (\scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..' || $entry[0] === '_') {
                continue;
            }
            if (\str_ends_with($entry, '.json') && !\str_contains($entry, '.tmp.')) {
                $ids[] = \substr($entry, 0, -5);
            }
        }
        \sort($ids);
        return $ids;
    }

    public function listDocs(string $collection): array
    {
        $out = [];
        foreach ($this->listIds($collection) as $id) {
            $d = $this->readDoc($collection, $id);
            if ($d !== null) {
                $out[] = $d;
            }
        }
        return $out;
    }

    public function collectionExists(string $collection): bool
    {
        $this->validateName($collection, 'collection');
        return \is_dir($this->collectionPath($collection));
    }

    public function ensureCollection(string $collection): void
    {
        $this->validateName($collection, 'collection');
        $dir = $this->collectionPath($collection);
        if (!\is_dir($dir)) {
            if (!@\mkdir($dir, 0700, true) && !\is_dir($dir)) {
                throw new AxiException("FsJsonDriver: no pude crear {$dir}.", AxiException::INTERNAL_ERROR);
            }
        }
    }

    public function dropCollection(string $collection): bool
    {
        $this->validateName($collection, 'collection');
        $dir = $this->collectionPath($collection);
        if (!\is_dir($dir)) {
            return false;
        }
        $this->rmrf($dir);
        return !\is_dir($dir);
    }

    public function acquireLock(string $collection, int $timeoutMs = 5000): mixed
    {
        $this->validateName($collection, 'collection');
        $this->ensureCollection($collection);
        $lockFile = $this->collectionPath($collection) . '/._lock';
        $fp = \fopen($lockFile, 'c+');
        if (!$fp) {
            throw new AxiException("FsJsonDriver: no pude abrir lockfile {$lockFile}.", AxiException::INTERNAL_ERROR);
        }
        $start = \microtime(true);
        while (!\flock($fp, LOCK_EX | LOCK_NB)) {
            if ((\microtime(true) - $start) * 1000 > $timeoutMs) {
                \fclose($fp);
                throw new AxiException(
                    "FsJsonDriver: lock timeout en '{$collection}' tras {$timeoutMs}ms.",
                    AxiException::CONFLICT
                );
            }
            \usleep(50000);                  // 50ms
        }
        return $fp;
    }

    public function releaseLock(mixed $handle): void
    {
        if (\is_resource($handle)) {
            \flock($handle, LOCK_UN);
            \fclose($handle);
        }
    }

    private function collectionPath(string $collection): string
    {
        return $this->basePath . '/' . $collection;
    }

    private function docPath(string $collection, string $id): string
    {
        return $this->collectionPath($collection) . '/' . $id . '.json';
    }

    /**
     * Validacion anti-path-traversal: solo [a-zA-Z0-9_-], opcionalmente con puntos (ULID-like).
     * Rechaza '..', '/', '\', null byte, strings vacios.
     */
    private function validateName(string $name, string $kind): void
    {
        if ($name === '') {
            throw new AxiException("FsJsonDriver: {$kind} vacio.", AxiException::VALIDATION_FAILED);
        }
        if (\strlen($name) > 128) {
            throw new AxiException("FsJsonDriver: {$kind} demasiado largo.", AxiException::VALIDATION_FAILED);
        }
        if (!\preg_match('/^[A-Za-z0-9][A-Za-z0-9_\-.]*$/', $name)) {
            throw new AxiException(
                "FsJsonDriver: {$kind} invalido '{$name}' (solo [A-Za-z0-9_-.]).",
                AxiException::VALIDATION_FAILED
            );
        }
        if (\str_contains($name, '..')) {
            throw new AxiException("FsJsonDriver: {$kind} no puede contener '..'.", AxiException::VALIDATION_FAILED);
        }
    }

    private function rmrf(string $path): void
    {
        if (!\is_dir($path)) {
            @\unlink($path);
            return;
        }
        foreach (\scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $this->rmrf($path . '/' . $entry);
        }
        @\rmdir($path);
    }
}
