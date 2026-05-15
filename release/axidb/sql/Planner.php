<?php
/**
 * AxiDB - Sql\Planner: compila AST a secuencia de Ops ejecutables.
 *
 * Subsistema: sql
 * Responsable: transformar el AST (del Parser) en una Operation o Batch
 *              que el dispatcher del motor puede ejecutar. No ejecuta nada.
 * Entrada:    AST array ('type' => 'select'|'insert'|...).
 * Salida:     Axi\Engine\Op\Operation.
 */

namespace Axi\Sql;

use Axi\Engine\AxiException;
use Axi\Engine\Op\Alter\AddField;
use Axi\Engine\Op\Alter\CreateCollection;
use Axi\Engine\Op\Alter\CreateIndex;
use Axi\Engine\Op\Alter\DropCollection;
use Axi\Engine\Op\Alter\DropField;
use Axi\Engine\Op\Alter\DropIndex;
use Axi\Engine\Op\Count;
use Axi\Engine\Op\Delete;
use Axi\Engine\Op\Insert;
use Axi\Engine\Op\Operation;
use Axi\Engine\Op\Select;
use Axi\Engine\Op\Update;

final class Planner
{
    public function plan(array $ast): Operation
    {
        return match ($ast['type']) {
            'select'           => $this->planSelect($ast),
            'count'            => $this->planCount($ast),
            'insert'           => $this->planInsert($ast),
            'update'           => $this->planUpdate($ast),
            'delete'           => $this->planDelete($ast),
            'create_collection'=> $this->planCreateCollection($ast),
            'drop_collection'  => $this->planDropCollection($ast),
            'create_index'     => $this->planCreateIndex($ast),
            'drop_index'       => $this->planDropIndex($ast),
            'alter_add_field'  => $this->planAddField($ast),
            'alter_drop_field' => $this->planDropField($ast),
            default => throw new AxiException(
                "Planner: tipo AST desconocido '{$ast['type']}'.",
                AxiException::INTERNAL_ERROR
            ),
        };
    }

    private function planSelect(array $ast): Select
    {
        $op = new Select($ast['collection']);
        if ($ast['where_expr'] !== null) {
            $op->params['where_expr'] = $ast['where_expr'];
        }
        foreach ($ast['order_by'] as $clause) {
            $op->orderBy($clause['field'], $clause['dir']);
        }
        if ($ast['limit'] !== null)  { $op->limit($ast['limit']); }
        if ($ast['offset'] !== null) { $op->offset($ast['offset']); }
        if ($ast['fields'] !== ['*']) {
            $op->fields($ast['fields']);
        }
        return $op;
    }

    private function planCount(array $ast): Count
    {
        $op = new Count($ast['collection']);
        if ($ast['where_expr'] !== null) {
            $op->params['where_expr'] = $ast['where_expr'];
        }
        return $op;
    }

    private function planInsert(array $ast): Insert
    {
        return (new Insert($ast['collection']))->data($ast['data']);
    }

    private function planUpdate(array $ast): Update
    {
        $op = (new Update($ast['collection']))->data($ast['set']);
        if ($ast['where_expr'] !== null) {
            $op->params['where_expr'] = $ast['where_expr'];
        }
        return $op;
    }

    private function planDelete(array $ast): Delete
    {
        $op = new Delete($ast['collection']);
        if ($ast['where_expr'] !== null) {
            $op->params['where_expr'] = $ast['where_expr'];
        }
        return $op;
    }

    private function planCreateCollection(array $ast): CreateCollection
    {
        $op = new CreateCollection($ast['collection']);
        if (!empty($ast['flags'])) {
            $op->flags($ast['flags']);
        }
        return $op;
    }

    private function planDropCollection(array $ast): DropCollection
    {
        return new DropCollection($ast['collection']);
    }

    private function planCreateIndex(array $ast): CreateIndex
    {
        return (new CreateIndex($ast['collection']))
            ->field($ast['field'], $ast['unique'] ?? false);
    }

    private function planDropIndex(array $ast): DropIndex
    {
        return (new DropIndex($ast['collection']))->field($ast['field']);
    }

    private function planAddField(array $ast): AddField
    {
        $f = $ast['field'];
        return (new AddField($ast['collection']))->field(
            $f['name'],
            $f['type'] ?? 'string',
            false,
            $f['default'] ?? null
        );
    }

    private function planDropField(array $ast): DropField
    {
        return (new DropField($ast['collection']))->name($ast['name']);
    }
}
