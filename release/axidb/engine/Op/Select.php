<?php
/**
 * AxiDB - Op\Select: lectura de documentos con filtros, orden y paginacion.
 *
 * Subsistema: engine/op
 * Responsable: compila a una consulta sobre QueryEngine.
 * Entrada:    collection, where[], order_by[], limit, offset, fields[]
 * Salida:     Result con array de documentos (filtrados si fields != ['*']).
 */

namespace Axi\Engine\Op;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Result;

class Select extends Operation
{
    public const OP_NAME = 'select';

    public function where(string $field, string $op, mixed $value): self
    {
        $this->params['where'][] = ['field' => $field, 'op' => $op, 'value' => $value];
        return $this;
    }

    public function orderBy(string $field, string $dir = 'asc'): self
    {
        $this->params['order_by'][] = ['field' => $field, 'dir' => \strtolower($dir)];
        return $this;
    }

    public function limit(int $n): self
    {
        $this->params['limit'] = $n;
        return $this;
    }

    public function offset(int $n): self
    {
        $this->params['offset'] = $n;
        return $this;
    }

    public function fields(array $fields): self
    {
        $this->params['fields'] = $fields;
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();

        foreach ($this->params['where'] ?? [] as $i => $clause) {
            if (!isset($clause['field'], $clause['op']) || !\array_key_exists('value', $clause)) {
                throw new AxiException(
                    "Select: where[{$i}] debe tener field, op y value.",
                    AxiException::VALIDATION_FAILED
                );
            }
        }
        foreach ($this->params['order_by'] ?? [] as $i => $clause) {
            if (!isset($clause['field'])) {
                throw new AxiException(
                    "Select: order_by[{$i}] debe tener field.",
                    AxiException::VALIDATION_FAILED
                );
            }
            $dir = \strtolower($clause['dir'] ?? 'asc');
            if (!\in_array($dir, ['asc', 'desc'], true)) {
                throw new AxiException(
                    "Select: order_by[{$i}].dir debe ser 'asc' o 'desc'.",
                    AxiException::VALIDATION_FAILED
                );
            }
        }
        if (isset($this->params['limit']) && (!\is_int($this->params['limit']) || $this->params['limit'] < 0)) {
            throw new AxiException("Select: limit debe ser entero >= 0.", AxiException::VALIDATION_FAILED);
        }
        if (isset($this->params['offset']) && (!\is_int($this->params['offset']) || $this->params['offset'] < 0)) {
            throw new AxiException("Select: offset debe ser entero >= 0.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        // Si hay where_expr (arbol AST de AxiSQL), evaluamos en PHP tras listar todos los docs.
        if (isset($this->params['where_expr'])) {
            return $this->executeWithExpr($engine);
        }

        $query = $engine->getService('query');
        if (!$query) {
            throw new AxiException("QueryEngine service no disponible.", AxiException::INTERNAL_ERROR);
        }

        $legacyParams = [];
        if (!empty($this->params['where'])) {
            $legacyParams['where'] = $this->mapWhere();
        }
        if (!empty($this->params['order_by'])) {
            $first = $this->params['order_by'][0];
            $legacyParams['orderBy'] = [
                'field'     => $first['field'],
                'direction' => \strtolower($first['dir'] ?? 'asc'),
            ];
        }
        if (isset($this->params['limit']))  { $legacyParams['limit']  = $this->params['limit']; }
        if (isset($this->params['offset'])) { $legacyParams['offset'] = $this->params['offset']; }

        $docs  = $query->query($this->collection, $legacyParams);
        $items = \is_array($docs) && isset($docs['items']) ? $docs['items'] : ($docs ?: []);
        $items = $this->maybeDecrypt($engine, $items);
        $items = $this->projectFields($items);

        return Result::ok([
            'items' => $items,
            'count' => \count($items),
            'total' => $docs['total'] ?? \count($items),
        ]);
    }

    private function executeWithExpr(object $engine): Result
    {
        $storage = $engine->getService('storage');
        $all     = $storage->list($this->collection);
        $all     = $this->maybeDecrypt($engine, $all);
        $filtered = [];
        foreach ($all as $doc) {
            if (\Axi\Sql\WhereEvaluator::matches($doc, $this->params['where_expr'])) {
                $filtered[] = $doc;
            }
        }
        // Ordenacion multi-clave (la primera clausula tiene prioridad).
        if (!empty($this->params['order_by'])) {
            $clauses = $this->params['order_by'];
            \usort($filtered, static function ($a, $b) use ($clauses) {
                foreach ($clauses as $c) {
                    $va = $a[$c['field']] ?? null;
                    $vb = $b[$c['field']] ?? null;
                    if ($va === $vb) { continue; }
                    $cmp = ($va > $vb) ? 1 : -1;
                    return ($c['dir'] === 'desc') ? -$cmp : $cmp;
                }
                return 0;
            });
        }
        $total = \count($filtered);
        $offset = $this->params['offset'] ?? 0;
        $limit  = $this->params['limit']  ?? null;
        if ($limit !== null) {
            $filtered = \array_slice($filtered, $offset, $limit);
        } elseif ($offset > 0) {
            $filtered = \array_slice($filtered, $offset);
        }
        $filtered = $this->projectFields($filtered);
        return Result::ok([
            'items' => $filtered,
            'count' => \count($filtered),
            'total' => $total,
        ]);
    }

    private function mapWhere(): array
    {
        return \array_map(
            fn($c) => [$c['field'], $c['op'], $c['value']],
            $this->params['where']
        );
    }

    private function projectFields(array $items): array
    {
        $fields = $this->params['fields'] ?? ['*'];
        if ($fields === ['*']) {
            return $items;
        }
        $flip = \array_flip($fields);
        return \array_map(fn($d) => \array_intersect_key($d, $flip), $items);
    }

    /**
     * Si la coleccion tiene flag encrypted, descifra los documentos antes de
     * proyectar/devolver. Si vault esta locked, propaga AxiException UNAUTHORIZED.
     */
    private function maybeDecrypt(object $engine, array $items): array
    {
        $meta = $engine->getService('meta');
        if (!$meta || !($meta->readMeta($this->collection)['flags']['encrypted'] ?? false)) {
            return $items;
        }
        $vault = $engine->getService('vault');
        return \array_map(fn($d) => $vault->decryptDoc($d), $items);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'select',
            synopsis:    'Axi\\Op\\Select(collection) ->where(...)->orderBy(...)->limit(n)',
            description: 'Lectura de documentos de una coleccion con filtros where, orden y paginacion.',
            params: [
                ['name' => 'collection', 'type' => 'string',   'required' => true,  'description' => 'Nombre de la coleccion.'],
                ['name' => 'fields',     'type' => 'string[]', 'required' => false, 'default' => ['*'], 'description' => 'Proyeccion de campos. [*] devuelve todo.'],
                ['name' => 'where',      'type' => 'clause[]', 'required' => false, 'description' => '[{field, op, value}]. Operadores: =, !=, >, <, >=, <=, IN, contains.'],
                ['name' => 'order_by',   'type' => 'clause[]', 'required' => false, 'description' => '[{field, dir: asc|desc}]. v1 aplica solo la primera clausula.'],
                ['name' => 'limit',      'type' => 'int',      'required' => false, 'description' => 'Numero maximo de documentos a devolver.'],
                ['name' => 'offset',     'type' => 'int',      'required' => false, 'default' => 0, 'description' => 'Paginacion.'],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Op\\Select('products'))\n    ->where('price', '<', 3)\n    ->orderBy('price')\n    ->limit(20));"],
                ['lang' => 'json',   'code' => '{"op":"select","collection":"products","where":[{"field":"price","op":"<","value":3}],"limit":20}'],
                ['lang' => 'axisql', 'code' => "SELECT * FROM products WHERE price < 3 ORDER BY price LIMIT 20"],
                ['lang' => 'cli',    'code' => 'axi select products --where "price<3" --order-by price --limit 20'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED,    'when' => 'collection vacia, where/order_by malformado, limit/offset negativos.'],
                ['code' => AxiException::COLLECTION_NOT_FOUND, 'when' => 'La coleccion no existe y strict_collection=true.'],
            ],
            related: ['Count', 'Exists', 'Explain', 'Insert'],
        );
    }
}
