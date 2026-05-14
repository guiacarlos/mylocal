<?php
/**
 * NoopDriver — driver de pruebas y entornos sin proveedor configurado.
 * Registra el envío en AxiDB como si lo hubiera enviado, sin salir a red.
 */

declare(strict_types=1);

namespace Notificaciones\Drivers;

class NoopDriver
{
    public function send(string $destinatario, string $asunto, string $cuerpo): array
    {
        return [
            'driver'       => 'noop',
            'destinatario' => $destinatario,
            'asunto'       => $asunto,
            'enviado'      => true,
            'ts'           => date('c'),
        ];
    }

    public function nombre(): string { return 'noop'; }
}
