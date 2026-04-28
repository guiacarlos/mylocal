<?php
/**
 * AxiDB - Op\Alter\DropCollection: elimina coleccion completa (docs + meta).
 *
 * Subsistema: engine/op/alter
 * Entrada:    collection.
 * Salida:     Result con {dropped: bool}.
 */

namespace Axi\Engine\Op\Alter;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class DropCollection extends Operation
{
    public const OP_NAME = 'drop_collection';

    public function validate(): void
    {
        $this->requireCollection();
    }

    public function execute(object $engine): Result
    {
        $meta = $engine->getService('meta');
        $ok = $meta->dropCollection($this->collection);
        if (!$ok) {
            throw new AxiException(
                "DropCollection: '{$this->collection}' no existe.",
                AxiException::COLLECTION_NOT_FOUND
            );
        }
        return Result::ok(['dropped' => true, 'collection' => $this->collection]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'drop_collection',
            synopsis:    'Axi\\Op\\Alter\\DropCollection(collection)',
            description: 'Elimina la coleccion completa, documentos e indices. Operacion destructiva: dispara snapshot automatico en Fase 3 si agent_id presente.',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute(new Axi\\Op\\Alter\\DropCollection('temp_draft'));"],
                ['lang' => 'json',   'code' => '{"op":"drop_collection","collection":"temp_draft"}'],
                ['lang' => 'axisql', 'code' => "DROP COLLECTION temp_draft"],
                ['lang' => 'cli',    'code' => 'axi alter collection drop temp_draft'],
            ],
            errors: [
                ['code' => AxiException::COLLECTION_NOT_FOUND, 'when' => 'coleccion no existe.'],
            ],
            related: ['CreateCollection', 'RenameCollection'],
        );
    }
}
