# `select`

> Lectura de documentos de una coleccion con filtros where, orden y paginacion.

**Since**: v1.0

## Synopsis

```
Axi\Op\Select(collection) ->where(...)->orderBy(...)->limit(n)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes | Nombre de la coleccion. |
| `fields` | `string[]` | no | Proyeccion de campos. [*] devuelve todo. _(default: `["*"]`)_ |
| `where` | `clause[]` | no | [{field, op, value}]. Operadores: =, !=, >, <, >=, <=, IN, contains. |
| `order_by` | `clause[]` | no | [{field, dir: asc|desc}]. v1 aplica solo la primera clausula. |
| `limit` | `int` | no | Numero maximo de documentos a devolver. |
| `offset` | `int` | no | Paginacion. _(default: `0`)_ |

## Examples

**php**

```php
$db->execute((new Axi\Op\Select('products'))
    ->where('price', '<', 3)
    ->orderBy('price')
    ->limit(20));
```

**json**

```json
{"op":"select","collection":"products","where":[{"field":"price","op":"<","value":3}],"limit":20}
```

**axisql**

```axisql
SELECT * FROM products WHERE price < 3 ORDER BY price LIMIT 20
```

**cli**

```cli
axi select products --where "price<3" --order-by price --limit 20
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | collection vacia, where/order_by malformado, limit/offset negativos. |
| `COLLECTION_NOT_FOUND` | La coleccion no existe y strict_collection=true. |

## See also

`Count`, `Exists`, `Explain`, `Insert`
