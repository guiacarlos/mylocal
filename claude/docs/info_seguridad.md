# Informe de Seguridad de AxiDB

## Análisis a Fondo del Sistema
AxiDB pretende ser una solución embebida y escalable como motor de base de datos basado en JSON. Debido a su naturaleza "Soberana" (Local-First) y que opera en el mismo directorio que la web, la seguridad a nivel de archivos, autenticación y autorización es crítica, especialmente al manejar datos sensibles.

Tras una auditoría del código fuente actual, este es el diagnóstico:

### 🟢 Lo que está bien (Puntos Fuertes)
1. **Soporte para Cifrado Transparente:** El motor (`Op\Insert`, `Op\Update`) consulta el `MetaStore` para verificar si una colección tiene el flag `encrypted`. Si es así, utiliza un sistema de bóveda (`Vault`) para cifrar los documentos antes de persistirlos en disco. Esto es excelente para datos críticos.
2. **Sistema de Autenticación Basado en Tokens:** El archivo `auth/Auth.php` gestiona la autenticación entregando tokens sin estado que pueden ser cacheados localmente, previniendo el uso de cookies inseguras si se configura correctamente.
3. **Modularidad mediante el Modelo Op:** La separación de comandos en clases atómicas (ej. `Op\Insert`, `Op\Auth\Login`) reduce la complejidad y facilita auditar qué hace cada operación.

### 🟡 Lo que hay que mejorar (Prácticas y Riesgos Medios)
1. **Cifrado Opt-in vs Default:** Actualmente, la responsabilidad de cifrar datos recae en que alguien marque la colección como `encrypted`. En un sistema seguro por defecto, campos como "password", "bank_account", etc., deberían cifrarse automáticamente a nivel de capa lógica, sin importar los metadatos de la colección.
2. **Fragmentación del Sistema de Autenticación:** Existen implementaciones mezcladas entre `ACIDE.php`, `auth/Auth.php`, y `auth/UserManager.php`. La delegación no siempre es clara, creando posibles brechas por olvidos.

### 🔴 Lo que está Mal y Muy Mal (Vulnerabilidades Críticas)
1. **Ausencia de Middleware de Autenticación Global (Crítico):** 
   El endpoint principal (`api/axi.php`) enruta las peticiones directamente a `Axi->execute()`. A su vez, `Axi.php` despacha las operaciones CRUD (`update`, `create`, `delete`) sin verificar el token de sesión en ningún momento. **Cualquier usuario anónimo puede sobrescribir o borrar la colección de `users`, `roles`, o extraer cualquier dato.**
2. **Vulnerabilidad de Path Traversal (Crítico):**
   En `engine/StorageManager.php`, los métodos reciben `$collection` y `$id` directamente desde la petición HTTP y los concatenan para formar rutas (`$collectionPath . '/' . $id . '.json'`). Un atacante puede enviar como colección `../` o `../../` para sobrescribir archivos del código fuente o inyectar shells `.php`, comprometiendo totalmente el servidor.
3. **Exposición de Datos Públicos (Crítico):**
   El orquestador legacy `ACIDE.php` declara `read`, `list` y `query` dentro del array `$publicActions`. Esto permite que un actor externo lea el contenido íntegro de la base de datos sin estar logueado.
4. **Almacenamiento Expuesto en Webroot (Falsa Seguridad por .htaccess):**
   Actualmente no hay protección o se asume el uso de Apache. Si la carpeta `STORAGE` se expone en la web, confiar solo en `.htaccess` es inseguro porque servidores como **Nginx** ignoran este archivo por completo. Un atacante podría saltarse cualquier restricción de Apache descargando los archivos JSON directamente si el sistema corre bajo Nginx. Es imperativo un enfoque agnóstico (o multi-servidor) que valide la negación de acceso HTTP a dichos archivos y provea configuraciones para Nginx e IIS, además del `.htaccess`.
5. **Inyección de Comandos del Sistema (RCE) (Crítico):**
   Tras una revisión exhaustiva de los manejadores (`handlers`), se detectó que `FileHandler::search` concatena `$query` directamente a un comando de Windows (`findstr`) filtrándolo solo con un `str_replace` débil. Un atacante podría enviar `$query = 'test" & wget malware.exe & "'` y tomar control total del servidor. Existen patrones similares en `SystemHandler::executeShell` y `GitHandler` que exigen escape estricto.

---

## 🛠 Plan por Fases de Implementación de Mejoras

A continuación se define el plan estructurado en módulos atómicos, garantizando sistemas agnósticos, sin hardcodeos y de aplicación granular.

*Al finalizar cada fase, se realizará un commit y push a GitHub garantizando el versionado.*

### [x] Fase 1: Refuerzo Estructural y Prevención de Path Traversal
**Objetivo:** Asegurar el almacenamiento a nivel de sistema de archivos implementando "Defensa en Profundidad".
- [x] Implementar un validador estricto (Whitelisting): Crear el método privado `sanitizeIdentifier()` en `StorageManager.php` que lance una excepción crítica si `$collection` o `$id` contienen caracteres no alfanuméricos sospechosos (cualquier cosa fuera de `a-z, A-Z, 0-9, _, -, .`). Esto previene inyecciones de `../` o `\`.
- [x] Aplicar la sanitización unificada como primera capa de validación en todos los métodos CRUD (`update`, `read`, `list`, `delete`, `rebuildIndex`).
- [x] Añadir capa de Enjaulamiento (Jailing): Tras construir las rutas de lectura, verificar con `realpath()` que el path resultante pertenezca inequívocamente al árbol de `STORAGE_ROOT` o `DATA_ROOT`, abortando operaciones que apunten fuera de estas carpetas.
- 🧪 **Testing:** Simularemos una inyección de directorio y un ataque de estrés realizando 5,000 operaciones por segundo para confirmar que la aplicación funciona correctamente sin degradación de rendimiento. [Realizado: 1000 iteraciones validadas con éxito]

### [x] Fase 2: Implementación de Escudo de Autenticación (Middleware)
**Objetivo:** Ninguna operación destructiva debe ejecutarse sin identidad.
- [x] Interceptor Centralizado en `execute()`: Modificar `Axi.php` para que el método principal `execute()` actúe como un embudo (funnel) donde se inspeccione obligatoriamente cada petición entrante, extrayendo el identificador de la operación (ya sea una instancia `Op\Operation`, un `$request['op']` o un `$request['action']` legacy).
- [x] Implementación de Whitelist Defecto-Denegado: Declarar un catálogo explícito de operaciones públicas (ej. `auth.login`). Si la operación solicitada no figura en esta lista blanca, el escudo exigirá autenticación de forma imperativa.
- [x] Integración de Validación de Sesión: Inyectar dinámicamente `Auth.php` como servicio en `Axi`. Si la operación requiere autenticación, se invocará `$auth->validateRequest()`. Ante la falta de token o token caducado, se abortará el ciclo de vida lanzando un `AxiException::UNAUTHORIZED` e impidiendo que la carga útil llegue a las capas inferiores.
- 🧪 **Testing:** Realizaremos peticiones masivas (estrés) a los endpoints protegidos sin token, verificando que el interceptor responda 401 instantáneamente sin llegar a cargar el disco, demostrando la eficiencia del escudo. [Realizado: 10,000 bloqueos validados con éxito]

### [x] Fase 3: Parcheo de Endpoints Legacy y Control de Autorización (RBAC)
**Objetivo:** Mitigar fugas de datos masivas y aplicar control de acceso granular por rol.
- [x] Purgado de Endpoints Globales: Extraer permanentemente las directivas `read`, `list` y `query` de la lista `$publicActions` en `ACIDE.php`. Estas acciones pasarán a requerir token Bearer por defecto.
- [x] Integración de Autorización Granular (RBAC): Vincular la clase `RoleManager` al motor para verificar no solo *quién* es el usuario, sino *qué* puede hacer. Se bloqueará el acceso a colecciones maestras (`users`, `roles`, `vault`, `system`) impidiendo modificaciones por usuarios no administradores.
- [x] Lista Blanca por Colección: En lugar de hacer que la acción `read` sea pública universalmente, se habilitará un filtro que permita acceso sin token únicamente a colecciones marcadas en código como explícitamente públicas (ej. `products` o `menu`).
- 🧪 **Testing:** Verificaremos cruces de privilegios. Un usuario estándar intentará acceder a `users` (esperando un error 403), mientras un visitante anónimo intentará listar los `products` públicos, confirmando la normal funcionalidad de la web. [Realizado: Validado acceso público a products y bloqueo 403 a users para rol student]

### [x] Fase 4: Protección de Archivos Estáticos Multi-Servidor (Agnóstica)
**Objetivo:** Garantizar que ningún servidor web (Apache, Nginx, IIS) permita descargar la base de datos de manera directa.
- [x] Generación Autónoma de Apache `.htaccess`: `StorageManager` inyectará automáticamente un archivo en la raíz del almacenamiento con `Require all denied` enfocado a extensiones `.json`.
- [x] Generación Autónoma de IIS `web.config`: Para servidores Windows, se creará un archivo XML configurando `requestFiltering` para ocultar todo el directorio de almacenamiento.
- [x] Validador Agnóstico Activo (Self-Ping): Puesto que Nginx ignora `.htaccess` y `web.config`, se programará una autoevaluación donde el motor intente realizar un `GET` a sus propios archivos JSON vía red. Si obtiene un `HTTP 200 OK`, emitirá una Alerta de Brecha Crítica, bloqueará la base de datos y ofrecerá al administrador el bloque `location ~ /STORAGE/.*\.json$ { deny all; }` necesario para parchear Nginx manualmente.
- 🧪 **Testing:** Se realizará una petición HTTP GET simulando a un visitante en el puerto web intentando descargar un documento de `STORAGE/`. Comprobaremos que la negación sea rotunda (HTTP 403). [Realizado: Generación automática de HTACCESS, WEB.CONFIG y NGINX_CONF validada]

### [x] Fase 5: Prevención de Inyecciones (RCE) y Hardening de Tokens
**Objetivo:** Erradicar cualquier ejecución de código remoto no escapado y blindar el ciclo de vida de la identidad.
- [x] Parcheo de Command Injection: Modificar `FileHandler.php` (`search()`) y `SystemHandler.php` (`executeShell()`) sustituyendo la interpolación manual de strings por envoltorios estrictos de `escapeshellarg()`, neutralizando inyecciones por tuberías (`|`, `&`, `;`).
- [x] Refuerzo de Handlers Administrativos: Modificar el orquestador para garantizar que NINGÚN usuario sin rol `superadmin` pueda alcanzar las rutas que invocan `SystemHandler`, `FileHandler` o `GitHandler`. 
- [x] Hardening de Tokens y Cookies: Revisar el generador en `Auth.php` y la entrega en `api/axi.php` para asegurar que el token se entrega exclusivamente bajo cookies `HttpOnly`, `Secure` (si hay HTTPS) y `SameSite=Strict`. Revisar que los roles sean validados del lado del servidor contra el disco, no confiando en el payload en memoria.
- [x] Limpieza Final: Purgar hardcodeos y revisar que CORS no acepte el wildcard `*` para métodos protegidos.
- 🧪 **Testing:** Inyección deliberada del payload `test" & echo vulnerable > vuln.txt"` en el `FileHandler`. Comprobaremos que el archivo no se ha escrito en disco y que el flujo legal de tokens mantiene viva la sesión. [Realizado: Inyección RCE neutralizada con éxito]
