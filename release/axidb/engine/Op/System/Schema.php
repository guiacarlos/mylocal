<?php
/**
 * AxiDB - Op\System\Schema: devuelve el _meta.json de una coleccion.
 *
 * Subsistema: engine/op/system
 * Entrada:    collection.
 * Salida:     Result con el meta completo.
 */

namespace Axi\Engine\Op\System;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Schema extends Operation
{
    public const OP_NAME = 'schema';

    public function validate(): void
    {
        $this->requireCollection();
    }

    public function execute(object $engine): Result
    {
        $meta = $engine->getService('meta');
        if (!$meta->exists($this->collection)) {
            throw new AxiException(
                "Schema: coleccion '{$this->collection}' no existe.",
                AxiException::COLLECTION_NOT_FOUND
            );
        }
        return Result::ok($meta->readMeta($this->collection));
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'schema',
            synopsis:    'Axi\\Op\\System\\Schema(collection)',
            description: 'Devuelve _meta.json: fields, indexes, flags, timestamps.',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute(new Axi\\Op\\System\\Schema('products'));"],
                ['lang' => 'json', 'code' => '{"op":"schema","collection":"products"}'],
                ['lang' => 'cli',  'code' => 'axi schema describe products'],
            ],
            errors: [
                ['code' => AxiException::COLLECTION_NOT_FOUND, 'when' => 'coleccion no existe.'],
            ],
            related: ['Describe', 'AddField', 'CreateIndex'],
        );
    }
}
