<?php
/**
 * AxiDB - MetaStore: lectura/escritura de _meta.json por coleccion.
 *
 * Subsistema: engine/schema
 * Responsable: centralizar el acceso al metadato de coleccion (schema, fields,
 *              indexes, flags). Las Ops de Schema usan este helper en vez de
 *              manipular archivos directamente.
 * Fase 1.4:    se sustituye por StorageDriver::meta() manteniendo este
 *              contrato publico.
 */

namespace Axi\Engine\Schema;

final class MetaStore
{
    public function __construct(private string $basePath)
    {
    }

    public function collectionPath(string $collection): string
    {
        return $this->basePath . '/' . $collection;
    }

    public function metaPath(string $collection): string
    {
        return $this->collectionPath($collection) . '/_meta.json';
    }

    public function exists(string $collection): bool
    {
        return \is_dir($this->collectionPath($collection));
    }

    public function readMeta(string $collection): array
    {
        $path = $this->metaPath($collection);
        if (!\is_file($path)) {
            return $this->defaultMeta($collection);
        }
        $raw = \file_get_contents($path);
        $meta = \json_decode($raw, true);
        return \is_array($meta) ? $meta : $this->defaultMeta($collection);
    }

    public function writeMeta(string $collection, array $meta): void
    {
        $dir = $this->collectionPath($collection);
        if (!\is_dir($dir)) {
            \mkdir($dir, 0700, true);
        }
        \file_put_contents(
            $this->metaPath($collection),
            \json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function defaultMeta(string $collection): array
    {
        return [
            'name'       => $collection,
            'created_at' => \date('c'),
            'fields'     => [],
            'indexes'    => [],
            'flags'      => [
                'encrypted'         => false,
                'keep_versions'     => false,
                'strict_schema'     => false,
                'strict_durability' => false,
            ],
        ];
    }

    public function createCollection(string $collection, array $flags = []): array
    {
        if ($this->exists($collection) && \is_file($this->metaPath($collection))) {
            return $this->readMeta($collection);
        }
        $meta = $this->defaultMeta($collection);
        $meta['flags'] = \array_merge($meta['flags'], $flags);
        $this->writeMeta($collection, $meta);
        return $meta;
    }

    public function dropCollection(string $collection): bool
    {
        $path = $this->collectionPath($collection);
        if (!\is_dir($path)) {
            return false;
        }
        $this->rmrf($path);
        return true;
    }

    public function renameCollection(string $from, string $to): bool
    {
        $fromPath = $this->collectionPath($from);
        $toPath   = $this->collectionPath($to);
        if (!\is_dir($fromPath) || \is_dir($toPath)) {
            return false;
        }
        return \rename($fromPath, $toPath);
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
