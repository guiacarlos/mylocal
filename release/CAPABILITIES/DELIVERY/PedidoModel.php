<?php
/**
 * PedidoModel — CRUD sobre la colección `pedidos` en AxiDB.
 *
 * Clave: `pd_<uuid>`
 * Estados: recibido → preparando → en_ruta → entregado | incidencia
 */

declare(strict_types=1);

namespace Delivery;

class PedidoModel
{
    const ESTADOS = ['recibido', 'preparando', 'en_ruta', 'entregado', 'incidencia'];

    public static function create(array $data): array
    {
        $localId = s_id($data['local_id'] ?? '');
        if (!$localId) throw new \InvalidArgumentException('local_id requerido.');

        $id = 'pd_' . self::uuid();
        $codigo = self::generarCodigo();
        $doc = [
            'id'               => $id,
            'local_id'         => $localId,
            'cliente'          => s_str($data['cliente'] ?? '', 100),
            'telefono'         => s_str($data['telefono'] ?? '', 30),
            'email'            => s_email($data['email'] ?? ''),
            'direccion'        => s_str($data['direccion'] ?? '', 300),
            'items'            => array_slice((array) ($data['items'] ?? []), 0, 50),
            'estado'           => 'recibido',
            'codigo_seguimiento' => $codigo,
            'notas'            => s_str($data['notas'] ?? '', 1000),
            'created_at'       => date('c'),
        ];
        $saved = data_put('pedidos', $id, $doc, true);
        if (class_exists(\EventBus::class)) {
            \EventBus::emit('pedido.creado', [
                'pedido_id' => $id,
                'codigo'    => $codigo,
                'cliente'   => $doc['cliente'],
                'local_id'  => $localId,
            ]);
        }
        return $saved;
    }

    public static function get(string $id): ?array
    {
        return data_get('pedidos', $id);
    }

    public static function getByCode(string $codigo): ?array
    {
        foreach (data_all('pedidos') as $p) {
            if (($p['codigo_seguimiento'] ?? '') === strtoupper($codigo)) return $p;
        }
        return null;
    }

    public static function cambiarEstado(string $id, string $estado): array
    {
        if (!in_array($estado, self::ESTADOS, true)) {
            throw new \InvalidArgumentException("Estado inválido: $estado");
        }
        $doc = data_get('pedidos', $id);
        if (!$doc) throw new \RuntimeException('Pedido no encontrado.');
        return data_put('pedidos', $id, array_merge($doc, ['estado' => $estado]), true);
    }

    public static function listByLocal(string $localId, ?string $estado = null): array
    {
        $todos = array_values(array_filter(
            data_all('pedidos'),
            fn($p) => ($p['local_id'] ?? '') === $localId
                   && ($estado === null || ($p['estado'] ?? '') === $estado)
        ));
        usort($todos, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return $todos;
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private static function generarCodigo(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
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
