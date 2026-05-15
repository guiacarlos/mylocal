# `describe`

> Lista todas las colecciones con conteo de documentos, fields y flags.

**Since**: v1.0

## Synopsis

```
Axi\Op\System\Describe()
```

## Examples

**php**

```php
$db->execute(new Axi\Op\System\Describe());
```

**json**

```json
{"op":"describe"}
```

**cli**

```cli
axi describe
```

## See also

`Schema`, `Ping`
