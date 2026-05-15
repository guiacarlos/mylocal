<?php
/**
 * AxiDB - Backup\Manifest: estructura JSON del manifest de un snapshot.
 *
 * Subsistema: engine/backup
 * Formato:    snapshots/<name>/manifest.json
 * Contenido:  {name, type: full|incremental, ts, base_snapshot?, collections,
 *              counts, axidb_version}
 */

namespace Axi\Engine\Backup;

final class Manifest
{
    public const TYPE_FULL        = 'full';
    public const TYPE_INCREMENTAL = 'incremental';

    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $ts,
        public readonly array  $collections,
        public readonly array  $counts,
        public readonly ?string $baseSnapshot = null,
        public readonly string $axidbVersion = 'v1.0-dev'
    ) {
    }

    public function toArray(): array
    {
        return [
            'name'           => $this->name,
            'type'           => $this->type,
            'ts'             => $this->ts,
            'base_snapshot'  => $this->baseSnapshot,
            'collections'    => $this->collections,
            'counts'         => $this->counts,
            'axidb_version'  => $this->axidbVersion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? '',
            $data['type'] ?? self::TYPE_FULL,
            $data['ts']   ?? \date('c'),
            $data['collections'] ?? [],
            $data['counts'] ?? [],
            $data['base_snapshot'] ?? null,
            $data['axidb_version'] ?? 'v1.0-dev'
        );
    }
}
