<?php

require_once __DIR__ . '/../../../../../CAPABILITIES/GCAL/GoogleOAuthStore.php';
require_once __DIR__ . '/../../../../../CAPABILITIES/GCAL/GoogleCalendarExecutor.php';

/**
 * Google Calendar gland — delega en GoogleCalendarExecutor (llamadas HTTP reales).
 *
 * El patrón TUNNEL_REQUEST/TUNNEL_CONTRACT fue eliminado: generaba contratos
 * que el frontend recibía y descartaba en silencio. Ninguna llamada real llegaba
 * a la Google Calendar API. Este archivo ejecuta directamente.
 */
class Calendar
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    /** Sincroniza eventos futuros desde Google Calendar. */
    public function sync($params = []): array
    {
        $localId = $params['local_id'] ?? ($this->services['local_id'] ?? '');
        if (!$localId) return ['success' => false, 'error' => 'local_id requerido'];

        try {
            $events = \GCal\GoogleCalendarExecutor::listEvents(
                $localId,
                $params['since'] ?? ''
            );
            return ['success' => true, 'data' => $events];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /** Crea un evento real en Google Calendar. */
    public function createEvent($params): array
    {
        $localId    = $params['local_id'] ?? ($this->services['local_id'] ?? '');
        $calendarId = $params['calendar_id'] ?? 'primary';

        if (!$localId) return ['success' => false, 'error' => 'local_id requerido'];

        try {
            $result = \GCal\GoogleCalendarExecutor::createEvent($localId, $calendarId, $params);
            return ['success' => true, 'data' => $result];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /** Elimina un evento de Google Calendar. */
    public function deleteEvent($gcalEventId, $calendarId = 'primary', $localId = ''): array
    {
        if (!$localId) $localId = $this->services['local_id'] ?? '';
        if (!$localId) return ['success' => false, 'error' => 'local_id requerido'];

        try {
            $result = \GCal\GoogleCalendarExecutor::deleteEvent($localId, $calendarId, $gcalEventId);
            return ['success' => true, 'data' => $result];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /** Consulta disponibilidad (freebusy) en un rango horario. */
    public function checkAvailability($params): array
    {
        $localId = $params['local_id'] ?? ($this->services['local_id'] ?? '');
        if (!$localId) return ['success' => false, 'error' => 'local_id requerido'];

        try {
            $result = \GCal\GoogleCalendarExecutor::checkAvailability(
                $localId,
                $params['start'] ?? '',
                $params['end']   ?? ''
            );
            return ['success' => true, 'data' => $result];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /** Estado de conexión OAuth2 para este local. */
    public function status($params = []): array
    {
        $localId = $params['local_id'] ?? ($this->services['local_id'] ?? '');
        if (!$localId) return ['success' => false, 'error' => 'local_id requerido'];

        return ['success' => true, 'data' => \GCal\GoogleOAuthStore::status($localId)];
    }
}
