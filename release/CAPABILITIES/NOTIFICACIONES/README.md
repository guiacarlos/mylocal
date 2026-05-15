# CAPABILITY: NOTIFICACIONES

Envío de notificaciones multi-canal (email, WhatsApp) con drivers intercambiables y plantillas `{{var}}`.

## Colecciones AxiDB

| Colección | Clave | Descripción |
|-----------|-------|-------------|
| `notif_log` | `nl_<hex>` | Log inmutable de cada envío |
| `templates_notif` | `<nombre>` | Plantillas reutilizables (asunto + cuerpo HTML) |
| `config/notif_settings` | `notif_settings` | Driver activo (`noop`\|`email`\|`whatsapp`) |
| `config/email_settings` | `email_settings` | Configuración SMTP / From |
| `config/whatsapp_settings` | `whatsapp_settings` | phone_number_id + access_token |

## Acciones (handler: `spa/server/handlers/notificaciones.php`)

| Acción | Auth | Descripción |
|--------|------|-------------|
| `notif_send` | admin | Envío directo: destinatario, asunto, cuerpo |
| `notif_send_template` | admin | Envío con plantilla + variables `{{var}}` |
| `notif_list` | admin | Log de envíos (filtrable por local_id) |
| `notif_template_list` | admin | Lista de plantillas guardadas |
| `notif_template_save` | admin | Crea o actualiza una plantilla |

## Drivers

| Driver | Clase | Requisitos |
|--------|-------|------------|
| `noop` | `NoopDriver` | Ninguno — solo registra el log |
| `email` | `EmailDriver` | `config/email_settings` con `from_email` |
| `whatsapp` | `WhatsAppDriver` | `config/whatsapp_settings` con `phone_number_id` + `access_token` |

## Notas de diseño

- **Driver configurable**: se selecciona en `notif_settings.driver`; cambiar el campo activa el nuevo driver sin tocar código.
- **Log siempre**: `NotificationEngine` registra cada envío en `notif_log` independientemente del driver y su resultado.
- **Plantillas escapadas**: `Template::interpolate` aplica `htmlspecialchars` a cada variable para prevenir XSS en emails HTML.
