# Template: Asesoría

Gestión documental, OCR de facturas, calendario fiscal y kanban de tareas para despachos de asesoría.

**Puerto dev:** 5176 | **CSS prefix:** `as-*` | **Build:** `.\build.ps1 -Template asesoria`

## Levantar en desarrollo

```powershell
run.bat asesoria   # → http://localhost:5176
```

## Páginas

| Ruta | Página | Descripción |
|------|--------|-------------|
| `/clientes` | ClientesPage | CRM con campos fiscales: NIF, régimen, próximas obligaciones |
| `/documentos` | DocumentosPage | Subida drag-drop + OCR automático (extrae NIF, total, fecha) |
| `/calendario` | CalendarioFiscalPage | Vencimientos fiscales por cliente con estado calculado |
| `/tareas` | TareasPage | Kanban 3 columnas: pendiente / en_curso / hecho |
| `/facturas` | FacturasPage | Emisión con Verifactu/TicketBAI (requiere activar capability FISCAL) |

Ruta raíz `/` redirige a `/clientes`.

## Capabilities

```json
["LOGIN","OPTIONS","CRM","CITAS","NOTIFICACIONES","OCR","FISCAL","TAREAS","AI"]
```

## Diseño de datos

**Clientes:** colección `crm_contactos` + campos extra `nif`, `regimen_fiscal`

**Vencimientos fiscales:** reutiliza `CITAS` con `recurso_id: 'r_fiscal'` — no hay colección nueva.

**Tareas:** colección `tareas` vía `CAPABILITIES/TAREAS/`

## Contexto

```tsx
import { useAsesoria } from './context/AsesoriaContext';

const { client, localId } = useAsesoria();
```

## Servicios disponibles

```ts
import {
    listClientes, createCliente,
    listTareas, createTarea, moverTarea, deleteTarea,
    listVencimientos, createVencimiento, cancelarVencimiento,
} from './services/asesoria.service';
```

## OCR de documentos

`DocumentosPage` llama a `ocr_extract` (capability OCR/AI). Si el endpoint IA no está configurado, muestra un fallback gracioso con el nombre del archivo. No rompe si OCR no está disponible.

## Configurar OpenClaw para asesoría

En OPTIONS, namespace `openclaw`:

```json
{
  "app_name": "Asesoría López",
  "skill_api_key": "clave-secreta",
  "allowed_actions": ["tarea_create", "tarea_list", "cita_list", "crm_contacto_list"],
  "push_url": "http://localhost:3001/api/send",
  "push_channel": "telegram"
}
```

## Variables de entorno

```env
VITE_API_URL=http://localhost:8091/acide/index.php
VITE_LOCAL_ID=local_asesoria
```
