# `count`

> Cuenta documentos en una coleccion. Acepta clausulas where identicas a Select.

**Since**: v1.0

## Synopsis

```
Axi\Op\Count(collection) [->where(...)]
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes |  |
| `where` | `clause[]` | no |  |

## Examples

**php**

```php
$db->execute((new Axi\Op\Count('products'))->where('stock', '<', 10));
```

**json**

```json
{"op":"count","collection":"products","where":[{"field":"stock","op":"<","value":10}]}
```

**axisql**

```axisql
SELECT COUNT(*) FROM products WHERE stock < 10
```

**cli**

```cli
axi count products --where "stock<10"
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | where malformado. |

## See also

`Select`, `Exists`
