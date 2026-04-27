# `vault.lock`

> Borra la clave maestra del proceso. Lecturas/escrituras posteriores sobre colecciones cifradas fallaran con UNAUTHORIZED hasta que se haga unlock de nuevo.

**Since**: v1.0

## Synopsis

```
Axi\Op\Vault\Lock()
```

## Examples

**php**

```php
$db->execute(new Axi\Op\Vault\Lock());
```

**json**

```json
{"op":"vault.lock"}
```

**cli**

```cli
axi vault lock
```

## See also

`Unlock`, `Status`
