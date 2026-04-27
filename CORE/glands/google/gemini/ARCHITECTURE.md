# 🏛️ GEMINI - ARQUITECTURA MODULAR ATÓMICA

## 📋 RESUMEN

Gemini ha sido **completamente reconstruido desde cero** con una arquitectura modular, granular y atómica siguiendo los principios de ACIDE y la documentación oficial de Google Gemini API.

---

## 🏗️ ESTRUCTURA MODULAR

```
glands/google/gemini/
├── Gemini.php (Orquestador principal - 280 líneas)
├── config/
│   └── GeminiConfig.php (Configuración - 100 líneas)
└── core/
    ├── GeminiClient.php (Cliente HTTP - 120 líneas)
    ├── GeminiPayloadBuilder.php (Constructor de payloads - 110 líneas)
    ├── GeminiResponseParser.php (Parser de respuestas - 100 líneas)
    └── GeminiToolExecutor.php (Ejecutor de herramientas - 100 líneas)
```

**Total**: ~810 líneas organizadas en 6 archivos modulares
**Antes**: ~300 líneas monolíticas en 1 archivo

---

## 🎯 RESPONSABILIDADES ÚNICAS

### 1. **GeminiConfig** (config/GeminiConfig.php)
**Responsabilidad**: Gestionar configuración de Gemini

**Funciones**:
- Cargar configuración del sistema
- Valores por defecto
- Validación de API key
- Modelos de fallback
- Configuración de generación (temperature, maxTokens, etc.)

**Configuración**:
```php
'temperature' => 0.7,
'maxOutputTokens' => 8192,
'maxToolsPerTurn' => 3,
'maxRecursionDepth' => 10,
'timeout' => 60
```

---

### 2. **GeminiClient** (core/GeminiClient.php)
**Responsabilidad**: Comunicación HTTP con Gemini API

**Funciones**:
- Peticiones POST/GET
- Validación de respuestas HTTP
- Construcción de URLs
- Manejo de errores de red
- Timeouts configurables

**Métodos**:
- `post(url, payload)` - Petición POST
- `get(url)` - Petición GET
- `buildGenerateContentUrl(model, apiVersion)` - Construir URL
- `buildListModelsUrl(apiVersion)` - URL para listar modelos

---

### 3. **GeminiPayloadBuilder** (core/GeminiPayloadBuilder.php)
**Responsabilidad**: Construir payloads para Gemini API

**Funciones**:
- Construir payload inicial
- Agregar respuestas del modelo
- Agregar respuestas de herramientas
- Manipular estructura de payloads
- Eliminar herramientas (para forzar solo texto)

**Métodos**:
- `buildInitialPayload(prompt, tools)` - Payload inicial
- `addModelResponse(payload, response)` - Agregar respuesta del modelo
- `addToolResponses(payload, responses)` - Agregar respuestas de herramientas
- `removeTools(payload)` - Eliminar herramientas

---

### 4. **GeminiResponseParser** (core/GeminiResponseParser.php)
**Responsabilidad**: Parsear y validar respuestas de Gemini

**Funciones**:
- Validar estructura de respuestas
- Extraer texto
- Detectar function calls
- Extraer function calls
- Logs detallados de errores

**Métodos**:
- `parse(response)` - Validar y extraer contenido
- `extractText(content)` - Extraer texto
- `hasFunctionCalls(content)` - Detectar function calls
- `extractFunctionCalls(content)` - Extraer function calls

---

### 5. **GeminiToolExecutor** (core/GeminiToolExecutor.php)
**Responsabilidad**: Ejecutar herramientas MCP y gestionar límites

**Funciones**:
- Ejecutar herramientas MCP
- Controlar límites de ejecución
- Detectar bucles (misma herramienta repetida)
- Formatear respuestas para Gemini
- Contador de herramientas ejecutadas

**Métodos**:
- `execute(toolName, args)` - Ejecutar herramienta
- `hasReachedLimit()` - Verificar límite
- `isLoop(toolName)` - Detectar bucle
- `getExecutedCount()` - Contador
- `reset()` - Resetear para nuevo turno

---

### 6. **Gemini** (Gemini.php)
**Responsabilidad**: Orquestar todos los componentes

**Funciones**:
- Inicializar componentes modulares
- Coordinar flujo de generación
- Gestionar recursión
- Forzar respuesta final cuando alcanza límites
- Fallback v1beta → v1

**Métodos públicos**:
- `listModels()` - Listar modelos disponibles
- `generate(params)` - Generar respuesta

**Métodos privados**:
- `processConversation(url, payload, depth)` - Procesar conversación
- `forceFinalResponse(url, payload)` - Forzar respuesta final
- `parseModelsList(response)` - Parsear lista de modelos

---

## 🔄 FLUJO DE EJECUCIÓN

```
Usuario hace pregunta
    ↓
Gemini::generate(params)
    ↓
GeminiPayloadBuilder::buildInitialPayload()
    ↓
GeminiClient::post(url, payload)
    ↓
GeminiResponseParser::parse(response)
    ↓
¿Hay function calls?
    ├─ NO → Retornar texto
    └─ SÍ → GeminiToolExecutor::execute()
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
- Valida código HTTP 200
- Valida JSON válido
- Valida estructura de candidatos
- Logs detallados de cada error

### 5. **Fallback Automático**
- v1beta → v1 si el modelo no está disponible
- Modelos de fallback si la API falla
- Respuesta genérica si todo falla

---

## 🔧 CONFIGURACIÓN

### Ajustar límites:
Editar `config/GeminiConfig.php`:
```php
private const DEFAULTS = [
    'maxToolsPerTurn' => 3,      // Cambiar a 5, 10, etc.
    'maxRecursionDepth' => 10,   // Cambiar a 15, 20, etc.
    'timeout' => 60              // Cambiar timeout HTTP
];
```

### Ajustar generación:
```php
'temperature' => 0.7,      // 0.0 - 1.0
'maxOutputTokens' => 8192, // Máximo de tokens
'topP' => 0.95,            // Nucleus sampling
'topK' => 40               // Top-k sampling
```

---

## 🧪 TESTING

### Test de sintaxis:
```bash
php -l Gemini.php
php -l config/GeminiConfig.php
php -l core/GeminiClient.php
php -l core/GeminiPayloadBuilder.php
php -l core/GeminiResponseParser.php
php -l core/GeminiToolExecutor.php
```

### Test funcional:
```php
$gemini = new Gemini($services);

// Test 1: Listar modelos
$models = $gemini->listModels();

// Test 2: Generar respuesta
$result = $gemini->generate([
    'prompt' => '¿Qué es ACIDE?',
    'model' => 'gemini-2.0-flash-exp'
]);
```

---

## 📈 BENEFICIOS

### 1. **Modularidad**
- Cada componente tiene UNA responsabilidad
- Fácil de entender y mantener
- Testeable individualmente

### 2. **Escalabilidad**
- Agregar nuevas funcionalidades sin romper existentes
- Reemplazar componentes sin afectar otros
- Extender sin modificar (Open/Closed Principle)

### 3. **Mantenibilidad**
- Código limpio y organizado
- Logs detallados para debugging
- Fácil localizar y corregir errores

### 4. **Robustez**
- Múltiples niveles de validación
- Manejo de errores en cada componente
- Fallbacks automáticos

### 5. **Rendimiento**
- Control inteligente de herramientas
- Evita bucles infinitos
- Timeouts configurables

---

## 🎓 PRINCIPIOS APLICADOS

1. ✅ **Single Responsibility Principle** - Cada clase una responsabilidad
2. ✅ **Open/Closed Principle** - Abierto para extensión, cerrado para modificación
3. ✅ **Dependency Inversion** - Depende de abstracciones, no de implementaciones
4. ✅ **Separation of Concerns** - Configuración, lógica, presentación separadas
5. ✅ **DRY** (Don't Repeat Yourself) - Sin código duplicado
6. ✅ **KISS** (Keep It Simple, Stupid) - Cada componente es simple
7. ✅ **YAGNI** (You Aren't Gonna Need It) - Solo lo necesario

---

## 📚 DOCUMENTACIÓN DE REFERENCIA

- [Google Gemini API - generateContent](https://ai.google.dev/api/generate-content)
- [Google Gemini API - Function Calling](https://ai.google.dev/docs/function_calling)
- [ACIDE Architecture](../../ACIDE_ARCHITECTURE.md)

---

**Arquitecto, Gemini ha sido completamente reconstruido con arquitectura modular, granular y atómica. Cada componente tiene una responsabilidad única y está perfectamente alineado con los principios de ACIDE.** 🏛️♊⚡
