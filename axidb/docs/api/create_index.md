# `create_index`

> Declara un indice secundario. En v1 solo se registra en meta; la construccion fisica llega con StorageDriver (Fase 1.4).

**Since**: v1.0

## Synopsis

```
Axi\Op\Alter\CreateIndex(collection) ->field("field", unique?)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes |  |
| `field` | `string` | yes |  |
| `unique` | `bool` | no |  _(default: `false`)_ |

## Examples

**php**

```php
$db->execute((new Axi\Op\Alter\CreateIndex('users'))
    ->field('email', true));
```

**json**

```json
{"op":"create_index","collection":"users","field":"email","unique":true}
```

**axisql**

```axisql
CREATE UNIQUE INDEX ON users (email)
```

**cli**

```cli
axi alter table users create-index email --unique
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | field vacio. |
| `CONFLICT` | indice ya existe. |

## See also

`DropIndex`, `AddField`
