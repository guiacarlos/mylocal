<?php
/**
 * AxiDB - Sql\Parser: construye AST desde tokens del Lexer.
 *
 * Subsistema: sql
 * Responsable: recursive descent para SELECT/INSERT/UPDATE/DELETE/CREATE/DROP/ALTER.
 *              Las expresiones WHERE se delegan a ExprParser.
 * Entrada:    Token[].
 * Salida:     AST (array asoc) con claves 'type' + campos especificos.
 *
 * Excepcion §6.2.10: el archivo supera 250 lineas por cohesion del
 * recursive-descent (todos los statement parsers comparten el stream de
 * tokens y los helpers peek/advance/consume*). Split ya realizado: expresiones
 * WHERE en ExprParser.php.
 */

namespace Axi\Sql;

use Axi\Engine\AxiException;

final class Parser
{
    /** @var Token[] */
    private array $tokens = [];
    private int $i = 0;

    /** @param Token[] $tokens */
    public function parse(array $tokens): array
    {
        $this->tokens = $tokens;
        $this->i = 0;

        $head = $this->peek();
        if ($head->type !== Token::KW) {
            throw new AxiException(
                "Parser: esperaba keyword al inicio, obtuvo {$head->type} '{$head->value}' en pos {$head->pos}.",
                AxiException::BAD_REQUEST
            );
        }

        $stmt = match (\strtoupper((string) $head->value)) {
            'SELECT' => $this->parseSelect(),
            'INSERT' => $this->parseInsert(),
            'UPDATE' => $this->parseUpdate(),
            'DELETE' => $this->parseDelete(),
            'CREATE' => $this->parseCreate(),
            'DROP'   => $this->parseDrop(),
            'ALTER'  => $this->parseAlter(),
            default  => throw new AxiException(
                "Parser: keyword inicial desconocido: {$head->value}.",
                AxiException::BAD_REQUEST
            ),
        };

        if ($this->peek()->isPunct(';')) { $this->advance(); }
        $this->expectEof();
        return $stmt;
    }

    private function parseSelect(): array
    {
        $this->consumeKw('SELECT');

        $isCount = false;
        if ($this->peek()->isKw('COUNT')) {
            $this->advance();
            $this->consumePunct('(');
            $this->consumePunct('*');
            $this->consumePunct(')');
            $isCount = true;
            $fields = ['*'];
        } else {
            $fields = $this->parseFieldList();
        }

        $this->consumeKw('FROM');
        $collection = $this->consumeIdent();

        $ast = [
            'type'       => $isCount ? 'count' : 'select',
            'collection' => $collection,
            'fields'     => $fields,
            'where_expr' => null,
            'order_by'   => [],
            'limit'      => null,
            'offset'     => null,
        ];

        if ($this->peek()->isKw('WHERE')) {
            $this->advance();
            $ast['where_expr'] = $this->parseExpr();
        }

        if ($this->peek()->isKw('ORDER')) {
            $this->advance();
            $this->consumeKw('BY');
            do {
                $f = $this->consumeIdent();
                $dir = 'asc';
                if ($this->peek()->isKw('ASC'))  { $this->advance(); }
                elseif ($this->peek()->isKw('DESC')) { $this->advance(); $dir = 'desc'; }
                $ast['order_by'][] = ['field' => $f, 'dir' => $dir];
            } while ($this->matchPunct(','));
        }

        if ($this->peek()->isKw('LIMIT'))  { $this->advance(); $ast['limit']  = $this->consumeInt(); }
        if ($this->peek()->isKw('OFFSET')) { $this->advance(); $ast['offset'] = $this->consumeInt(); }

        return $ast;
    }

    private function parseInsert(): array
    {
        $this->consumeKw('INSERT');
        $this->consumeKw('INTO');
        $collection = $this->consumeIdent();

        $this->consumePunct('(');
        $fields = [$this->consumeIdent()];
        while ($this->matchPunct(',')) { $fields[] = $this->consumeIdent(); }
        $this->consumePunct(')');
        $this->consumeKw('VALUES');
        $this->consumePunct('(');
        $values = [$this->parseLiteral()];
        while ($this->matchPunct(',')) { $values[] = $this->parseLiteral(); }
        $this->consumePunct(')');

        if (\count($fields) !== \count($values)) {
            throw new AxiException(
                "Parser: INSERT con " . \count($fields) . " fields pero " . \count($values) . " values.",
                AxiException::BAD_REQUEST
            );
        }

        $data = [];
        foreach ($fields as $k => $f) { $data[$f] = $values[$k]; }
        return ['type' => 'insert', 'collection' => $collection, 'data' => $data];
    }

    private function parseUpdate(): array
    {
        $this->consumeKw('UPDATE');
        $collection = $this->consumeIdent();
        $this->consumeKw('SET');

        $set = [];
        do {
            $field = $this->consumeIdent();
            if (!$this->peek()->isOp('=')) {
                throw new AxiException("Parser: esperaba '=' tras field en SET.", AxiException::BAD_REQUEST);
            }
            $this->advance();
            $set[$field] = $this->parseLiteral();
        } while ($this->matchPunct(','));

        $whereExpr = null;
        if ($this->peek()->isKw('WHERE')) {
            $this->advance();
            $whereExpr = $this->parseExpr();
        }
        return [
            'type'       => 'update',
            'collection' => $collection,
            'set'        => $set,
            'where_expr' => $whereExpr,
        ];
    }

    private function parseDelete(): array
    {
        $this->consumeKw('DELETE');
        $this->consumeKw('FROM');
        $collection = $this->consumeIdent();
        $whereExpr = null;
        if ($this->peek()->isKw('WHERE')) {
            $this->advance();
            $whereExpr = $this->parseExpr();
        }
        return ['type' => 'delete', 'collection' => $collection, 'where_expr' => $whereExpr];
    }

    private function parseCreate(): array
    {
        $this->consumeKw('CREATE');

        $unique = false;
        if ($this->peek()->isKw('UNIQUE')) { $this->advance(); $unique = true; }
        if ($this->peek()->isKw('INDEX')) {
            $this->advance();
            $this->consumeKw('ON');
            $collection = $this->consumeIdent();
            $this->consumePunct('(');
            $field = $this->consumeIdent();
            $this->consumePunct(')');
            return [
                'type' => 'create_index', 'collection' => $collection,
                'field' => $field, 'unique' => $unique,
            ];
        }

        if ($this->peek()->isKw('COLLECTION') || $this->peek()->isKw('TABLE')) {
            $this->advance();
            $collection = $this->consumeIdent();
            $flags = [];
            if ($this->peek()->isKw('WITH')) {
                $this->advance();
                $this->consumePunct('(');
                do {
                    $k = $this->consumeIdent();
                    if (!$this->peek()->isOp('=')) {
                        throw new AxiException("Parser: esperaba '=' en WITH.", AxiException::BAD_REQUEST);
                    }
                    $this->advance();
                    $flags[$k] = $this->parseLiteral();
                } while ($this->matchPunct(','));
                $this->consumePunct(')');
            }
            return ['type' => 'create_collection', 'collection' => $collection, 'flags' => $flags];
        }

        throw new AxiException("Parser: tras CREATE esperaba COLLECTION, TABLE o INDEX.", AxiException::BAD_REQUEST);
    }

    private function parseDrop(): array
    {
        $this->consumeKw('DROP');
        if ($this->peek()->isKw('INDEX')) {
            $this->advance();
            $this->consumeKw('ON');
            $collection = $this->consumeIdent();
            $this->consumePunct('(');
            $field = $this->consumeIdent();
            $this->consumePunct(')');
            return ['type' => 'drop_index', 'collection' => $collection, 'field' => $field];
        }
        if ($this->peek()->isKw('COLLECTION') || $this->peek()->isKw('TABLE')) {
            $this->advance();
            $collection = $this->consumeIdent();
            return ['type' => 'drop_collection', 'collection' => $collection];
        }
        throw new AxiException("Parser: tras DROP esperaba COLLECTION, TABLE o INDEX.", AxiException::BAD_REQUEST);
    }

    private function parseAlter(): array
    {
        $this->consumeKw('ALTER');
        if (!($this->peek()->isKw('COLLECTION') || $this->peek()->isKw('TABLE'))) {
            throw new AxiException("Parser: tras ALTER esperaba COLLECTION o TABLE.", AxiException::BAD_REQUEST);
        }
        $this->advance();
        $collection = $this->consumeIdent();

        if ($this->peek()->isKw('ADD')) {
            $this->advance();
            if (!$this->peek()->isKw('FIELD') && !$this->peek()->isKw('COLUMN')) {
                throw new AxiException("Parser: tras ADD esperaba FIELD o COLUMN.", AxiException::BAD_REQUEST);
            }
            $this->advance();
            $name = $this->consumeIdent();
            $type = $this->consumeIdent();
            $default = null;
            if ($this->peek()->isKw('DEFAULT')) {
                $this->advance();
                $default = $this->parseLiteral();
            }
            return [
                'type' => 'alter_add_field', 'collection' => $collection,
                'field' => ['name' => $name, 'type' => $type, 'default' => $default],
            ];
        }
        if ($this->peek()->isKw('DROP')) {
            $this->advance();
            if (!$this->peek()->isKw('FIELD') && !$this->peek()->isKw('COLUMN')) {
                throw new AxiException("Parser: tras DROP esperaba FIELD o COLUMN.", AxiException::BAD_REQUEST);
            }
            $this->advance();
            $name = $this->consumeIdent();
            return ['type' => 'alter_drop_field', 'collection' => $collection, 'name' => $name];
        }
        throw new AxiException("Parser: tras ALTER COLLECTION esperaba ADD o DROP.", AxiException::BAD_REQUEST);
    }

    // --- Delegacion a ExprParser ---
    private function parseExpr(): array
    {
        $exp = new ExprParser($this->tokens, $this->i);
        $ast = $exp->parseExpr();
        $this->i = $exp->currentIndex();
        return $ast;
    }

    // --- Helpers ---
    private function parseFieldList(): array
    {
        if ($this->peek()->isPunct('*')) { $this->advance(); return ['*']; }
        $fields = [$this->consumeIdent()];
        while ($this->matchPunct(',')) { $fields[] = $this->consumeIdent(); }
        return $fields;
    }

    private function parseLiteral(): mixed
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

    private function consumeKw(string $kw): void
    {
        $tk = $this->peek();
        if (!$tk->isKw($kw)) {
            throw new AxiException(
                "Parser: esperaba '{$kw}' en pos {$tk->pos}, obtuvo {$tk->type} '{$tk->value}'.",
                AxiException::BAD_REQUEST
            );
        }
        $this->advance();
    }

    private function consumePunct(string $p): void
    {
        $tk = $this->peek();
        if (!$tk->isPunct($p)) {
            throw new AxiException(
                "Parser: esperaba '{$p}' en pos {$tk->pos}, obtuvo {$tk->type} '{$tk->value}'.",
                AxiException::BAD_REQUEST
            );
        }
        $this->advance();
    }

    private function consumeIdent(): string
    {
        $tk = $this->peek();
        if ($tk->type !== Token::IDENT) {
            throw new AxiException(
                "Parser: esperaba identificador en pos {$tk->pos}, obtuvo {$tk->type} '{$tk->value}'.",
                AxiException::BAD_REQUEST
            );
        }
        $this->advance();
        return (string) $tk->value;
    }

    private function consumeInt(): int
    {
        $tk = $this->peek();
        if ($tk->type !== Token::NUM || !\is_int($tk->value)) {
            throw new AxiException("Parser: esperaba entero en pos {$tk->pos}.", AxiException::BAD_REQUEST);
        }
        $this->advance();
        return (int) $tk->value;
    }

    private function expectEof(): void
    {
        $tk = $this->peek();
        if ($tk->type !== Token::EOF) {
            throw new AxiException(
                "Parser: texto extra tras statement en pos {$tk->pos}: '{$tk->value}'.",
                AxiException::BAD_REQUEST
            );
        }
    }
}
