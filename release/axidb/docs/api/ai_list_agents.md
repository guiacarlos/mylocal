# `ai.list_agents`

> Devuelve la lista de agentes registrados con su estado, tools y contadores de budget. Filtrable por status o parent.

**Since**: v1.0

## Synopsis

```
Axi\Op\Ai\ListAgents() [->filter(status, parent_id)]
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `status` | `string` | no | idle|running|waiting|errored|killed|done. |
| `parent_id` | `string` | no | Lista solo microagentes de un parent concreto. |

## Examples

**php**

```php
$db->execute(new Axi\Op\Ai\ListAgents());
```

**json**

```json
{"op":"ai.list_agents","status":"running"}
```

**cli**

```cli
axi ai list-agents [--status running]
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | status no valido. |

## See also

`NewAgent`, `KillAgent`, `RunAgent`
