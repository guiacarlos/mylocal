# Agente Maître / Camarero IA — Socolá

Chat asistente que recomienda de la carta, responde sobre alérgenos, ayuda a elegir, y aprende con un **vault** curado de preguntas-respuestas.

## Arquitectura del chat — tres capas con fallback

```
Usuario pregunta: "¿qué me recomiendas hoy?"
            │
            ▼
┌──────────────────────────────┐
│ 1. Vault Check (local)       │ ── fuzzy match Levenshtein ≥ 0.5
└──────────────┬───────────────┘
       hit?   │  no hit
               ▼
┌──────────────────────────────┐
│ 2. Catalog Match (local)     │ ── "¿qué lleva el cortado?"
└──────────────┬───────────────┘
       hit?   │  no hit
               ▼
┌──────────────────────────────┐
│ 3. Gemini (server)           │ ── chat_restaurant → API key server
└──────────────┬───────────────┘
               │
        respuesta → auto-save al vault
```

Código cliente: [`src/services/maitre.service.ts`](../src/services/maitre.service.ts).
Código servidor: [`server/handlers/ai.php`](../server/handlers/ai.php).

### Capa 1: Vault (Local)

El **vault** es un documento en la colección `agente_restaurante` (id `vault_carta`) con un array de entries `{id, query, answer, auto, created_at}`.

Matching: Levenshtein normalizado ≥ `0.5` sobre `query`. Si hay múltiples candidatos, gana el mayor score.

Ventajas de la capa vault:
- **Sin red**: responde instantáneo.
- **Editable**: el admin puede curar respuestas en el Dashboard sin tocar código.
- **Auto-aprendizaje**: cada respuesta de Gemini se guarda como `auto: true`. El admin puede promover a `auto: false` (curada) o eliminar.

### Capa 2: Catálogo (Local)

Si la pregunta contiene o se parece al nombre de un producto (`similarity ≥ 0.7`), se responde con el propio producto (nombre, precio, descripción, alérgenos). No pasa por IA.

Heurística en [`matchProduct`](../src/services/maitre.service.ts):

- `q.includes(product.name)` → score 1.0.
- `product.name.includes(q)` → score 0.9.
- Levenshtein fuzzy → score variable.

### Capa 3: Gemini (Server)

Si las dos capas anteriores no dan respuesta, la SPA llama a `chat_restaurant` (scope `server`). El handler [`ai.php`](../server/handlers/ai.php):

1. Carga `server/config/gemini.json` (API key).
2. Construye el **system prompt del Maître** inyectando:
   - El `context` y `tone` del agente (de `agente_restaurante/settings`).
   - El momento del día (MAÑANA / MEDIODÍA / TARDE).
   - **La carta publicada** del servidor (productos con `status: publish`).
   - Las **notas internas** (`agente_restaurante/internal_notes`) — no visibles al cliente final.
   - Reglas duras: "no inventes precios ni productos fuera de la carta", "responde breve (2-3 frases)".
3. Envía `prompt + history[últimos N]` a `gemini-1.5-flash` (o el modelo configurado).
4. Al recibir respuesta, la añade al vault como `auto: true` (con poda a los últimos 200).

## Structures de datos

Ver [DATA_MODEL.md §10](DATA_MODEL.md#10-agente_restaurante--configuración-del-maître) para el esquema completo.

Resumen rápido:

| Doc | ¿Qué es? | Editable desde |
| :-- | :-- | :-- |
| `agente_restaurante/settings` | Personalidad del Maître (name, tone, context, suggestions) | Dashboard admin (local) |
| `agente_restaurante/vault_carta` | Banco de respuestas curadas + auto-aprendidas | Dashboard admin (local) |
| `agente_restaurante/internal_notes` | Notas de dirección comercial (no se muestran al cliente) | Dashboard admin (local) |

## Acciones del catálogo

| Acción | Scope | Servicio TS |
| :-- | :-- | :-- |
| `chat_restaurant` | server | `chatWithMaitre` |
| `get` sobre `agente_restaurante/vault_carta` | local | `getVault` |
| `update` sobre `agente_restaurante/vault_carta` | local | `saveVault` |
| `get` sobre `agente_restaurante/settings` | local | `getAgentConfig` |
| `update` sobre `agente_restaurante/settings` | local | `updateAgentConfig` |

## Uso típico desde un componente

```tsx
import { useSynaxisClient } from '@/hooks/useSynaxis';
import { chatWithMaitre } from '@/services/maitre.service';

const client = useSynaxisClient();
const res = await chatWithMaitre(client, {
  prompt: '¿qué me recomiendas?',
  history: priorMessages,
  agentId: 'default',
  tableId: 't_5',
});
// res.source puede ser 'vault' | 'catalog' | 'ai'
console.log(res.content);
```

## Configuración

### En el cliente

Editable desde el Dashboard (cuando esté implementado). Los cambios se guardan directamente en IndexedDB — sin red. Si hay server y sync, el oplog envía los cambios cuando toque.

### En el servidor

Archivo `server/config/gemini.json` (copiar de [`gemini.json.example`](../server/config/gemini.json.example) y rellenar):

```json
{
    "api_key": "AIza...",
    "default_model": "gemini-1.5-flash",
    "allowed_models": ["gemini-1.5-flash", "gemini-1.5-pro"],
    "max_history_turns": 20,
    "rate_limit_per_minute_per_ip": 30,
    "timeout_seconds": 30
}
```

**Importante** — ver [SECRETS.md](SECRETS.md):
- `gemini.json` está en el `.gitignore` del directorio `server/config/`.
- Solo se commitea `gemini.json.example`.
- El handler rechaza la petición si falta la clave.

## Consideraciones de privacidad

- El cliente nunca ve el system prompt completo (con notas internas + carta inyectada).
- El server tampoco ve el `authToken` del usuario en los prompts; solo usa el user-level rate limiting por IP.
- El vault se guarda sin PII por defecto. Si se integra con un `sessionId`, asegúrate de que no contenga datos identificativos.
- Conversaciones (`conversations`) son **locales del cliente** — no se envían al server a no ser que explícitamente se haga sync.

## Lo que falta

- UI del chat en `src/pages/MesaQR.tsx` y `src/pages/Dashboard.tsx` (editor del vault).
- Botón "promover a respuesta curada" (toggle de `auto: true → false`).
- Panel de notas internas en el dashboard.
- Validación de prompt length y prompt injection en `ai.php`.
- Sync de `agente_restaurante/settings` entre el cliente y el server para que el prompt del Maître use el tono curado del admin y no el fallback estático de [`load_agent()`](../server/handlers/ai.php).
