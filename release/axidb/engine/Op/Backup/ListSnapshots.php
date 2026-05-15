<?php
/**
 * AxiDB - Op\Backup\ListSnapshots: lista snapshots existentes con su manifest.
 *
 * Subsistema: engine/op/backup
 * Salida: Result con {snapshots: [{name, type, ts, counts, ...}, ...]}.
 *
 * Nota: el OP_NAME es "backup.list" (espacio de nombres del registry); el
 * nombre de clase es ListSnapshots para evitar colision con palabra reservada.
 */

namespace Axi\Engine\Op\Backup;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class ListSnapshots extends Operation
{
    public const OP_NAME = 'backup.list';

    public function validate(): void
    {
        // Sin params.
    }

    public function execute(object $engine): Result
    {
        $store = $engine->getService('backup');
        if (!$store) {
            throw new AxiException("Backup: servicio no disponible.", AxiException::INTERNAL_ERROR);
        }
        $items = [];
        foreach ($store->listSnapshots() as $name) {
            $items[] = $store->readManifest($name)->toArray();
        }
        return Result::ok(['snapshots' => $items, 'count' => \count($items)]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'backup.list',
            synopsis:    'Axi\\Op\\Backup\\ListSnapshots()',
            description: 'Devuelve todos los snapshots con sus manifests (name, type, ts, counts, base_snapshot).',
            params: [],
            examples: [
                ['lang' => 'php',  'code' => "\$r = \$db->execute(new Axi\\Op\\Backup\\ListSnapshots());"],
                ['lang' => 'json', 'code' => '{"op":"backup.list"}'],
                ['lang' => 'cli',  'code' => 'axi backup list'],
            ],
            errors: [],
            related: ['Create', 'Restore', 'Drop'],
        );
    }
}
