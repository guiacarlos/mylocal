# `add_field`

> Registra un field en el schema de la coleccion. No altera docs existentes.

**Since**: v1.0

## Synopsis

```
Axi\Op\Alter\AddField(collection) ->field(name, type, required?, default?)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes |  |
| `field` | `object` | yes | {name, type, required?, default?} |

## Examples

**php**

```php
$db->execute((new Axi\Op\Alter\AddField('products'))
    ->field('discount', 'number', false, 0));
```

**json**

```json
{"op":"add_field","collection":"products","field":{"name":"discount","type":"number","required":false,"default":0}}
```

**axisql**

```axisql
ALTER COLLECTION products ADD FIELD discount number DEFAULT 0
```

**cli**

```cli
axi alter table products add-field discount number --default 0
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | name vacio, no snake_case, o reservado (_*). |
| `CONFLICT` | field ya existe. |

## See also

`DropField`, `RenameField`, `CreateIndex`
