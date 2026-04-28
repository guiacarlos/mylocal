<?php
/**
 * AxiDB - Op\System\Sql: compila una cadena AxiSQL y ejecuta el Op resultante.
 *
 * Subsistema: engine/op/system
 * Entrada:    query (string).
 * Salida:     Result del Op compilado (Select, Insert, Update, etc.).
 */

namespace Axi\Engine\Op\System;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;
use Axi\Sql\Lexer;
use Axi\Sql\Parser;
use Axi\Sql\Planner;

class Sql extends Operation
{
    public const OP_NAME = 'sql';

    public function query(string $query): self
    {
        $this->params['query'] = $query;
        return $this;
    }

    public function validate(): void
    {
        if (empty($this->params['query']) || !\is_string($this->params['query'])) {
            throw new AxiException("Sql: 'query' requerido (string).", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $tokens = (new Lexer())->tokenize($this->params['query']);
        $ast    = (new Parser())->parse($tokens);
        $op     = (new Planner())->plan($ast);

        // Propagar namespace si se setteo.
        if ($this->namespace !== 'default') {
            $op->namespace = $this->namespace;
        }

        // Delegamos al dispatcher para que aplique validate+timing+audit.
        $inner = $engine->execute($op);
        // El dispatcher retorna array; recuperamos Result equivalente.
        if (($inner['success'] ?? false) === true) {
            return Result::ok($inner['data']);
        }
        throw new AxiException(
            $inner['error'] ?? 'Sql: fallo sin mensaje.',
            $inner['code']  ?? AxiException::INTERNAL_ERROR
        );
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'sql',
            synopsis:    'Axi\\Op\\System\\Sql()->query("SELECT * FROM col WHERE ...")',
            description: 'Compila una cadena AxiSQL a Op y la ejecuta. Sintaxis subset: SELECT/INSERT/UPDATE/DELETE/CREATE|DROP|ALTER COLLECTION|INDEX, WHERE con AND/OR/NOT/IN/LIKE/CONTAINS/IS NULL.',
            params: [
                ['name' => 'query', 'type' => 'string', 'required' => true, 'description' => 'Cadena AxiSQL completa.'],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Op\\System\\Sql())\n    ->query('SELECT * FROM products WHERE price < 3'));"],
                ['lang' => 'json',   'code' => '{"op":"sql","query":"SELECT * FROM products WHERE price < 3"}'],
                ['lang' => 'axisql', 'code' => 'SELECT * FROM products WHERE price < 3'],
                ['lang' => 'cli',    'code' => 'axi sql "SELECT * FROM products WHERE price < 3"'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'query ausente o vacio.'],
                ['code' => AxiException::BAD_REQUEST,       'when' => 'AxiSQL malformado (error del lexer o parser).'],
            ],
            related: ['Select', 'Insert', 'Update', 'Delete'],
        );
    }
}
