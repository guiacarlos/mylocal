# `auth.revoke_role`

> Retira un rol del array users[].roles. Si era el role singular, cae a "user" por defecto.

**Since**: v1.0

## Synopsis

```
Axi\Op\Auth\RevokeRole() ->remove(user_id, role)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `user_id` | `string` | yes |  |
| `role` | `string` | yes |  |

## Examples

**php**

```php
$db->execute((new Axi\Op\Auth\RevokeRole())
    ->remove('usr_abc', 'editor'));
```

**json**

```json
{"op":"auth.revoke_role","user_id":"usr_abc","role":"editor"}
```

**cli**

```cli
axi user revoke usr_abc editor
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | user_id o role vacios. |
| `DOCUMENT_NOT_FOUND` | user no existe. |

## See also

`GrantRole`
