# CLAUDE.md — MyLocal

Guia de trabajo para agentes de IA en este proyecto.

---

## Que es este proyecto

Plataforma SaaS de hosteleria espanola. Carta digital QR + TPV + agentes IA.
Arquitectura: React (frontend) + PHP (backend) + AxiDB (datos).
Plan completo: claude/planes/mylocal.md

---

## Arquitectura fundamental

MyLocal es una aplicacion React + PHP. No hay MySQL. No hay base de datos externa.

- El frontend es React compilado con Vite. Vive en socola_spa/
- El backend es PHP puro. Vive en CORE/ y CAPABILITIES/
- La capa de datos es AxiDB: un motor file-based propio (JSON en STORAGE/)
- AxiDB reemplaza completamente a MySQL. Toda lectura y escritura pasa por el
- No se usa SQL. No se instala nada en el servidor mas alla de PHP.

La SPA se comunica con el backend exclusivamente via:
  POST /acide/index.php  (API soberana, accion + datos en JSON)

---

## Modulos activos en la SPA

Rutas React que existen en el producto:
  /               pagina de inicio publica
  /carta          carta digital publica (lectura)
  /carta/:mesa    carta con contexto de mesa (pedido QR)
  /mesa/:slug     vista de mesa QR para el cliente
  /login          autenticacion del hostelero
  /dashboard/*    panel de gestion (hostelero autenticado)
  /sistema/tpv/*  punto de venta (roles sala/cocina/admin)
  /nosotros       pagina informativa
  /contacto       pagina informativa

Modulos eliminados por no pertenecer al producto:
  Academia — eliminada. No es parte del roadmap.

---

## Reglas de construccion

- Cada archivo tiene una sola responsabilidad
- Maximo 250 lineas de codigo por archivo
- Sin comentarios que expliquen el que; solo el por que cuando no es obvio
- Sin emojis en codigo, interfaces ni documentacion
- Construccion atomica: una cosa a la vez, completa y funcional
- No se pasa a la siguiente fase sin cerrar la anterior

---

---

## Flujo de Desarrollo Ultra-Rápido

Para trabajar en el proyecto con cambios instantáneos (HMR):

1. **Ejecuta `run.bat`** en la raíz.
   - Levanta el Backend PHP (puerto 8090).
   - Levanta el Frontend Vite (puerto 5173).
   - Abre el navegador automáticamente.
2. **Edita el código** en `spa/src/`.
3. **Ver cambios**: El navegador se actualiza solo al guardar.

**IMPORTANTE**: No es necesario hacer `build.ps1` durante el desarrollo. Solo se hace al finalizar el proyecto o para despliegues reales.

---

## Proceso de Build (Producción Final)

### Carpeta de trabajo (desarrollo)

spa/    codigo fuente React + Vite
        aqui se trabaja, aqui se ejecuta npm run dev

### Carpeta de produccion: /release/

El script build.ps1 (en la raiz del proyecto) genera /release/ completa y
autosuficiente. Para construir una version de produccion:

  .\build.ps1

Eso es todo. El script:
  1. Compila la SPA React (spa/ -> release/index.html + release/assets/)
  2. Copia CORE/, CAPABILITIES/, axidb/, fonts/, seed/
  3. Copia .htaccess, gateway.php, router.php, favicon.png, manifest.json, robots.txt
  4. Crea STORAGE/ vacia (el servidor escribe datos ahi en tiempo real)

REGLAS CRITICAS:
- release/ es la carpeta de produccion completa. Se sube TAL CUAL al servidor.
- release/ incluye frontend + backend + motor de datos. Todo en uno.
- STORAGE/ NO se incluye en release/ (son datos del restaurante, van aparte)
- spa/ NUNCA va al servidor — es solo codigo fuente de desarrollo
- No se copia a mano nada. El script lo hace todo.

### Lo que contiene release/ (completo)

  index.html             SPA compilada
  assets/                JS y CSS minificados
  CORE/                  backend PHP — auth, motor API
  CAPABILITIES/          modulos PHP de negocio
  axidb/                 motor de datos AxiDB
  fonts/                 tipografias locales
  seed/                  datos de ejemplo para primer arranque
  STORAGE/               carpeta vacia — el servidor escribe aqui
  MEDIA/                 imagenes de productos
  .htaccess              enrutamiento Apache
  gateway.php            gateway de autenticacion
  router.php             router PHP
  favicon.png            icono de la app
  manifest.json          PWA manifest
  robots.txt             SEO
  schema.json            esquema AxiDB

### Para desplegar

1. .\build.ps1
2. Subir el contenido de release/ al servidor (FTP, rsync, panel de hosting)
3. Configurar permisos de escritura en STORAGE/ y MEDIA/
4. Copiar STORAGE/ con los datos del restaurante si es una migracion

No requiere Node.js. No requiere npm. No requiere ninguna instalacion.
Funciona en cualquier servidor con Apache o LiteSpeed y PHP >= 7.4.

---

## Protocolo de datos

Toda lectura y escritura de datos pasa por AxiDB.
No se accede directamente a STORAGE sin pasar por la capa AxiDB.
No se usa SQL externo. No se instala MySQL ni ninguna base de datos.

---

## Estructura de modulos

```
spa/                   fuente React — solo para desarrollo local
release/               build de produccion — lo que se sube al servidor (raiz del proyecto)
axidb/                 motor de datos — no modificar sin entender el protocolo
CORE/                  framework base — auth, config, gestion de archivos
CAPABILITIES/CARTA/    carta digital hostelera
CAPABILITIES/QR/       generacion de QR
CAPABILITIES/TPV/      punto de venta
CAPABILITIES/AGENTE_RESTAURANTE/  agente IA
CAPABILITIES/PRODUCTS/ productos genericos (base)
CAPABILITIES/GEMINI/   conector IA
STORAGE/               datos en tiempo real — excluidos de git
MEDIA/                 imagenes de productos
```

---

## Flujo de trabajo

1. **Desarrollo**: Usar `run.bat` para ver cambios instantáneos.
2. **Implementar**: Seguir el plan en `claude/planes/mylocal.md`.
3. **Calidad**: Verificar que ningún archivo supera las 250 líneas.
4. **Finalizar**: Solo al terminar el proyecto o fase crítica, ejecutar `build.ps1` para generar la carpeta `release/`.

---

## Lo que NO se hace

- No se crean modulos fuera del plan (claude/planes/mylocal.md)
- No se usa MySQL ni ninguna base de datos externa
- No se usa la carpeta dist/ — la carpeta de produccion es /release/ en la raiz
- No se sube spa/ al servidor de produccion
- No se instala hardware propietario en el codigo
- No se sube STORAGE, vault ni config con credenciales
- No se añaden features de fases futuras antes de cerrar la fase actual
- No se añade Academia ni ningun modulo que no este en el roadmap

---

## Credenciales y secretos

Los archivos en STORAGE/.vault/, vault/ y CORE/config.json contienen credenciales.
Estan en .gitignore. No modificar esa politica.
