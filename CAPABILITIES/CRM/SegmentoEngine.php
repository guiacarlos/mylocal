<?php
/**
 * SegmentoEngine — filtrado y segmentación de contactos.
 * Combina múltiples filtros con AND implícito.
 */

declare(strict_types=1);

namespace Crm;

class SegmentoEngine
{
    /**
     * Devuelve contactos de un local que cumplen TODOS los filtros indicados.
     *
     * Filtros soportados:
     *   etiqueta   string  — tiene esta etiqueta exacta
     *   fuente     string  — canal de entrada (manual, qr, web, …)
     *   desde      string  — created_at >= desde (ISO8601)
     *   hasta      string  — created_at <= hasta (ISO8601)
     *   email      string  — contiene este fragmento (insensible a mayúsculas)
     *   telefono   string  — contiene este fragmento
     */
    public static function query(string $localId, array $filtros): array
    {
        $contactos = array_filter(
            data_all('crm_contactos'),
            fn($c) => ($c['local_id'] ?? '') === $localId
        );

        foreach ($filtros as $clave => $valor) {
            if ($valor === null || $valor === '') continue;
            $contactos = match ($clave) {
                'etiqueta'  => array_filter($contactos, fn($c) => in_array($valor, $c['etiquetas'] ?? [], true)),
                'fuente'    => array_filter($contactos, fn($c) => ($c['fuente'] ?? '') === $valor),
                'desde'     => array_filter($contactos, fn($c) => ($c['created_at'] ?? '') >= $valor),
                'hasta'     => array_filter($contactos, fn($c) => ($c['created_at'] ?? '') <= $valor),
                'email'     => array_filter($contactos, fn($c) => str_contains(strtolower($c['email'] ?? ''), strtolower($valor))),
                'telefono'  => array_filter($contactos, fn($c) => str_contains($c['telefono'] ?? '', $valor)),
                default     => $contactos,
            };
        }

        return array_values($contactos);
    }
}
