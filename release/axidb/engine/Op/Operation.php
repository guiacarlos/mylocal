<?php
/**
 * AxiDB - Operation: contrato base de toda operacion del motor.
 *
 * Subsistema: engine/op
 * Responsable: definir la forma comun de cualquier Op (CRUD, schema, AI, etc).
 *              Toda entrada (PHP, HTTP JSON, AxiSQL, CLI, agente) converge
 *              en una instancia de Operation y ejecuta via execute($engine).
 * Ver:        plan §2.5.
 */

namespace Axi\Engine\Op;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Result;

abstract class Operation
{
    public const OP_NAME = 'abstract';

    public string $namespace  = 'default';
    public string $collection = '';
    public array  $params     = [];

    public function __construct(string $collection = '', array $params = [])
    {
        $this->collection = $collection;
        $this->params     = $params;
    }

    public static function opName(): string
    {
        return static::OP_NAME;
    }

    abstract public function validate(): void;

    abstract public function execute(object $engine): Result;

    abstract public static function help(): HelpEntry;

    public function toArray(): array
    {
        return \array_merge([
            'op'         => static::opName(),
            'namespace'  => $this->namespace,
            'collection' => $this->collection,
        ], $this->params);
    }

    public static function fromArray(array $data): static
    {
        $op = new static();
        $op->namespace  = $data['namespace']  ?? 'default';
        $op->collection = $data['collection'] ?? '';
        $known = ['op', 'namespace', 'collection'];
        $op->params = \array_diff_key($data, \array_flip($known));
        return $op;
    }

    protected function requireParam(string $name): mixed
    {
        if (!\array_key_exists($name, $this->params)) {
            throw new AxiException(
                "Op '" . static::opName() . "' requires param '{$name}'.",
                AxiException::VALIDATION_FAILED
            );
        }
        return $this->params[$name];
    }

    protected function requireCollection(): void
    {
        if ($this->collection === '') {
            throw new AxiException(
                "Op '" . static::opName() . "' requires non-empty 'collection'.",
                AxiException::VALIDATION_FAILED
            );
        }
    }
}
