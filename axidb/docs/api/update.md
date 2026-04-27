# `update`

> Modifica un documento existente. Por defecto hace merge con los campos nuevos; con replace() sustituye el documento entero.

**Since**: v1.0

## Synopsis

```
Axi\Op\Update(collection) ->id("...") ->data([...]) [->replace()]
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes |  |
| `id` | `string` | yes |  |
| `data` | `object` | yes |  |
| `replace` | `bool` | no | true = sustituye; false = merge. _(default: `false`)_ |

## Examples

**php**

```php
$db->execute((new Axi\Op\Update('notas'))
    ->id('abc123')->data(['body' => 'nuevo']));
```

**json**

```json
{"op":"update","collection":"notas","id":"abc123","data":{"body":"nuevo"}}
```

**axisql**

```axisql
UPDATE notas SET body = 'nuevo' WHERE id = 'abc123'
```

**cli**

```cli
axi update notas abc123 --set '{"body":"nuevo"}'
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | id o data ausentes. |
| `DOCUMENT_NOT_FOUND` | id no existe y modo strict. |

## See also

`Insert`, `Delete`, `Select`
