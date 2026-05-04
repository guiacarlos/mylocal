# `backup.drop`

> Elimina un snapshot del filesystem. Operacion destructiva, sin papelera.

**Since**: v1.0

## Synopsis

```
Axi\Op\Backup\Drop() ->name("...")
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `name` | `string` | yes |  |

## Examples

**php**

```php
$db->execute((new Axi\Op\Backup\Drop())->name('old-2025'));
```

**json**

```json
{"op":"backup.drop","name":"old-2025"}
```

**cli**

```cli
axi backup drop old-2025
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | name ausente. |
| `DOCUMENT_NOT_FOUND` | snapshot no existe. |

## See also

`Create`, `List_`, `Restore`
