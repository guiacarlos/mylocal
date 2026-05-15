# 🧠 ACIDE IA KNOWLEDGE BASE: Errores y Soluciones

Este búnker de conocimiento está destinado a sincronizar a las IAs que operen sobre este sistema, evitando la repetición de errores de arquitectura.

## 🏛️ ARQUITECTURA DE ENRUTAMIENTO (Critical)

### [ERROR]: 404 en Activos desde Rutas Deep (Multi-level)
*   **Contexto**: En URLs como `/academy/slug-curso/slug-leccion`, el navegador solicita `/academy/slug-curso/assets/index.js`.
*   **Fallo**: El servidor busca el archivo físicamente en esa ruta y falla.
*   **Solución ACIDE**: El `router.php` debe interceptar via Regex: `/\/(assets|js|css|media|fonts)\/(.+)$/` y redirigir la búsqueda físicamente a la raíz del búnker: `__DIR__ . '/release/' . $folder . '/' . $file`.
*   **Regla**: Los activos son globales en `release`, independientemente de la profundidad de la URL estética.

## 📡 MOTOR DE DATOS (API / ACIDE)

### [ERROR]: API Inalcanzable desde la Academia
*   **Contexto**: El frontend intenta llamar a `acide/index.php`.
*   **Fallo**: Se hace una llamada relativa que resulta en `/academy/curso/acide/index.php`.
*   **Solución ACIDE**: El `router.php` debe buscar la cadena `/acide/` en cualquier parte de la URI (`strpos($uri, '/acide/') !== false`) y capturarla para derivarla al motor real en la raíz.

### [ERROR]: Inconsistencia en DATA_ROOT
*   **Fallo**: Diferentes scripts (`tunel.php`, `ACIDE.php`, `chat_gemini.php`) definían `DATA_ROOT` de forma distinta (uno en `/data`, otro en `/acide/data`).
*   **Solución ACIDE**: Unificar todo a `define('DATA_ROOT', __DIR__ . '/data')` dentro de la carpeta `acide`.

## 🤖 TUTOR IA (Gemini & Vault)

### [ERROR]: Chat IA Vacío o Error de Modelo
*   **Causa**: Uso de nombres de modelo obsoletos como `gemini-flash-latest`.
*   **Solución ACIDE**: Usar `gemini-1.5-flash`.
*   **Fallo de Vault**: Si `lessonId` no llega al backend, Gemini no tiene contexto. El frontend debe asegurar el envío del ID de lección forjado.

## 🔐 SEGURIDAD Y LOGIN

### [ERROR]: Bucle de Redirección o 404 en Login
*   **Causa**: El motor de la SPA (React) intenta capturar la ruta `/login` antes que el servidor sirva el archivo estático.
*   **Solución ACIDE**: La ruta `/login` debe ser servida de forma prioritaria en el `router.php` directamente desde `release/login.html` ANTES de activar el motor de React.

---
**Nota para la IA**: No intentes refactorizar el enrutador a un modelo de controladores MVC. ACIDE es un sistema de túneles soberanos sobre archivos forjados.
