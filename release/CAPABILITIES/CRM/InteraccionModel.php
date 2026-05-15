<?php
/**
 * InteraccionModel — registro inmutable de interacciones con un contacto.
 *
 * Colección: `crm_interacciones`  Clave: `i_<uuid>`
 * Tipos: llamada | email | whatsapp | nota | visita
 *
 * Inmutabilidad: las interacciones no se editan ni borran; solo se añaden.
 * Esto garantiza auditoría completa del historial.
 */

declare(strict_types=1);

namespace Crm;

class InteraccionModel
{
    const TIPOS = ['llamada', 'email', 'whatsapp', 'nota', 'visita'];

    public static function add(string $contactoId, string $autorId, array $data): array
    {
        if (!data_get('crm_contactos', $contactoId)) {
            throw new \RuntimeException('Contacto no encontrado.');
        }
        $tipo = $data['tipo'] ?? 'nota';
        if (!in_array($tipo, self::TIPOS, true)) {
            throw new \InvalidArgumentException('Tipo inválido: ' . $tipo);
        }
        $id  = 'i_' . self::uuid();
        $doc = [
            'id'          => $id,
            'contacto_id' => s_id($contactoId),
            'tipo'        => $tipo,
            'contenido'   => s_str($data['contenido'] ?? '', 2000),
            'autor_id'    => s_id($autorId),
            'ts'          => date('c'),
        ];
        return data_put('crm_interacciones', $id, $doc, true);
    }

    public static function listByContacto(string $contactoId): array
    {
        $lista = array_values(array_filter(
            data_all('crm_interacciones'),
            fn($i) => ($i['contacto_id'] ?? '') === $contactoId
        ));
        usort($lista, fn($a, $b) => strcmp($b['ts'], $a['ts']));
        return $lista;
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
