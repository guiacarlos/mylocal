# `ai.broadcast`

> Deposita una copia del mensaje en el inbox de todos los agentes cuyo role o name matchean el patron glob. Devuelve el contador delivered.

**Since**: v1.0

## Synopsis

```
Axi\Op\Ai\Broadcast() ->send(pattern, message, from?)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `pattern` | `string` | yes | Patron glob (ej: "reviewer*"). Matchea role o name. |
| `message` | `string` | yes |  |
| `from` | `string` | no |  |

## Examples

**php**

```php
$db->execute((new Axi\Op\Ai\Broadcast())
    ->send('reviewer*', 'Stop current work'));
```

**json**

```json
{"op":"ai.broadcast","pattern":"reviewer*","message":"Stop current work"}
```

**cli**

```cli
axi ai broadcast "reviewer*" "Stop current work"
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | pattern o message vacios. |

## See also

`Attach`, `ListAgents`
