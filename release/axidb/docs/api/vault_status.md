# `vault.status`

> Reporta si el vault esta unlocked en este proceso y si existen los archivos de soporte (.salt, .canary).

**Since**: v1.0

## Synopsis

```
Axi\Op\Vault\Status()
```

## Examples

**php**

```php
$r = $db->execute(new Axi\Op\Vault\Status()); var_dump($r['data']);
```

**json**

```json
{"op":"vault.status"}
```

**cli**

```cli
axi vault status
```

## See also

`Unlock`, `Lock`
