<?php
/**
 * AxiDB - Backup\SnapshotStore: gestor de snapshots full + incremental.
 *
 * Subsistema: engine/backup
 * Responsable: serializar colecciones a un archivo zip con manifest.json,
 *              listar snapshots existentes, restaurar a un STORAGE_ROOT.
 *
 * Layout fisico:
 *   <backupsDir>/
 *   └── snapshots/
 *       └── <name>/
 *           ├── manifest.json
 *           └── data.zip            (un entry por archivo: <collection>/<id>.json)
 */

namespace Axi\Engine\Backup;

use Axi\Engine\AxiException;

final class SnapshotStore
{
    public function __construct(
        private string $storageRoot,
        private string $backupsDir
    ) {
        $base = $this->backupsDir . '/snapshots';
        if (!\is_dir($base)) {
            @\mkdir($base, 0700, true);
        }
    }

    /**
     * Crea un snapshot. Si $base es null o no existe, crea uno full.
     * Si $base es un nombre de snapshot existente, hace incremental basado en _updatedAt.
     * @return Manifest
     */
    public function create(string $name, ?string $base = null): Manifest
    {
        $name = $this->sanitizeName($name);
        $dir  = $this->snapshotDir($name);
        if (\is_dir($dir)) {
            throw new AxiException(
                "Backup: snapshot '{$name}' ya existe.",
                AxiException::CONFLICT
            );
        }
        \mkdir($dir, 0700, true);

        $sinceTs = null;
        $type    = Manifest::TYPE_FULL;
        if ($base !== null) {
            $baseManifest = $this->readManifest($base);
            $sinceTs = $baseManifest->ts;
            $type    = Manifest::TYPE_INCREMENTAL;
        }

        $zip = new \ZipArchive();
        $zipPath = $dir . '/data.zip';
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new AxiException("Backup: no pude crear {$zipPath}.", AxiException::INTERNAL_ERROR);
        }

        $collections = [];
        $counts      = [];
        foreach ($this->listCollections() as $col) {
            $files = $this->collectionDocs($col, $sinceTs);
            if ($files === []) {
                continue;
            }
            $collections[] = $col;
            $counts[$col]  = \count($files);
            foreach ($files as $relPath => $absPath) {
                $zip->addFile($absPath, $relPath);
            }
            // Tambien incluir _meta.json si existe.
            $metaPath = $this->storageRoot . '/' . $col . '/_meta.json';
            if (\is_file($metaPath)) {
                $zip->addFile($metaPath, $col . '/_meta.json');
            }
        }
        $zip->close();

        $manifest = new Manifest(
            name:         $name,
            type:         $type,
            ts:           \date('c'),
            collections:  $collections,
            counts:       $counts,
            baseSnapshot: $base
        );
        \file_put_contents(
            $dir . '/manifest.json',
            \json_encode($manifest->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        @\chmod($dir . '/manifest.json', 0600);
        @\chmod($zipPath, 0600);

        return $manifest;
    }

    /**
     * Restaura el snapshot $name al STORAGE_ROOT. Si dryRun, devuelve preview sin escribir.
     * @return array {restored: int, files: string[], dry_run: bool}
     */
    public function restore(string $name, bool $dryRun = false): array
    {
        $name = $this->sanitizeName($name);
        $dir  = $this->snapshotDir($name);
        if (!\is_dir($dir)) {
            throw new AxiException(
                "Backup: snapshot '{$name}' no existe.",
                AxiException::DOCUMENT_NOT_FOUND
            );
        }
        $zipPath = $dir . '/data.zip';
        if (!\is_file($zipPath)) {
            throw new AxiException(
                "Backup: snapshot '{$name}' sin data.zip.",
                AxiException::INTERNAL_ERROR
            );
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new AxiException("Backup: no pude abrir {$zipPath}.", AxiException::INTERNAL_ERROR);
        }

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $files[] = $zip->getNameIndex($i);
        }

        if ($dryRun) {
            $zip->close();
            return [
                'restored' => \count($files),
                'files'    => $files,
                'dry_run'  => true,
            ];
        }

        $zip->extractTo($this->storageRoot);
        $zip->close();
        return [
            'restored' => \count($files),
            'files'    => $files,
            'dry_run'  => false,
        ];
    }

    /** @return string[] nombres ordenados de snapshots existentes. */
    public function listSnapshots(): array
    {
        $base = $this->backupsDir . '/snapshots';
        if (!\is_dir($base)) {
            return [];
        }
        $names = [];
        foreach (\scandir($base) as $entry) {
            if ($entry === '.' || $entry === '..') { continue; }
            $manifestPath = $base . '/' . $entry . '/manifest.json';
            if (\is_file($manifestPath)) {
                $names[] = $entry;
            }
        }
        \sort($names);
        return $names;
    }

    public function readManifest(string $name): Manifest
    {
        $path = $this->snapshotDir($this->sanitizeName($name)) . '/manifest.json';
        if (!\is_file($path)) {
            throw new AxiException(
                "Backup: manifest de '{$name}' no existe.",
                AxiException::DOCUMENT_NOT_FOUND
            );
        }
        $data = \json_decode(\file_get_contents($path), true);
        if (!\is_array($data)) {
            throw new AxiException(
                "Backup: manifest de '{$name}' corrupto.",
                AxiException::CONFLICT
            );
        }
        return Manifest::fromArray($data);
    }

    public function drop(string $name): bool
    {
        $dir = $this->snapshotDir($this->sanitizeName($name));
        if (!\is_dir($dir)) {
            return false;
        }
        foreach (\scandir($dir) as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                @\unlink($dir . '/' . $entry);
            }
        }
        return @\rmdir($dir);
    }

    private function snapshotDir(string $name): string
    {
        return $this->backupsDir . '/snapshots/' . $name;
    }

    private function sanitizeName(string $name): string
    {
        if (!\preg_match('/^[A-Za-z0-9][A-Za-z0-9_\-.]{0,80}$/', $name)) {
            throw new AxiException(
                "Backup: nombre invalido '{$name}' (solo [A-Za-z0-9_\\-.], max 81 chars).",
                AxiException::VALIDATION_FAILED
            );
        }
        return $name;
    }

    /** Lista directorios bajo STORAGE_ROOT que parecen colecciones. */
    private function listCollections(): array
    {
        $cols = [];
        if (!\is_dir($this->storageRoot)) {
            return [];
        }
        foreach (\scandir($this->storageRoot) as $entry) {
            if ($entry === '.' || $entry === '..' || $entry[0] === '.' || $entry[0] === '_') { continue; }
            $path = $this->storageRoot . '/' . $entry;
            if (\is_dir($path)) {
                $cols[] = $entry;
            }
        }
        return $cols;
    }

    /**
     * Devuelve [<rel-path-en-zip> => <abs-path-en-disco>] de los docs de la coleccion.
     * Si $sinceTs no es null, filtra por archivos cuyo _updatedAt > $sinceTs.
     */
    private function collectionDocs(string $col, ?string $sinceTs): array
    {
        $files = [];
        $dir   = $this->storageRoot . '/' . $col;
        foreach (\glob($dir . '/*.json') as $abs) {
            $base = \basename($abs);
            if ($base === '_index.json' || $base === '_meta.json' || $base[0] === '_') {
                continue;
            }
            if ($sinceTs !== null) {
                $doc = \json_decode(\file_get_contents($abs), true);
                $updated = $doc['_updatedAt'] ?? null;
                if (!\is_string($updated) || $updated <= $sinceTs) {
                    continue;
                }
            }
            $files[$col . '/' . $base] = $abs;
        }
        return $files;
    }
}
