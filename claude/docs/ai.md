# Sistema de IA — MyLocal

Referencia completa del stack de inteligencia artificial del proyecto.

---

## 1. Arquitectura general

MyLocal usa dos motores de IA en cascada: primero intenta el servidor
local propio (llama.cpp con Gemma 4), y si falla o no responde bien
cae automáticamente a Gemini (Google). Si ambos fallan, el parser de
texto tiene un tercer nivel heurístico sin IA.

```
Imagen subida
      │
      ▼
OCREngine → IA LOCAL primaria (gemma-4-e2b via https://ai.miaplic.com/v1)
              ¿OK? → texto  (engine: local_ai)
              ¿Falla? ↓
           → GEMINI VISION fallback (gemini-2.5-flash)
              ¿OK? → texto  (engine: gemini_vision)
              ¿Falla? → error al cliente

PDF subido
      │
      ▼
OCREngine → convierte páginas a JPEG con Imagick / GhostScript / pdftoppm
              ¿Conversión OK? → IA LOCAL (una llamada por página)  (engine: local_ai_pdf)
              ¿Sin herramienta de conversión o fallo? ↓
           → GEMINI VISION PDF (acepta PDF inline nativo)  (engine: gemini_vision_pdf)
              ¿Falla? → error al cliente

Texto OCR
      │
      ▼
OCRParser → IA LOCAL  (engine: local_ai_parser)
              ¿Falla? ↓
           → GEMINI   (engine: gemini_parser)
              ¿Falla? ↓
           → HEURÍSTICO regex  (engine: heuristic_v2)
```

La propiedad `_engine` que devuelve el backend indica cuál nivel procesó
la solicitud. Útil para diagnóstico desde el cliente.

---

## 2. El servidor IA local

### Qué es

Un servidor llama.cpp corriendo en el VPS del proyecto. Expone una API
100 % compatible con la especificación OpenAI (`/v1/chat/completions`),
lo que permite conectarlo desde cualquier cliente que soporte OpenAI:
Python `openai`, JavaScript `openai`, PHP con curl, Dify, AnythingLLM,
Flowise, etc.

### Hardware y software

| Campo        | Valor                                                 |
| ------------ | ----------------------------------------------------- |
| Proceso      | `python3 -m llama_cpp.server`                       |
| Config       | `/home/icoopi/llama_server/config.json`             |
| Entorno      | `/home/icoopi/llama_server/llama_env/` (virtualenv) |
| Script init  | `/home/icoopi/llama_server/start_server.sh`         |
| Puerto local | 8000 (expuesto vía proxy inverso en HTTPS/443)       |
| GPU layers   | 28 (en GPU)                                           |
| Contexto     | 8192 tokens                                           |
| Batch        | 512                                                   |

### Modelo

| Campo            | Valor                                                           |
| ---------------- | --------------------------------------------------------------- |
| Familia          | Gemma 4 — Google DeepMind (pesos abiertos)                     |
| Variante         | E2B = "effective 2B parameters" (arquitectura con PLE)          |
| Cuantización    | Q4_K_M (~3.2 GB VRAM para inferencia)                           |
| Alias API        | `gemma-4-e2b`                                                 |
| Archivo modelo   | `gemma-4-E2B-it-Q4_K_M.gguf`                                  |
| Archivo visión  | `mmproj-F16.gguf` (clip model para procesar imágenes)        |
| Capacidades      | Texto + visión multimodal nativa (imágenes, vídeo, audio)    |
| Contexto oficial | 128 000 tokens                                                  |
| Contexto config  | 8 192 tokens (en `config.json` — ver nota sobre VRAM)       |
| Chat format      | `gemma`                                                       |

**Importante — contexto actual vs. máximo:**
El servidor está configurado con `n_ctx: 8192`. Gemma 4 E2B soporta hasta
128K tokens. Con 4 GB de VRAM y ~3.2 GB ocupados por el modelo, quedan
~0.8 GB libres para la caché KV. Si se necesita más contexto (cartas muy
largas), probar `n_ctx: 16384` en `config.json` y reiniciar; si agota
VRAM o falla, volver a 8192.

Gemma 4 E2B es multimodal nativo: acepta imágenes en base64 con el formato
`image_url` del estándar OpenAI vision. Los PDFs se convierten a imágenes
JPEG página a página en el servidor PHP antes de enviárselos al modelo.

### Configuración del servidor (`config.json`)

llama-cpp-python ≥ 0.3.x requiere estructura `models: [...]` (array).
La config flat de nivel raíz falla con errores de validación Pydantic.
`clip_model_path` va dentro del objeto del modelo en el array:

```json
{
  "host": "0.0.0.0",
  "port": 8000,
  "api_key": "asir-gemma-2026",
  "models": [{
    "model": "/home/icoopi/llama_server/gemma-4-E2B-it-Q4_K_M.gguf",
    "model_alias": "gemma-4-e2b",
    "chat_format": "gemma",
    "clip_model_path": "/home/icoopi/llama_server/mmproj-F16.gguf",
    "n_gpu_layers": 28,
    "n_ctx": 8192,
    "n_batch": 512
  }]
}
```

**IMPORTANTE — parche `model.py` requerido para visión con Gemma:**
El código de llama-cpp-python solo activa el `clip_model_path` para un
conjunto fijo de chat formats (`llava-1-5`, `llava-1-6`, `qwen2.5-vl`…).
Para `chat_format: "gemma"` el clip model nunca se cargaba y las imágenes
se ignoraban silenciosamente. El parche en
`/home/icoopi/llama_server/llama_env/lib/python3.12/site-packages/llama_cpp/server/model.py`
añade un caso `elif` para `"gemma"` / `"gemma3"` que instancia un handler
`_GemmaVisionHandler` (subclase de `Llava15ChatHandler`) con el template
de turno nativo de Gemma 4.

Si se actualiza llama-cpp-python (`pip install -U llama-cpp-python`) se
debe volver a aplicar el parche desde
`/home/icoopi/llama_server/patch_model.py`.

Diagnóstico: si `prompt_tokens` es ~19 para una petición con imagen, el
clip model no está cargado. Valores > 200 confirman visión activa.

---

## 3. Datos de conexión

| Campo     | Valor                            |
| --------- | -------------------------------- |
| Base URL  | `http://ai.miaplic.com/v1`     |
| API Key   | `asir-gemma-2026`              |
| Modelo    | `gemma-4-e2b`                  |
| Protocolo | HTTP (el proxy inverso no tiene SSL para este subdominio) |

---

## 4. Integración desde otras aplicaciones

El servidor es 100 % compatible con la API de OpenAI. Cualquier cliente
que soporte "OpenAI custom endpoint" puede conectarse sin modificaciones.

### Python

```python
from openai import OpenAI

client = OpenAI(
    base_url="https://ai.miaplic.com/v1",
    api_key="asir-gemma-2026"
)

response = client.chat.completions.create(
    model="gemma-4-e2b",
    messages=[
        {"role": "user", "content": "Hola, ¿qué eres?"}
    ]
)
print(response.choices[0].message.content)
```

### JavaScript / Node.js

```javascript
const OpenAI = require("openai");

const openai = new OpenAI({
    baseURL: "https://ai.miaplic.com/v1",
    apiKey: "asir-gemma-2026",
});

const completion = await openai.chat.completions.create({
    model: "gemma-4-e2b",
    messages: [{ role: "user", content: "Hola!" }],
});
console.log(completion.choices[0].message.content);
```

### PHP (curl directo)

```php
$payload = json_encode([
    'model'    => 'gemma-4-e2b',
    'messages' => [['role' => 'user', 'content' => 'Hola']],
]);
$ch = curl_init('https://ai.miaplic.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer asir-gemma-2026',
    ],
]);
$resp = json_decode(curl_exec($ch), true);
echo $resp['choices'][0]['message']['content'];
```

### PHP con visión (OCR de imagen)

```php
$b64     = base64_encode(file_get_contents('/ruta/carta.jpg'));
$payload = json_encode([
    'model'    => 'gemma-4-e2b',
    'messages' => [[
        'role'    => 'user',
        'content' => [
            ['type' => 'text',      'text'      => 'Extrae todo el texto de esta imagen'],
            ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$b64}"]],
        ],
    ]],
]);
```

### Dify / AnythingLLM / Flowise

En cualquiera de estas plataformas, al añadir un modelo, selecciona el
proveedor "OpenAI Compatible" o "LocalAI" y rellena:

| Campo      | Valor                         |
| ---------- | ----------------------------- |
| Endpoint   | `https://ai.miaplic.com/v1` |
| API Key    | `asir-gemma-2026`           |
| Model Name | `gemma-4-e2b`               |

Para visión (adjuntar imágenes en el chat), el modelo lo soporta siempre
que la plataforma envíe el mensaje en formato `image_url` con base64.

### curl (prueba rápida desde terminal)

```bash
curl https://ai.miaplic.com/v1/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer asir-gemma-2026" \
  -d '{
    "model": "gemma-4-e2b",
    "messages": [{"role": "user", "content": "Hola!"}]
  }'
```

---

## 5. Configuración en MyLocal (OPTIONS)

Los valores se leen desde `STORAGE/options/ai.json` vía `OptionsConnector`.
Para cambiarlos edita directamente `STORAGE/options/ai.json` o usa:

```php
mylocal_options()->set('ai.local_endpoint', 'https://nuevo-servidor/v1');
```

| Clave OPTIONS         | Valor actual                  | Descripción                          |
| --------------------- | ----------------------------- | ------------------------------------- |
| `ai.local_endpoint` | `https://ai.miaplic.com/v1` | URL base del servidor local           |
| `ai.local_api_key`  | `asir-gemma-2026`           | Bearer token del servidor             |
| `ai.local_model`    | `gemma-4-e2b`               | Alias del modelo                      |
| `ai.api_key`        | (Gemini key)                  | Fallback Gemini                       |
| `ai.default_model`  | `gemini-2.5-flash`          | Modelo Gemini para texto              |
| `ai.vision_model`   | `gemini-2.5-flash`          | Modelo Gemini para visión/PDF inline |

Para desactivar el servidor local y forzar Gemini, deja `ai.local_endpoint`
vacío. El sistema detecta automáticamente con `AIClient::isConfigured()`.

---

## 6. Archivos del módulo

| Ruta                                | Qué hace                                                |
| ----------------------------------- | -------------------------------------------------------- |
| `CAPABILITIES/AI/AIClient.php`    | Cliente HTTP OpenAI-compatible.`chat()`, `vision()`  |
| `CAPABILITIES/AI/capability.json` | Descriptor del módulo                                   |
| `CAPABILITIES/OCR/OCREngine.php`  | OCR: local_ai → local_ai_pdf → gemini_vision_pdf       |
| `CAPABILITIES/OCR/OCRParser.php`  | Parser: local_ai_parser → gemini_parser → heuristic_v2 |
| `STORAGE/options/ai.json`         | Config en tiempo real (no sube a git)                    |

---

## 7. Notas operativas

- **Conversor de PDF en producción**: el servidor PHP necesita al menos una de
  estas herramientas para convertir PDFs a imágenes: Imagick (ext PHP),
  GhostScript (`gs`) o pdftoppm. En el VPS Linux la más ligera es pdftoppm:
  `apt install poppler-utils`. Sin conversor, los PDFs pasan a Gemini.
- **Aumentar contexto**: cambiar `n_ctx` a 16384 en `config.json` del servidor
  y reiniciar. Con 4 GB de VRAM y Q4_K_M hay margen suficiente.
- **Latencia en arranque**: la primera llamada puede tardar varios segundos
  mientras carga los pesos en GPU. Las siguientes son rápidas.
- **No peticiones paralelas de visión**: el modelo Q4_K_M ocupa ~3.2 GB de los
  ~4 GB disponibles. Procesar varias imágenes a la vez puede agotar la VRAM.
