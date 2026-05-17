# Política de Cookies

*GESTASAI TECNOLOGY SL — Última actualización: mayo de 2026*

*Conforme a las directrices de la AEPD, el RGPD (UE 2016/679) y la Directiva ePrivacy.*

Aplicable a todos los ciudadanos y residentes del Espacio Económico Europeo (EEE) y Suiza.

---

## 1. ¿Qué son las Cookies?

Las cookies son pequeños archivos de texto que un sitio web almacena en el navegador del usuario al visitarlo. Permiten recordar información entre sesiones (idioma preferido, inicio de sesión, configuración del TPV) y facilitan el funcionamiento técnico de la plataforma.

Bajo el término "cookies" agrupamos también tecnologías relacionadas:

- **Scripts:** fragmentos de código que hacen interactiva la web (carga dinámica de cartas QR, asistentes de IA)
- **Web Beacons:** imágenes o píxeles invisibles que miden el tráfico de forma agregada

---

## 2. Clasificación por Finalidad

### 2.1 Cookies Técnicas o Estrictamente Necesarias

Garantizan el funcionamiento integral de la plataforma: sesión del hostelero en el Dashboard, persistencia de pedidos en Order & Pay, seguridad frente a ataques. **No requieren consentimiento** y no pueden desactivarse sin afectar al servicio.

### 2.2 Cookies de Personalización

Recuerdan preferencias del usuario: idioma de la carta QR para el comensal, moneda de cobro de la pasarela de pagos, configuración de pantalla del TPV.

### 2.3 Cookies de Estadística y Analítica

Permiten conocer cómo interactúan los usuarios con el ecosistema (páginas más visitadas, tiempo de permanencia, rendimiento de los nodos de IA) con el fin exclusivo de mejorar el servicio. Se procesan de forma anonimizada.

### 2.4 Cookies de Marketing y Redes Sociales

Crean perfiles de comportamiento, miden conversiones publicitarias y habilitan plugins de redes sociales. **Requieren consentimiento previo y explícito** mediante el panel de gestión.

---

## 3. Inventario de Tecnologías de Almacenamiento Utilizadas

La plataforma MyLocal es una aplicación web propia desarrollada íntegramente por GESTASAI TECNOLOGY SL. No utiliza sistemas de gestión de contenidos de terceros (WordPress, Drupal u otros). Todo el almacenamiento en el navegador corresponde a tecnologías nativas del stack propio.

### A. Sesión y Autenticación (Propio — MyLocal SPA)

- **Finalidad:** mantener la sesión activa del hostelero en el Dashboard y el TPV en la nube durante el tiempo de trabajo
- **Almacenamiento:** `sessionStorage` del navegador — claves `mylocal_session` (token de acceso Bearer), `mylocal_localId` (identificador del local activo), caché del perfil de usuario
- **Duración:** sesión del navegador (se elimina al cerrar la pestaña)
- **Tipo:** Técnica / Estrictamente Necesaria

### B. Base de Datos Local del Dispositivo — SynaxisCore (Propio)

- **Finalidad:** almacenar localmente la carta digital, categorías, productos, configuración del TPV y opciones del local para permitir consulta sin conexión y sincronización multidispositivo
- **Almacenamiento:** IndexedDB del navegador — bases de datos `synaxis_*` y `synaxis_*__master`
- **Duración:** persistente hasta que el usuario cierra sesión o borra los datos del navegador
- **Tipo:** Técnica / Estrictamente Necesaria

### C. Control de Consentimiento de Cookies

- **Finalidad:** almacenar legalmente la decisión del usuario sobre las preferencias del panel de cookies
- **Almacenamiento:** `localStorage` — clave `mylocal_cookie_consent`
- **Duración:** 365 días
- **Tipo:** Estrictamente Necesaria

### D. Seguridad del Backend (Propio — GESTASAI CORE)

- **Finalidad:** proteger las peticiones al API contra accesos no autorizados; los tokens no se almacenan en cookies sino en `sessionStorage` del cliente (arquitectura bearer-only)
- **Almacenamiento:** ninguna cookie httponly; autenticación gestionada exclusivamente mediante cabeceras `Authorization: Bearer`
- **Duración:** no aplica
- **Tipo:** Técnica / Estrictamente Necesaria

### E. Analítica Web

- **Finalidad:** estadísticas de audiencia de forma agregada y anonimizada (páginas visitadas, rendimiento, rutas de conversión)
- **Cookies:** `_ga`, `_gid`, `_ga_*`
- **Duración:** de 1 día a 2 años
- **Tipo:** Estadísticas (requiere consentimiento)

### F. Protección Antispam

- **Finalidad:** evitar solicitudes automáticas fraudulentas en formularios de alta y contacto
- **Cookies:** `_grecaptcha`, `rc::a`, `rc::b`, `rc::c`
- **Duración:** de sesión a 6 meses
- **Tipo:** Seguridad / Funcional

### G. Pasarelas de Pago

- **Finalidad:** procesamiento técnico seguro de transacciones y prevención de fraude en los pagos desde mesa (Order & Pay)
- **Cookies:** cookies de sesión propias de Revolut
- **Duración:** de sesión
- **Tipo:** Necesaria para ejecutar el pago

### H. Mapas y Geolocalización

- **Finalidad:** mostrar la ubicación física de los locales hosteleros en la carta pública
- **Cookies:** cookies de servicios de mapas integrados
- **Duración:** de sesión a 8 meses
- **Tipo:** Preferencias (requiere consentimiento)

---

## 4. Gestión del Consentimiento

Al acceder por primera vez a nuestra web o plataforma, se mostrará un panel informativo:

- **"Aceptar todas"** — consiente todas las categorías de cookies
- **"Denegar"** — rechaza todas excepto las técnicas/necesarias
- **"Ver preferencias"** — configuración granular por categoría

---

## 5. Cómo Gestionar las Cookies desde su Navegador

Puede configurar su navegador en cualquier momento para bloquear, eliminar o recibir avisos sobre cookies. Tenga en cuenta que bloquear cookies técnicas puede afectar al funcionamiento del panel de control y del TPV en la nube.

Instrucciones por navegador:

- **Google Chrome:** support.google.com/chrome/answer/95647
- **Mozilla Firefox:** support.mozilla.org/es/kb/habilitar-y-deshabilitar-cookies-sitios-web
- **Microsoft Edge:** support.microsoft.com/es-es/windows/eliminar-y-administrar-cookies
- **Safari (Apple):** support.apple.com/es-es/guide/safari/sfri11471/mac

---

## 6. Sus Derechos sobre sus Datos

El uso de cookies de estadística y marketing puede implicar tratamiento de datos personales (como su dirección IP). En virtud del RGPD, dispone de los derechos de acceso, rectificación, supresión, limitación y oposición al tratamiento. Para ejercerlos, remita comunicación con copia de su documento de identidad a **info@gestasai.com**. También puede presentar reclamación ante la AEPD (aepd.es).

---

## 7. Contacto

| Canal | Datos |
| --- | --- |
| **Razón Social** | GESTASAI TECNOLOGY SL — CIF: E23950967 |
| **Dirección** | C/ Farmacéutico José María López Leal, 7, 30820 Alcantarilla (Murcia) |
| **Correo general** | info@gestasai.com |
| **Soporte MyLocal** | soporte@mylocal.es |
| **Teléfono** | +34 611 677 577 |

---

*© 2026, GESTASAI TECNOLOGY SL. Todos los derechos reservados.*
