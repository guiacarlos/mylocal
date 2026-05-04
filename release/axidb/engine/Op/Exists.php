<?php
/**
 * AxiDB - Op\Exists: comprueba si un doc cumple una condicion o si existe por id.
 *
 * Subsistema: engine/op
 * Entrada:    collection; id O where[].
 * Salida:     Result con {exists: bool}.
 */

namespace Axi\Engine\Op;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Result;

class Exists extends Operation
{
    public const OP_NAME = 'exists';

    public function id(string $id): self
    {
        $this->params['id'] = $id;
        return $this;
    }

    public function where(string $field, string $op, mixed $value): self
    {
        $this->params['where'][] = ['field' => $field, 'op' => $op, 'value' => $value];
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        $hasId    = isset($this->params['id']);
        $hasWhere = !empty($this->params['where']);
        if (!$hasId && !$hasWhere) {
            throw new AxiException("Exists: requiere 'id' o 'where'.", AxiException::VALIDATION_FAILED);
        }
        if ($hasId && !\is_string($this->params['id'])) {
            throw new AxiException("Exists: 'id' debe ser string.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        if (isset($this->params['id'])) {
            $storage = $engine->getService('storage');
            $doc = $storage->read($this->collection, $this->params['id']);
            return Result::ok(['exists' => $doc !== null]);
        }

        $query = $engine->getService('query');
        $legacy = [
            'where' => \array_map(
                fn($c) => [$c['field'], $c['op'], $c['value']],
                $this->params['where']
            ),
            'limit' => 1,
        ];
        $docs = $query->query($this->collection, $legacy);
        $items = \is_array($docs) && isset($docs['items']) ? $docs['items'] : ($docs ?: []);
        return Result::ok(['exists' => \count($items) > 0]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'exists',
            synopsis:    'Axi\\Op\\Exists(collection) ->id("...") | ->where(...)',
            description: 'Devuelve true/false segun exista un doc por id o matching la clausula where.',
            params: [
                ['name' => 'collection', 'type' => 'string',   'required' => true],
                ['name' => 'id',         'type' => 'string',   'required' => false, 'description' => 'Id exacto. Alternativa a where.'],
                ['name' => 'where',      'type' => 'clause[]', 'required' => false, 'description' => 'Alternativa a id.'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Exists('users'))->where('email', '=', 'a@b.c'));"],
                ['lang' => 'json', 'code' => '{"op":"exists","collection":"users","id":"abc123"}'],
                ['lang' => 'cli',  'code' => 'axi exists users --where "email=a@b.c"'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'ni id ni where proporcionados.'],
            ],
            related: ['Count', 'Select'],
        );
    }
}
