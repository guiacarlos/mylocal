# `ai.kill_agent`

> Detiene un agente (status -> killed) y libera budget. Con all=true detiene todos los agentes activos y activa el kill switch global.

**Since**: v1.0

## Synopsis

```
Axi\Op\Ai\KillAgent() ->target(agent_id) | ->target(\"\", true)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `agent_id` | `string` | no | Id especifico a matar. |
| `all` | `bool` | no | Si true, mata todos los agentes activos. |

## Examples

**php**

```php
$db->execute((new Axi\Op\Ai\KillAgent())->target('agent_xyz'));
```

**json**

```json
{"op":"ai.kill_agent","all":true}
```

**cli**

```cli
axi ai kill agent_xyz | axi ai kill-all
```

## Errors

| code | when |
| :-- | :-- |
| `DOCUMENT_NOT_FOUND` | agent_id no existe. |
| `VALIDATION_FAILED` | ni agent_id ni all. |

## See also

`ListAgents`, `RunAgent`
