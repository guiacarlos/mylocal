# `drop_collection`

> Elimina la coleccion completa, documentos e indices. Operacion destructiva: dispara snapshot automatico en Fase 3 si agent_id presente.

**Since**: v1.0

## Synopsis

```
Axi\Op\Alter\DropCollection(collection)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes |  |

## Examples

**php**

```php
$db->execute(new Axi\Op\Alter\DropCollection('temp_draft'));
```

**json**

```json
{"op":"drop_collection","collection":"temp_draft"}
```

**axisql**

```axisql
DROP COLLECTION temp_draft
```

**cli**

```cli
axi alter collection drop temp_draft
```

## Errors

| code | when |
| :-- | :-- |
| `COLLECTION_NOT_FOUND` | coleccion no existe. |

## See also

`CreateCollection`, `RenameCollection`
