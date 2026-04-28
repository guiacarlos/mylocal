<?php

/**
 *  ATOMIC REPO: Google Calendar
 * Responsabilidad: Sincronización bidireccional de eventos y gestión de disponibilidad.
 */
class Calendar
{
    private $services;
    private $endpoint = '/api/google/calendar';

    public function __construct($services)
    {
        $this->services = $services;
    }

    /**
     * Sincronización proactiva (Diferencial)
     */
    public function sync($params = [])
    {
        //  ESTRATEGIA SOBERANA: ACIDE pide al túnel los cambios desde el último sync
        return [
            'action' => 'TUNNEL_SYNC',
            'method' => 'GET',
            'path' => $this->endpoint . '/events',
            'params' => [
                'calendarId' => $params['calendarId'] ?? 'primary',
                'timeMin' => $params['since'] ?? date('c', strtotime('-1 month'))
            ],
            'storage_key' => 'calendars:google:events'
        ];
    }

    /**
     * Crear evento real
     */
    public function createEvent($params)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => $this->endpoint . '/events',
            'data' => [
                'summary' => $params['title'],
                'start' => ['dateTime' => $params['start']],
                'end' => ['dateTime' => $params['end']],
                'description' => $params['description'] ?? ''
            ]
        ];
    }

    /**
     * Eliminar evento
     */
    public function deleteEvent($id, $calendarId = 'primary')
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'DELETE',
            'path' => $this->endpoint . "/events/$id",
            'params' => ['calendarId' => $calendarId]
        ];
    }

    /**
     * Verificar disponibilidad real
     */
    public function checkAvailability($params)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => $this->endpoint . '/freebusy',
            'data' => [
                'timeMin' => $params['start'],
                'timeMax' => $params['end'],
                'items' => [['id' => 'primary']]
            ]
        ];
    }
}
