<?php
/**
 * AxiDB - Op\Batch: ejecuta N Ops en secuencia (todo-o-nada logica).
 *
 * Subsistema: engine/op
 * Responsable: agrupar varias operaciones en una unica llamada. En v1 la
 *              atomicidad real (rollback) no esta garantizada — requiere
 *              StorageDriver con transacciones (Fase 1.4+). En v1 si falla
 *              en la operacion N, las N-1 previas ya estan persistidas.
 * Entrada:    ops: Operation[] o array[] (serializadas).
 * Salida:     Result con {results: Result[], failed_at: int|null}.
 */

namespace Axi\Engine\Op;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Result;

class Batch extends Operation
{
    public const OP_NAME = 'batch';

    public function add(Operation $op): self
    {
        $this->params['ops'][] = $op;
        return $this;
    }

    public function validate(): void
    {
        if (empty($this->params['ops']) || !\is_array($this->params['ops'])) {
            throw new AxiException("Batch: 'ops' requerido (array no vacio).", AxiException::VALIDATION_FAILED);
        }
        if (\count($this->params['ops']) > 500) {
            throw new AxiException("Batch: maximo 500 Ops por batch en v1.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $results   = [];
        $failedAt  = null;
        foreach ($this->params['ops'] as $i => $op) {
            try {
                if (\is_array($op)) {
                    $singleResult = $engine->execute($op);
                } elseif ($op instanceof Operation) {
                    $singleResult = $engine->execute($op);
                } else {
                    throw new AxiException("Batch: ops[{$i}] no es Operation ni array.", AxiException::VALIDATION_FAILED);
                }
                $results[] = $singleResult;
                if (!($singleResult['success'] ?? false)) {
                    $failedAt = $i;
                    break;
                }
            } catch (\Throwable $e) {
                $failedAt = $i;
                $results[] = [
                    'success' => false,
                    'error'   => $e->getMessage(),
                    'code'    => $e instanceof AxiException ? $e->getAxiCode() : AxiException::INTERNAL_ERROR,
                ];
                break;
            }
        }

        return Result::ok([
            'results'    => $results,
            'total'      => \count($this->params['ops']),
            'executed'   => \count($results),
            'failed_at'  => $failedAt,
        ]);
    }

    public static function fromArray(array $data): static
    {
        $op = new static();
        $op->namespace  = $data['namespace']  ?? 'default';
        $op->collection = $data['collection'] ?? '';
        // ops viene como array de dicts con 'op': 'select'/'insert'/... — dejamos que Axi::execute los reparta.
        $op->params['ops'] = $data['ops'] ?? [];
        return $op;
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'batch',
            synopsis:    'Axi\\Op\\Batch() ->add(Operation1) ->add(Operation2) ...',
            description: 'Ejecuta multiples Ops en secuencia. Si una falla, se detiene el batch (las ya ejecutadas quedan aplicadas). En v1 sin rollback transaccional.',
            params: [
                ['name' => 'ops', 'type' => 'Operation[]|array[]', 'required' => true, 'description' => 'Lista de Ops (objetos o JSON serializado).'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Batch())\n    ->add(new Axi\\Op\\Insert('notas'))\n    ->add(new Axi\\Op\\Insert('notas')));"],
                ['lang' => 'json', 'code' => '{"op":"batch","ops":[{"op":"insert","collection":"notas","data":{"title":"a"}},{"op":"insert","collection":"notas","data":{"title":"b"}}]}'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'ops vacio, no array, o >500 elementos.'],
            ],
            related: ['Insert', 'Update', 'Delete'],
        );
    }
}
