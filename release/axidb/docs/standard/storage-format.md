# AxiDB Storage Format — Especificacion fisica v1.0

**Estado**: Fase 1.4 cerrada (`FsJsonDriver`).
**Impl de referencia**: [axidb/engine/Storage/FsJsonDriver.php](../../engine/Storage/FsJsonDriver.php).
**Interface**: [axidb/engine/Storage/Driver.php](../../engine/Storage/Driver.php).

---

## 1. Principio

Un documento = un archivo JSON. La coleccion es un directorio. Namespaces (multi-tenant) son directorios raiz. Nada de bases binarias opacas: cualquier editor de texto abre y diagnostica el contenido. El driver abstracto (`Axi\Engine\Storage\Driver`) permite cambiar esto en v2 (p.ej. `PackedDriver` con MessagePack) sin tocar los Ops.

---

## 2. Layout fisico (driver `FsJsonDriver`)

```
<STORAGE_ROOT>/
├── <collection_a>/
│   ├── _meta.json             ← schema, flags, indices (gestionado por MetaStore)
│   ├── ._lock                 ← fichero de lock flock() (oculto)
│   ├── <doc_id_1>.json        ← un archivo por documento
│   ├── <doc_id_2>.json
│   └── ...
├── <collection_b>/
│   └── ...
└── _system/                   ← (futuro Fase 3) audit.log, snapshots, agentes
```

Claves:

- **Nombres de coleccion y documento** validados con regex `^[A-Za-z0-9][A-Za-z0-9_\-.]*$`, maximo 128 chars. Anti-path-traversal estricto: rechaza `..`, `/`, `\`, null byte, strings vacios.
- **Archivos que empiezan por `_`** (`_meta.json`, `_lock`, `_versions/`, `_changelog.log`) son reservados del sistema. `listIds()` los ignora.
- **Archivos `.tmp.*`** son intermedios de escritura atomica; `listIds()` tambien los ignora.

---

## 3. Forma de un documento

Cada archivo `<id>.json` contiene un objeto JSON con metadata estandar + datos libres:

```json
{
  "_id":        "20260424114955ae4da3b9",
  "_version":   3,
  "_createdAt": "2026-04-24T11:49:55+00:00",
  "_updatedAt": "2026-04-24T12:03:17+00:00",
  "_deletedAt": null,

  "title":      "Primera nota",
  "body":       "contenido libre...",
  "tags":       ["intro", "demo"]
}
```

**Campos reservados** (no usar como nombres de campo aplicativo):

| Campo | Tipo | Descripcion |
| :-- | :-- | :-- |
| `_id` | string | Id unico dentro de la coleccion. Auto-generado (time-ordered) si no se da. |
| `_version` | int | Autoincrementa en cada write. Inicio: 1. |
| `_createdAt` | ISO-8601 | Fijado en primer write, inmutable. |
| `_updatedAt` | ISO-8601 | Se actualiza en cada write. |
| `_deletedAt` | ISO-8601 o null | Soft delete (opcional). Default: no presente. |

**Nombres de field validos** (para documentos): `^[a-z][a-z0-9_]*$` (snake_case). Los que empiezan por `_` son reservados del motor.

---

## 4. Escritura atomica

Todo `writeDoc()` sigue este protocolo para evitar half-writes:

```
1. Construir path tmp aleatorio: <collection>/<id>.json.tmp.<hex-random>
2. file_put_contents(tmp, json_encode($data))
3. chmod(tmp, 0600)
4. rename(tmp, <collection>/<id>.json)   ← POSIX atomic
5. Si rename falla: unlink(tmp), lanzar AxiException INTERNAL_ERROR
```

**Invariante**: un crash entre paso 1 y 4 deja un `.tmp.*` huerfano, pero **nunca corrompe** el `<id>.json` original. El tmp puede limpiarse con `axi storage gc` (comando futuro) o manualmente — no afecta el funcionamiento.

**Merge vs replace**: por defecto `writeDoc()` hace **merge** con el documento existente (preserva campos no presentes en la entrada). Para sustituir entero, pasar `_REPLACE_: true` en los datos (se elimina antes de persistir).

---

## 5. Locks

```
acquireLock(collection, timeoutMs = 5000) -> resource
releaseLock(resource)
```

Implementado con `flock(fp, LOCK_EX | LOCK_NB)` en un archivo `<collection>/._lock`. Si no se puede adquirir en `timeoutMs`, lanza `AxiException::CONFLICT`.

**Uso previsto**: `Batch` Op adquiere lock una vez, ejecuta N Ops, libera. Evita N locks individuales y ofrece consistencia relativa entre operaciones en un mismo batch.

**Limitacion v1**: no hay transaccion real (rollback) — si el batch falla en el op N, los N-1 previos ya estan persistidos. Fase 2 evalua si anadir WAL o compensacion.

---

## 6. Permisos

Por defecto en instalacion Unix:

- Directorios: `0700` (solo owner).
- Archivos de documento: `0600` (solo owner).
- `_lock`: `0600`.

El default es intencionalmente estricto. Si necesitas compartir con el webserver (www-data, etc.), ajusta el ownership tras crear la carpeta.

---

## 7. Indices (v1 y plan)

**v1**: sin indices fisicos. `listIds()` hace `scandir()` del directorio de la coleccion; el filtrado y ordering lo hace `QueryEngine` en memoria. Aceptable hasta ~10.000 docs por coleccion.

**Fase 1.4b** (prevista): `<collection>/_index/<field>.idx` — archivos binarios con indice ordenado por campo, declarados via `Op\Alter\CreateIndex`. El Driver se encarga de mantenerlos consistentes en write/delete.

**Fase 2** (AxiSQL): el planner usa los indices declarados para evitar scans full.

---

## 8. Versioning opcional

Si `_meta.flags.keep_versions == true`:

```
<collection>/_versions/<doc_id>/
├── v1.json
├── v2.json
└── v3.json      ← version actual, identica al <doc_id>.json principal
```

Cada `writeDoc()` escribe una copia bajo `_versions/<id>/<timestamp>.json` antes de sobrescribir. Permite inspeccion historica via `Op\Describe` (Fase 2+ pondra una Op `History` dedicada).

Si la flag es `false` (default), no se mantiene historial. Decision explicita: el default es lean.

---

## 9. Changelog opcional (Fase 2)

Si `_meta.flags.changelog == true`:

```
<collection>/_changelog.log
```

Append-only JSON-lines. Una linea por write/delete:

```json
{"op":"insert","id":"xyz","ts":"...","user":"..."}
{"op":"update","id":"xyz","ts":"...","user":"...","version":2}
{"op":"delete","id":"xyz","ts":"...","user":"...","hard":false}
```

Util para agentes IA que quieren seguir cambios sin re-escanear la coleccion, y para replicacion a un AxiDB secundario (Fase futura).

---

## 10. Multi-tenant (namespaces)

El `namespace` de una Op (default `'default'`) se mapea a un subdirectorio debajo de `<STORAGE_ROOT>`:

```
<STORAGE_ROOT>/
├── default/
│   └── <collection>/...
├── project_socola/
│   └── <collection>/...
└── project_portfolio/
    └── <collection>/...
```

**Nota v1**: esta estructura esta prevista en el diseno pero no enforced por el driver actual — `FsJsonDriver` trata el `basePath` como raiz unica. El motor Fase 1.5+ anade un parametro por constructor o un switch por Op. Para Socolá actual (mono-tenant) no afecta.

---

## 11. Compatibilidad con el layout legacy ACIDE

El `StorageManager` legacy (pre-driver) usa el mismo formato basico (un archivo por doc), pero con diferencias:

- Mantiene `_index.json` generado en cada write (cacheable).
- Versiones bajo `.versions/<collection>/<id>/<timestamp>.json` (nombre de dir oculto).
- Master collections (`users`, `roles`, `projects`, `system_logs`, `vault`) viven en `DATA_ROOT` (global), no en el namespace del proyecto.

El `FsJsonDriver` no replica `_index.json` (decidimos lean), pero coexiste con el legacy durante la transicion: ambos leen/escriben del mismo `<id>.json`. La migracion completa a driver-only llega en Fase 5.

---

## 12. Reglas para modificar este formato

1. **Nunca** quitar un campo reservado `_*` sin migrador documentado.
2. **Anadir** campos reservados nuevos requiere bumpear a v1.x (compatible) o v2 (breaking).
3. Cambios de nombre de archivo reservado (`_meta.json` → `_schema.json`, etc.) son **breaking** y requieren migrador.
4. El `FsJsonDriver` es la impl de referencia; otros drivers deben respetar el mismo contrato `Driver` aunque el formato fisico interno sea distinto.
