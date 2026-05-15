<?php
/**
 * AxiDB - Op\System\Describe: lista todas las colecciones con stats basicos.
 *
 * Subsistema: engine/op/system
 * Salida:     Result con [{collection, count, fields, indexes, flags}].
 */

namespace Axi\Engine\Op\System;

use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Describe extends Operation
{
    public const OP_NAME = 'describe';

    public function validate(): void
    {
        // Sin params; describe todas las colecciones del storage activo.
    }

    public function execute(object $engine): Result
    {
        $meta    = $engine->getService('meta');
        $base    = $this->discoverStorageBase($engine);
        $items   = [];

        if (!\is_string($base) || !\is_dir($base)) {
            return Result::ok(['collections' => []]);
        }

        foreach (\scandir($base) as $entry) {
            if ($entry === '.' || $entry === '..' || $entry[0] === '.') {
                continue;
            }
            $path = $base . '/' . $entry;
            if (!\is_dir($path)) {
                continue;
            }
            $docs = \array_filter(\scandir($path), fn($f) => $f !== '.' && $f !== '..' && $f[0] !== '_' && \str_ends_with($f, '.json'));
            $m    = $meta->readMeta($entry);
            $items[] = [
                'collection' => $entry,
                'count'      => \count($docs),
                'fields'     => $m['fields']  ?? [],
                'indexes'    => $m['indexes'] ?? [],
                'flags'      => $m['flags']   ?? [],
            ];
        }

        return Result::ok(['collections' => $items]);
    }

    private function discoverStorageBase(object $engine): ?string
    {
        if (\defined('STORAGE_ROOT') && STORAGE_ROOT) {
            return STORAGE_ROOT;
        }
        if (\defined('DATA_ROOT') && DATA_ROOT) {
            return DATA_ROOT;
        }
        return null;
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'describe',
            synopsis:    'Axi\\Op\\System\\Describe()',
            description: 'Lista todas las colecciones con conteo de documentos, fields y flags.',
            params:      [],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute(new Axi\\Op\\System\\Describe());"],
                ['lang' => 'json', 'code' => '{"op":"describe"}'],
                ['lang' => 'cli',  'code' => 'axi describe'],
            ],
            errors:  [],
            related: ['Schema', 'Ping'],
        );
    }
}
