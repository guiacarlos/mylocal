# `rename_field`

> Renombra un field en el schema. v1 no reescribe documentos existentes.

**Since**: v1.0

## Synopsis

```
Axi\Op\Alter\RenameField(collection) ->rename("from", "to")
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes |  |
| `from` | `string` | yes |  |
| `to` | `string` | yes | snake_case. |

## Examples

**php**

```php
$db->execute((new Axi\Op\Alter\RenameField('products'))
    ->rename('precio', 'price'));
```

**json**

```json
{"op":"rename_field","collection":"products","from":"precio","to":"price"}
```

**cli**

```cli
axi alter table products rename-field precio price
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | from/to vacio o to no snake_case. |
| `COLLECTION_NOT_FOUND` | coleccion no existe. |
| `DOCUMENT_NOT_FOUND` | field "from" no esta en schema. |

## See also

`AddField`, `DropField`
