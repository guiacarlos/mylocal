<?php
/**
 * AxiDB - Op\Count: conteo de documentos con filtros opcionales.
 *
 * Subsistema: engine/op
 * Entrada:    collection, where[] (opcional).
 * Salida:     Result con {count: int}.
 */

namespace Axi\Engine\Op;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Result;

class Count extends Operation
{
    public const OP_NAME = 'count';

    public function where(string $field, string $op, mixed $value): self
    {
        $this->params['where'][] = ['field' => $field, 'op' => $op, 'value' => $value];
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        foreach ($this->params['where'] ?? [] as $i => $c) {
            if (!isset($c['field'], $c['op']) || !\array_key_exists('value', $c)) {
                throw new AxiException("Count: where[{$i}] malformado.", AxiException::VALIDATION_FAILED);
            }
        }
    }

    public function execute(object $engine): Result
    {
        // Ruta AxiSQL: where_expr tree evaluado en PHP sobre lista completa.
        if (isset($this->params['where_expr'])) {
            $storage = $engine->getService('storage');
            $all = $storage->list($this->collection);
            $n = 0;
            foreach ($all as $doc) {
                if (\Axi\Sql\WhereEvaluator::matches($doc, $this->params['where_expr'])) {
                    $n++;
                }
            }
            return Result::ok(['count' => $n]);
        }

        $query = $engine->getService('query');
        $legacy = [];
        if (!empty($this->params['where'])) {
            $legacy['where'] = \array_map(
                fn($c) => [$c['field'], $c['op'], $c['value']],
                $this->params['where']
            );
        }
        $docs = $query->query($this->collection, $legacy);
        $items = \is_array($docs) && isset($docs['items']) ? $docs['items'] : ($docs ?: []);
        return Result::ok(['count' => \count($items)]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'count',
            synopsis:    'Axi\\Op\\Count(collection) [->where(...)]',
            description: 'Cuenta documentos en una coleccion. Acepta clausulas where identicas a Select.',
            params: [
                ['name' => 'collection', 'type' => 'string',   'required' => true],
                ['name' => 'where',      'type' => 'clause[]', 'required' => false],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Op\\Count('products'))->where('stock', '<', 10));"],
                ['lang' => 'json',   'code' => '{"op":"count","collection":"products","where":[{"field":"stock","op":"<","value":10}]}'],
                ['lang' => 'axisql', 'code' => "SELECT COUNT(*) FROM products WHERE stock < 10"],
                ['lang' => 'cli',    'code' => 'axi count products --where "stock<10"'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'where malformado.'],
            ],
            related: ['Select', 'Exists'],
        );
    }
}
