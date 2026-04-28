# Auditoría de Seguridad AxiDB

## Resumen Ejecutivo
Se ha realizado un análisis intensivo de seguridad de **AxiDB**, un motor de base de datos híbrido planteado como sustituto local a MongoDB/MySQL de la arquitectura ACIDE. El análisis refleja que el sistema se encuentra en una **fase temprana de madurez**, habiendo adoptado enfoques innovadores pero arrastrando importantes descuidos arquitectónicos provenientes de un rápido desarrollo. Al tratarse de un entorno que pretende almacenar y transaccionar datos altamente sensibles (correos, datos bancarios de pagos, etc.), **el estado actual del sistema representa un alto riesgo y no es seguro para despliegues en producción**.

## Lo que está bien (Puntos Fuertes)
* **.htaccess de Bloqueo General**: El punto de entrada principal en la raíz deniega con acierto accesos directos al almacenamiento (`STORAGE`), capacidades y `CORE` con `[F,L]`. 
* **Uso de Criptografía Actual**: Para las contraseñas, se observa un adecuado uso de algoritmos fuertes a nivel backend (`PASSWORD_ARGON2ID` implementado en `UserEditor.php` de forma correcta).
* **Generación de Tokens**: Los tokens generados se emiten usando una entropía local pseudoaleatoria buena (`bin2hex(random_bytes(32))`), que es estadísticamente segura a fuerza bruta.
* **Separación de Responsabilidades**: En `UserManager` se empiezan a usar patrones interesantes como *Factory*, *Finder*, *Registry*, intentando delegar validaciones.

## Lo que hay que mejorar
* **Dependencia crítica de Apache**: Al depender del `.htaccess` para aislar el directorio clave `/STORAGE` y proteger bases de datos, cualquier migración accidental a Nginx (o Node, Gunicorn, Docker mal configurados) provocará que todo el sistema entero (usuarios, tokens, facturación) quede de consulta pública, descargable mediante HTTP. Las sesiones no deben dejarse de raíz en este estado; es imperativo mover la persistencia fuera del "web-root" o bloquearla explícitamente en su propio nivel también (bloqueo en cascada).
* **Gestión de Respuestas y Sanitización**: Se confía en exceso de los datos de envío provistos por los clientes (se usan masivamente datos vía el objeto `$args` o `$request["data"]` sin tipado restrictivo ni validación formal de esquemas).
* **Ausencia de Middlewares Robustos**: Las peticiones de la API y las delegaciones a controladores no poseen un mecanismo de middleware unitario para comprobaciones de identidad, permitiendo filtraciones puntuales de acceso.

---

## Brechas de Seguridad Encontradas (Vulnerabilidades Críticas)

Se enumeran todas las fallas categorizadas y comprobadas a nivel de código durante la auditoría profunda:

### [1] Escalación de Privilegios por "Mass Assignment" (Crítico)
* **Archivo**: `axidb/auth/users/UserEditor.php`
* **Flaw**: En el modelo de actualización del usuario `update()`, la variable `$protected` especifica qué campos se bloquean y no se dejan sobrescribir `['id', 'password_hash', 'email', 'created_at', 'last_login']`.
* **Impacto**: **Falta la clave 'role' en las protecciones**. Cualquier usuario que llame al servidor para actualizar su perfil (ej. al mandar `'action': 'update_profile'`) puede pasar silenciosamente `{ "role": "superadmin" }` y el sistema le aceptará la escritura en su JSON del nivel de base de datos.
* **Explotación Front-end**: Se genera una solicitud de actualización propia con la propiedad oculta introduciendo máximos permisos; obteniendo control total de la empresa de su panel a placer de un *backdoor* inherente.

### [2] Falta de Autorización (Missing Auth / IDOR) en Usuarios (Crítico)
* **Archivo**: `axidb/engine/handlers/UserHandler.php`
* **Flaw**: El array restrictivo de acciones (línea 27) indica: `$protectedActions = ['create_user', 'delete_user', 'list_users'];`. **No constan registradas** las acciones de `update_user` ni `read_user`.
* **Impacto**: Si un atacante externo, en su frontend manda una petición API con `{'action': 'read_user', 'id': 'ID_ALEATORIO'}`, este endpoint responde con el estado completo del perfil ajeno; lo mismo pasa con `{'action': 'update_user'}`. No se solicita en ningún momento estar logueado ($currentUser == null se pasa por alto) si la acción no está en el array cerrado de 'protegidos'. Exposición inmediata de correos y cuentas.

### [3] Bypass en Validación de Políticas CORS (Alto)
* **Archivo**: `axidb/api/axi.php`
* **Flaw**: En el validador de conexión remota, el sistema local comprueba la procedencia haciendo `$isLocalhost  = strpos($origin, 'localhost') !== false;`.
* **Impacto**: Esta lógica es débil. Si expone la API como `Access-Control-Allow-Credentials: true` para orígenes válidos, un atacante sólo debe crear una web llamada `https://attacker-localhost.com` para recibir todos los permisos de conexión. Dado que usa `strpos`, validará la palabra parcial, permitiendo un secuestro de sesión (Session Hijacking) aprovechando las cookies activas en la puerta trasera.

### [4] LFI (Local File Inclusion) / Path Traversal en Sesiones (Alto)
* **Archivo**: `axidb/auth/Auth.php`
* **Flaw**: En la función `validateRequest()`, se obtiene el nombre del token vía Cookie y a posteriori se adjunta para la lectura de la sesión: `$sessionFile = $this->sessionDir . '/' . $token . '.json';`.
* **Impacto**: El atacante puede reescribir manualmente la cookie `acide_session` en el navegador pasándole una ruta relativa (ej. `../../../../etc/passwd`). Ya que `file_get_contents` se ejecutará intentando leer dicho archivo. Como falla al convertir JSON el leak está un poco mitigado, pero se confirma el potencial chequeo de archivos internos de existencia. Todo input para variables de tipo 'hex' debe de ser estrictamente de rango (a-f, 0-9 y una longitud fija).

### [5] Carencia de control de inputs (XSS Almacenado / Payload Pollution) (Medio)
* **Archivo**: `ActionDispatcher.php` (Core)
* **Flaw**: El manejador procesa sin límite campos enviados (`$data`).
* **Impacto**: No existen protecciones evidentes contra el inyectado de contenido de front-end peligroso (Tags `<script>` u objetos complejos JSON), lo que convierte a AxiDB en portadora de payloads que afectarán a los agentes AI o cuando la app o el panel lea lo guardado en los clientes.

### [6] Fugas de Información por Configuración Restrictiva Transparente (Medio)
* **Archivo**: `ActionDispatcher.php` (action 'get_mesa_settings')
* **Flaw**: Cualquier persona que revise las peticiones en red desde el portal público de pagos en mesa ve la descarga integra de configuraciones de TPV que en ocasiones no están filtradas devolviendo abiertamente los números privados directos (como `bizumPhone`).

## Recomendaciones Inmediatas (Action Plan)
1. Bloquear y parchear `UserHandler.php` para exigir siempre la variable `$currentUser != null` en operaciones y definir bien quién modifica qué. Asegurar IDOR.
2. Configurar la seguridad Strict y añadir el parámetro ausente de 'role' en el arreglo del `$protected` de `UserEditor.php`. Revertir a todo cliente extra que lo haya explotado.
3. Arreglar el CORS usando `$urlParts = parse_url($origin)` comparando explícitamente y no con `strpos()`.
4. Añadir validación REGEX para todos los Tokens al recibirlos vía Headers o Cookies (Limitando explícitamente a que cumpla `^[a-f0-9]{64}$`).
5. Trasladar el almacenado del estado plano `.json` a algo más aislado criptográficamente como bases pre-encriptadas, limitando totalmente directorios con `-R` 700 desde el inicio del script.
