<?php
/**
 * AxiDB - Sql\Lexer: tokeniza una cadena AxiSQL.
 *
 * Subsistema: sql
 * Entrada:    string.
 * Salida:     Token[] terminado en Token(EOF).
 * Ver:        docs/guide/03-axisql.md y docs/standard/op-model.md.
 */

namespace Axi\Sql;

use Axi\Engine\AxiException;

final class Lexer
{
    /** Keywords reconocidos (se comparan case-insensitive). */
    private const KEYWORDS = [
        'SELECT', 'FROM', 'WHERE', 'AND', 'OR', 'NOT',
        'IN', 'LIKE', 'IS', 'NULL', 'CONTAINS',
        'ORDER', 'BY', 'ASC', 'DESC', 'LIMIT', 'OFFSET',
        'INSERT', 'INTO', 'VALUES',
        'UPDATE', 'SET',
        'DELETE',
        'CREATE', 'DROP', 'ALTER', 'COLLECTION', 'TABLE',
        'ADD', 'FIELD', 'COLUMN', 'INDEX', 'UNIQUE', 'ON',
        'COUNT',
        'TRUE', 'FALSE',
        'WITH', 'DEFAULT', 'REPLACE',
    ];

    /** @return Token[] */
    public function tokenize(string $input): array
    {
        $tokens = [];
        $len    = \strlen($input);
        $i      = 0;

        while ($i < $len) {
            $c = $input[$i];

            // Whitespace
            if (\ctype_space($c)) {
                $i++;
                continue;
            }

            // Comentario SQL -- resto-de-linea
            if ($c === '-' && ($input[$i + 1] ?? '') === '-') {
                while ($i < $len && $input[$i] !== "\n") { $i++; }
                continue;
            }

            // String literal: 'x' o "x" con escape de comilla doblada
            if ($c === "'" || $c === '"') {
                $quote = $c;
                $start = $i;
                $i++;
                $buf = '';
                while ($i < $len) {
                    if ($input[$i] === $quote) {
                        if (($input[$i + 1] ?? '') === $quote) {
                            // comilla doblada = escape
                            $buf .= $quote;
                            $i += 2;
                            continue;
                        }
                        $i++;
                        $tokens[] = new Token(Token::STR, $buf, $start);
                        continue 2;
                    }
                    $buf .= $input[$i];
                    $i++;
                }
                throw new AxiException("Lexer: string sin cerrar desde pos {$start}.", AxiException::BAD_REQUEST);
            }

            // Numero
            if (\ctype_digit($c) || ($c === '-' && \ctype_digit($input[$i + 1] ?? ''))) {
                $start = $i;
                if ($c === '-') { $i++; }
                while ($i < $len && \ctype_digit($input[$i])) { $i++; }
                $isFloat = false;
                if (($input[$i] ?? '') === '.' && \ctype_digit($input[$i + 1] ?? '')) {
                    $isFloat = true;
                    $i++;
                    while ($i < $len && \ctype_digit($input[$i])) { $i++; }
                }
                $raw = \substr($input, $start, $i - $start);
                $val = $isFloat ? (float) $raw : (int) $raw;
                $tokens[] = new Token(Token::NUM, $val, $start);
                continue;
            }

            // Identificador o keyword
            if (\ctype_alpha($c) || $c === '_') {
                $start = $i;
                while ($i < $len && (\ctype_alnum($input[$i]) || $input[$i] === '_')) { $i++; }
                $word = \substr($input, $start, $i - $start);
                $upper = \strtoupper($word);
                if (\in_array($upper, self::KEYWORDS, true)) {
                    $tokens[] = new Token(Token::KW, $upper, $start);
                } else {
                    $tokens[] = new Token(Token::IDENT, $word, $start);
                }
                continue;
            }

            // Operadores multi-char
            $two = \substr($input, $i, 2);
            if (\in_array($two, ['!=', '<>', '>=', '<=', '=='], true)) {
                $tokens[] = new Token(Token::OP, $two === '<>' ? '!=' : ($two === '==' ? '=' : $two), $i);
                $i += 2;
                continue;
            }

            // Operadores de 1 char
            if (\in_array($c, ['=', '>', '<'], true)) {
                $tokens[] = new Token(Token::OP, $c, $i);
                $i++;
                continue;
            }

            // Puntuacion
            if (\in_array($c, ['(', ')', ',', ';', '*'], true)) {
                $tokens[] = new Token(Token::PUNCT, $c, $i);
                $i++;
                continue;
            }

            throw new AxiException(
                "Lexer: caracter inesperado '{$c}' en pos {$i}.",
                AxiException::BAD_REQUEST
            );
        }

        $tokens[] = new Token(Token::EOF, null, $len);
        return $tokens;
    }
}
