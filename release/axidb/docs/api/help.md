# `help`

> Sin target devuelve el indice de Ops. Con target devuelve el HelpEntry completo de ese Op.

**Since**: v1.0

## Synopsis

```
Axi\Op\System\Help() [->forOp("select")]
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `target` | `string` | no | Nombre del Op (p.ej. "select", "ai.ask"). Omitir para el indice. |

## Examples

**php**

```php
$db->execute((new Axi\Op\System\Help())->forOp('select'));
```

**json**

```json
{"op":"help","target":"select"}
```

**cli**

```cli
axi help | axi help select
```

## Errors

| code | when |
| :-- | :-- |
| `OP_UNKNOWN` | target no esta en el registry. |

## See also

`Describe`, `Schema`
