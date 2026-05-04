# `legacy.action`

> Wrapper formal de las acciones ACIDE legacy de Socola. Permite que un cliente Op model invoque cualquier action legacy (list_products, get_mesa_settings, process_external_order, etc) sin abandonar el contrato {op:...}. Durante la migracion (Fase 5) los clientes pueden ir reemplazando las acciones legacy por Ops nativas progresivamente.

**Since**: v1.0

## Synopsis

```
Axi\Op\System\LegacyAction() ->action(name, data?)
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `name` | `string` | yes | Nombre del action ACIDE (ej. list_products, get_mesa_settings). |
| `data` | `object` | no | Datos adicionales del payload del action. |

## Examples

**php**

```php
$db->execute((new Axi\Op\System\LegacyAction())
    ->action('list_products'));
```

**json**

```json
{"op":"legacy.action","name":"list_products"}
```

**cli**

```cli
axi legacy.action --name list_products
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | name vacio o no string. |
| `INTERNAL_ERROR` | el legacy ACIDE devuelve success=false sin mensaje claro. |

## See also

`Sql`, `Help`
