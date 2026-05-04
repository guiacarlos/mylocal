# `auth.logout`

> Invalida un token de sesion. Idempotente: si el token no existe, devuelve logged_out=false.

**Since**: v1.0

## Synopsis

```
Axi\Op\Auth\Logout() ->token("...")
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `token` | `string` | yes |  |

## Examples

**php**

```php
$db->execute((new Axi\Op\Auth\Logout())->token('abc...'));
```

**json**

```json
{"op":"auth.logout","token":"abc..."}
```

**cli**

```cli
axi auth logout --token abc...
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | token vacio. |

## See also

`Login`
