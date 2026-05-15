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
     * Devuelve la cita creada.
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
        return CitasModel::cancel($citaId);
    }

    /** Completa la cita (estado → completada). */
    public static function complete(string $citaId): array
    {
        $doc = CitasModel::get($citaId);
        if (!$doc) throw new \RuntimeException('Cita no encontrada.');
        return CitasModel::update($citaId, ['estado' => 'completada']);
    }
}
