# `create_collection`

> Crea una coleccion y su _meta.json. Idempotente: si ya existe, devuelve la meta actual.

**Since**: v1.0

## Synopsis

```
Axi\Op\Alter\CreateCollection(collection) [->flags([...])]
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes | Nombre snake_case. |
| `flags` | `object` | no | {encrypted, keep_versions, strict_schema, strict_durability}. |

## Examples

**php**

```php
$db->execute((new Axi\Op\Alter\CreateCollection('notas'))
    ->flags(['keep_versions' => true]));
```

**json**

```json
{"op":"create_collection","collection":"notas","flags":{"keep_versions":true}}
```

**axisql**

```axisql
CREATE COLLECTION notas WITH (keep_versions=true)
```

**cli**

```cli
axi alter collection create notas --keep-versions
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | nombre no snake_case, flags no es array. |

## See also

`DropCollection`, `AlterCollection`, `RenameCollection`
