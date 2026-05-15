<?php
/**
 * AxiDB - Op\System\Help: devuelve el HelpEntry de cualquier Op.
 *
 * Subsistema: engine/op/system
 * Responsable: exponer la ayuda via HTTP/CLI sin reimplementar logica.
 * Entrada:    target: string (nombre del Op, p.ej. "select", "ai.ask").
 * Salida:     Result con el HelpEntry serializado a array.
 * Ver:        plan §2.8 (tres vistas de la misma fuente).
 */

namespace Axi\Engine\Op\System;

use Axi\Engine\Axi;
use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Help extends Operation
{
    public const OP_NAME = 'help';

    public function forOp(string $opName): self
    {
        $this->params['target'] = $opName;
        return $this;
    }

    public function validate(): void
    {
        if (isset($this->params['target']) && !\is_string($this->params['target'])) {
            throw new AxiException("Help: 'target' debe ser string si se proporciona.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $registry = Axi::opRegistry();

        // Sin target: devuelve indice {op_name: {name, description, since}}.
        if (!isset($this->params['target']) || $this->params['target'] === '') {
            $index = [];
            foreach ($registry as $op => $class) {
                $h = $class::help();
                $index[$op] = [
                    'name'        => $h->name,
                    'description' => $h->description,
                    'since'       => $h->since,
                ];
            }
            return Result::ok(['ops' => $index, 'total' => \count($registry)]);
        }

        $target = $this->params['target'];
        if (!isset($registry[$target])) {
            throw new AxiException(
                "Help: Op '{$target}' no existe. Usa help sin target para ver el indice.",
                AxiException::OP_UNKNOWN
            );
        }
        return Result::ok($registry[$target]::help()->toArray());
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'help',
            synopsis:    'Axi\\Op\\System\\Help() [->forOp("select")]',
            description: 'Sin target devuelve el indice de Ops. Con target devuelve el HelpEntry completo de ese Op.',
            params: [
                ['name' => 'target', 'type' => 'string', 'required' => false, 'description' => 'Nombre del Op (p.ej. "select", "ai.ask"). Omitir para el indice.'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\System\\Help())->forOp('select'));"],
                ['lang' => 'json', 'code' => '{"op":"help","target":"select"}'],
                ['lang' => 'cli',  'code' => 'axi help | axi help select'],
            ],
            errors: [
                ['code' => AxiException::OP_UNKNOWN, 'when' => 'target no esta en el registry.'],
            ],
            related: ['Describe', 'Schema'],
        );
    }
}
