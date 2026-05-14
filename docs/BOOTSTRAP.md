# Bootstrap y Build — Guía completa

---

## Arrancar en desarrollo (< 2 minutos)

```powershell
# 1. Instalar dependencias (solo la primera vez o tras cambios en package.json)
pnpm install

# 2. Levantar el template con HMR
run.bat hosteleria    # → http://localhost:5173
run.bat clinica       # → http://localhost:5174
run.bat logistica     # → http://localhost:5175
run.bat asesoria      # → http://localhost:5176
run.bat               # sin parámetro = hosteleria
```

`run.bat` levanta simultáneamente:
- Backend PHP en puerto 8091 (API `/acide/index.php`)
- Frontend Vite con HMR

---

## Generar release de producción

```powershell
# Build del template por defecto (hosteleria)
.\build.ps1

# Build de un template específico
.\build.ps1 -Template clinica
.\build.ps1 -Template logistica
.\build.ps1 -Template asesoria
```

### Qué hace build.ps1

```
[1/3] Compila el template con pnpm -F <nombre> build
      → genera index.html + assets/ en release/

[2/3] Copia el backend PHP
      → CORE/, CAPABILITIES/, axidb/, fonts/, seed/
      → gateway.php, router.php, .htaccess, favicon, manifest.json, robots.txt, schema.json
      → spa/server/ (handlers PHP + test de login)

      Materializa configs: *.json.example → *.json (sin sobreescribir existentes)
      Limpia: debug_*.php, diag.php, *.log

[2.3] GATE: ejecuta spa/server/tests/test_login.php
      → si CUALQUIER assertion falla, la build aborta (exit 1)
      → 31 assertions de auth — ver claude/AUTH_LOCK.md

[3/3] Crea STORAGE/ vacío con .gitkeep
```

### Resultado en release/

```
release/
  index.html           ← SPA compilada
  assets/              ← JS + CSS minificados
  CORE/                ← framework PHP
  CAPABILITIES/        ← módulos de negocio
  axidb/               ← motor de datos
  spa/server/          ← handlers PHP + config
  STORAGE/             ← vacío (el servidor escribe aquí)
  gateway.php
  router.php
  .htaccess
  favicon.png
  manifest.json
  robots.txt
  schema.json
```

---

## Desplegar al servidor

```bash
# FTP / rsync — subir el contenido de release/ (no la carpeta release/ en sí)
rsync -avz release/ usuario@servidor:/var/www/html/

# Permisos necesarios en el servidor
chmod -R 775 STORAGE/ MEDIA/
chown -R www-data:www-data STORAGE/ MEDIA/
```

**Requisitos del servidor:** Apache o LiteSpeed + PHP ≥ 7.4. Sin Node.js, sin MySQL, sin nada más.

---

## Estructura de `manifest.json` de un template

```json
{
    "capabilities": [
        "LOGIN",
        "OPTIONS",
        "CRM",
        "CITAS",
        "NOTIFICACIONES",
        "TAREAS"
    ],
    "public_routes": [
        "/seguimiento/:codigo"
    ]
}
```

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `capabilities` | `string[]` | IDs de CAPABILITIES que este template necesita |
| `public_routes` | `string[]` | Rutas sin auth (el router PHP las sirve sin validación) |

**Nota:** El build actual copia todas las CAPABILITIES — la selección selectiva por manifest está planificada pero no implementada. El manifest documenta la intención y sirve como referencia.

---

## Variables de entorno del frontend

Crear `.env.local` en `templates/<nombre>/` para desarrollo:

```env
VITE_API_URL=http://localhost:8091/acide/index.php
VITE_LOCAL_ID=local_demo
```

En producción, `VITE_API_URL` se omite y el frontend usa `/acide/index.php` (relativo al servidor).

---

## Primer arranque en una instalación limpia

Cuando `STORAGE/` está vacío, el sistema hace auto-bootstrap:

1. `LOGIN/LoginBootstrap.php` detecta que no hay usuarios
2. Crea el superadmin con credenciales por defecto: `socola@socola.es` / `socola2026`
3. La primera petición a `/acide/index.php?action=auth_login` funciona inmediatamente

**Cambiar las credenciales tras el primer login** desde el panel de configuración o via CLI:

```bash
php release/spa/server/bin/create-admin.php \
    --email=admin@minegocio.es \
    --password=MiPasswordSeguro \
    --root=./release
```

---

## Añadir un template nuevo al workspace

```powershell
# 1. Copiar base
cp -r templates/clinica/ templates/mi-vertical/

# 2. Editar package.json
#    "name": "mi-vertical"

# 3. Editar vite.config.ts
#    server: { port: 5177 }   ← siguiente puerto libre

# 4. Instalar en el workspace
pnpm install

# 5. Verificar build
pnpm -F mi-vertical build
```

---

## Regla crítica — NUNCA `npm run build` solo

`npm run build` en `spa/` usa `--emptyOutDir` que **borra release/** antes de compilar.
Pierdes `router.php`, `gateway.php`, `spa/server/`, configs y assets PHP.

**Siempre usar `.\build.ps1`** o, si solo quieres compilar el frontend:

```powershell
pnpm -F hosteleria build   # compila solo el frontend del template
# Luego restaurar manualmente los PHP si release/ fue vaciada
```
