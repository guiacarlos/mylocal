<?php
/**
 * CitasModel — CRUD sobre la colección `citas` en AxiDB.
 *
 * Clave: `c_<uuid>`
 * Campos: local_id, cliente_id, recurso_id, inicio (ISO8601), fin (ISO8601),
 *         estado (pendiente|confirmada|cancelada|completada), notas.
 */

declare(strict_types=1);

namespace Citas;

class CitasModel
{
    const ESTADOS = ['pendiente', 'confirmada', 'cancelada', 'completada'];

    public static function create(array $data): array
    {
        self::validateFields($data);
        $id = 'c_' . self::uuid();
        $doc = [
            'id'          => $id,
            'local_id'    => s_id($data['local_id']),
            'cliente_id'  => s_str($data['cliente_id'] ?? '', 80),
            'recurso_id'  => s_id($data['recurso_id']),
            'inicio'      => $data['inicio'],
            'fin'         => $data['fin'],
            'estado'      => 'pendiente',
            'notas'       => s_str($data['notas'] ?? '', 1000),
            'created_at'  => date('c'),
        ];
        return data_put('citas', $id, $doc, true);
    }

    public static function get(string $id): ?array
    {
        return data_get('citas', $id);
    }

    public static function update(string $id, array $data): array
    {
        $doc = data_get('citas', $id);
        if (!$doc) throw new \RuntimeException('Cita no encontrada.');
        $patch = array_filter([
            'estado' => isset($data['estado']) ? self::validarEstado($data['estado']) : null,
            'notas'  => isset($data['notas'])  ? s_str($data['notas'], 1000)          : null,
            'inicio' => isset($data['inicio'])  ? self::validarFecha($data['inicio'])   : null,
            'fin'    => isset($data['fin'])      ? self::validarFecha($data['fin'])      : null,
        ], fn($v) => $v !== null);
        return data_put('citas', $id, array_merge($doc, $patch), true);
    }

    public static function cancel(string $id): array
    {
        $doc = data_get('citas', $id);
        if (!$doc) throw new \RuntimeException('Cita no encontrada.');
        if ($doc['estado'] === 'cancelada') throw new \InvalidArgumentException('La cita ya está cancelada.');
        $saved = data_put('citas', $id, array_merge($doc, ['estado' => 'cancelada']), true);
        if (class_exists(\EventBus::class)) {
            \EventBus::emit('cita.cancelada', [
                'cita_id'      => $id,
                'cliente'      => $doc['cliente_id'] ?? '',
                'fecha_inicio' => $doc['inicio'] ?? '',
                'local_id'     => $doc['local_id'] ?? '',
            ]);
        }
        return $saved;
    }

    /** Lista citas de un local, opcionalmente filtradas por rango ISO8601 */
    public static function listByLocal(string $localId, ?string $desde = null, ?string $hasta = null): array
    {
        $todas = array_values(array_filter(
            data_all('citas'),
            fn($c) => ($c['local_id'] ?? '') === $localId
        ));
        if ($desde) $todas = array_filter($todas, fn($c) => $c['inicio'] >= $desde);
        if ($hasta) $todas = array_filter($todas, fn($c) => $c['inicio'] <= $hasta);
        usort($todas, fn($a, $b) => strcmp($a['inicio'], $b['inicio']));
        return array_values($todas);
    }

    // ── Helpers privados ──────────────────────────────────────────────

    private static function validateFields(array $d): void
    {
        foreach (['local_id', 'recurso_id', 'inicio', 'fin'] as $f) {
            if (empty($d[$f])) throw new \InvalidArgumentException("Campo requerido: $f");
        }
        self::validarFecha($d['inicio']);
        self::validarFecha($d['fin']);
        if ($d['inicio'] >= $d['fin']) throw new \InvalidArgumentException('inicio debe ser anterior a fin.');
    }

    private static function validarEstado(string $e): string
    {
        if (!in_array($e, self::ESTADOS, true)) {
            throw new \InvalidArgumentException('Estado inválido: ' . $e);
        }
        return $e;
    }

    private static function validarFecha(string $f): string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $f)) {
            throw new \InvalidArgumentException("Fecha inválida (debe ser ISO8601): $f");
        }
        return $f;
    }

    private static function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%012x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0x4000, 0x4fff),
            random_int(0x8000, 0xbfff),
            random_int(0, 0xffffffffffff)
        );
    }
}
