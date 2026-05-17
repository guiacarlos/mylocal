# AxiDB Wire Protocol — HTTP JSON v1.0

**Estado**: Fase 1.5 (parcial). Endpoint operativo con retrocompat ACIDE.
**Endpoint unico**: `POST /axidb/api/axi.php`
**Encoding**: JSON UTF-8.

Esta spec documenta el protocolo HTTP minimo para que terceros puedan escribir clientes en cualquier lenguaje. El SDK PHP oficial (`axidb/sdk/php/Client.php`, Fase 2) es una de varias implementaciones posibles.

---

## 1. Peticion

### Headers minimos

```
POST /axidb/api/axi.php HTTP/1.1
Host: <servidor>
Content-Type: application/json
Accept: application/json
```

### Autenticacion (opcional en modo anonimo)

Dos formas aceptadas:

1. **Bearer token** (preferida):
   ```
   Authorization: Bearer <token>
   ```

2. **Cookie httponly** (lo que hace el login):
   ```
   Cookie: acide_session=<token>
   ```

El token se obtiene ejecutando el Op `auth.login`. En futuras versiones se documentara Basic auth para integraciones legacy.

### Cuerpo (Op model, preferido)

```json
{
  "op": "<OP_NAME>",
  "namespace": "default",
  "collection": "<nombre>",
  "<param1>": ...,
  "<param2>": ...
}
```

### Cuerpo (Legacy ACIDE, retrocompat)

```json
{
  "action": "<action_name>",
  "data":   {...}
}
```

El dispatcher detecta `op` o `action` y rutea. Ambos formatos coexisten hasta la migracion completa de Socola (Fase 5.4).

---

## 2. Respuesta

### Forma estable

```json
{
  "success":     true | false,
  "data":        <mixed | null>,
  "error":       "<string | null>",
  "code":        "<codigo semantico | null>",
  "duration_ms": 1.23
}
```

En modo legacy, `code` y `duration_ms` pueden estar ausentes (viejos handlers no los producen).

### Headers de respuesta

```
Content-Type: application/json; charset=UTF-8
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-Axi-Op: <nombre del op/action>
X-Axi-Duration-Ms: <ms>
```

Nota: X-Axi-Storage-Root y X-Axi-Project fueron eliminados en la auditoría de seguridad (exponían rutas del sistema de archivos del servidor).

---

## 3. Codigos HTTP

| Status | Cuando |
| :-- | :-- |
| 200 OK                 | Se completa la ejecucion (success puede ser false dentro del body — se respeta el body). |
| 400 Bad Request        | JSON malformado o `op`/`action` ausentes. |
| 500 Internal Server    | Excepcion no capturada antes de construir el body. |

**Importante**: `success: false` con `code: VALIDATION_FAILED` llega con HTTP 200, no 4xx. La capa HTTP indica "la peticion fue procesada correctamente"; la capa aplicacion en `success` indica el resultado semantico. Esto facilita el parsing uniforme.

---

## 4. CORS

### Origen publico (Ops publicas)

Para Ops que deben ser accesibles desde cualquier origen (catalogo publico, health check):

```
Access-Control-Allow-Origin: *
```

Lista de acciones publicas v1 (legacy ACIDE): `list_products`, `chat_restaurant`, `health_check`, `auth_login`, `validate_coupon`, `get_payment_settings`, `get_media_formats`, `process_external_order`, `table_request`, `get_table_order`, `update_table_cart`, `get_table_requests`, `get_mesa_settings`, `create_revolut_payment`, `check_revolut_payment`.

Para Ops nuevas (`select`, `insert`, etc.), se aplican reglas mas estrictas: mismo origen o localhost.

### Preflight OPTIONS

```
Access-Control-Allow-Methods: POST, GET, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With
Access-Control-Max-Age: 86400
```

---

## 5. Ejemplos

### 5.1 Insert + Select

```http
POST /axidb/api/axi.php HTTP/1.1
Content-Type: application/json

{"op":"insert","collection":"notas","data":{"title":"hola"}}
```

```json
{
  "success": true,
  "data": {
    "_id": "20260424114612abcdef12",
    "title": "hola",
    "_version": 1,
    "_createdAt": "2026-04-24T11:46:12+00:00",
    "_updatedAt": "2026-04-24T11:46:12+00:00"
  },
  "code": null,
  "error": null,
  "duration_ms": 0.8
}
```

### 5.2 Ayuda (introspeccion)

```http
POST /axidb/api/axi.php HTTP/1.1
Content-Type: application/json

{"op":"help","target":"select"}
```

La respuesta incluye el `HelpEntry` de Select en `data`.

### 5.3 Login

```http
POST /axidb/api/axi.php HTTP/1.1
Content-Type: application/json

{"op":"auth.login","email":"a@b.c","password":"..."}
```

```
HTTP/1.1 200 OK
Set-Cookie: acide_session=<token>; Path=/; HttpOnly; SameSite=Strict
```

```json
{"success": true, "data": {"token":"...", "user":{...}}}
```

### 5.4 Op desconocido

```json
{
  "success": false,
  "data": null,
  "error": "Op desconocido: 'foo'.",
  "code": "OP_UNKNOWN"
}
```

---

## 6. Como escribir un cliente en otro lenguaje

1. Implementar POST HTTP con `Content-Type: application/json`.
2. Soportar Bearer token en header.
3. Parsear respuesta: si `success == true`, usar `data`; si no, manejar segun `code`.
4. Respetar `duration_ms` para telemetria.
5. Para ayuda en runtime: llamar `{"op":"help"}` sin target → indice; con target → detalle.

El protocolo es estable: si apareciera un breaking change, habria aviso de 1 version major.

---

## 7. Lo que NO esta en wire v1

- Streaming de resultados (queries grandes → paginar con `limit/offset`).
- Multiplexing multiple Ops en un request (usar `batch` Op).
- Compresion (gzip lo maneja el webserver externo si se configura).
- Rate limiting explicito (lo hace Apache/nginx).
- WebSockets / long-polling.

Estas features se evaluan para v1.1+ o P2 (AxiDB Server con protocolo binario MySQL-wire).
