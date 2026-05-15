<?php
/**
 * RecursosModel — consultorios, vehículos, mesas reservables o cualquier
 * entidad sobre la que se agenda una cita. Genérico por diseño.
 *
 * Colección AxiDB: `recursos_agenda`
 * Clave: `r_<uuid>`
 */

declare(strict_types=1);

namespace Citas;

class RecursosModel
{
    public static function create(string $localId, array $data): array
    {
        $id = 'r_' . self::uuid();
        $doc = [
            'id'          => $id,
            'local_id'    => s_id($localId),
            'nombre'      => s_str($data['nombre'] ?? '', 100),
            'tipo'        => s_str($data['tipo'] ?? 'generico', 40),
            'descripcion' => s_str($data['descripcion'] ?? '', 500),
            'activo'      => true,
            'created_at'  => date('c'),
        ];
        if (!$doc['nombre']) throw new \InvalidArgumentException('El recurso necesita nombre.');
        return data_put('recursos_agenda', $id, $doc, true);
    }

    public static function update(string $id, array $data): array
    {
        $doc = data_get('recursos_agenda', $id);
        if (!$doc) throw new \RuntimeException('Recurso no encontrado.');
        $patch = array_filter([
            'nombre'      => isset($data['nombre'])      ? s_str($data['nombre'], 100)      : null,
            'tipo'        => isset($data['tipo'])         ? s_str($data['tipo'], 40)          : null,
            'descripcion' => isset($data['descripcion'])  ? s_str($data['descripcion'], 500)  : null,
            'activo'      => isset($data['activo'])       ? (bool) $data['activo']            : null,
        ], fn($v) => $v !== null);
        return data_put('recursos_agenda', $id, array_merge($doc, $patch), true);
    }

    public static function listByLocal(string $localId): array
    {
        return array_values(array_filter(
            data_all('recursos_agenda'),
            fn($r) => ($r['local_id'] ?? '') === $localId && ($r['activo'] ?? true)
        ));
    }

    public static function delete(string $id): bool
    {
        if (!data_get('recursos_agenda', $id)) throw new \RuntimeException('Recurso no encontrado.');
        return data_delete('recursos_agenda', $id);
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
