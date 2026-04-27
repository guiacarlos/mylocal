# `ai.new_agent`

> Crea un agente primario persistente con identidad, role, sandbox de tools, budget y backend LLM. Disponible en Fase 6.

**Since**: v1.0

## Synopsis

```
Axi\Op\Ai\NewAgent() ->spec(name, role, tools?, budget?, llm?)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `name` | `string` | yes | Identificador logico. |
| `role` | `string` | yes | Prompt de sistema que define comportamiento. |
| `tools` | `string[]` | no | Lista de Ops del catalogo permitidas. |
| `budget` | `object` | no | {max_steps, max_tokens, max_children}. |
| `llm` | `string` | no | noop | groq:<model> | ollama:<model>. _(default: `"noop"`)_ |

## Examples

**php**

```php
$db->execute((new Axi\Op\Ai\NewAgent())
    ->spec('reviewer', 'Revisa productos nuevos', ['select','count']));
```

**json**

```json
{"op":"ai.new_agent","name":"reviewer","role":"...","tools":["select","count"]}
```

**cli**

```cli
axi ai new-agent reviewer --role "..." --tools select,count
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | name o role vacios. |

## See also

`NewMicroAgent`, `RunAgent`, `ListAgents`, `Ask`
