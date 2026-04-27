# `insert`

> Alta de un nuevo documento. Si se omite el id, el motor lo genera (ordenable por tiempo).

**Since**: v1.0

## Synopsis

```
Axi\Op\Insert(collection) ->data([...]) [->id("...")]
```

## Parameters

| name | type | required | description |
| :-- | :-- | :--: | :-- |
| `collection` | `string` | yes | Nombre de la coleccion destino. |
| `data` | `object` | yes | Mapa field=>value con los datos del nuevo documento. |
| `id` | `string` | no | Id explicito. Si se omite, se genera automaticamente. |

## Examples

**php**

```php
$db->execute((new Axi\Op\Insert('notas'))
    ->data(['title' => 'test', 'body' => 'hola']));
```

**json**

```json
{"op":"insert","collection":"notas","data":{"title":"test","body":"hola"}}
```

**axisql**

```axisql
INSERT INTO notas (title, body) VALUES ('test', 'hola')
```

**cli**

```cli
axi insert notas --data '{"title":"test","body":"hola"}'
```

## Errors

| code | when |
| :-- | :-- |
| `VALIDATION_FAILED` | data ausente/vacia, id no-string, collection vacia. |
| `CONFLICT` | id especificado ya existe y modo strict. |

## See also

`Update`, `Delete`, `Select`
