<?php
namespace GCal;

/**
 * GoogleCalendarExecutor — ejecuta llamadas HTTP reales a la Google Calendar API v3.
 *
 * Recibe el localId, obtiene el access_token vigente desde GoogleOAuthStore
 * (con refresco automático si expiró), y hace la petición cURL.
 *
 * Todos los endpoints de Google Calendar v3:
 *   https://www.googleapis.com/calendar/v3/...
 */
class GoogleCalendarExecutor
{
    private const BASE_URL = 'https://www.googleapis.com/calendar/v3';

    /**
     * Crea un evento en Google Calendar.
     *
     * @param string $localId   ID del local (para recuperar el token)
     * @param string $calendarId  'primary' o un ID concreto de calendario
     * @param array  $event     { summary, start (RFC3339), end (RFC3339), description? }
     * @return array            Evento creado (incluye 'id' del evento en Google)
     */
    public static function createEvent(string $localId, string $calendarId, array $event): array
    {
        $token = GoogleOAuthStore::getAccessToken($localId);
        $url   = self::BASE_URL . '/calendars/' . urlencode($calendarId) . '/events';

        $body = [
            'summary'     => $event['summary'] ?? $event['title'] ?? '(sin título)',
            'description' => $event['description'] ?? '',
            'start'       => ['dateTime' => self::toRfc3339($event['start'] ?? ''), 'timeZone' => 'Europe/Madrid'],
            'end'         => ['dateTime' => self::toRfc3339($event['end']   ?? ''), 'timeZone' => 'Europe/Madrid'],
        ];

        if (!empty($event['attendees'])) {
            $body['attendees'] = array_map(fn($e) => ['email' => $e], (array)$event['attendees']);
        }

        return self::request('POST', $url, $token, $body);
    }

    /**
     * Elimina un evento de Google Calendar.
     *
     * @param string $localId
     * @param string $calendarId
     * @param string $gcalEventId   El 'id' devuelto por createEvent
     * @return array                Respuesta vacía (204 No Content → success: true)
     */
    public static function deleteEvent(string $localId, string $calendarId, string $gcalEventId): array
    {
        $token = GoogleOAuthStore::getAccessToken($localId);
        $url   = self::BASE_URL . '/calendars/' . urlencode($calendarId) . '/events/' . urlencode($gcalEventId);

        return self::request('DELETE', $url, $token);
    }

    /**
     * Comprueba disponibilidad en un rango de tiempo (freebusy query).
     *
     * @param string $localId
     * @param string $start   RFC3339
     * @param string $end     RFC3339
     * @return array  ['busy' => [[start, end], ...]]
     */
    public static function checkAvailability(string $localId, string $start, string $end): array
    {
        $token = GoogleOAuthStore::getAccessToken($localId);
        $url   = self::BASE_URL . '/freeBusy';

        $resp = self::request('POST', $url, $token, [
            'timeMin' => self::toRfc3339($start),
            'timeMax' => self::toRfc3339($end),
            'items'   => [['id' => 'primary']],
        ]);

        $busy = $resp['calendars']['primary']['busy'] ?? [];
        return ['busy' => $busy];
    }

    /**
     * Sincroniza eventos futuros desde Google Calendar al local.
     *
     * @param string $localId
     * @param string $since   ISO8601 (por defecto: hoy)
     * @return array          Lista de eventos [{id, summary, start, end}]
     */
    public static function listEvents(string $localId, string $since = ''): array
    {
        $token = GoogleOAuthStore::getAccessToken($localId);
        $doc   = data_get('gcal_tokens', $localId) ?: [];
        $calId = $doc['calendar_id'] ?? 'primary';

        $params = [
            'timeMin'      => self::toRfc3339($since ?: date('c')),
            'singleEvents' => 'true',
            'orderBy'      => 'startTime',
            'maxResults'   => 100,
        ];

        $url  = self::BASE_URL . '/calendars/' . urlencode($calId) . '/events?' . http_build_query($params);
        $resp = self::request('GET', $url, $token);

        return array_map(fn($e) => [
            'gcal_id'  => $e['id'],
            'summary'  => $e['summary'] ?? '',
            'start'    => $e['start']['dateTime'] ?? $e['start']['date'] ?? '',
            'end'      => $e['end']['dateTime']   ?? $e['end']['date']   ?? '',
            'html_link'=> $e['htmlLink'] ?? '',
        ], $resp['items'] ?? []);
    }

    // ─────────────────────────── Private ───────────────────────────

    private static function request(string $method, string $url, string $token, array $body = []): array
    {
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT        => 15,
        ]);

        if ($method !== 'GET' && $method !== 'DELETE' && !empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \RuntimeException("Google Calendar cURL error: $err");

        // 204 No Content (DELETE exitoso) → éxito sin body
        if ($code === 204) return ['success' => true];

        $resp = json_decode((string)$raw, true) ?: [];

        if ($code >= 400) {
            $msg = $resp['error']['message'] ?? "HTTP $code";
            throw new \RuntimeException("Google Calendar API error: $msg");
        }

        return $resp;
    }

    private static function toRfc3339(string $dt): string
    {
        if ($dt === '') return date('c');
        // Ya es RFC3339
        if (preg_match('/T\d{2}:\d{2}:\d{2}/', $dt)) return $dt;
        // Solo fecha → añadir hora y zona
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) return $dt . 'T00:00:00+02:00';
        // Timestamp Unix
        if (is_numeric($dt)) return date('c', (int)$dt);
        return date('c', strtotime($dt) ?: time());
    }
}
