# `sql`

> Compila una cadena AxiSQL a Op y la ejecuta. Sintaxis subset: SELECT/INSERT/UPDATE/DELETE/CREATE|DROP|ALTER COLLECTION|INDEX, WHERE con AND/OR/NOT/IN/LIKE/CONTAINS/IS NULL.

**Since**: v1.0

## Synopsis

```
Axi\Op\System\Sql()->query("SELECT * FROM col WHERE ...")
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `query` | `string` | yes | Cadena AxiSQL completa. |

## Examples

**php**

```php
$db->execute((new Axi\Op\System\Sql())
    ->query('SELECT * FROM products WHERE price < 3'));
```

**json**

```json
{"op":"sql","query":"SELECT * FROM products WHERE price < 3"}
```

**axisql**

```axisql
SELECT * FROM products WHERE price < 3
```

**cli**

```cli
axi sql "SELECT * FROM products WHERE price < 3"
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | query ausente o vacio. |
| `BAD_REQUEST` | AxiSQL malformado (error del lexer o parser). |

## See also

`Select`, `Insert`, `Update`, `Delete`
