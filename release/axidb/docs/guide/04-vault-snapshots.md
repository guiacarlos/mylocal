# Vault y Snapshots — Cifrado y backups

**Estado**: **DISPONIBLE EN v1.0 tras Fase 3** ✅.
**Vault**: `Axi\Engine\Vault\Vault` + `Vault\Crypto`.
**Backups**: `Axi\Engine\Backup\SnapshotStore` + `Backup\Manifest`.
**Requisitos**: extensiones PHP `openssl` (vault) y `zip` (backups). Estandar.

---

## Vault — cifrado AES-256-GCM por coleccion

### 1. Como funciona

El motor cifra **transparente** los documentos de las colecciones marcadas
con `_meta.flags.encrypted: true`. Tu codigo usa los Ops normales
(`Insert`, `Select`, `Update`, `Delete`) y el motor:

1. Al **escribir**: serializa el doc, lo cifra con AES-256-GCM y lo guarda
   con shape `{_id, _version, _createdAt, _updatedAt, _enc: "<envelope-base64>"}`.
2. Al **leer**: detecta `_enc`, lo descifra, mergea con los `_*` reservados
   y devuelve el doc original.

Los campos reservados `_id`, `_version`, `_createdAt`, `_updatedAt`,
`_deletedAt` quedan **en claro** porque los necesitan los indices y la
auditoria. Todo lo demas (email, secretos, payload aplicativo) se cifra.

La clave maestra se deriva con **PBKDF2-SHA256 (100k iteraciones)** desde
una password admin + salt unico de la instalacion (`storage/../vault/.salt`,
generada al primer unlock con `random_bytes(32)`).

### 2. Quickstart

```bash
# 1. La primera vez: unlock genera el salt y un canary cifrado.
$ axi vault unlock --password "tu-master-key-fuerte"
[ok] {"unlocked":true}

# 2. Crea una coleccion cifrada.
$ axi sql 'CREATE COLLECTION users WITH (encrypted = true)'
[ok] ...

# 3. Inserta y lee transparente.
$ axi sql "INSERT INTO users (email, secret) VALUES ('a@b.c', 'TopSecret')"
[ok] {"_id": "20260424...", "email": "a@b.c", "secret": "TopSecret", ...}

$ axi sql "SELECT * FROM users"
+----------------+--------+------------+
| _id            | email  | secret     |
+----------------+--------+------------+
| 20260424...    | a@b.c  | TopSecret  |
+----------------+--------+------------+

# 4. Inspecciona el archivo en disco: NO veras el secret en claro.
$ cat STORAGE/users/20260424...json
{
    "_id": "20260424...",
    "_version": 1,
    "_createdAt": "...",
    "_updatedAt": "...",
    "_enc": "BgaXmsTW9..."
}

# 5. Lock vacia la clave del proceso. Lecturas posteriores -> UNAUTHORIZED.
$ axi vault lock
[ok] {"locked":true}

$ axi sql "SELECT * FROM users"
[ERR] UNAUTHORIZED: Vault: bloqueado. Ejecuta vault.unlock antes de leer...
```

### 3. Desde PHP

```php
<?php
require 'axidb/axi.php';

use Axi\Sdk\Php\Client;

$db = new Client();

// Unlock (necesario una vez por proceso PHP).
$db->execute(['op' => 'vault.unlock', 'password' => 'tu-master-key']);

// Operar con normalidad.
$db->collection('users')->insert(['email' => 'a@b.c', 'secret' => 'X']);
$users = $db->collection('users')->get();   // descifrado transparente

// Lock cuando termines (o dejas que el proceso muera).
$db->execute(['op' => 'vault.lock']);
```

### 4. Modelo de seguridad y limitaciones v1

- **Estado por proceso**. La clave maestra vive en memoria del proceso PHP.
  CLI uno-shot: `unlock` antes de cada comando o usa una sesion bash que
  re-lance el proceso con el flag `--password`. HTTP por-request:
  cliente envia password via header `Authorization: Bearer <pwd>` (futura
  Fase 6 anade tokens de sesion derivados).
- **Sin recuperacion** si pierdes la password. No hay backdoor: si
  olvidas la password, los datos cifrados son inrecuperables. Guarda
  la password en un gestor de credenciales seguro.
- **Cifrado solo de payload**, no de metadatos. `_id`, `_version`,
  `_createdAt`, `_updatedAt` siguen visibles. Si necesitas que el id
  no revele info, usa ULIDs (no nombres semanticos).
- **No se cifra `_meta.json`** (schema de la coleccion). Solo
  documentos.

---

## Snapshots — backups full e incrementales

### 1. Como funciona

`SnapshotStore` empaqueta colecciones en un archivo zip + un
`manifest.json` con metadata. Layout:

```
storage/../backups/snapshots/<nombre>/
├── manifest.json    {name, type, ts, base_snapshot?, collections, counts}
└── data.zip         entries: <collection>/<doc-id>.json + <collection>/_meta.json
```

Tipos:
- **full**: incluye todos los documentos no-`_*` de cada coleccion.
- **incremental**: solo documentos cuyo `_updatedAt` > timestamp del
  snapshot base. Necesita una snapshot base (full o anterior incremental).

Las **colecciones cifradas se respaldan tal cual** (con su `_enc`).
Para restaurar y leer, necesitas la misma password que cuando se cifraron.

### 2. Crear snapshots

```bash
# Full
$ axi backup create pre-deploy
[ok] {"name": "pre-deploy", "type": "full", "ts": "...", "collections": [...]}

# Incremental sobre el ultimo snapshot disponible
$ axi backup create daily-2026-04-24 --incremental
[ok] {"name": "daily-2026-04-24", "type": "incremental", "base_snapshot": "pre-deploy", ...}

# Incremental con base explicita
$ axi backup create week-2 --incremental --base pre-deploy
```

### 3. Listar y restaurar

```bash
$ axi backup list
NAME                            TYPE          TIMESTAMP                 COLLECTIONS
------------------------------------------------------------------------------------------
daily-2026-04-24                incremental   2026-04-24T10:00:00+00:00 1
pre-deploy                      full          2026-04-23T23:55:12+00:00 5

# Dry-run: lista sin escribir.
$ axi backup restore pre-deploy --dry-run
[ok] {"restored": 145, "files": [...], "dry_run": true}

# Restore real (extrae al STORAGE/).
$ axi backup restore pre-deploy
[ok] {"restored": 145, "files": [...], "dry_run": false}

# Borrar
$ axi backup drop daily-2026-04-24
[ok] {"dropped": true, "name": "daily-2026-04-24"}
```

### 4. Desde PHP

```php
$db->execute(['op' => 'backup.create', 'name' => 'pre-migration']);

// Restore con dry-run para inspeccionar antes
$preview = $db->execute(['op' => 'backup.restore', 'name' => 'pre-migration', 'dry_run' => true]);
echo "Va a restaurar " . $preview['data']['restored'] . " archivos\n";

// Confirmar
$db->execute(['op' => 'backup.restore', 'name' => 'pre-migration']);
```

### 5. Estrategia recomendada

- **Full diario**: `axi backup create daily-$(date +%F)` desde cron.
- **Incremental cada hora** (en horario laboral): `axi backup create
  hourly-$(date +%F-%H) --incremental`.
- **Limpieza**: borrar snapshots con > N dias. AxiDB v1 no tiene
  retencion automatica — escribelo en cron como `axi backup drop`.
- **Probar restore en staging** mensualmente. Un backup que no se ha
  probado restaurando no es un backup, es esperanza.

### 6. Limitaciones v1

- **Sin compresion adicional**. ZIP por defecto. Para colecciones
  grandes considera comprimir el zip resultante por separado.
- **Sin cifrado del propio snapshot**. Si las colecciones eran cifradas,
  el zip contiene los `_enc` opacos (seguros). Si eran en claro, el
  zip lo es. Almacena snapshots en sitio seguro.
- **Sin verificacion de integridad** mas alla de la del zip. Fase 3.1
  evalua firmas HMAC del manifest.
- **No hace dump** del `_meta.json` con flag `encrypted` ni de la salt
  del vault. Recuerda backupear `vault/.salt` y `vault/.canary`
  manualmente o con tu solucion de backup del FS.

---

## Codigos de error

| Codigo | Cuando |
| :-- | :-- |
| `UNAUTHORIZED`         | Lectura/escritura sobre coleccion cifrada con vault locked, o password incorrecta en `vault.unlock`. |
| `VALIDATION_FAILED`    | Password vacio. Nombre de snapshot invalido. |
| `CONFLICT`             | Snapshot con ese nombre ya existe. Envelope cifrado corrupto. |
| `DOCUMENT_NOT_FOUND`   | `backup.restore` o `backup.drop` sobre snapshot inexistente. |
| `INTERNAL_ERROR`       | openssl_encrypt fallo, ZipArchive::open fallo, salt < 16 bytes. |

---

## Ver tambien

- [../api/vault_unlock.md](../api/vault_unlock.md), [vault_lock.md](../api/vault_lock.md), [vault_status.md](../api/vault_status.md) — referencia auto-generada.
- [../api/backup_create.md](../api/backup_create.md), [backup_restore.md](../api/backup_restore.md), [backup_list.md](../api/backup_list.md), [backup_drop.md](../api/backup_drop.md).
- [../standard/storage-format.md](../standard/storage-format.md) — layout fisico.
- [03-axisql.md](03-axisql.md) — `CREATE COLLECTION ... WITH (encrypted = true)`.
- `axi help vault.unlock`, `axi help backup.create`, etc.
