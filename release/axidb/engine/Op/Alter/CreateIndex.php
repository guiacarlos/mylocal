<?php
/**
 * AxiDB - Op\Alter\CreateIndex: declara un indice secundario sobre un field.
 *
 * Subsistema: engine/op/alter
 * Entrada:    collection, field, unique? (default false).
 * Salida:     Result con la meta actualizada.
 * Nota v1:    el indice se registra en _meta.indexes pero la construccion
 *             fisica (_index/<field>.idx) llega con StorageDriver en Fase 1.4.
 */

namespace Axi\Engine\Op\Alter;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class CreateIndex extends Operation
{
    public const OP_NAME = 'create_index';

    public function field(string $field, bool $unique = false): self
    {
        $this->params['field']  = $field;
        $this->params['unique'] = $unique;
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        if (empty($this->params['field']) || !\is_string($this->params['field'])) {
            throw new AxiException("CreateIndex: 'field' requerido.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $meta = $engine->getService('meta');
        if (!$meta->exists($this->collection)) {
            $meta->createCollection($this->collection);
        }
        $current = $meta->readMeta($this->collection);
        $current['indexes'] ??= [];
        $indexName = 'idx_' . $this->params['field'];
        foreach ($current['indexes'] as $ix) {
            if (($ix['name'] ?? null) === $indexName) {
                throw new AxiException("CreateIndex: '{$indexName}' ya existe.", AxiException::CONFLICT);
            }
        }
        $current['indexes'][] = [
            'name'   => $indexName,
            'field'  => $this->params['field'],
            'unique' => !empty($this->params['unique']),
        ];
        $current['updated_at'] = \date('c');
        $meta->writeMeta($this->collection, $current);
        return Result::ok($current);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'create_index',
            synopsis:    'Axi\\Op\\Alter\\CreateIndex(collection) ->field("field", unique?)',
            description: 'Declara un indice secundario. En v1 solo se registra en meta; la construccion fisica llega con StorageDriver (Fase 1.4).',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true],
                ['name' => 'field',      'type' => 'string', 'required' => true],
                ['name' => 'unique',     'type' => 'bool',   'required' => false, 'default' => false],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Op\\Alter\\CreateIndex('users'))\n    ->field('email', true));"],
                ['lang' => 'json',   'code' => '{"op":"create_index","collection":"users","field":"email","unique":true}'],
                ['lang' => 'axisql', 'code' => "CREATE UNIQUE INDEX ON users (email)"],
                ['lang' => 'cli',    'code' => 'axi alter table users create-index email --unique'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'field vacio.'],
                ['code' => AxiException::CONFLICT,          'when' => 'indice ya existe.'],
            ],
            related: ['DropIndex', 'AddField'],
        );
    }
}
