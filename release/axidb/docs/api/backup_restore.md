# `backup.restore`

> Extrae un snapshot al STORAGE actual. Con dry_run no escribe nada; solo lista los archivos que se restaurarian.

**Since**: v1.0

## Synopsis

```
Axi\Op\Backup\Restore() ->spec(name, dry_run?)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `name` | `string` | yes |  |
| `dry_run` | `bool` | no |  _(default: `false`)_ |

## Examples

**php**

```php
$db->execute((new Axi\Op\Backup\Restore())->spec('pre-migration'));
```

**json**

```json
{"op":"backup.restore","name":"pre-migration","dry_run":true}
```

**cli**

```cli
axi backup restore pre-migration --dry-run
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | name ausente. |
| `DOCUMENT_NOT_FOUND` | snapshot no existe. |
| `INTERNAL_ERROR` | data.zip ausente o corrupto. |

## See also

`Create`, `List_`
