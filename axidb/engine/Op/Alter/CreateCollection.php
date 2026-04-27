<?php
/**
 * AxiDB - Op\Alter\CreateCollection: crea coleccion con metadata inicial.
 *
 * Subsistema: engine/op/alter
 * Entrada:    collection, flags? (encrypted, keep_versions, strict_schema, strict_durability).
 * Salida:     Result con el _meta.json creado.
 */

namespace Axi\Engine\Op\Alter;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class CreateCollection extends Operation
{
    public const OP_NAME = 'create_collection';

    public function flags(array $flags): self
    {
        $this->params['flags'] = $flags;
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        if (!\preg_match('/^[a-z][a-z0-9_]*$/', $this->collection)) {
            throw new AxiException(
                "CreateCollection: nombre debe ser snake_case (^[a-z][a-z0-9_]*\$).",
                AxiException::VALIDATION_FAILED
            );
        }
        if (isset($this->params['flags']) && !\is_array($this->params['flags'])) {
            throw new AxiException("CreateCollection: 'flags' debe ser array.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $meta = $engine->getService('meta');
        $created = $meta->createCollection($this->collection, $this->params['flags'] ?? []);
        return Result::ok($created);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'create_collection',
            synopsis:    'Axi\\Op\\Alter\\CreateCollection(collection) [->flags([...])]',
            description: 'Crea una coleccion y su _meta.json. Idempotente: si ya existe, devuelve la meta actual.',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true,  'description' => 'Nombre snake_case.'],
                ['name' => 'flags',      'type' => 'object', 'required' => false, 'description' => '{encrypted, keep_versions, strict_schema, strict_durability}.'],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Op\\Alter\\CreateCollection('notas'))\n    ->flags(['keep_versions' => true]));"],
                ['lang' => 'json',   'code' => '{"op":"create_collection","collection":"notas","flags":{"keep_versions":true}}'],
                ['lang' => 'axisql', 'code' => "CREATE COLLECTION notas WITH (keep_versions=true)"],
                ['lang' => 'cli',    'code' => 'axi alter collection create notas --keep-versions'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'nombre no snake_case, flags no es array.'],
            ],
            related: ['DropCollection', 'AlterCollection', 'RenameCollection'],
        );
    }
}
