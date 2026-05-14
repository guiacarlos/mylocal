<?php
/**
 * EmailDriver — envía emails via SMTP con la configuración de OPTIONS.
 * Usa la extensión mail() nativa como fallback si no hay SMTP configurado.
 */

declare(strict_types=1);

namespace Notificaciones\Drivers;

class EmailDriver
{
    private array $cfg;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }

    public static function fromOptions(): self
    {
        $cfg = data_get('config', 'email_settings') ?: [];
        return new self($cfg);
    }

    public function send(string $destinatario, string $asunto, string $cuerpo): array
    {
        $from    = $this->cfg['from_email'] ?? 'noreply@mylocal.es';
        $fromName = $this->cfg['from_name'] ?? 'MyLocal';

        $headers  = "From: $fromName <$from>\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "X-Mailer: MyLocal/1.0\r\n";

        $sent = @mail($destinatario, $asunto, $cuerpo, $headers);

        return [
            'driver'       => 'email',
            'destinatario' => $destinatario,
            'asunto'       => $asunto,
            'enviado'      => $sent,
            'ts'           => date('c'),
        ];
    }

    public function nombre(): string { return 'email'; }
}
