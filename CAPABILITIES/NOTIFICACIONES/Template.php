<?php
/**
 * Template — motor de plantillas {{var}} para notificaciones.
 * Plantillas guardadas en STORAGE/templates/notificaciones/<nombre>.json
 * con campos: asunto, cuerpo (HTML).
 */

declare(strict_types=1);

namespace Notificaciones;

class Template
{
    /**
     * Renderiza la plantilla con las variables dadas.
     * Devuelve ['asunto' => '...', 'cuerpo' => '...'].
     */
    public static function render(string $nombre, array $vars): array
    {
        $tpl = self::load($nombre);
        return [
            'asunto' => self::interpolate($tpl['asunto'] ?? '', $vars),
            'cuerpo' => self::interpolate($tpl['cuerpo'] ?? '', $vars),
        ];
    }

    /** Carga la plantilla desde AxiDB (colección templates_notif). */
    public static function load(string $nombre): array
    {
        $doc = data_get('templates_notif', $nombre);
        if (!$doc) throw new \RuntimeException("Plantilla de notificación no encontrada: $nombre");
        return $doc;
    }

    /** Guarda o actualiza una plantilla. */
    public static function save(string $nombre, string $asunto, string $cuerpo): array
    {
        $doc = ['id' => $nombre, 'asunto' => $asunto, 'cuerpo' => $cuerpo, 'updated_at' => date('c')];
        data_put('templates_notif', $nombre, $doc);
        return $doc;
    }

    /** Sustituye {{var}} por su valor; variables desconocidas quedan vacías. */
    private static function interpolate(string $text, array $vars): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($m) use ($vars) {
            return htmlspecialchars((string) ($vars[$m[1]] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }, $text) ?? $text;
    }
}
