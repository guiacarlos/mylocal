# CAPABILITY: CITAS

Gestión de citas para cualquier servicio agendable (clínicas, asesorías, talleres, restaurantes con reserva).

## Colecciones AxiDB

| Colección | Clave | Descripción |
|-----------|-------|-------------|
| `citas` | `c_<uuid>` | Reservas individuales |
| `recursos_agenda` | `r_<uuid>` | Recursos reservables (consultorios, mesas, vehículos) |

## Acciones (handler: `spa/server/handlers/citas.php`)

| Acción | Auth | Descripción |
|--------|------|-------------|
| `cita_create` | admin | Crea cita con protección anti-conflicto (flock) |
| `cita_update` | admin | Modifica estado o notas |
| `cita_cancel` | admin | Cancela la cita |
| `cita_get` | admin | Devuelve una cita por id |
| `cita_list` | admin | Lista por local + rango opcional |
| `cita_publica_crear` | público | Solicitud desde formulario embebido |
| `recurso_create/update/list/delete` | admin | CRUD de recursos |

## Dependencias

- `LOGIN` — sanitización y autenticación
- `OPTIONS` — configuración de negocio
- `NOTIFICACIONES` — recordatorios automáticos (opcional)

## Seguridad

- Conflictos de horario detectados con `flock` por recurso.
- Borde exacto (fin_A == inicio_B) se considera libre.
- Citas `cancelada` y `completada` no bloquean el hueco.
