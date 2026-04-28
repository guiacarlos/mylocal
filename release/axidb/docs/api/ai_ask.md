# `ai.ask`

> Pregunta one-shot. Sin agent_id usa un ask-bot efimero (read-only). Con agent_id reutiliza un agente persistente. Devuelve answer + observation + history.

**Since**: v1.0

## Synopsis

```
Axi\Op\Ai\Ask() ->prompt("...") [->agent("agent-id")]
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `prompt` | `string` | yes | Pregunta o instruccion en lenguaje natural. |
| `agent_id` | `string` | no | Id del agente a interrogar. Omitir para ask-bot efimero. |

## Examples

**php**

```php
$db->execute((new Axi\Op\Ai\Ask())
    ->prompt('count products'));
```

**json**

```json
{"op":"ai.ask","prompt":"count products"}
```

**cli**

```cli
axi ai ask "count products"
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | prompt ausente o vacio. |

## See also

`Ai\NewAgent`, `Ai\ListAgents`, `Ai\RunAgent`
