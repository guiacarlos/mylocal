# `backup.create`

> Crea un snapshot zip del STORAGE actual. Sin --incremental hace full; con incremental + base toma solo docs cambiados desde la ts del base. Si no se da base, usa el ultimo snapshot disponible.

**Since**: v1.0

## Synopsis

```
Axi\Op\Backup\Create() ->spec(name, incremental?, base?)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `name` | `string` | yes | Nombre del snapshot. Solo [A-Za-z0-9_\-.], max 81 chars. |
| `incremental` | `bool` | no |  _(default: `false`)_ |
| `base` | `string` | no | Nombre del snapshot base (solo si incremental). |

## Examples

**php**

```php
$db->execute((new Axi\Op\Backup\Create())->spec('pre-migration'));
```

**json**

```json
{"op":"backup.create","name":"daily","incremental":true}
```

**cli**

```cli
axi backup create pre-migration  |  axi backup create daily --incremental
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | name vacio/invalido o incremental sin snapshot base disponible. |
| `CONFLICT` | snapshot con ese nombre ya existe. |

## See also

`Restore`, `List_`, `Drop`
