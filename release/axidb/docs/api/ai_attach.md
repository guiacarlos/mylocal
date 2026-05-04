# `ai.attach`

> Deposita un mensaje {from, to, subject, body, ts, correlation_id} en el inbox.jsonl del agente destino. Append-only.

**Since**: v1.0

## Synopsis

```
Axi\Op\Ai\Attach() ->message(to, subject, body, from?)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `to` | `string` | yes |  |
| `subject` | `string` | yes |  |
| `body` | `string` | yes |  |
| `from` | `string` | no | Id del agente emisor. Omitir para "system". |

## Examples

**php**

```php
$db->execute((new Axi\Op\Ai\Attach())
    ->message('agent_xyz', 'check', 'Revisa el inventario'));
```

**json**

```json
{"op":"ai.attach","to":"agent_xyz","subject":"check","body":"..."}
```

**cli**

```cli
axi ai attach agent_xyz --subject check --body "Revisa..."
```

## Errors

| code | when |
| :-- | :-- |
| `DOCUMENT_NOT_FOUND` | to no existe. |
| `VALIDATION_FAILED` | to/subject/body vacios. |

## See also

`Broadcast`, `RunAgent`
