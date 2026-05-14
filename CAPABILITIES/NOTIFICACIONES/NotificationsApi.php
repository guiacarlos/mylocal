<?php
/**
 * NotificationsApi — handler de todas las acciones de notificaciones autenticadas.
 */

declare(strict_types=1);

namespace Notificaciones;

function handle_notificaciones(string $action, array $req, array $user): array
{
    switch ($action) {
        case 'notif_send':
            $dest   = s_str($req['destinatario'] ?? '');
            $asunto = s_str($req['asunto'] ?? '');
            $cuerpo = s_str($req['cuerpo'] ?? '');
            if (!$dest || !$asunto) throw new \InvalidArgumentException('destinatario y asunto requeridos.');
            return NotificationEngine::send($dest, $asunto, $cuerpo, $req['meta'] ?? []);

        case 'notif_send_template':
            $dest      = s_str($req['destinatario'] ?? '');
            $plantilla = s_str($req['plantilla'] ?? '');
            if (!$dest || !$plantilla) throw new \InvalidArgumentException('destinatario y plantilla requeridos.');
            return NotificationEngine::sendTemplate($dest, $plantilla, $req['vars'] ?? [], $req['meta'] ?? []);

        case 'notif_list':
            $localId = s_id($req['local_id'] ?? ($user['local_id'] ?? ''));
            $logs    = data_all('notif_log');
            if ($localId) {
                $logs = array_filter($logs, fn($l) => ($l['meta']['local_id'] ?? '') === $localId);
            }
            usort($logs, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
            return ['items' => array_values($logs)];

        case 'notif_template_list':
            return ['items' => array_values(data_all('templates_notif'))];

        case 'notif_template_save':
            $nombre = s_id($req['nombre'] ?? '');
            $asunto = s_str($req['asunto'] ?? '');
            $cuerpo = $req['cuerpo'] ?? '';
            if (!$nombre || !$asunto) throw new \InvalidArgumentException('nombre y asunto requeridos.');
            return Template::save($nombre, $asunto, $cuerpo);

        default:
            throw new \RuntimeException("Acción de notificaciones no reconocida: $action");
    }
}
