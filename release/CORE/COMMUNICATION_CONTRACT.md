# 🛰️ CONTRATO DE COMUNICACIÓN ACIDE - MARCO CMS

> **ESTADO:** VIGENTE
> **TIPO:** LEY MARCIAL DE CÓDIGO
> **UBICACIÓN:** `/headless/acide/`

Este documento define la **ÚNICA** forma autorizada en la que Marco CMS (El Cuerpo) puede comunicarse con ACIDE (El Cerebro). Cualquier desviación se considera una violación de la soberanía del sistema.

---

## 1. 🏛️ FILOSOFÍA: CLIENTE / SERVIDOR SOBERANO

- **Marco CMS** es un "Renderizador Puro". No tiene base de datos, no tiene lógica de negocio compleja, no decide permisos. Solo pide datos y los pinta.
- **ACIDE** es la "Autoridad Central". Gestiona el sistema de archivos (Búnker), autentica usuarios y ejecuta comandos.

La relación es jerárquica: **Marco pide, ACIDE concede (o deniega).**

---

## 2. 🔌 EL TÚNEL ÚNICO

Toda comunicación debe ocurrir a través de un único endpoint HTTP local.

- **Método:** `POST`
- **Endpoint:** `/acide/index.php`
- **Headers:** 
  - `Content-Type: application/json`
  - `Authorization: Bearer <token>` (si requiere auth)

🚫 **PROHIBIDO:** 
- Llamadas REST a otras URLs (`/api/v1/...`).
- Webhooks externos directos desde el cliente.
- Consultas SQL desde el frontend.

---

## 3. 📦 ESTRUCTURA DEL PAQUETE (PAYLOAD)

El cliente (acideService.js) siempre envía un JSON con esta estructura:

```json
{
  "action": "nombre_de_la_accion_registrada",
  "data": {
    "parametro_1": "valor",
    "parametro_2": "valor"
  }
}
```

### Respuesta Estándar (Response)
ACIDE siempre responde con este formato normalizado:

```json
{
  "success": true,       // booleano: true = éxito, false = fallo
  "data": { ... },       // Objeto o Array con la respuesta solicitada
  "error": null          // String descriptivo si success es false
}
```

---

## 4. 📚 CATÁLOGO DE ACCIONES (Action Registry)

Estas son las acciones reconocidas por `ActionDispatcher.php`. Si una acción no está aquí, ACIDE devolverá error.

### 🔐 Autenticación (Auth)
| Acción | Datos Requeridos (`data`) | Descripción |
| :--- | :--- | :--- |
| `auth_login` | `{ email, password, tenantId? }` | Inicia sesión y devuelve token JWT simulado. |
| `auth_resolve_tenant` | `{ slug }` | Busca el ID de un tenant por su nombre. |
| `auth_refresh_session` | `{}` | Renueva el token y devuelve datos frescos del usuario. |

### 🗄️ Datos (CRUD Soberano)
| Acción | Datos Requeridos | Descripción |
| :--- | :--- | :--- |
| `read` | `{ collection, id }` | Lee un archivo JSON específico (ej: `pages/home`). |
| `query` | `{ collection, params{} }` | Busca registros en una colección con filtros. |
| `list` | `{ collection }` | Lista todos los elementos de una carpeta. |
| `update` | `{ collection, id, data }` | Escribe/Actualiza un archivo JSON en el Búnker. |
| `delete` | `{ collection, id }` | Elimina (o mueve a trash) un archivo. |

### 🎨 Temas y Diseño
| Acción | Datos Requeridos | Descripción |
| :--- | :--- | :--- |
| `list_themes` | `{}` | Devuelve lista de temas instalados. |
| `activate_theme` | `{ theme_id }` | Cambia el tema activo en `settings.json`. |
| `get_active_theme_home`| `{}` | Obtiene la configuración de portada del tema actual. |
| `save_theme_part` | `{ theme_id, part_name, content }` | Guarda cambios en headers/footers. |

### 🛠️ Sistema
| Acción | Datos Requeridos | Descripción |
| :--- | :--- | :--- |
| `build_site` | `{}` | (Opcional) Dispara regeneración estática. |
| `upload` | `FormData` (Multipart) | Sube archivos a `storage/media`. |

---

## 5. ⚠️ REGLAS DE SEGURIDAD

1.  **Colecciones Privadas:** El frontend nunca debe pedir directamente `data/users`. Debe usar acciones `auth_*` o pedir colecciones autorizadas.
2.  **Validación de Flujo:** Es responsabilidad de `ActionDispatcher` validar que la petición sea legítima antes de pasarla al Handler.
3.  **No Fugas:** Los errores de PHP no deben llegar al cliente en producción (usar `try/catch` global en index.php).

---
*Este contrato es vinculante. Cualquier cambio en `acideService.js` debe reflejarse aquí.*
