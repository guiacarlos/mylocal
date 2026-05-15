<?php
/**
 * AxiDB - Op\Alter\AddField: registra un field en el schema de una coleccion.
 *
 * Subsistema: engine/op/alter
 * Entrada:    collection, field (name), type, required?, default?.
 * Salida:     Result con la meta actualizada.
 * Nota v1:    no backfillea documentos existentes (los docs son JSON flexibles).
 *             El schema se usa para validar NUEVOS docs si strict_schema=true.
 */

namespace Axi\Engine\Op\Alter;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class AddField extends Operation
{
    public const OP_NAME = 'add_field';

    public function field(string $name, string $type = 'string', bool $required = false, mixed $default = null): self
    {
        $this->params['field'] = [
            'name'     => $name,
            'type'     => $type,
            'required' => $required,
            'default'  => $default,
        ];
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        $f = $this->params['field'] ?? null;
        if (!\is_array($f) || empty($f['name']) || empty($f['type'])) {
            throw new AxiException("AddField: 'field' requiere al menos {name, type}.", AxiException::VALIDATION_FAILED);
        }
        if (!\preg_match('/^[a-z][a-z0-9_]*$/', $f['name'])) {
            throw new AxiException("AddField: name debe ser snake_case.", AxiException::VALIDATION_FAILED);
        }
        if ($f['name'][0] === '_') {
            throw new AxiException("AddField: nombres con '_' son reservados del sistema.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $meta = $engine->getService('meta');
        if (!$meta->exists($this->collection)) {
            $meta->createCollection($this->collection);
        }
        $current = $meta->readMeta($this->collection);
        $current['fields'] ??= [];
        foreach ($current['fields'] as $existing) {
            if (($existing['name'] ?? null) === $this->params['field']['name']) {
                throw new AxiException(
                    "AddField: field '{$this->params['field']['name']}' ya existe.",
                    AxiException::CONFLICT
                );
            }
        }
        $current['fields'][] = $this->params['field'];
        $current['updated_at'] = \date('c');
        $meta->writeMeta($this->collection, $current);
        return Result::ok($current);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'add_field',
            synopsis:    'Axi\\Op\\Alter\\AddField(collection) ->field(name, type, required?, default?)',
            description: 'Registra un field en el schema de la coleccion. No altera docs existentes.',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true],
                ['name' => 'field',      'type' => 'object', 'required' => true, 'description' => '{name, type, required?, default?}'],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Op\\Alter\\AddField('products'))\n    ->field('discount', 'number', false, 0));"],
                ['lang' => 'json',   'code' => '{"op":"add_field","collection":"products","field":{"name":"discount","type":"number","required":false,"default":0}}'],
                ['lang' => 'axisql', 'code' => "ALTER COLLECTION products ADD FIELD discount number DEFAULT 0"],
                ['lang' => 'cli',    'code' => 'axi alter table products add-field discount number --default 0'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'name vacio, no snake_case, o reservado (_*).'],
                ['code' => AxiException::CONFLICT,          'when' => 'field ya existe.'],
            ],
            related: ['DropField', 'RenameField', 'CreateIndex'],
        );
    }
}
