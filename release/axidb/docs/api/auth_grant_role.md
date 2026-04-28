# `auth.grant_role`

> Anade un rol al array users[].roles. Idempotente: no duplica.

**Since**: v1.0

## Synopsis

```
Axi\Op\Auth\GrantRole() ->assign(user_id, role)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `user_id` | `string` | yes |  |
| `role` | `string` | yes |  |

## Examples

**php**

```php
$db->execute((new Axi\Op\Auth\GrantRole())
    ->assign('usr_abc', 'editor'));
```

**json**

```json
{"op":"auth.grant_role","user_id":"usr_abc","role":"editor"}
```

**cli**

```cli
axi user grant usr_abc editor
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | user_id o role vacios. |
| `DOCUMENT_NOT_FOUND` | user no existe. |

## See also

`RevokeRole`, `CreateUser`
