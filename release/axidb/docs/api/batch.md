# `batch`

> Ejecuta multiples Ops en secuencia. Si una falla, se detiene el batch (las ya ejecutadas quedan aplicadas). En v1 sin rollback transaccional.

**Since**: v1.0

## Synopsis

```
Axi\Op\Batch() ->add(Operation1) ->add(Operation2) ...
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `ops` | `Operation[]|array[]` | yes | Lista de Ops (objetos o JSON serializado). |

## Examples

**php**

```php
$db->execute((new Axi\Op\Batch())
    ->add(new Axi\Op\Insert('notas'))
    ->add(new Axi\Op\Insert('notas')));
```

**json**

```json
{"op":"batch","ops":[{"op":"insert","collection":"notas","data":{"title":"a"}},{"op":"insert","collection":"notas","data":{"title":"b"}}]}
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | ops vacio, no array, o >500 elementos. |

## See also

`Insert`, `Update`, `Delete`
