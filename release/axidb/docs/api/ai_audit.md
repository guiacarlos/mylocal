# `ai.audit`

> Lee las ultimas N lineas del audit log NDJSON. Cada linea registra una Op invocada por un agente con actor=agent:<id>, params, success, code y duration_ms.

**Since**: v1.0

## Synopsis

```
Axi\Op\Ai\Audit() ->tail(n, agent_id?)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `limit` | `int` | no | Numero de entradas (1-1000). _(default: `50`)_ |
| `agent_id` | `string` | no | Filtra entradas de un agente concreto. |

## Examples

**php**

```php
$db->execute((new Axi\Op\Ai\Audit())->tail(20));
```

**json**

```json
{"op":"ai.audit","limit":20}
```

**cli**

```cli
axi ai audit --limit 20 [--agent ag_xyz]
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | limit fuera de 1..1000. |

## See also

`ListAgents`, `RunAgent`
