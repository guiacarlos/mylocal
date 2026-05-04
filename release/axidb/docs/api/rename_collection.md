# `rename_collection`

> Renombra la carpeta de coleccion. Falla si el destino ya existe.

**Since**: v1.0

## Synopsis

```
Axi\Op\Alter\RenameCollection(from) ->to("new_name")
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes | Nombre actual. |
| `to` | `string` | yes | Nombre nuevo (snake_case). |

## Examples

**php**

```php
$db->execute((new Axi\Op\Alter\RenameCollection('notas_v1'))->to('notas'));
```

**json**

```json
{"op":"rename_collection","collection":"notas_v1","to":"notas"}
```

**cli**

```cli
axi alter collection rename notas_v1 notas
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | to no snake_case o vacio. |
| `COLLECTION_NOT_FOUND` | origen no existe. |
| `CONFLICT` | destino ya existe. |

## See also

`CreateCollection`, `DropCollection`, `AlterCollection`
