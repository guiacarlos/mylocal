# Data Model — colecciones de SynaxisCore

Toda la app lee/escribe documentos JSON en **colecciones**. Cada colección vive en un `IDBObjectStore` de IndexedDB. Este documento es la referencia canónica de qué colecciones existen, qué forma tiene cada documento, y en qué scope se maneja.

Convención de metadatos (los añade `SynaxisCore` automáticamente):

- `id: string` — único dentro de la colección (keyPath IndexedDB).
- `_version: number` — se incrementa en cada `update`.
- `_createdAt: string` — ISO-8601, solo primera inserción.
- `_updatedAt: string` — ISO-8601, cada escritura.

Flag especial en `data` de entrada:

- `_REPLACE_: true` — no hace merge, sustituye el doc entero.

---

## 1. `products` — catálogo de la carta

Scope: **hybrid**. Pública (la carta la ve cualquiera).

```json
{
    "id": "cafe_cortado",
    "slug": "cortado",
    "name": "Cortado",
    "sku": "CAF-COR",
    "price": 1.9,
    "currency": "EUR",
    "stock": 999,
    "status": "publish",
    "category": "CAFÉ",
    "description": "Espresso cortado con leche cremosa en dosis perfecta.",
    "image": "/media/2026/04/cortado.webp",
    "allergens": ["Lácteos"],
    "badges": ["recomendado"]
}
```

Index (derivable): `status`, `category`, `slug`.

Tipos TS: [`Product`](../src/types/domain.ts).

---

## 2. `config` — configuración operativa

Scope: **hybrid**.

Documentos singleton por id:

### `tpv_settings`

```json
{
    "id": "tpv_settings",
    "mesaPayment": true,
    "enabledPaymentMethods": ["cash", "card", "revolut", "bizum"],
    "bizumPhone": "+34..."
}
```

### `company_settings`, `legal_settings`

Similares; singletones. Ver [`Product`](#1-products--catálogo-de-la-carta) arriba para la convención.

---

## 3. `payment_settings` — métodos de pago activos

Scope: **hybrid**.

```json
{
    "id": "payment_settings",
    "enabled": ["cash", "card", "revolut", "bizum"],
    "bizumPhone": "+34...",
    "revolut": { "active": true, "mode": "sandbox" }
}
```

**No contiene la API key de Revolut**. Ese secreto vive en `server/config/revolut.json` — ver [SECRETS.md](SECRETS.md).

---

## 4. `coupons`

Scope: **local** (crear/editar) / **hybrid** (validar).

```json
{
    "id": "SUMMER10",
    "code": "SUMMER10",
    "type": "percent",
    "value": 10,
    "minTotal": 15,
    "maxUses": 100,
    "usedCount": 0,
    "validFrom": "2026-06-01T00:00:00Z",
    "validTo": "2026-09-30T23:59:59Z",
    "active": true
}
```

---

## 5. `orders`

Scope: **hybrid**. El server guarda la verdad para órdenes pagadas (Revolut/webhook).

```json
{
    "id": "o_1776420000000",
    "items": [
        { "id": "cafe_cortado", "_key": "ext_...", "name": "Cortado", "price": 1.9, "qty": 2, "note": "" }
    ],
    "subtotal": 3.8,
    "discount": 0,
    "tax": 0,
    "total": 3.8,
    "currency": "EUR",
    "status": "paid",
    "paymentMethod": "revolut",
    "tableId": "t_1",
    "source": "TPV",
    "revolut": {
        "orderId": "revolut_xxx",
        "publicId": "tkn_yyy",
        "state": "COMPLETED",
        "mode": "sandbox"
    }
}
```

---

## 6. `restaurant_zones`

Scope: **hybrid**. Layout del local (zonas, mesas).

```json
{
    "id": "z_1773449176187",
    "name": "Salon",
    "tables": [
        { "id": "t_1", "number": 1, "capacity": 2, "x": 50, "y": 50, "width": 70, "height": 70, "shape": "square", "status": "free" }
    ]
}
```

Ver [QR.md](QR.md) para cómo se usan para generar los QRs de mesa.

---

## 7. `table_orders` *(solo servidor)*

Scope: **server**. Comanda viva de cada mesa. No vive en IndexedDB — por eso son server-only.

Clave: `t_<n>`.

```json
{
    "id": "t_4",
    "cart": [
        { "id": "cafe_cortado", "_key": "ext_cortado_abc123", "name": "Cortado", "price": 1.9, "qty": 3, "note": "Sin azúcar" }
    ],
    "updated_at": "2026-04-17T10:55:00Z",
    "source": "QR_CUSTOMER",
    "status": "pending_confirmation",
    "table_number": "4"
}
```

Merge race-safe: items con `_key` que empieza por `ext_` vienen del QR y **no deben ser pisados** por un update del TPV. Ver [`server/handlers/qr.php`](../server/handlers/qr.php).

---

## 8. `sent_orders` *(solo servidor)*

Scope: **server**. Cola de cocina — comandas enviadas pero aún no confirmadas por el TPV.

```json
{
    "id": "t_4",
    "items": [{ "id": "...", "name": "...", "price": 1.9, "qty": 1 }],
    "sent_at": "2026-04-17T10:50:00Z",
    "table": "4",
    "seller": "Cliente QR (Móvil)"
}
```

---

## 9. `table_requests` *(solo servidor)*

Scope: **server**. Peticiones de camarero/cuenta desde la mesa.

```json
{
    "id": "req_ab12cd34",
    "table_id": "t_1",
    "table_name": "Salón · Mesa 1",
    "type": "bill",
    "message": "",
    "status": "pending",
    "created_at": "2026-04-17T11:00:00Z",
    "acknowledged_at": null
}
```

---

## 10. `agente_restaurante` — configuración del Maître

Scope: **local** (edición), **hybrid** (lectura del vault en `chat_restaurant`).

Contiene 3 documentos singleton:

### `settings`

```json
{
    "id": "settings",
    "agents": [
        {
            "id": "default",
            "name": "Maître Socolá",
            "category": "SALA",
            "tone": "Cordial, elegante y conciso",
            "context": "Eres el Maître de Socolá...",
            "persona": {
                "greeting": "¿En qué puedo ayudarte?",
                "suggestions": ["¿Qué me recomiendas?", "Algo sin gluten"]
            }
        }
    ]
}
```

### `vault_carta`

```json
{
    "id": "vault_carta",
    "entries": [
        { "id": "v0", "query": "qué me recomiendas", "answer": "…", "auto": false, "created_at": "..." },
        { "id": "vault_1773...", "query": "café con leche", "answer": "...", "auto": true, "created_at": "..." }
    ]
}
```

`auto: true` indica que la entrada la añadió el servidor tras una respuesta de Gemini (Fase de aprendizaje). El cliente puede promocionarla a `auto: false` (curada) editándola.

### `internal_notes`

```json
{
    "id": "internal_notes",
    "notes": [
        "Potenciar repostería propia (croissant, tarta de queso, brownie).",
        "Avisar siempre de alérgenos."
    ]
}
```

Notas que se inyectan al system prompt del Maître — no las ve el cliente final, solo guían al agente.

Ver [AGENTS.md](AGENTS.md) para el flujo completo.

---

## 11. `reservas`

Scope: **hybrid** (lista) / **server** (crear — evita overbooking).

```json
{
    "id": "r_ab12cd34",
    "name": "Juan García",
    "email": "juan@example.com",
    "phone": "+34 666 000 000",
    "datetime": "2026-04-20T20:00:00Z",
    "people": 4,
    "notes": "Cumpleaños",
    "status": "pending"
}
```

---

## 12. `courses` + `lessons` — Academia

Scope: **hybrid** (lista/lectura) / **local** (edición).

```json
{
    "id": "curso_ia",
    "slug": "ia-basica",
    "title": "IA aplicada a negocios",
    "description": "...",
    "lessons": ["lesson_1", "lesson_2"],
    "status": "publish"
}
```

```json
{
    "id": "lesson_1",
    "courseId": "curso_ia",
    "title": "Qué es un LLM",
    "content": "...",
    "summary": "...",
    "flashcards": [{ "front": "...", "back": "..." }],
    "quiz": [{ "q": "...", "options": ["a", "b", "c"], "answer": 1 }],
    "ai_config": { "system_prompt": "...", "tone": "..." }
}
```

---

## 13. `users`, `roles` — master collections

Scope: **server** para auth (el hash vive solo en server). Los docs "ligeros" de perfil sí pueden sincronizarse al cliente como hybrid.

```json
{
    "id": "u_admin_1",
    "email": "admin@socola.com",
    "name": "Admin",
    "role": "superadmin",
    "tenantId": "socola"
}
```

**Nunca** se escribe `password_hash` en IndexedDB. Solo existe en `server/data/users/<id>.json`.

---

## 14. `conversations` — historial del chat

Scope: **local**. Privado de cada cliente/dispositivo.

```json
{
    "id": "conv_xyz",
    "agentId": "default",
    "tableId": "t_1",
    "sessionId": "sess_abc",
    "messages": [
        { "role": "user", "content": "¿Qué me recomiendas?", "ts": "..." },
        { "role": "assistant", "content": "Hoy...", "ts": "..." }
    ],
    "updated_at": "..."
}
```

---

## 15. `__oplog__` *(interna)*

Scope: **local**, consumida por `synaxis_sync`. Log append-only de cada escritura para poder sincronizar al server cuando esté online. Se gestiona con `core.drainOplog()` / `core.clearOplog(ids)`.

```json
{
    "id": "op_1776420000000_abc",
    "op": "put",
    "collection": "products",
    "targetId": "cafe_cortado",
    "version": 1,
    "ts": "2026-04-17T10:00:00Z",
    "payload": { "...doc completo..." }
}
```

---

## 16. `<collection>__versions` *(interna)*

Cada `update` snapshotea el doc anterior aquí. Última **5 versiones por id** (se podan automáticamente). Útil para "deshacer" en la UI del Dashboard.

```
products__versions/
  cafe_cortado@1     (snapshot de versión 1)
  cafe_cortado@2
  cafe_cortado@3
```
