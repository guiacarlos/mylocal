<?php
/**
 * CompanyLegal — documentos legales de GESTASAI TECNOLOGY SL / MyLocal.
 * Se sirven desde /legal/:doc cuando local_id es vacío o 'mylocal'.
 */

declare(strict_types=1);

namespace Legal;

class CompanyLegal
{
    public const DOCS = [
        'aviso'           => 'Aviso Legal y Condiciones de Contratación',
        'privacidad'      => 'Política de Privacidad',
        'cookies'         => 'Política de Cookies',
        'reembolsos'      => 'Política de Cancelación y Reembolsos',
        'canal-denuncias' => 'Canal de Denuncias (Ley 2/2023)',
    ];

    public static function get(string $doc): ?array
    {
        if (!array_key_exists($doc, self::DOCS)) return null;

        $file = __DIR__ . '/company/' . $doc . '.md';
        if (!file_exists($file)) return null;

        $contenido = file_get_contents($file);
        if ($contenido === false) return null;

        return [
            'contenido' => $contenido,
            'titulo'    => self::DOCS[$doc],
            'tipo'      => 'company',
        ];
    }

    public static function list(): array
    {
        return array_map(
            fn (string $slug, string $titulo) => ['doc' => $slug, 'titulo' => $titulo],
            array_keys(self::DOCS),
            array_values(self::DOCS)
        );
    }
}
