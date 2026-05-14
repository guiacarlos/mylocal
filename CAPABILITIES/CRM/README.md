# CAPABILITY: CRM

Gestión de contactos (B2C/B2B), interacciones y segmentación básica.

## Colecciones AxiDB

| Colección | Clave | Descripción |
|-----------|-------|-------------|
| `crm_contactos` | `ct_<uuid>` | Ficha de cliente/contacto |
| `crm_interacciones` | `i_<uuid>` | Log inmutable de interacciones |

## Acciones (handler: `spa/server/handlers/crm.php`)

| Acción | Auth | Descripción |
|--------|------|-------------|
| `crm_contacto_create` | admin | Crea contacto; detecta duplicado por email |
| `crm_contacto_update` | admin | Actualiza nombre, email, etiquetas, notas |
| `crm_contacto_get` | admin | Devuelve un contacto |
| `crm_contacto_list` | admin | Lista con filtros opcionales |
| `crm_contacto_delete` | admin | Elimina contacto |
| `crm_interaccion_add` | admin | Añade interacción (inmutable) |
| `crm_interaccion_list` | admin | Historial de un contacto |
| `crm_segmento_query` | admin | Filtrado multi-criterio |

## Notas de diseño

- **Dedupe automática**: crear con email existente devuelve el contacto previo con `duplicate_of`.
- **Inmutabilidad de interacciones**: no se editan ni borran; garantizan auditoría.
- **SegmentoEngine** combina filtros con AND implícito; se puede extender con OR sin cambiar la API.
