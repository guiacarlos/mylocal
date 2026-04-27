# `exists`

> Devuelve true/false segun exista un doc por id o matching la clausula where.

**Since**: v1.0

## Synopsis

```
Axi\Op\Exists(collection) ->id("...") | ->where(...)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes |  |
| `id` | `string` | no | Id exacto. Alternativa a where. |
| `where` | `clause[]` | no | Alternativa a id. |

## Examples

**php**

```php
$db->execute((new Axi\Op\Exists('users'))->where('email', '=', 'a@b.c'));
```

**json**

```json
{"op":"exists","collection":"users","id":"abc123"}
```

**cli**

```cli
axi exists users --where "email=a@b.c"
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | ni id ni where proporcionados. |

## See also

`Count`, `Select`
