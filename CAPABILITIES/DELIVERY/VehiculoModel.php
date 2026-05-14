<?php
/**
 * VehiculoModel — flota de vehículos y conductores.
 *
 * Clave: `vh_<uuid>`
 */

declare(strict_types=1);

namespace Delivery;

class VehiculoModel
{
    public static function create(string $localId, array $data): array
    {
        $matricula = s_str($data['matricula'] ?? '', 20);
        if (!$matricula) throw new \InvalidArgumentException('matricula requerida.');

        $id = 'vh_' . self::uuid();
        $doc = [
            'id'        => $id,
            'local_id'  => $localId,
            'matricula' => $matricula,
            'conductor' => s_str($data['conductor'] ?? '', 100),
            'modelo'    => s_str($data['modelo'] ?? '', 80),
            'estado'    => 'activo',
            'created_at' => date('c'),
        ];
        return data_put('vehiculos', $id, $doc, true);
    }

    public static function update(string $id, array $data): array
    {
        $doc = data_get('vehiculos', $id);
        if (!$doc) throw new \RuntimeException('Vehículo no encontrado.');
        $patch = array_filter([
            'conductor' => isset($data['conductor']) ? s_str($data['conductor'], 100) : null,
            'modelo'    => isset($data['modelo'])    ? s_str($data['modelo'], 80)     : null,
            'estado'    => isset($data['estado'])    ? (in_array($data['estado'], ['activo', 'inactivo'], true) ? $data['estado'] : null) : null,
        ], fn($v) => $v !== null);
        return data_put('vehiculos', $id, array_merge($doc, $patch), true);
    }

    public static function listByLocal(string $localId): array
    {
        return array_values(array_filter(
            data_all('vehiculos'),
            fn($v) => ($v['local_id'] ?? '') === $localId
        ));
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
