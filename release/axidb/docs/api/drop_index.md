# `drop_index`

> Elimina el indice secundario sobre un field.

**Since**: v1.0

## Synopsis

```
Axi\Op\Alter\DropIndex(collection) ->field("field")
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes |  |
| `field` | `string` | yes |  |

## Examples

**php**

```php
$db->execute((new Axi\Op\Alter\DropIndex('users'))->field('email'));
```

**json**

```json
{"op":"drop_index","collection":"users","field":"email"}
```

**axisql**

```axisql
DROP INDEX ON users (email)
```

**cli**

```cli
axi alter table users drop-index email
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | field vacio. |
| `COLLECTION_NOT_FOUND` | coleccion no existe. |
| `DOCUMENT_NOT_FOUND` | indice para ese field no registrado. |

## See also

`CreateIndex`
