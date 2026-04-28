# `alter_collection`

> Modifica flags de coleccion: encrypted, keep_versions, strict_schema, strict_durability.

**Since**: v1.0

## Synopsis

```
Axi\Op\Alter\AlterCollection(collection) ->setFlag(name, value)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes |  |
| `flags` | `object` | yes |  |

## Examples

**php**

```php
$db->execute((new Axi\Op\Alter\AlterCollection('users'))
    ->setFlag('encrypted', true));
```

**json**

```json
{"op":"alter_collection","collection":"users","flags":{"encrypted":true}}
```

**cli**

```cli
axi alter collection users --set encrypted=true
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | flags ausente o vacio. |
| `COLLECTION_NOT_FOUND` | coleccion no existe. |

## See also

`CreateCollection`, `AddField`, `DropField`
