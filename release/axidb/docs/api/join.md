# `join`

> JOIN entre dos colecciones por field. [FASE 2] Fuera de scope v1.

**Since**: v1.0

## Synopsis

```
Axi\Join() ->with(left, right) ->on(leftField, rightField)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes | Left collection. |
| `right` | `string` | yes | Right collection. |
| `on` | `object` | yes | {left: field, right: field}. |

## Examples

**php**

```php
$db->execute((new Axi\Join())
    ->with('orders', 'users')
    ->on('user_id', '_id'));
```

**axisql**

```axisql
SELECT * FROM orders JOIN users ON orders.user_id = users._id
```

## Errors

| code | when |
| :-- | :-- |
| `NOT_IMPLEMENTED` | Siempre en v1. |

## See also

`Select`
