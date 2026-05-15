# `explain`

> Devuelve el plan de ejecucion para un Op dado sin ejecutarlo. Util para debugging.

**Since**: v1.0

## Synopsis

```
Axi\Op\System\Explain() ->forOp([...])
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `target` | `object` | yes | Op serializada (con "op", "collection", etc.). Se llama target para evitar colision con la clave "op" del dispatcher. |

## Examples

**php**

```php
$db->execute((new Axi\Op\System\Explain())
    ->forOp(['op' => 'select', 'collection' => 'products', 'where' => [['field'=>'price','op'=>'<','value'=>3]]]));
```

**json**

```json
{"op":"explain","target":{"op":"select","collection":"products"}}
```

**axisql**

```axisql
EXPLAIN SELECT * FROM products WHERE price < 3
```

**cli**

```cli
axi explain "SELECT * FROM products WHERE price < 3"
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | op ausente, malformado, o falta op.op. |

## See also

`Select`, `Count`
