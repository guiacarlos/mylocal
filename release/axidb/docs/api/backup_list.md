# `backup.list`

> Devuelve todos los snapshots con sus manifests (name, type, ts, counts, base_snapshot).

**Since**: v1.0

## Synopsis

```
Axi\Op\Backup\ListSnapshots()
```

## Examples

**php**

```php
$r = $db->execute(new Axi\Op\Backup\ListSnapshots());
```

**json**

```json
{"op":"backup.list"}
```

**cli**

```cli
axi backup list
```

## See also

`Create`, `Restore`, `Drop`
