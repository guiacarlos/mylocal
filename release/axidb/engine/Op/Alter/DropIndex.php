<?php
/**
 * AxiDB - Op\Alter\DropIndex: elimina un indice secundario.
 *
 * Subsistema: engine/op/alter
 * Entrada:    collection, field.
 * Salida:     Result con la meta actualizada.
 */

namespace Axi\Engine\Op\Alter;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class DropIndex extends Operation
{
    public const OP_NAME = 'drop_index';

    public function field(string $field): self
    {
        $this->params['field'] = $field;
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        if (empty($this->params['field']) || !\is_string($this->params['field'])) {
            throw new AxiException("DropIndex: 'field' requerido.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $meta = $engine->getService('meta');
        if (!$meta->exists($this->collection)) {
            throw new AxiException("DropIndex: '{$this->collection}' no existe.", AxiException::COLLECTION_NOT_FOUND);
        }
        $current = $meta->readMeta($this->collection);
        $before  = \count($current['indexes'] ?? []);
        $current['indexes'] = \array_values(\array_filter(
            $current['indexes'] ?? [],
            fn($ix) => ($ix['field'] ?? null) !== $this->params['field']
        ));
        if (\count($current['indexes']) === $before) {
            throw new AxiException(
                "DropIndex: indice para field '{$this->params['field']}' no existe.",
                AxiException::DOCUMENT_NOT_FOUND
            );
        }
        $current['updated_at'] = \date('c');
        $meta->writeMeta($this->collection, $current);
        return Result::ok($current);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'drop_index',
            synopsis:    'Axi\\Op\\Alter\\DropIndex(collection) ->field("field")',
            description: 'Elimina el indice secundario sobre un field.',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true],
                ['name' => 'field',      'type' => 'string', 'required' => true],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Op\\Alter\\DropIndex('users'))->field('email'));"],
                ['lang' => 'json',   'code' => '{"op":"drop_index","collection":"users","field":"email"}'],
                ['lang' => 'axisql', 'code' => "DROP INDEX ON users (email)"],
                ['lang' => 'cli',    'code' => 'axi alter table users drop-index email'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED,    'when' => 'field vacio.'],
                ['code' => AxiException::COLLECTION_NOT_FOUND, 'when' => 'coleccion no existe.'],
                ['code' => AxiException::DOCUMENT_NOT_FOUND,   'when' => 'indice para ese field no registrado.'],
            ],
            related: ['CreateIndex'],
        );
    }
}
