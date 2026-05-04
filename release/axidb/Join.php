<?php
/**
 * AxiDB - Axi\Join: stub para JOIN entre colecciones (Fase 2+).
 *
 * Subsistema: sugar
 * Estado:    JOIN esta fuera de scope v1 (ver plan §1.3 "Fuera de scope v1: JOIN").
 *            Esta clase existe para que el nombre este reservado y el SDK
 *            exponga el contrato desde Fase 1, devolviendo NOT_IMPLEMENTED
 *            en execute() hasta que Fase 2 lo implemente.
 */

namespace Axi;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

final class Join extends Operation
{
    public const OP_NAME = 'join';

    public function with(string $leftCollection, string $rightCollection): self
    {
        $this->collection           = $leftCollection;
        $this->params['right']      = $rightCollection;
        return $this;
    }

    public function on(string $leftField, string $rightField): self
    {
        $this->params['on'] = ['left' => $leftField, 'right' => $rightField];
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        if (empty($this->params['right']) || !\is_string($this->params['right'])) {
            throw new AxiException("Join: 'right' collection requerida.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        throw new AxiException(
            "Join no implementado en v1. JOIN entre colecciones llega en Fase 2 con AxiSQL parser.",
            AxiException::NOT_IMPLEMENTED
        );
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'join',
            synopsis:    'Axi\\Join() ->with(left, right) ->on(leftField, rightField)',
            description: 'JOIN entre dos colecciones por field. [FASE 2] Fuera de scope v1.',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true,  'description' => 'Left collection.'],
                ['name' => 'right',      'type' => 'string', 'required' => true,  'description' => 'Right collection.'],
                ['name' => 'on',         'type' => 'object', 'required' => true,  'description' => '{left: field, right: field}.'],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Join())\n    ->with('orders', 'users')\n    ->on('user_id', '_id'));"],
                ['lang' => 'axisql', 'code' => "SELECT * FROM orders JOIN users ON orders.user_id = users._id"],
            ],
            errors: [
                ['code' => AxiException::NOT_IMPLEMENTED, 'when' => 'Siempre en v1.'],
            ],
            related: ['Select'],
        );
    }
}
