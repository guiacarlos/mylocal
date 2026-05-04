# `drop_field`

> Elimina un field del schema. En v1 NO purga el campo de documentos existentes.

**Since**: v1.0

## Synopsis

```
Axi\Op\Alter\DropField(collection) ->name("field_name")
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes |  |
| `name` | `string` | yes |  |

## Examples

**php**

```php
$db->execute((new Axi\Op\Alter\DropField('products'))->name('legacy_flag'));
```

**json**

```json
{"op":"drop_field","collection":"products","name":"legacy_flag"}
```

**axisql**

```axisql
ALTER COLLECTION products DROP FIELD legacy_flag
```

**cli**

```cli
axi alter table products drop-field legacy_flag
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | name vacio. |
| `COLLECTION_NOT_FOUND` | coleccion no existe. |
| `DOCUMENT_NOT_FOUND` | field no esta en schema. |

## See also

`AddField`, `RenameField`
