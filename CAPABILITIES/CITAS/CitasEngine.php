<?php
/**
 * CitasEngine — lógica de negocio de citas.
 *
 * tryReserve() usa flock sobre un lock por recurso para garantizar que
 * dos peticiones concurrentes no reserven el mismo hueco.
 */

declare(strict_types=1);

namespace Citas;

class CitasEngine
{
    /**
     * Intenta reservar un hueco. Si hay conflicto lanza InvalidArgumentException.
     * Devuelve la cita creada (con gcal_event_id si Google Calendar está conectado).
     */
    public static function tryReserve(array $data): array
    {
        $recursoId = s_id($data['recurso_id'] ?? '');
        if (!$recursoId) throw new \InvalidArgumentException('recurso_id requerido.');

        $lockFile = sys_get_temp_dir() . '/citas_' . $recursoId . '.lock';
        $fh = fopen($lockFile, 'c');
        if (!$fh) throw new \RuntimeException('No se pudo adquirir el lock de reserva.');

        flock($fh, LOCK_EX);
        try {
            self::assertNoConflict($recursoId, $data['inicio'], $data['fin'], null);
            $cita = CitasModel::create($data);
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }

        // Sincronizar con Google Calendar si está conectado para este local.
        // El fallo de gcal no cancela la cita (degradación elegante).
        $localId = $cita['local_id'] ?? '';
        if ($localId) {
            try {
                self::gcalRequire();
                if (\GCal\GoogleOAuthStore::hasToken($localId)) {
                    $gcalDoc = data_get('gcal_tokens', $localId) ?: [];
                    $calId   = $gcalDoc['calendar_id'] ?? 'primary';
                    $gcalEvent = \GCal\GoogleCalendarExecutor::createEvent($localId, $calId, [
                        'summary'     => $cita['notas'] ?: ('Cita #' . $cita['id']),
                        'start'       => $cita['inicio'],
                        'end'         => $cita['fin'],
                        'description' => 'Cliente: ' . ($cita['cliente_id'] ?? ''),
                    ]);
                    if (!empty($gcalEvent['id'])) {
                        $cita = data_put('citas', $cita['id'], array_merge($cita, [
                            'gcal_event_id' => $gcalEvent['id'],
                        ]), true);
                    }
                }
            } catch (\Throwable $e) {
                error_log('[CitasEngine] gcal createEvent falló: ' . $e->getMessage());
            }
        }

        return $cita;
    }

    /**
     * Verifica que no exista solapamiento con citas activas del recurso.
     * Cubre todos los casos: parcial izquierdo, parcial derecho, anidado, idéntico, borde exacto.
     */
    public static function assertNoConflict(
        string  $recursoId,
        string  $inicio,
        string  $fin,
        ?string $excludeId
    ): void {
        $citas = array_filter(
            data_all('citas'),
            fn($c) =>
                ($c['recurso_id'] ?? '') === $recursoId &&
                !in_array($c['estado'] ?? '', ['cancelada', 'completada'], true) &&
                $c['id'] !== $excludeId
        );

        foreach ($citas as $c) {
            // Borde exacto: fin de una == inicio de otra → sin solapamiento
            if ($c['fin'] <= $inicio || $c['inicio'] >= $fin) continue;
            throw new \InvalidArgumentException(
                "Conflicto con cita existente {$c['id']} ({$c['inicio']} – {$c['fin']})."
            );
        }
    }

    /** Cancela la cita y devuelve el documento actualizado. */
    public static function cancel(string $citaId): array
    {
        $cita = CitasModel::get($citaId);
        $saved = CitasModel::cancel($citaId);

        // Borrar el evento de Google Calendar si fue sincronizado.
        $gcalEventId = $cita['gcal_event_id'] ?? '';
        $localId     = $cita['local_id'] ?? '';
        if ($gcalEventId && $localId) {
            try {
                self::gcalRequire();
                $gcalDoc = data_get('gcal_tokens', $localId) ?: [];
                $calId   = $gcalDoc['calendar_id'] ?? 'primary';
                \GCal\GoogleCalendarExecutor::deleteEvent($localId, $calId, $gcalEventId);
            } catch (\Throwable $e) {
                error_log('[CitasEngine] gcal deleteEvent falló: ' . $e->getMessage());
            }
        }

        return $saved;
    }

    /** Completa la cita (estado → completada). */
    public static function complete(string $citaId): array
    {
        $doc = CitasModel::get($citaId);
        if (!$doc) throw new \RuntimeException('Cita no encontrada.');
        return CitasModel::update($citaId, ['estado' => 'completada']);
    }

    private static function gcalRequire(): void
    {
        static $loaded = false;
        if ($loaded) return;
        $base = defined('CORE_ROOT') ? CORE_ROOT : dirname(__DIR__, 2);
        require_once $base . '/CAPABILITIES/GCAL/GoogleOAuthStore.php';
        require_once $base . '/CAPABILITIES/GCAL/GoogleCalendarExecutor.php';
        $loaded = true;
    }
}
