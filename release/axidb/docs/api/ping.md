# `ping`

> Health check del motor. Devuelve estado y servicios activos. Sin params.

**Since**: v1.0

## Synopsis

```
Axi\Op\System\Ping()
```

## Examples

**php**

```php
$db->execute(new Axi\Op\System\Ping());
```

**json**

```json
{"op":"ping"}
```

**cli**

```cli
axi ping
```

## See also

`Describe`, `Schema`
