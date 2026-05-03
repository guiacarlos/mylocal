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

## 3. AxiDB y Comunicación Segura (Patrón ACIDE)

### 3.1 Motor de Base de Datos AxiDB
AxiDB no es una base de datos convencional. Es un motor basado en ficheros con un modelo de **Operaciones (Ops)** y **Acciones**. Para desarrollar correctamente:

- **Arquitectura de Comunicación Única:**
    - **Frontend (SPA):** Las **páginas y componentes React** no llaman a `fetch` directamente. Usan `SynaxisClient` (en `spa/src/synaxis`), que internamente sí usa `fetch()` para el scope `server` (POST a `/acide/index.php`), gestionando cookies de sesión, CSRF y enrutamiento local/server/hybrid. El `fetch` existe — encapsulado en `SynaxisClient`, nunca expuesto a los componentes.
    - **Backend (PHP):** Para operaciones de datos dentro del servidor, usar los **objetos internos de AxiDB** en modo embebido (`$db = new Client();`). El modo HTTP de AxiDB (`new Client('http://...')`) existe para llamadas PHP-a-PHP entre servicios distintos, no para llamadas internas del mismo proceso.
- **Modelo de Contrato (Op Model):**
    - AxiDB responde a un contrato único: `{op, ...params}` o `{action, data}`.
    - Las respuestas son siempre estandarizadas: `{success, data, error, code, duration_ms}`.
- **Seguridad y Credenciales:**
    - **NUNCA** se pasan credenciales (passwords, tokens) manualmente en el cuerpo de las peticiones visibles por el navegador una vez establecida la sesión.
    - La sesión se gestiona mediante **cookies HttpOnly** (`acide_session` / `socola_session`) que el navegador adjunta automáticamente.
    - La protección contra CSRF se delega en el `SynaxisClient`, que inyecta el header `X-CSRF-Token` de forma automática.

---

## 4. Ciclo de Vida: Build y Release

### 4.1 El proceso de Build
Para que los cambios realizados en la aplicación (especialmente en la SPA) sean efectivos y testeables:
- **Es necesario ejecutar un build (`npm run build` o `build.ps1`).** Las pruebas finales no se realizan sobre el código fuente de desarrollo, sino sobre la versión compilada en `release/`.
- **Verificación en Release:** Tras el build, se debe comprobar que la versión guardada en la carpeta `release/` preserva y refleja fielmente los cambios realizados.

### 4.2 Integridad de la carpeta Release
- **REGLA DE ORO:** NUNCA, bajo ninguna circunstancia, se manipula o modifica manualmente la carpeta `release/`.
- Cualquier cambio necesario debe realizarse en el código fuente (`spa/`, `CAPABILITIES/`, etc.) de forma que el proceso de build incorpore dichos cambios de manera **natural y automática** en el release. Si algo no llega al release, se arregla el build o el código fuente, nunca el destino.

---

## 5. Sistema de Plugins

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
- [ ] ¿Se usó la skill de dashboard para admin? (`./artifacts/skilldashboard.md`)
- [ ] ¿Las páginas/componentes React usan `SynaxisClient` (no `fetch` directo)? (SynaxisClient usa fetch internamente — eso es correcto)
- [ ] ¿Se ha realizado un `build` y verificado que los cambios están en `release/`?
- [ ] ¿Se ha evitado CUALQUIER manipulación manual de la carpeta `release/`?
- [ ] ¿Las peticiones respetan el Op Model `{op, ...}` o `{action, data}`?

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
| `fetch()` directo en una página/componente React | Bypasa CSRF, sesión y scope routing de SynaxisClient | Usar `client.execute({action: '...'})` — SynaxisClient usa fetch internamente, pero con todas las cabeceras correctas |
| IndexedDB / localStorage para datos de negocio | Los datos deben vivir en STORAGE/ (AxiDB), no en el navegador | Todo dato persistente va al servidor via acción |
| Conexión MySQL/PDO al motor de datos | AxiDB no usa SQL externo ni TCP — es embebido en PHP | `$db = new Client(); $db->collection('...')` |
| Modificar `release/` directamente | Se sobreescribirá en el próximo build | Modificar la fuente y ejecutar `.\build.ps1` |
| Probar cambios sin hacer build | `release/` puede estar desactualizado | Ejecutar `.\build.ps1` antes de probar |

---

---

## 9. AxiDB — El Motor de Datos Soberano

AxiDB no es una base de datos convencional. Es un motor PHP file-based que reemplaza completamente a MySQL/PostgreSQL/MongoDB. Entender cómo funciona es OBLIGATORIO para desarrollar correctamente en este proyecto.

### 9.1 Qué es AxiDB

- **Motor embebido en PHP**: no hay servidor de BD separado, no hay puerto TCP, no hay credenciales de conexión.
- **Almacenamiento**: archivos JSON en `STORAGE/<coleccion>/<id>.json`. Sin SQL, sin esquemas rígidos.
- **Op Model**: toda operación es un objeto inmutable serializable. Se ejecuta con `$db->execute(['op' => '...', ...])`.
- **Sin intermediarios**: PHP lee y escribe STORAGE/ directamente via filesystem. Latencia sub-milisegundo.
- **Sin cliente en el navegador**: el navegador NUNCA accede a AxiDB. Solo ve respuestas JSON del servidor.

### 9.2 Cómo se comunica el frontend con AxiDB

```
React (SPA)
  └── SynaxisClient.execute({action, ...})
        │
        ├── scope: local  → SynaxisCore (IndexedDB del navegador)
        │
        └── scope: server → fetch() POST /acide/index.php
                                  │
                            PHP (CORE/index.php o spa/server/index.php)
                                  └── AxiDB embebido ──► STORAGE/
                            ◄─────────────────────────────────────────
                            { success, data, error }
```

El `fetch()` existe y es correcto — lo usa `SynaxisClient` internamente. Lo que NO hace el navegador es acceder directamente a `STORAGE/`, usar tokens de base de datos, ni llamar al endpoint AxiDB raw (`/axidb/api/axi.php`). Solo envía acciones de negocio (`auth_login`, `list_products`, etc.) y recibe JSON limpio.

### 9.3 Cómo NO usar AxiDB (errores frecuentes)

```php
// ❌ MAL — AxiDB no es SQL externo
$db = new PDO('mysql://localhost:3306/axidb');

// ❌ MAL — el frontend no accede directamente a STORAGE/
fetch('/STORAGE/productos/123.json')

// ❌ MAL — desde una página React, llamar fetch sin pasar por SynaxisClient
const res = await fetch('/acide/index.php', { method: 'POST', body: ... }); // en un componente

// ❌ MAL — en PHP embebido, llamar HTTP contra sí mismo es innecesario
$db = new Client('http://localhost/axidb/api/axi.php'); // cuando ya estás en el mismo proceso
```

### 9.4 Cómo SÍ usar AxiDB (modo embebido — el modo correcto para este proyecto)

```php
// ✅ BIEN — Modo embebido, sin red, sin credenciales
require 'axidb/axi.php';
use Axi\Sdk\Php\Client;

$db = new Client();  // sin argumentos = embebido directo
$productos = $db->collection('productos');

// Leer
$lista = $productos->orderBy('nombre')->get();

// Escribir
$productos->insert(['nombre' => 'Tortilla', 'precio' => 4.50]);

// AxiSQL fluido
$caros = $db->sql("SELECT nombre, precio FROM productos WHERE precio > 5 ORDER BY precio DESC LIMIT 10");
```

### 9.5 Cómo el frontend SPA envía acciones al servidor

En el SPA (React), las **páginas y componentes** nunca llaman a `fetch()` directamente. Usan `SynaxisClient`, que internamente usa `fetch()` para el scope `server` — esto es correcto y esperado. El `fetch` existe, encapsulado:

```ts
// ✅ BIEN — página/componente usa SynaxisClient
const res = await client.execute({ action: 'list_products' });
// SynaxisClient internamente hace: fetch('/acide/index.php', { method: 'POST', ... })
// con cookies, CSRF header y manejo de 401/419 — transparente para el componente.

// ✅ BIEN — scope local: solo IndexedDB (SynaxisCore), sin red
const res = await client.execute({ action: 'get_product', id: '123' }); // scope: local

// ❌ MAL — fetch raw en una página/componente React (bypasa CSRF, sesión, scope routing)
const res = await fetch('/acide/index.php', { method: 'POST', body: JSON.stringify({action: 'list_products'}) });

// ❌ MAL — acceso directo a STORAGE/ desde el navegador
await fetch('/STORAGE/productos/index.json');

// ❌ MAL — IndexedDB manual para datos de negocio persistentes
indexedDB.open('productos');  // Los datos persistentes van en STORAGE/ (servidor)
```

### 9.6 Protocolo de Op AxiDB

```json
// Request al servidor (POST /acide/index.php)
{ "op": "select", "collection": "productos", "where": [{"field": "precio", "op": "<", "value": 5}] }

// O formato legacy ACIDE (también válido)
{ "action": "list_products" }

// Respuesta siempre tiene esta forma
{ "success": true, "data": [...], "error": null }
```

---

## 10. Flujo de Build y Verificación

### 10.1 La regla de oro: nunca tocar `release/` directamente

`release/` es la carpeta de producción. Se genera AUTOMÁTICAMENTE con `.\build.ps1`. **Nunca se modifica manualmente**. Si se modifica a mano:

- El próximo build SOBREESCRIBIRÁ los cambios (los archivos PHP se copian con `-Force`).
- Los errores se volverán imposibles de reproducir (diferencia entre fuente y binario).
- Se rompe la integridad del sistema de build.

### 10.2 Flujo correcto para cualquier cambio

```
1. Editar en la FUENTE:
   - Frontend React: spa/src/
   - Backend PHP:    CORE/  o  CAPABILITIES/  o  axidb/

2. Build:
   .\build.ps1

3. Verificar en release/:
   - Que el JS/CSS compilado refleja los cambios
   - Que el PHP fue copiado correctamente
   - Que STORAGE/ conserva los datos (NO se borra en el build)

4. Probar con el servidor de desarrollo:
   start.bat   (php -S localhost:3000 -t release release\router.php)

5. Solo si todo funciona → commit y push
```

### 10.3 Por qué `release/STORAGE/` sobrevive al build

`build.ps1` NO incluye `STORAGE/` en la lista de elementos a copiar. Solo crea `STORAGE/` vacío si NO EXISTE. Los datos del restaurante (usuarios, carta, pedidos) se preservan entre builds.

### 10.4 Cómo verificar que un cambio llegó a release/

```powershell
# Verificar que el PHP fuente y release son iguales
diff CORE/index.php release/CORE/index.php

# Verificar que el JS se compiló (fecha de modificación)
ls release/assets/*.js | sort -Property LastWriteTime

# Verificar integridad de datos
cat release/STORAGE/.vault/users/index.json
```

---

## Resumen de la filosofía

> **Modular**: cada cosa en su lugar, cada lugar con una cosa.  
> **Atómico**: un archivo, una responsabilidad, menos de 250 líneas.  
> **Agnóstico**: módulos que no saben de la implementación de otros.  
> **Real**: sin simulaciones, sin datos inventados, sin conexiones fingidas — el sistema conecta y opera de verdad.  
> **Extensible**: crecer agregando plugins, no modificando el core.  
> **Mantenible**: cualquier desarrollador puede entender, modificar y ampliar sin miedo.  
> **Soberano**: los datos viven en STORAGE/ (AxiDB), nunca en el navegador. El frontend solo renderiza lo que el servidor entrega.
