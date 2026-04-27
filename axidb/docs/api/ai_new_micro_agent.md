# `ai.new_micro_agent`

> Crea un microagente efimero hijo de un agente primario. Se autodestruye al completar (status=done) o agotar max_steps. Profundidad maxima 3.

**Since**: v1.0

## Synopsis

```
Axi\Op\Ai\NewMicroAgent() ->spawn(parent_id, task, max_steps?)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `parent_id` | `string` | yes | Id del agente primario que lo crea. |
| `task` | `string` | yes | Descripcion de la tarea acotada (rol del micro). |
| `max_steps` | `int` | no |  _(default: `10`)_ |

## Examples

**php**

```php
$db->execute((new Axi\Op\Ai\NewMicroAgent())
    ->spawn('agent_xyz', 'Indexar documentos sin tag', 50));
```

**json**

```json
{"op":"ai.new_micro_agent","parent_id":"agent_xyz","task":"...","max_steps":50}
```

**cli**

```cli
axi ai spawn agent_xyz "Indexar documentos" --max-steps 50
```

## Errors

| code | when |
| :-- | :-- |
| `DOCUMENT_NOT_FOUND` | parent_id no existe. |
| `FORBIDDEN` | Profundidad >= 3 o max_children agotado. |
| `VALIDATION_FAILED` | parent_id o task vacios. |

## See also

`NewAgent`, `KillAgent`, `RunAgent`
