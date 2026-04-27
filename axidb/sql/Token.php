<?php
/**
 * AxiDB - Sql\Token: unidad emitida por el Lexer.
 *
 * Subsistema: sql
 * Responsable: transportar tipo + valor + posicion para errores claros.
 */

namespace Axi\Sql;

final class Token
{
    public const KW     = 'KW';      // keyword reservado (SELECT, FROM, ...)
    public const IDENT  = 'IDENT';   // identificador ([a-zA-Z_][a-zA-Z0-9_]*)
    public const STR    = 'STR';     // string literal 'x' o "x"
    public const NUM    = 'NUM';     // numero entero o float
    public const OP     = 'OP';      // operador: = != <> > < >= <=
    public const PUNCT  = 'PUNCT';   // ( ) , ; *
    public const EOF    = 'EOF';

    public function __construct(
        public readonly string $type,
        public readonly string|int|float|null $value,
        public readonly int $pos
    ) {
    }

    public function isKw(string $kw): bool
    {
        return $this->type === self::KW && \strtoupper((string) $this->value) === \strtoupper($kw);
    }

    public function isPunct(string $p): bool
    {
        return $this->type === self::PUNCT && $this->value === $p;
    }

    public function isOp(string $op): bool
    {
        return $this->type === self::OP && $this->value === $op;
    }
}
