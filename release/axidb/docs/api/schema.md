# `schema`

> Devuelve _meta.json: fields, indexes, flags, timestamps.

**Since**: v1.0

## Synopsis

```
Axi\Op\System\Schema(collection)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes |  |

## Examples

**php**

```php
$db->execute(new Axi\Op\System\Schema('products'));
```

**json**

```json
{"op":"schema","collection":"products"}
```

**cli**

```cli
axi schema describe products
```

## Errors

| code | when |
| :-- | :-- |
| `COLLECTION_NOT_FOUND` | coleccion no existe. |

## See also

`Describe`, `AddField`, `CreateIndex`
