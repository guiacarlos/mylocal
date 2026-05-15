<?php
/**
 * AxiDB - Op\Alter\RenameCollection: renombra coleccion.
 *
 * Subsistema: engine/op/alter
 * Entrada:    collection (from), to.
 * Salida:     Result con {renamed: true, from, to}.
 */

namespace Axi\Engine\Op\Alter;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class RenameCollection extends Operation
{
    public const OP_NAME = 'rename_collection';

    public function to(string $newName): self
    {
        $this->params['to'] = $newName;
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        if (!isset($this->params['to']) || !\is_string($this->params['to']) || $this->params['to'] === '') {
            throw new AxiException("RenameCollection: 'to' requerido.", AxiException::VALIDATION_FAILED);
        }
        if (!\preg_match('/^[a-z][a-z0-9_]*$/', $this->params['to'])) {
            throw new AxiException(
                "RenameCollection: 'to' debe ser snake_case.",
                AxiException::VALIDATION_FAILED
            );
        }
    }

    public function execute(object $engine): Result
    {
        $meta = $engine->getService('meta');
        if (!$meta->exists($this->collection)) {
            throw new AxiException(
                "RenameCollection: '{$this->collection}' no existe.",
                AxiException::COLLECTION_NOT_FOUND
            );
        }
        $ok = $meta->renameCollection($this->collection, $this->params['to']);
        if (!$ok) {
            throw new AxiException(
                "RenameCollection: destino '{$this->params['to']}' ya existe o fallo E/S.",
                AxiException::CONFLICT
            );
        }
        return Result::ok([
            'renamed' => true,
            'from'    => $this->collection,
            'to'      => $this->params['to'],
        ]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'rename_collection',
            synopsis:    'Axi\\Op\\Alter\\RenameCollection(from) ->to("new_name")',
            description: 'Renombra la carpeta de coleccion. Falla si el destino ya existe.',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true,  'description' => 'Nombre actual.'],
                ['name' => 'to',         'type' => 'string', 'required' => true,  'description' => 'Nombre nuevo (snake_case).'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Alter\\RenameCollection('notas_v1'))->to('notas'));"],
                ['lang' => 'json', 'code' => '{"op":"rename_collection","collection":"notas_v1","to":"notas"}'],
                ['lang' => 'cli',  'code' => 'axi alter collection rename notas_v1 notas'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED,   'when' => 'to no snake_case o vacio.'],
                ['code' => AxiException::COLLECTION_NOT_FOUND, 'when' => 'origen no existe.'],
                ['code' => AxiException::CONFLICT,             'when' => 'destino ya existe.'],
            ],
            related: ['CreateCollection', 'DropCollection', 'AlterCollection'],
        );
    }
}
