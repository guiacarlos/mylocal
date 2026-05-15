<?php
/**
 * WhatsAppDriver — envía mensajes vía WhatsApp Business API (Meta).
 * Requiere: phone_number_id y access_token en OPTIONS config whatsapp_settings.
 */

declare(strict_types=1);

namespace Notificaciones\Drivers;

class WhatsAppDriver
{
    private array $cfg;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }

    public static function fromOptions(): self
    {
        $cfg = data_get('config', 'whatsapp_settings') ?: [];
        return new self($cfg);
    }

    public function send(string $destinatario, string $asunto, string $cuerpo): array
    {
        $phoneNumberId = $this->cfg['phone_number_id'] ?? '';
        $accessToken   = $this->cfg['access_token'] ?? '';

        if (!$phoneNumberId || !$accessToken) {
            throw new \RuntimeException('WhatsApp no configurado: faltan phone_number_id o access_token.');
        }

        // Normalizar número: eliminar +, espacios; añadir + si falta
        $phone = preg_replace('/\s+/', '', $destinatario);
        if ($phone[0] !== '+') $phone = '+' . $phone;

        $url  = "https://graph.facebook.com/v19.0/$phoneNumberId/messages";
        $body = json_encode([
            'messaging_product' => 'whatsapp',
            'to'                => ltrim($phone, '+'),
            'type'              => 'text',
            'text'              => ['body' => "$asunto\n\n$cuerpo"],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                "Authorization: Bearer $accessToken",
            ],
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resp = json_decode((string) $raw, true) ?: [];
        $ok   = $code === 200 && isset($resp['messages']);

        if (!$ok) {
            throw new \RuntimeException('WhatsApp API error: ' . ($resp['error']['message'] ?? "HTTP $code"));
        }

        return [
            'driver'       => 'whatsapp',
            'destinatario' => $destinatario,
            'asunto'       => $asunto,
            'enviado'      => true,
            'message_id'   => $resp['messages'][0]['id'] ?? null,
            'ts'           => date('c'),
        ];
    }

    public function nombre(): string { return 'whatsapp'; }
}
