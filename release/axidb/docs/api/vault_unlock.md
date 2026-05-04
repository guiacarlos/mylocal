# `vault.unlock`

> Deriva la clave maestra del vault con la password admin. Necesario antes de leer/escribir colecciones cifradas. Estado vive en el proceso.

**Since**: v1.0

## Synopsis

```
Axi\Op\Vault\Unlock() ->password("...")
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `password` | `string` | yes | Password admin del vault. |

## Examples

**php**

```php
$db->execute((new Axi\Op\Vault\Unlock())->password('s3cret'));
```

**json**

```json
{"op":"vault.unlock","password":"s3cret"}
```

**cli**

```cli
axi vault unlock --password s3cret
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | password vacio. |
| `UNAUTHORIZED` | password incorrecto. |

## See also

`Lock`, `Status`
