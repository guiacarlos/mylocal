# `auth.create_user`

> Alta de usuario. Hashea la password antes de persistir. Devuelve el user sin hash.

**Since**: v1.0

## Synopsis

```
Axi\Op\Auth\CreateUser() ->profile(email, password, role?)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `email` | `string` | yes |  |
| `password` | `string` | yes | Minimo 8 chars. |
| `role` | `string` | no |  _(default: `"user"`)_ |
| `extra` | `object` | no | Campos adicionales del profile. |

## Examples

**php**

```php
$db->execute((new Axi\Op\Auth\CreateUser())
    ->profile('a@b.c', 'secret123', 'admin'));
```

**json**

```json
{"op":"auth.create_user","email":"a@b.c","password":"secret123","role":"admin"}
```

**cli**

```cli
axi user create a@b.c --role admin
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | email invalido o password < 8. |
| `CONFLICT` | email ya registrado. |

## See also

`Login`, `GrantRole`
