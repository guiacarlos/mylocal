# AxiDB Op Model — Especificacion formal v1.0

**Estado**: Fase 1.3 cerrada (33 Ops + System/Help = 34).
**Alcance**: contrato de serializacion, ejecucion y validacion de Ops. Transport-agnostico.

---

## 1. Principio

Toda interaccion con el motor AxiDB (PHP embebido, HTTP JSON, AxiSQL, CLI, agente IA) se reduce a una **Operacion** (Op). Las Ops son objetos inmutables con forma conocida, serializables a/desde JSON, con validacion explicita y ayuda embebida.

```
PHP object   ─┐
HTTP JSON    ─┤
AxiSQL       ─┼──► Axi\Engine\Op\Operation ──► Engine::execute() ──► Result
CLI          ─┤
Agent IA     ─┘
```

Las cinco formas convergen al mismo `execute()`. No hay rutas alternativas.

---

## 2. Clase base `Axi\Engine\Op\Operation`

```php
abstract class Operation {
    public const OP_NAME = 'abstract';

    public string $namespace  = 'default';
    public string $collection = '';
    public array  $params     = [];

    public static function opName(): string;         // devuelve OP_NAME
    public function toArray(): array;                // serializa a JSON
    public static function fromArray(array $d): static;

    abstract public function validate(): void;       // throws AxiException
    abstract public function execute(object $engine): Result;
    abstract public static function help(): HelpEntry;
}
```

**Invariantes**:
1. `OP_NAME` unico por clase. Namespace convencional: `category.action` (ej. `auth.login`, `ai.ask`).
2. `validate()` no muta estado y no toca el motor. Si falla, lanza `AxiException` con codigo `VALIDATION_FAILED`.
3. `execute()` puede mutar storage, pero **siempre** devuelve un `Result` (nunca lanza excepciones no-Axi salvo `\Throwable` imprevisto — el dispatcher las captura y las transforma en `Result::fail`).
4. `help()` es puro: no depende de estado, es idempotente, se puede cachear.
5. `toArray()` ↔ `fromArray()` es round-trip: `X::fromArray($op->toArray())` produce un Op semanticamente igual.

---

## 3. Serializacion JSON

```json
{
  "op": "<OP_NAME>",
  "namespace": "default",
  "collection": "<nombre o vacio>",
  "<param1>": ...,
  "<param2>": ...
}
```

- Claves `op`, `namespace`, `collection` son reservadas.
- Todas las demas claves van a `$params`.
- No hay anidacion fija: cada Op define sus propios params.
- Orden de claves irrelevante (JSON).

**Ejemplo** (Select):

```json
{
  "op": "select",
  "collection": "products",
  "where": [
    {"field": "price", "op": "<", "value": 3}
  ],
  "order_by": [
    {"field": "price", "dir": "asc"}
  ],
  "limit": 20
}
```

---

## 4. Catalogo canonico

Se consulta en runtime via `Axi\Engine\Axi::opRegistry()`. Para el listado actualizado ver [docs/api/README.md](../api/README.md).

Categorias:

| Categoria | Prefijo/clase | Ejemplo |
| :-- | :-- | :-- |
| CRUD          | `Op\*`              | select, insert, update, delete, count, exists, batch |
| Schema        | `Op\Alter\*`        | create_collection, add_field, create_index, ... |
| System        | `Op\System\*`       | ping, describe, schema, explain, help |
| Auth          | `Op\Auth\*`         | auth.login, auth.create_user, auth.grant_role, ... |
| AI (stubs v1) | `Op\Ai\*`           | ai.ask, ai.new_agent, ai.run_agent, ... |

---

## 5. Dispatcher

`Axi\Engine\Axi::execute()` acepta tres formas:

1. **Operation instance**: `$db->execute(new Op\Select('products')->where(...))`.
2. **Array con `op`**: `$db->execute(['op' => 'select', 'collection' => '...', ...])`.
3. **Legacy array con `action`** (retrocompat ACIDE): `$db->execute(['action' => 'list_products', ...])`.

Las tres devuelven un array con forma:

```json
{
  "success": true|false,
  "data":    <mixed | null>,
  "error":   "<string | null>",
  "code":    "<codigo semantico | null>",
  "duration_ms": 1.23
}
```

El codigo `OP_UNKNOWN` se devuelve si el nombre no esta en el registry.

---

## 6. Codigos de error (`Axi\Engine\AxiException`)

| Codigo | Cuando |
| :-- | :-- |
| `VALIDATION_FAILED`    | Un `validate()` detecta params invalidos antes de ejecutar. |
| `OP_UNKNOWN`           | El nombre del Op no esta en el registry. |
| `COLLECTION_NOT_FOUND` | Se refiere a una coleccion inexistente y `strict_*` esta activo. |
| `DOCUMENT_NOT_FOUND`   | Un read/update/delete sobre id inexistente con modo strict. |
| `CONFLICT`             | Id/field/index ya existe (duplicado no permitido). |
| `UNAUTHORIZED`         | Credenciales invalidas (Login) o token caducado. |
| `FORBIDDEN`            | Accion permitida en general pero no para este actor. |
| `NOT_IMPLEMENTED`      | Siempre en Ops AI durante Fase 1-5. Implementacion llega Fase 6. |
| `BAD_REQUEST`          | Formato de entrada basico malformado. |
| `INTERNAL_ERROR`       | Excepcion no capturada en `execute()`. Nunca es culpa del cliente. |

---

## 7. Regla de ayuda (`HelpEntry`)

Cada Op define un `HelpEntry` consumible como:
- `axi help <op>` en CLI,
- `POST /axidb/api/axi.php {"op":"help","target":"<op>"}` en HTTP,
- `docs/api/<op>.md` generado por `axi docs build`.

Campos obligatorios: `name`, `synopsis`, `description`, al menos 1 example.

`axi docs check` valida la consistencia y bloquea merge si hay divergencia.

---

## 8. Reglas para anadir un Op nuevo

1. Crear clase en `axidb/engine/Op/<Category>/<Name>.php` extendiendo `Operation`.
2. Definir `const OP_NAME`, `validate()`, `execute()`, `help()`.
3. Anadir al registry en `Axi::opRegistry()`.
4. Test minimo: round-trip + validate ok + validate fail + ejecucion happy path.
5. Regenerar docs: `axi docs build`.
6. Commit.

La clase debe ser ≤250 lineas (regla §6.2 del plan), tener cabecera documentada sin emojis, y no depender de capacidades legacy que no esten en otros Ops (para mantener agnosticismo).
