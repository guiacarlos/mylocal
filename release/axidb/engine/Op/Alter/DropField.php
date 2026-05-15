<?php
/**
 * AxiDB - Op\Alter\DropField: elimina un field del schema.
 *
 * Subsistema: engine/op/alter
 * Entrada:    collection, name.
 * Salida:     Result con la meta actualizada.
 * Nota v1:    no borra el campo de documentos existentes (quedan como legacy).
 *             Fase 1.4 anade --purge para reescribir los docs.
 */

namespace Axi\Engine\Op\Alter;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class DropField extends Operation
{
    public const OP_NAME = 'drop_field';

    public function name(string $name): self
    {
        $this->params['name'] = $name;
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        if (empty($this->params['name']) || !\is_string($this->params['name'])) {
            throw new AxiException("DropField: 'name' requerido.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $meta = $engine->getService('meta');
        if (!$meta->exists($this->collection)) {
            throw new AxiException("DropField: '{$this->collection}' no existe.", AxiException::COLLECTION_NOT_FOUND);
        }
        $current = $meta->readMeta($this->collection);
        $before  = \count($current['fields'] ?? []);
        $current['fields'] = \array_values(\array_filter(
            $current['fields'] ?? [],
            fn($f) => ($f['name'] ?? null) !== $this->params['name']
        ));
        if (\count($current['fields']) === $before) {
            throw new AxiException(
                "DropField: field '{$this->params['name']}' no existe en schema.",
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
            name:        'drop_field',
            synopsis:    'Axi\\Op\\Alter\\DropField(collection) ->name("field_name")',
            description: 'Elimina un field del schema. En v1 NO purga el campo de documentos existentes.',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true],
                ['name' => 'name',       'type' => 'string', 'required' => true],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Op\\Alter\\DropField('products'))->name('legacy_flag'));"],
                ['lang' => 'json',   'code' => '{"op":"drop_field","collection":"products","name":"legacy_flag"}'],
                ['lang' => 'axisql', 'code' => "ALTER COLLECTION products DROP FIELD legacy_flag"],
                ['lang' => 'cli',    'code' => 'axi alter table products drop-field legacy_flag'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED,    'when' => 'name vacio.'],
                ['code' => AxiException::COLLECTION_NOT_FOUND, 'when' => 'coleccion no existe.'],
                ['code' => AxiException::DOCUMENT_NOT_FOUND,   'when' => 'field no esta en schema.'],
            ],
            related: ['AddField', 'RenameField'],
        );
    }
}
