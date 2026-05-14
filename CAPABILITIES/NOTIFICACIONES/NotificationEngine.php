<?php
/**
 * NotificationEngine — dispatcher de notificaciones con driver intercambiable.
 * Lee el driver activo desde OPTIONS (config notif_settings.driver).
 * Registra cada envío en la colección notif_log.
 */

declare(strict_types=1);

namespace Notificaciones;

class NotificationEngine
{
    /**
     * Envía una notificación usando el driver configurado.
     *
     * @param string $destinatario  Email o teléfono según el driver
     * @param string $asunto        Asunto / título del mensaje
     * @param string $cuerpo        Cuerpo (HTML para email, texto para WhatsApp)
     * @param array  $meta          Datos extra guardados en el log
     */
    public static function send(
        string $destinatario,
        string $asunto,
        string $cuerpo,
        array  $meta = []
    ): array {
        $driver = self::resolveDriver();
        $result = $driver->send($destinatario, $asunto, $cuerpo);

        $logId  = 'nl_' . bin2hex(random_bytes(8));
        $logDoc = array_merge($result, [
            'id'         => $logId,
            'meta'       => $meta,
            'created_at' => date('c'),
        ]);
        data_put('notif_log', $logId, $logDoc);

        return $logDoc;
    }

    /**
     * Envía usando una plantilla almacenada.
     */
    public static function sendTemplate(
        string $destinatario,
        string $nombrePlantilla,
        array  $vars = [],
        array  $meta = []
    ): array {
        $rendered = Template::render($nombrePlantilla, $vars);
        return self::send($destinatario, $rendered['asunto'], $rendered['cuerpo'], $meta);
    }

    /** Instancia el driver activo según configuración. */
    private static function resolveDriver(): object
    {
        $cfg    = data_get('config', 'notif_settings') ?: [];
        $nombre = $cfg['driver'] ?? 'noop';

        return match ($nombre) {
            'email'    => Drivers\EmailDriver::fromOptions(),
            'whatsapp' => Drivers\WhatsAppDriver::fromOptions(),
            default    => new Drivers\NoopDriver(),
        };
    }
}
