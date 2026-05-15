<?php
/**
 * TareaModel — CRUD sobre la colección `tareas` en AxiDB.
 *
 * Clave: `ta_<uuid>`
 * Estados kanban: pendiente | en_curso | hecho
 * Prioridades: alta | media | baja
 */

declare(strict_types=1);

namespace Tareas;

class TareaModel
{
    const ESTADOS    = ['pendiente', 'en_curso', 'hecho'];
    const PRIORIDADES = ['alta', 'media', 'baja'];

    public static function create(array $data): array
    {
        $localId = s_id($data['local_id'] ?? '');
        if (!$localId) throw new \InvalidArgumentException('local_id requerido.');

        $id = 'ta_' . self::uuid();
        $doc = [
            'id'               => $id,
            'local_id'         => $localId,
            'titulo'           => s_str($data['titulo'] ?? '', 200),
            'descripcion'      => s_str($data['descripcion'] ?? '', 1000),
            'estado'           => 'pendiente',
            'prioridad'        => self::validarPrioridad($data['prioridad'] ?? 'media'),
            'cliente_id'       => s_id($data['cliente_id'] ?? ''),
            'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
            'created_at'       => date('c'),
        ];
        if (!$doc['titulo']) throw new \InvalidArgumentException('titulo requerido.');
        return data_put('tareas', $id, $doc, true);
    }

    public static function get(string $id): ?array
    {
        return data_get('tareas', $id);
    }

    public static function update(string $id, array $data): array
    {
        $doc = data_get('tareas', $id);
        if (!$doc) throw new \RuntimeException('Tarea no encontrada.');
        $patch = array_filter([
            'titulo'      => isset($data['titulo'])      ? s_str($data['titulo'], 200)         : null,
            'descripcion' => isset($data['descripcion']) ? s_str($data['descripcion'], 1000)   : null,
            'estado'      => isset($data['estado'])      ? self::validarEstado($data['estado']) : null,
            'prioridad'   => isset($data['prioridad'])   ? self::validarPrioridad($data['prioridad']) : null,
            'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
        ], fn($v) => $v !== null);
        return data_put('tareas', $id, array_merge($doc, $patch), true);
    }

    public static function delete(string $id): void
    {
        if (!data_get('tareas', $id)) throw new \RuntimeException('Tarea no encontrada.');
        data_delete('tareas', $id);
    }

    public static function listByLocal(string $localId, ?string $estado = null): array
    {
        $todas = array_values(array_filter(
            data_all('tareas'),
            fn($t) => ($t['local_id'] ?? '') === $localId
                   && ($estado === null || ($t['estado'] ?? '') === $estado)
        ));
        usort($todas, function ($a, $b) {
            $orden = ['alta' => 0, 'media' => 1, 'baja' => 2];
            return ($orden[$a['prioridad'] ?? 'media'] ?? 1) <=> ($orden[$b['prioridad'] ?? 'media'] ?? 1);
        });
        return $todas;
    }

    private static function validarEstado(string $e): string
    {
        if (!in_array($e, self::ESTADOS, true)) throw new \InvalidArgumentException("Estado inválido: $e");
        return $e;
    }

    private static function validarPrioridad(string $p): string
    {
        return in_array($p, self::PRIORIDADES, true) ? $p : 'media';
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
