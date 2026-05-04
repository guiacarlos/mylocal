# `ai.run_agent`

> Dispara el loop del Kernel sobre el agente. Ejecuta receive -> think -> act -> observe hasta que el agente declara done o agota budget. Devuelve answer + history + status final.

**Since**: v1.0

## Synopsis

```
Axi\Op\Ai\RunAgent() ->run(agent_id, input?)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `agent_id` | `string` | yes |  |
| `input` | `string` | no | Mensaje inicial. Si se omite, procesa solo el inbox. |

## Examples

**php**

```php
$db->execute((new Axi\Op\Ai\RunAgent())
    ->run('agent_xyz', 'Revisa productos sin imagen'));
```

**json**

```json
{"op":"ai.run_agent","agent_id":"agent_xyz","input":"..."}
```

**cli**

```cli
axi ai run agent_xyz "Revisa productos sin imagen"
```

## Errors

| code | when |
| :-- | :-- |
| `DOCUMENT_NOT_FOUND` | agent_id no existe. |
| `FORBIDDEN` | Kill switch global activo. |
| `CONFLICT` | Agente ya esta done/killed/errored. |
| `VALIDATION_FAILED` | agent_id vacio. |

## See also

`NewAgent`, `KillAgent`, `Ask`, `ListAgents`
