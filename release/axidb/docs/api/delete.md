# `delete`

> Baja de documento. Por defecto soft-delete (marca _deletedAt). hard() borra el archivo.

**Since**: v1.0

## Synopsis

```
Axi\Op\Delete(collection) ->id("...") [->hard()]
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes |  |
| `id` | `string` | yes |  |
| `hard` | `bool` | no |  _(default: `false`)_ |

## Examples

**php**

```php
$db->execute((new Axi\Op\Delete('notas'))->id('abc123'));
```

**json**

```json
{"op":"delete","collection":"notas","id":"abc123"}
```

**axisql**

```axisql
DELETE FROM notas WHERE id = 'abc123'
```

**cli**

```cli
axi delete notas abc123 [--hard]
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | id ausente o vacio. |
| `DOCUMENT_NOT_FOUND` | id no existe en la coleccion. |

## See also

`Update`, `Insert`, `Select`
