# MyLocal — Framework Multi-Sector

Un framework PHP + React para agencias: el backend es reutilizable entre proyectos, el frontend es un template intercambiable.

---

## Arquitectura en tres capas

```
templates/<vertical>/        ← React + Vite — diseño y páginas propias
sdk/                         ← @mylocal/sdk — lógica de datos compartida
CAPABILITIES/<modulo>/       ← PHP — lógica de negocio reutilizable
```

**Regla:** Las capas no se saltan. React habla con `@mylocal/sdk`. El SDK habla con el backend PHP. El backend escribe en AxiDB (JSON en disco, sin MySQL).

---

## Flujo para un proyecto nuevo

```
1. Copiar un template base
   cp -r templates/clinica/ templates/veterinaria/

2. Ajustar el manifest
   templates/veterinaria/manifest.json
   → declarar qué CAPABILITIES necesita este vertical

3. Ajustar el package.json
   "name": "veterinaria"

4. Desarrollar la UI
   templates/veterinaria/src/pages/
   templates/veterinaria/src/App.tsx
   → usar client.execute({ action: '...' }) para datos

5. Levantar en dev
   run.bat veterinaria         (Windows)
   → Vite HMR en http://localhost:5175

6. Compilar para producción
   .\build.ps1 -Template veterinaria
   → genera release/ lista para subir al servidor

7. Subir al servidor
   → copiar release/ por FTP/rsync
   → no requiere Node.js en el servidor
```

---

## Cómo añadir una CAPABILITY nueva

Una CAPABILITY es un módulo PHP con una responsabilidad. Ejemplo: `CAPABILITIES/VETERINARIA/`.

### Estructura mínima

```
CAPABILITIES/VETERINARIA/
  capability.json        ← declaración: acciones, colecciones, dependencias
  PacienteModel.php      ← CRUD sobre AxiDB
  VeterinariaApi.php     ← función handle_veterinaria(action, req, user)
```

### `capability.json`

```json
{
  "id": "VETERINARIA",
  "version": "1.0.0",
  "depends_on": ["LOGIN", "OPTIONS", "CRM"],
  "collections": ["pacientes_vet", "consultas"],
  "actions": ["paciente_create", "paciente_list", "consulta_create"]
}
```

### Registrar en el dispatcher

1. Crear `spa/server/handlers/veterinaria.php` — hace `require_once` de los PHP de la capability
2. En `spa/server/index.php` — añadir acciones a `ALLOWED_ACTIONS` y cases al switch
3. En `sdk/src/synaxis/actions.ts` — añadir entradas al `ACTION_CATALOG` con `scope: 'server'`

### Registrar en el template

En `templates/veterinaria/manifest.json`:
```json
{ "capabilities": ["LOGIN", "OPTIONS", "CRM", "VETERINARIA"] }
```

---

## Cómo añadir un template nuevo

Un template es un proyecto Vite autocontenido. No hereda nada de otros templates.

### Archivos mínimos

```
templates/mi-vertical/
  package.json           ← name: "mi-vertical", dep: "@mylocal/sdk": "workspace:*"
  vite.config.ts         ← port propio, alias @mylocal/sdk
  index.html             ← entry HTML
  manifest.json          ← capabilities que necesita
  src/
    main.tsx             ← <SynaxisProvider apiUrl={...}><App /></SynaxisProvider>
    App.tsx              ← BrowserRouter + Layout + Routes
    context/             ← MiVerticalContext.tsx (expone { client, localId })
    pages/               ← páginas de la app
    services/            ← wrappers tipados sobre client.execute()
    mi-vertical.css      ← variables CSS con prefijo propio (ej: --mv-*)
```

### Puertos asignados

| Template | Puerto dev |
|----------|-----------|
| hosteleria | 5173 |
| clinica    | 5174 |
| logistica  | 5175 |
| asesoria   | 5176 |
| siguiente  | 5177+ |

### Build y desarrollo

```powershell
# Desarrollo con HMR
run.bat mi-vertical

# Build de producción
.\build.ps1 -Template mi-vertical
```

---

## Reglas de construcción (no negociables)

- **≤ 250 LOC** por archivo — si crece, se parte
- **Un archivo, una responsabilidad**
- **Sin hardcodeos** — rutas, etiquetas, colores desde manifest/config/AxiDB
- **Sin datos ficticios** — estados vacíos reales con CTA
- **Sin funciones a medias** — si no está terminada, no existe
- **Auth sin tocar** — antes de modificar auth/login/sesiones, leer `claude/AUTH_LOCK.md`

---

## Stack técnico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Frontend | React + Vite + TypeScript | React 19, Vite 5 |
| Paquete compartido | pnpm workspaces + @mylocal/sdk | pnpm 9 |
| Backend | PHP puro | ≥ 7.4 |
| Base de datos | AxiDB (JSON file-based) | propio |
| Servidor | Apache / LiteSpeed | cualquier PHP hosting |

No requiere MySQL, Redis, Node.js en producción, ni ningún servicio externo.
