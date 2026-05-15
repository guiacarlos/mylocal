<?php
/**
 * AxiDB - Op\System\Explain: devuelve el plan de ejecucion de un Op sin ejecutarlo.
 *
 * Subsistema: engine/op/system
 * Entrada:    op: array (Op serializada).
 * Salida:     Result con {op_class, normalized_params, would_scan: collection, estimated_cost: string}.
 * Nota v1:    estimacion muy basica. Fase 2 (AxiSQL) anade plan con uso de indices.
 */

namespace Axi\Engine\Op\System;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Explain extends Operation
{
    public const OP_NAME = 'explain';

    public function forOp(array $opData): self
    {
        $this->params['target'] = $opData;
        return $this;
    }

    public function validate(): void
    {
        if (!isset($this->params['target']) || !\is_array($this->params['target'])) {
            throw new AxiException("Explain: 'target' requerido (array serializado de Op).", AxiException::VALIDATION_FAILED);
        }
        if (!isset($this->params['target']['op'])) {
            throw new AxiException("Explain: 'target.op' (nombre de la operacion) requerido.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $opData = $this->params['target'];
        $name   = $opData['op'];

        $plan = [
            'op_name'           => $name,
            'normalized_params' => $opData,
            'would_scan'        => $opData['collection'] ?? null,
            'indexes_used'      => [],                               // v1: no se usa indice real.
            'estimated_cost'    => 'linear scan (v1, no index runtime)',
            'note'              => 'Fase 2 anade plan con uso de indices y estimacion realista.',
        ];

        return Result::ok($plan);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'explain',
            synopsis:    'Axi\\Op\\System\\Explain() ->forOp([...])',
            description: 'Devuelve el plan de ejecucion para un Op dado sin ejecutarlo. Util para debugging.',
            params: [
                ['name' => 'target', 'type' => 'object', 'required' => true, 'description' => 'Op serializada (con "op", "collection", etc.). Se llama target para evitar colision con la clave "op" del dispatcher.'],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Op\\System\\Explain())\n    ->forOp(['op' => 'select', 'collection' => 'products', 'where' => [['field'=>'price','op'=>'<','value'=>3]]]));"],
                ['lang' => 'json',   'code' => '{"op":"explain","target":{"op":"select","collection":"products"}}'],
                ['lang' => 'axisql', 'code' => "EXPLAIN SELECT * FROM products WHERE price < 3"],
                ['lang' => 'cli',    'code' => 'axi explain "SELECT * FROM products WHERE price < 3"'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'op ausente, malformado, o falta op.op.'],
            ],
            related: ['Select', 'Count'],
        );
    }
}
