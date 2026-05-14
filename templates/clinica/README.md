# Template: Clínica

Agenda médica, historial de pacientes, recordatorios y control de stock de material sanitario.

**Puerto dev:** 5174 | **CSS prefix:** `cl-*` | **Build:** `.\build.ps1 -Template clinica`

## Levantar en desarrollo

```powershell
run.bat clinica   # → http://localhost:5174
```

## Páginas

| Ruta | Página | Descripción |
|------|--------|-------------|
| `/agenda` | AgendaPage | Calendario de citas del día / semana |
| `/pacientes` | PacientesPage | Listado y búsqueda de pacientes |
| `/pacientes/:id` | HistorialPage | Historial clínico del paciente |
| `/stock` | StockPage | Material sanitario e inventario |
| `/recordatorios` | RecordatoriosPage | Notificaciones programadas |

Ruta raíz `/` redirige a `/agenda`.

## Capabilities

```json
["LOGIN","OPTIONS","CITAS","CRM","NOTIFICACIONES"]
```

## Contexto

```tsx
import { useClinica } from './context/ClinicaContext';

const { client, localId } = useClinica();
```

## Servicios disponibles

```ts
import {
    listCitas, createCita, cancelCita,
    listPacientes, createPaciente,
} from './services/clinica.service';
```

## Eventos EventBus emitidos

| Evento | Cuándo |
|--------|--------|
| `cita.cancelada` | Al cancelar una cita — puede disparar push a OpenClaw |

## Variables de entorno

```env
VITE_API_URL=http://localhost:8091/acide/index.php
VITE_LOCAL_ID=local_clinica
```
