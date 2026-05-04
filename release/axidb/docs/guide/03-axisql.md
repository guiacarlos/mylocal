# AxiSQL — Gramatica y uso

**Estado**: **DISPONIBLE EN v1.0 tras Fase 2** ✅.
**Implementacion**: `Axi\Sql\Lexer`, `Axi\Sql\Parser`, `Axi\Sql\Planner`, `Axi\Sql\WhereEvaluator`.
**Entry points**:
- PHP SDK: `$client->sql("SELECT ...")`
- CLI: `axi sql "SELECT ..."`
- HTTP: `{"op":"sql","query":"..."}`

---

## Filosofia

AxiSQL **compila a Ops del motor**, no los esquiva. El compilador tokeniza, parsea y planifica → el resultado es un `Axi\Engine\Op\Operation` que se ejecuta por el mismo dispatcher que cualquier otra entrada. Por eso las **4 formas convergen en el mismo Result**:

```
PHP Op directo  ─┐
JSON {op:...}   ─┼──► Engine::execute() ──► Result
AxiSQL          ─┤
CLI             ─┘
```

Los tests garantizan equivalencia byte-a-byte (ver `axidb/tests/axisql_test.php` seccion [I]).

---

## Gramatica soportada (v1)

### SELECT

```sql
SELECT { * | field1 [, field2, ...] }
FROM collection
[ WHERE expr ]
[ ORDER BY field [ASC|DESC] [, field [ASC|DESC]] ]
[ LIMIT n [ OFFSET m ] ]
```

### INSERT

```sql
INSERT INTO collection (field1, field2, ...) VALUES (val1, val2, ...)
```

### UPDATE

```sql
UPDATE collection SET field1 = val1 [, field2 = val2] WHERE expr
```

### DELETE (soft por defecto)

```sql
DELETE FROM collection WHERE expr
```

### Schema (CREATE / DROP / ALTER)

```sql
CREATE COLLECTION name [ WITH (flag = value [, flag = value]) ]
DROP COLLECTION name
ALTER COLLECTION name ADD FIELD field type [ DEFAULT value ]
ALTER COLLECTION name DROP FIELD field

CREATE [UNIQUE] INDEX ON collection (field)
DROP INDEX ON collection (field)
```

(Equivalentes con `TABLE` en lugar de `COLLECTION` por compat.)

### COUNT

```sql
SELECT COUNT(*) FROM collection [ WHERE expr ]
```

---

## Expresiones WHERE

Operadores de comparacion: `=` (alias `==`), `!=` (alias `<>`), `<`, `>`, `<=`, `>=`.

Operadores especiales:

| Operador | Ejemplo | Semantica |
| :-- | :-- | :-- |
| `IN (...)` | `status IN ('active', 'paid')` | El valor esta en la lista. |
| `LIKE 'pat'` | `name LIKE '%foo%'` | Pattern matching: `%` = cualquier cadena, `_` = 1 char. Case-insensitive. |
| `CONTAINS 'x'` | `tags CONTAINS 'admin'` | Substring en string o elemento en array. |
| `IS NULL` | `deleted IS NULL` | Campo null o ausente. |
| `IS NOT NULL` | `email IS NOT NULL` | Campo presente y no null. |

Combinadores: `AND`, `OR`, `NOT`, `()`. Precedencia (baja a alta): `OR`, `AND`, `NOT`.

---

## 10 ejemplos copy-paste (probados)

### 1. Leer todo

```sql
SELECT * FROM products
```

### 2. Proyeccion + filtro + orden + limit

```sql
SELECT name, price FROM products
WHERE price < 5
ORDER BY price DESC
LIMIT 10
```

### 3. COUNT con condiciones

```sql
SELECT COUNT(*) FROM products WHERE stock > 0
```

### 4. Combinadores booleanos con parentesis

```sql
SELECT * FROM products
WHERE (price = 2 OR price = 7) AND stock > 0
```

### 5. IN con multiple valores

```sql
SELECT * FROM users WHERE role IN ('admin', 'editor', 'moderator')
```

### 6. LIKE con wildcards

```sql
SELECT name FROM products WHERE name LIKE 'c%'
SELECT name FROM products WHERE name LIKE '_ate'
```

### 7. CONTAINS sobre array

```sql
SELECT * FROM posts WHERE tags CONTAINS 'php'
```

### 8. IS NULL para filtrar soft-deleted

```sql
SELECT * FROM notas WHERE _deletedAt IS NULL
```

### 9. INSERT

```sql
INSERT INTO notas (title, body, pinned) VALUES ('Reunion', 'Acta...', true)
```

### 10. UPDATE con WHERE (afecta todos los que matchen)

```sql
UPDATE products SET stock = 0 WHERE status = 'discontinued'
```

### 11. DELETE soft (marca `_deletedAt`)

```sql
DELETE FROM sessions WHERE _updatedAt < '2026-01-01T00:00:00+00:00'
```

### 12. Schema: crear coleccion con flags

```sql
CREATE COLLECTION audit_log WITH (keep_versions = true, strict_schema = false)
```

### 13. Schema: añadir field con default

```sql
ALTER COLLECTION products ADD FIELD discount number DEFAULT 0
```

### 14. Schema: indice unico

```sql
CREATE UNIQUE INDEX ON users (email)
```

---

## Reserved identifiers

Los siguientes keywords son case-insensitive y no pueden usarse como nombres de field sin escape:

```
SELECT FROM WHERE AND OR NOT IN LIKE IS NULL CONTAINS
ORDER BY ASC DESC LIMIT OFFSET
INSERT INTO VALUES UPDATE SET DELETE
CREATE DROP ALTER COLLECTION TABLE ADD FIELD COLUMN INDEX UNIQUE ON
COUNT TRUE FALSE WITH DEFAULT REPLACE
```

Si necesitas un field con uno de esos nombres, usa la API fluent de `Collection` o los Ops directamente — evitan la sintaxis SQL.

---

## Fuera de scope v1 (no implementado)

Los siguientes se dejan para v1.1 o P2 (AxiSQL Server):

- `JOIN` entre colecciones.
- Subqueries: `SELECT ... WHERE x IN (SELECT ...)`.
- Funciones agregadas distintas a `COUNT(*)`: `SUM`, `AVG`, `MIN`, `MAX`, `GROUP BY`.
- Stored procedures, triggers, views.
- Transacciones explicitas: `BEGIN / COMMIT / ROLLBACK`. (Usa `Op\Batch` para ejecutar N Ops en secuencia.)
- Columnas calculadas en `SELECT`: `price * 1.21 AS total`.
- Cast explicito: `CAST(x AS int)`.

Si necesitas alguna de estas, usa Ops directos o combina varias queries en PHP.

---

## Errores y diagnostico

El compilador emite `AxiException::BAD_REQUEST` con mensaje especificando **posicion de caracter** donde ocurrio el error. Ejemplos:

```
Parser: esperaba keyword 'FROM' en pos 7, obtuvo PUNCT '*'.
Lexer:  string sin cerrar desde pos 23.
Parser: INSERT con 2 fields pero 1 values.
```

Si el query se ejecuta pero una operacion interna falla, veras el codigo semantico habitual:

```
VALIDATION_FAILED    — param invalido
COLLECTION_NOT_FOUND — coleccion no existe
CONFLICT             — id/field/index duplicado
INTERNAL_ERROR       — fallo imprevisto
```

---

## Ver tambien

- [../api/sql.md](../api/sql.md) — referencia del Op `Sql` generada auto.
- [../api/select.md](../api/select.md) — el Op al que compila `SELECT`.
- [../standard/op-model.md](../standard/op-model.md) — contrato que AxiSQL respeta.
- [../standard/wire-protocol.md](../standard/wire-protocol.md) — AxiSQL sobre HTTP (`{"op":"sql","query":"..."}`).
- `axi help sql` — spec resumida desde la terminal.
