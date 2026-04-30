---
name: skilldesarrollo
description: >
  Normas, reglas y estructura para el desarrollo de cualquier proyecto de software.
  USAR SIEMPRE que el usuario pida crear, planificar, estructurar, implementar o ampliar
  cualquier funcionalidad, módulo, sistema, plugin, API, CLI, dashboard o componente dentro
  de un proyecto. Aplica a proyectos nuevos y existentes. Si hay código que escribir,
  carpetas que crear o arquitectura que definir — esta skill debe activarse.
---

# Skill de Desarrollo de Proyectos

## Principios Fundamentales

Todo proyecto debe ser **modular, atómico, agnóstico y real**. Estas cuatro palabras son la base de todas las decisiones de arquitectura y código.

---

## 1. Arquitectura Modular por Capacidades

Cada funcionalidad es un **módulo independiente** que vive en su propio directorio autocontenido.

### Estructura base de un proyecto

```
./PROYECTO/
├── CORE/
│   ├── core/
│   │   ├── private/        # Lógica interna, utilidades base, no expuesta
│   │   └── modules/        # Módulos del núcleo reutilizables
├── CAPABILITIES/
│   ├── NOMBRE_CAPACIDAD/   # Cada capacidad en su propio directorio
│   │   ├── index.js        # Punto de entrada del módulo
│   │   ├── README.md       # Documentación del módulo
│   │   └── ...             # Archivos del módulo (≤250 líneas c/u)
├── plugins/                # Extensiones opcionales y agnósticas
│   ├── nombre_plugin/
│   │   └── ...
└── artifacts/              # Skills, plantillas, recursos del proyecto
```

### Ejemplos de rutas correctas

```
./CAPABILITIES/OCR/
./CAPABILITIES/RECETAS/
./CAPABILITIES/WEBSCRAPER/
./plugins/alimentos/
./plugins/ocr/
./CORE/core/private/
./CORE/core/modules/
```

---

## 2. Reglas de Código — NO NEGOCIABLES

### 2.1 Código Real — Sin Simulaciones ni Rellenos

**La regla más importante del proyecto: el código entregado debe funcionar con datos reales.**

Esto significa:
- **Prohibido** crear funciones con datos de prueba inventados (`return "resultado de prueba"`, arrays ficticios, objetos placeholder).
- **Prohibido** simular conexiones — si el módulo se conecta a una BD, API o servicio, la conexión debe ser real y funcional.
- **Prohibido** dejar `TODO`, `// implementar aquí`, `throw new Error("not implemented")` en código entregado.
- **Prohibido** crear archivos, rutas o módulos que no existan aún en el proyecto como si existieran — si algo no existe, se crea o se indica explícitamente.
- **Prohibido** inventar respuestas de servicios externos — si hay que llamar a una API, se llama de verdad.

```js
// ❌ MAL — simulación, datos inventados, no funciona
async function getUsuarios() {
  return [{ id: 1, nombre: "Usuario de prueba" }]; // hardcodeado falso
}

// ❌ MAL — conexión fingida
async function saveRecord(data) {
  console.log("Guardando...", data); // no hace nada real
  return { success: true }; // mentira
}

// ✅ BIEN — conexión y operación real
async function getUsuarios() {
  const db = await getConnection(); // conexión real al sistema existente
  return db.collection("usuarios").find({}).toArray();
}
```

**Si un servicio externo no está disponible en el momento del desarrollo**, se indica claramente qué falta y por qué — no se inventa una respuesta falsa.

### 2.2 Construcción Atómica — Un documento, una responsabilidad
- Cada archivo tiene **una sola responsabilidad**.
- Máximo **250 líneas de código** por archivo. Si se supera, dividir en submódulos.
- Nombres de archivo descriptivos del rol: `parser.js`, `validator.js`, `router.js`, no `utils.js` o `helpers.js`.

### 2.3 Modularidad Granular
- Cada módulo expone una **interfaz clara** (exports explícitos).
- Las dependencias entre módulos van **siempre hacia adentro** (capas externas dependen del core, no al revés).
- Ningún módulo debe conocer los detalles internos de otro.

```
CAPABILITIES/OCR/
├── index.js          # Interfaz pública del módulo
├── extractor.js      # Lógica de extracción (≤250 líneas)
├── normalizer.js     # Normalización de resultados (≤250 líneas)
└── config.js         # Configuración del módulo
```

### 2.4 Agnosticismo
- Los módulos **no deben acoplarse** a la implementación concreta de otros módulos.
- Usar interfaces/contratos, no implementaciones directas.
- Si el sistema base (axidb, core, etc.) necesita acoger una nueva solución, debe ser como **plugin modular**:

```
./axidb/plugins/alimentos/
./axidb/plugins/ocr/
```

---

## 3. Sistema de Plugins

Cuando una funcionalidad es extensión o integración opcional:

```
./plugins/
├── nombre_plugin/
│   ├── index.js        # Punto de entrada y registro del plugin
│   ├── README.md       # Qué hace, cómo instalarlo, dependencias
│   └── config.js       # Configuración propia del plugin
```

Los plugins se **registran**, no se importan directamente en el core.

---

## 4. Diseño y Vistas

### Frontend / Cliente (skill de diseño)
- Para diseño de vistas del cliente usar: `./artifacts/skilldiseño.md`
- Si se crea o modifica una implementación visual y se mejora → **sobreescribir la skill** con el patrón mejorado (autoaprendizaje).

### Dashboard / Administración (skill de dashboard)
- Para el diseño de la parte interna del dashboard de administración usar: `./artifacts/skilldashboard.md`

---

## 5. Plan de Implementación — Estructura Obligatoria

Antes de escribir código, generar un plan con esta estructura:

```
## Plan de Implementación: [Nombre del módulo/funcionalidad]

### Árbol de directorios
[estructura completa de carpetas y archivos a crear]

### Módulos a crear
| Archivo | Responsabilidad | Líneas estimadas |
|---------|----------------|-----------------|
| ...     | ...            | <250            |

### Dependencias externas
[librerías necesarias, sin hardcodeo]

### Variables de entorno necesarias
[lista de .env requeridas]

### Interfaz pública del módulo
[qué exporta, cómo se consume]
```

---

## 6. Calidad y Mantenibilidad

### Documentación mínima obligatoria
Cada módulo debe tener un `README.md` con:
- Qué hace
- Cómo instalarlo / configurarlo
- Variables de entorno necesarias
- Ejemplo de uso

### Nombramiento consistente
- Directorios: `MAYUSCULAS` para CAPABILITIES, `minusculas` para plugins y módulos internos.
- Archivos: `camelCase.js` o `kebab-case.js` — elegir uno y ser consistente en todo el proyecto.
- No abreviaturas crípticas: `userAuthValidator.js` no `uav.js`.

### Testing
- Cada módulo debe ser **testeable en aislamiento**.
- Si hay lógica de negocio, debe existir al menos un test de humo.

---

## 7. Checklist antes de entregar cualquier implementación

Antes de dar por finalizada cualquier tarea de desarrollo, verificar:

- [ ] ¿Cada archivo tiene ≤250 líneas?
- [ ] ¿Hay funciones con datos inventados o de prueba? → Reemplazar con lógica real
- [ ] ¿Todas las conexiones (BD, API, servicios) son reales y funcionales?
- [ ] ¿Hay algún `TODO`, placeholder o `not implemented`? → Completar o declarar dependencia explícita
- [ ] ¿Se usan solo módulos/rutas que realmente existen en el proyecto?
- [ ] ¿Cada archivo tiene una sola responsabilidad?
- [ ] ¿El módulo es independiente y tiene su propio directorio?
- [ ] ¿Existe un `index.js` o punto de entrada claro?
- [ ] ¿Existe `README.md` en el módulo?
- [ ] ¿Las extensiones van como plugins, no en el core?
- [ ] ¿El sistema funciona realmente (no es simulado)?
- [ ] ¿Se usó la skill de diseño para vistas? (`./artifacts/skilldiseño.md`)
- [ ] ¿Se usó la skill de dashboard para admin? (`./artifacts/skilldashboard.md`)

---

## 8. Anti-patrones — Nunca hacer esto

| Anti-patrón | Por qué está mal | Alternativa |
|-------------|-----------------|-------------|
| Función que devuelve datos inventados | No hace nada real, engaña al sistema | Implementar la lógica real completa |
| `return { success: true }` sin hacer nada | Simula éxito sin ejecutar ninguna operación | Ejecutar la operación y devolver resultado real |
| `// TODO: conectar aquí` en código entregado | El módulo no funciona | Conectar al sistema real o declarar dependencia faltante |
| Importar módulos o rutas que no existen | Error en runtime, arquitectura falsa | Solo referenciar lo que existe; crear lo que falte |
| Arrays o objetos de prueba en lógica de producción | Contamina el flujo real con datos falsos | Datos desde la fuente real (BD, API, archivo) |
| Un archivo `utils.js` con 800 líneas | Sin responsabilidad única, imposible mantener | Dividir en `dateUtils.js`, `stringUtils.js`, etc. |
| Toda la lógica en `index.js` | Viola atomicidad | Separar en submódulos |
| Módulos acoplados directamente entre sí | Imposible mantener o sustituir | Inyección de dependencia o interfaz |
| Lógica de negocio en rutas/controllers | Mezcla de responsabilidades | Services/UseCases separados |

---

## Resumen de la filosofía

> **Modular**: cada cosa en su lugar, cada lugar con una cosa.  
> **Atómico**: un archivo, una responsabilidad, menos de 250 líneas.  
> **Agnóstico**: módulos que no saben de la implementación de otros.  
> **Real**: sin simulaciones, sin datos inventados, sin conexiones fingidas — el sistema conecta y opera de verdad.  
> **Extensible**: crecer agregando plugins, no modificando el core.  
> **Mantenible**: cualquier desarrollador puede entender, modificar y ampliar sin miedo.
