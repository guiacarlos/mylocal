# 🌿 OLLAMA - ARQUITECTURA MODULAR ATÓMICA

## 📋 RESUMEN

Ollama ha sido **completamente reconstruido desde cero** con una arquitectura modular, granular y atómica siguiendo los principios de ACIDE y la documentación oficial de Ollama API.

---

## 🏗️ ESTRUCTURA MODULAR

```
glands/oss/ollama/
├── Ollama.php (Orquestador principal - 220 líneas)
├── config/
│   └── OllamaConfig.php (Configuración - 90 líneas)
└── core/
    ├── OllamaClient.php (Cliente HTTP - 90 líneas)
    ├── OllamaPayloadBuilder.php (Constructor de payloads - 90 líneas)
    ├── OllamaResponseParser.php (Parser de respuestas - 70 líneas)
    └── OllamaToolExecutor.php (Ejecutor de herramientas - 90 líneas)
```

**Total**: ~650 líneas organizadas en 6 archivos modulares
**Antes**: ~132 líneas monolíticas en 1 archivo

---

## 🎯 RESPONSABILIDADES ÚNICAS

### 1. **OllamaConfig** (config/OllamaConfig.php)
**Responsabilidad**: Gestionar configuración de Ollama

**Funciones**:
- Cargar configuración del sistema
- Valores por defecto
- URL base configurable
- Opciones de generación (temperature, num_predict, etc.)
- Timeout más largo (120s) para procesamiento local

**Configuración**:
```php
'temperature' => 0.7,
'num_predict' => 2048,
'maxToolsPerTurn' => 3,
'maxRecursionDepth' => 10,
'timeout' => 120,
'baseUrl' => 'http://localhost:11434'
```

---

### 2. **OllamaClient** (core/OllamaClient.php)
**Responsabilidad**: Comunicación HTTP con Ollama API

**Funciones**:
- Peticiones POST/GET
- Validación de respuestas HTTP
- Manejo de errores de red
- Timeout configurable (120s para local)

**Métodos**:
- `post(endpoint, payload)` - Petición POST
- `get(endpoint)` - Petición GET

**Endpoints**:
- `/api/chat` - Chat con tool calling
- `/api/tags` - Listar modelos

---

### 3. **OllamaPayloadBuilder** (core/OllamaPayloadBuilder.php)
**Responsabilidad**: Construir payloads para Ollama API

**Funciones**:
- Construir payload inicial para /api/chat
- Agregar mensajes al historial
- Gestionar herramientas
- Formato específico de Ollama

**Métodos**:
- `buildInitialPayload(prompt, tools)` - Payload inicial
- `addMessage(payload, role, content, toolCalls)` - Agregar mensaje
- `removeTools(payload)` - Eliminar herramientas

---

### 4. **OllamaResponseParser** (core/OllamaResponseParser.php)
**Responsabilidad**: Parsear y validar respuestas de Ollama

**Funciones**:
- Validar estructura de respuestas
- Extraer texto
- Detectar tool calls
- Extraer tool calls
- Formato específico de Ollama

**Métodos**:
- `parse(response)` - Validar y extraer mensaje
- `extractText(message)` - Extraer texto
- `hasToolCalls(message)` - Detectar tool calls
- `extractToolCalls(message)` - Extraer tool calls

---

### 5. **OllamaToolExecutor** (core/OllamaToolExecutor.php)
**Responsabilidad**: Ejecutar herramientas MCP y gestionar límites

**Funciones**:
- Ejecutar herramientas MCP
- Controlar límites de ejecución
- Detectar bucles (misma herramienta repetida)
- Retornar resultados como string

**Métodos**:
- `execute(toolName, args)` - Ejecutar herramienta
- `hasReachedLimit()` - Verificar límite
- `isLoop(toolName)` - Detectar bucle
- `getExecutedCount()` - Contador
- `reset()` - Resetear para nuevo turno

---

### 6. **Ollama** (Ollama.php)
**Responsabilidad**: Orquestar todos los componentes

**Funciones**:
- Inicializar componentes modulares
- Coordinar flujo de generación
- Gestionar recursión
- Forzar respuesta final cuando alcanza límites

**Métodos públicos**:
- `listModels()` - Listar modelos disponibles
- `generate(params)` - Generar respuesta

**Métodos privados**:
- `processConversation(payload, depth)` - Procesar conversación
- `forceFinalResponse(payload)` - Forzar respuesta final

---

## 🔄 FLUJO DE EJECUCIÓN

```
Usuario hace pregunta
    ↓
Ollama::generate(params)
    ↓
OllamaPayloadBuilder::buildInitialPayload()
    ↓
OllamaClient::post('/api/chat', payload)
    ↓
OllamaResponseParser::parse(response)
    ↓
¿Hay tool calls?
    ├─ NO → Retornar texto
    └─ SÍ → OllamaToolExecutor::execute()
            ↓
            ¿Alcanzó límite?
            ├─ SÍ → forceFinalResponse()
            └─ NO → Recursión (processConversation)
```

---

## 🛡️ CONTROLES IMPLEMENTADOS

### 1. **Límite de Recursión**
- Máximo 10 iteraciones (configurable)
- Evita bucles infinitos
- Log: `"Máxima recursión alcanzada (depth=10)"`

### 2. **Límite de Herramientas por Turno**
- Máximo 3 herramientas por respuesta (configurable)
- Fuerza respuesta final cuando alcanza el límite
- Log: `"Límite de herramientas alcanzado. Forzando respuesta final."`

### 3. **Detección de Bucles**
- Detecta si intenta ejecutar la misma herramienta 2 veces
- Salta la herramienta repetida
- Log: `"Bucle detectado - herramienta 'cat' ya ejecutada"`

### 4. **Validación de Respuestas**
- Valida estructura de mensaje
- Logs detallados de cada error
- Manejo robusto de errores

### 5. **Timeout Largo**
- 120 segundos para procesamiento local
- Ollama puede ser más lento que APIs cloud

---

## 🔧 CONFIGURACIÓN

### Ajustar límites:
Editar `config/OllamaConfig.php`:
```php
private const DEFAULTS = [
    'maxToolsPerTurn' => 3,      // Cambiar a 5, 10, etc.
    'maxRecursionDepth' => 10,   // Cambiar a 15, 20, etc.
    'timeout' => 120             // Cambiar timeout HTTP
];
```

### Ajustar generación:
```php
'temperature' => 0.7,      // 0.0 - 1.0
'num_predict' => 2048,     // Máximo de tokens
'top_p' => 0.9,            // Nucleus sampling
'top_k' => 40              // Top-k sampling
```

---

## 🧪 TESTING

### Test de sintaxis:
```bash
php -l Ollama.php
php -l config/OllamaConfig.php
php -l core/OllamaClient.php
php -l core/OllamaPayloadBuilder.php
php -l core/OllamaResponseParser.php
php -l core/OllamaToolExecutor.php
```

### Test funcional:
```php
$ollama = new Ollama($services);

// Test 1: Listar modelos
$models = $ollama->listModels();

// Test 2: Generar respuesta
$result = $ollama->generate([
    'prompt' => '¿Qué es ACIDE?'
]);
```

---

## 📊 DIFERENCIAS CON GEMINI

| Aspecto | Gemini | Ollama |
|---------|--------|--------|
| API | REST (Google) | REST (Local) |
| Endpoint | /v1beta/models/{model}:generateContent | /api/chat |
| Timeout | 60s | 120s |
| Formato tools | functionDeclarations | tools (JSON Schema) |
| Formato response | candidates[].content | message |
| Tool calls | functionCall | tool_calls |

---

## 🎓 PRINCIPIOS APLICADOS

1. ✅ **Single Responsibility** - Cada clase UNA responsabilidad
2. ✅ **Modularidad** - Componentes independientes
3. ✅ **Granularidad** - Funciones pequeñas y específicas
4. ✅ **Atomicidad** - Cada archivo una unidad atómica
5. ✅ **Testeable** - Cada componente se puede probar individualmente
6. ✅ **Mantenible** - Código limpio y organizado
7. ✅ **Escalable** - Fácil agregar funcionalidades

---

## 📚 DOCUMENTACIÓN DE REFERENCIA

- [Ollama API - Chat](https://github.com/ollama/ollama/blob/main/docs/api.md#generate-a-chat-completion)
- [Ollama API - Tool Calling](https://ollama.com/blog/tool-support)
- [ACIDE Architecture](../../ACIDE_ARCHITECTURE.md)

---

**Arquitecto, Ollama ha sido completamente reconstruido con arquitectura modular, granular y atómica. Cada componente tiene una responsabilidad única y está perfectamente alineado con los principios de ACIDE.** 🌿⚡🏛️
