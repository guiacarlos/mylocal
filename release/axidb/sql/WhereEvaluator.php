<?php
/**
 * AxiDB - Sql\WhereEvaluator: evalua un AST de expresion WHERE sobre un doc.
 *
 * Subsistema: sql
 * Responsable: resolver a true/false aplicando el arbol del parser sobre
 *              los campos de un documento. Soporta AND/OR/NOT, comparaciones,
 *              IN, LIKE ('%' y '_'), CONTAINS, IS [NOT] NULL.
 * Entrada:    AST node (array con 'type' => and|or|not|cmp).
 * Salida:     bool.
 */

namespace Axi\Sql;

use Axi\Engine\AxiException;

final class WhereEvaluator
{
    public static function matches(array $doc, ?array $expr): bool
    {
        if ($expr === null) { return true; }
        $type = $expr['type'] ?? null;

        return match ($type) {
            'and' => self::matches($doc, $expr['left']) && self::matches($doc, $expr['right']),
            'or'  => self::matches($doc, $expr['left']) || self::matches($doc, $expr['right']),
            'not' => !self::matches($doc, $expr['expr']),
            'cmp' => self::evalCmp($doc, $expr),
            default => throw new AxiException(
                "WhereEvaluator: tipo de nodo desconocido '{$type}'.",
                AxiException::INTERNAL_ERROR
            ),
        };
    }

    private static function evalCmp(array $doc, array $node): bool
    {
        $field  = $node['field'];
        $op     = $node['op'];
        $target = $node['value'];
        $actual = $doc[$field] ?? null;

        return match ($op) {
            '=', '=='    => self::eq($actual, $target),
            '!='         => !self::eq($actual, $target),
            '>'          => self::numCompare($actual, $target) > 0,
            '<'          => self::numCompare($actual, $target) < 0,
            '>='         => self::numCompare($actual, $target) >= 0,
            '<='         => self::numCompare($actual, $target) <= 0,
            'IN'         => \is_array($target) && self::inArrayLoose($actual, $target),
            'LIKE'       => \is_string($actual) && self::likeMatch($actual, (string) $target),
            'contains'   => self::containsMatch($actual, $target),
            'IS_NULL'    => $actual === null || !\array_key_exists($field, $doc),
            'IS_NOT_NULL' => $actual !== null && \array_key_exists($field, $doc),
            default => throw new AxiException(
                "WhereEvaluator: operador desconocido '{$op}'.",
                AxiException::INTERNAL_ERROR
            ),
        };
    }

    /** Igualdad con coercion light: numero vs string-numero = true. */
    private static function eq(mixed $a, mixed $b): bool
    {
        if ($a === null || $b === null) { return $a === $b; }
        if (\is_numeric($a) && \is_numeric($b)) {
            return (float) $a === (float) $b;
        }
        return $a == $b;
    }

    private static function numCompare(mixed $a, mixed $b): int
    {
        if ($a === null || $b === null) { return -1; }   // null < todo
        if (\is_numeric($a) && \is_numeric($b)) {
            $fa = (float) $a;
            $fb = (float) $b;
            return $fa === $fb ? 0 : ($fa > $fb ? 1 : -1);
        }
        return \strcmp((string) $a, (string) $b);
    }

    private static function inArrayLoose(mixed $needle, array $haystack): bool
    {
        foreach ($haystack as $item) {
            if (self::eq($needle, $item)) { return true; }
        }
        return false;
    }

    /** LIKE con % (cualquier secuencia) y _ (cualquier char). Case-insensitive. */
    private static function likeMatch(string $actual, string $pattern): bool
    {
        $regex = '/^' . \strtr(
            \preg_quote($pattern, '/'),
            ['%' => '.*', '_' => '.']
        ) . '$/i';
        return (bool) \preg_match($regex, $actual);
    }

    private static function containsMatch(mixed $actual, mixed $needle): bool
    {
        if (\is_string($actual) && \is_string($needle)) {
            return \stripos($actual, $needle) !== false;
        }
        if (\is_array($actual)) {
            return \in_array($needle, $actual, false);
        }
        return false;
    }
}
