<?php
/**
 * AxiDB - Sql\ExprParser: parser de expresiones WHERE (sub-gramatica).
 *
 * Subsistema: sql
 * Responsable: manejar OR/AND/NOT/comparaciones/parentesis. Se separa de
 *              Parser.php por cohesion interna y para cumplir §6.2.10.
 * Precedencia (baja a alta): OR, AND, NOT, cmp, primario.
 * Comparte el stream de tokens con Parser via getter/setter de $i.
 */

namespace Axi\Sql;

use Axi\Engine\AxiException;

final class ExprParser
{
    /** @var Token[] */
    private array $tokens;
    private int $i;

    /** @param Token[] $tokens */
    public function __construct(array $tokens, int $startIndex)
    {
        $this->tokens = $tokens;
        $this->i = $startIndex;
    }

    public function currentIndex(): int
    {
        return $this->i;
    }

    public function parseExpr(): array
    {
        $left = $this->parseAnd();
        while ($this->peek()->isKw('OR')) {
            $this->advance();
            $right = $this->parseAnd();
            $left = ['type' => 'or', 'left' => $left, 'right' => $right];
        }
        return $left;
    }

    private function parseAnd(): array
    {
        $left = $this->parseNot();
        while ($this->peek()->isKw('AND')) {
            $this->advance();
            $right = $this->parseNot();
            $left = ['type' => 'and', 'left' => $left, 'right' => $right];
        }
        return $left;
    }

    private function parseNot(): array
    {
        if ($this->peek()->isKw('NOT')) {
            $this->advance();
            return ['type' => 'not', 'expr' => $this->parseCmp()];
        }
        return $this->parseCmp();
    }

    private function parseCmp(): array
    {
        if ($this->peek()->isPunct('(')) {
            $this->advance();
            $inner = $this->parseExpr();
            $this->consumePunct(')');
            return $inner;
        }

        $field = $this->consumeIdent();

        if ($this->peek()->isKw('IS')) {
            $this->advance();
            $neg = false;
            if ($this->peek()->isKw('NOT')) { $this->advance(); $neg = true; }
            $this->consumeKw('NULL');
            return [
                'type'  => 'cmp',
                'field' => $field,
                'op'    => $neg ? 'IS_NOT_NULL' : 'IS_NULL',
                'value' => null,
            ];
        }

        if ($this->peek()->isKw('IN')) {
            $this->advance();
            $this->consumePunct('(');
            $values = [$this->parseLiteral()];
            while ($this->matchPunct(',')) {
                $values[] = $this->parseLiteral();
            }
            $this->consumePunct(')');
            return ['type' => 'cmp', 'field' => $field, 'op' => 'IN', 'value' => $values];
        }

        if ($this->peek()->isKw('LIKE')) {
            $this->advance();
            return ['type' => 'cmp', 'field' => $field, 'op' => 'LIKE', 'value' => $this->parseLiteral()];
        }

        if ($this->peek()->isKw('CONTAINS')) {
            $this->advance();
            return ['type' => 'cmp', 'field' => $field, 'op' => 'contains', 'value' => $this->parseLiteral()];
        }

        $tk = $this->peek();
        if ($tk->type === Token::OP) {
            $this->advance();
            return ['type' => 'cmp', 'field' => $field, 'op' => $tk->value, 'value' => $this->parseLiteral()];
        }

        throw new AxiException(
            "Parser: tras '{$field}' esperaba operador (=, !=, >, <, >=, <=, IN, LIKE, CONTAINS, IS NULL).",
            AxiException::BAD_REQUEST
        );
    }

    // --- Helpers (duplicados minimos de Parser para mantener autonomia) ---

    public function parseLiteral(): mixed
    {
        $tk = $this->peek();
        if ($tk->type === Token::STR) { $this->advance(); return $tk->value; }
        if ($tk->type === Token::NUM) { $this->advance(); return $tk->value; }
        if ($tk->isKw('TRUE'))  { $this->advance(); return true; }
        if ($tk->isKw('FALSE')) { $this->advance(); return false; }
        if ($tk->isKw('NULL'))  { $this->advance(); return null; }
        throw new AxiException(
            "Parser: esperaba literal en pos {$tk->pos}, obtuvo '{$tk->value}'.",
            AxiException::BAD_REQUEST
        );
    }

    private function peek(): Token
    {
        return $this->tokens[$this->i] ?? $this->tokens[\array_key_last($this->tokens)];
    }

    private function advance(): Token
    {
        return $this->tokens[$this->i++];
    }

    private function matchPunct(string $p): bool
    {
        if ($this->peek()->isPunct($p)) { $this->advance(); return true; }
        return false;
    }

    private function consumePunct(string $p): void
    {
        $tk = $this->peek();
        if (!$tk->isPunct($p)) {
            throw new AxiException("Parser: esperaba '{$p}' en pos {$tk->pos}.", AxiException::BAD_REQUEST);
        }
        $this->advance();
    }

    private function consumeKw(string $kw): void
    {
        $tk = $this->peek();
        if (!$tk->isKw($kw)) {
            throw new AxiException("Parser: esperaba '{$kw}' en pos {$tk->pos}.", AxiException::BAD_REQUEST);
        }
        $this->advance();
    }

    private function consumeIdent(): string
    {
        $tk = $this->peek();
        if ($tk->type !== Token::IDENT) {
            throw new AxiException(
                "Parser: esperaba identificador en pos {$tk->pos}.",
                AxiException::BAD_REQUEST
            );
        }
        $this->advance();
        return (string) $tk->value;
    }
}
