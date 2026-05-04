# `auth.login`

> Autentica un usuario y devuelve token Bearer. El token se guarda en cookie httponly si llega via HTTP.

**Since**: v1.0

## Synopsis

```
Axi\Op\Auth\Login() ->credentials(email, password)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `email` | `string` | yes |  |
| `password` | `string` | yes |  |

## Examples

**php**

```php
$db->execute((new Axi\Op\Auth\Login())
    ->credentials('a@b.c', '***'));
```

**json**

```json
{"op":"auth.login","email":"a@b.c","password":"***"}
```

**cli**

```cli
axi auth login --email a@b.c --password ***
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | email o password vacios. |
| `UNAUTHORIZED` | credenciales invalidas. |

## See also

`Logout`, `CreateUser`
