<?php
/**
 * AxiDB - Op\Alter\RenameField: renombra un field en el schema.
 *
 * Subsistema: engine/op/alter
 * Entrada:    collection, from, to.
 * Salida:     Result con la meta actualizada.
 * Nota v1:    no renombra la clave en documentos existentes. Fase 1.4 anade --rewrite.
 */

namespace Axi\Engine\Op\Alter;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class RenameField extends Operation
{
    public const OP_NAME = 'rename_field';

    public function rename(string $from, string $to): self
    {
        $this->params['from'] = $from;
        $this->params['to']   = $to;
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        foreach (['from', 'to'] as $k) {
            if (empty($this->params[$k]) || !\is_string($this->params[$k])) {
                throw new AxiException("RenameField: '{$k}' requerido.", AxiException::VALIDATION_FAILED);
            }
        }
        if (!\preg_match('/^[a-z][a-z0-9_]*$/', $this->params['to'])) {
            throw new AxiException("RenameField: 'to' debe ser snake_case.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $meta = $engine->getService('meta');
        if (!$meta->exists($this->collection)) {
            throw new AxiException("RenameField: '{$this->collection}' no existe.", AxiException::COLLECTION_NOT_FOUND);
        }
        $current = $meta->readMeta($this->collection);
        $renamed = false;
        foreach ($current['fields'] ?? [] as &$f) {
            if (($f['name'] ?? null) === $this->params['from']) {
                $f['name'] = $this->params['to'];
                $renamed = true;
                break;
            }
        }
        unset($f);
        if (!$renamed) {
            throw new AxiException(
                "RenameField: field '{$this->params['from']}' no esta en schema.",
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
            name:        'rename_field',
            synopsis:    'Axi\\Op\\Alter\\RenameField(collection) ->rename("from", "to")',
            description: 'Renombra un field en el schema. v1 no reescribe documentos existentes.',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true],
                ['name' => 'from',       'type' => 'string', 'required' => true],
                ['name' => 'to',         'type' => 'string', 'required' => true, 'description' => 'snake_case.'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Alter\\RenameField('products'))\n    ->rename('precio', 'price'));"],
                ['lang' => 'json', 'code' => '{"op":"rename_field","collection":"products","from":"precio","to":"price"}'],
                ['lang' => 'cli',  'code' => 'axi alter table products rename-field precio price'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED,    'when' => 'from/to vacio o to no snake_case.'],
                ['code' => AxiException::COLLECTION_NOT_FOUND, 'when' => 'coleccion no existe.'],
                ['code' => AxiException::DOCUMENT_NOT_FOUND,   'when' => 'field "from" no esta en schema.'],
            ],
            related: ['AddField', 'DropField'],
        );
    }
}
