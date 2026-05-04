<?php
/**
 * AxiDB - Op\Alter\AlterCollection: cambia flags/metadata de una coleccion.
 *
 * Subsistema: engine/op/alter
 * Entrada:    collection, flags (merge sobre meta.flags).
 * Salida:     Result con la meta actualizada.
 */

namespace Axi\Engine\Op\Alter;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class AlterCollection extends Operation
{
    public const OP_NAME = 'alter_collection';

    public function setFlag(string $flag, mixed $value): self
    {
        $this->params['flags'][$flag] = $value;
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        if (!isset($this->params['flags']) || !\is_array($this->params['flags']) || $this->params['flags'] === []) {
            throw new AxiException("AlterCollection: 'flags' requerido (array no vacio).", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $meta = $engine->getService('meta');
        if (!$meta->exists($this->collection)) {
            throw new AxiException(
                "AlterCollection: '{$this->collection}' no existe.",
                AxiException::COLLECTION_NOT_FOUND
            );
        }
        $current = $meta->readMeta($this->collection);
        $current['flags'] = \array_merge($current['flags'] ?? [], $this->params['flags']);
        $current['updated_at'] = \date('c');
        $meta->writeMeta($this->collection, $current);
        return Result::ok($current);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'alter_collection',
            synopsis:    'Axi\\Op\\Alter\\AlterCollection(collection) ->setFlag(name, value)',
            description: 'Modifica flags de coleccion: encrypted, keep_versions, strict_schema, strict_durability.',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true],
                ['name' => 'flags',      'type' => 'object', 'required' => true],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Alter\\AlterCollection('users'))\n    ->setFlag('encrypted', true));"],
                ['lang' => 'json', 'code' => '{"op":"alter_collection","collection":"users","flags":{"encrypted":true}}'],
                ['lang' => 'cli',  'code' => 'axi alter collection users --set encrypted=true'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED,   'when' => 'flags ausente o vacio.'],
                ['code' => AxiException::COLLECTION_NOT_FOUND, 'when' => 'coleccion no existe.'],
            ],
            related: ['CreateCollection', 'AddField', 'DropField'],
        );
    }
}
