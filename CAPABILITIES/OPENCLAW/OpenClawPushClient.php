<?php
/**
 * OpenClawPushClient — empuja eventos de MyLocal al agente OpenClaw local.
 *
 * OpenClaw expone un endpoint HTTP donde pueden enviarse mensajes/eventos.
 * MyLocal lo llama desde el EventBus para avisar al agente de cosas importantes.
 *
 * Config via OPTIONS namespace "openclaw":
 *   openclaw.push_url     — URL del webhook de OpenClaw (ej: http://localhost:3001/api/send)
 *   openclaw.push_token   — Token de auth si lo requiere OpenClaw
 *   openclaw.push_channel — Canal destino (telegram, whatsapp, discord...)
 *
 * Si push_url no está configurado → isConfigured() false, sin errores.
 * Timeout: 2s — si OpenClaw no responde, MyLocal no se cae.
 */

declare(strict_types=1);

namespace OpenClaw;

class OpenClawPushClient
{
    private string $pushUrl;
    private string $pushToken;
    private string $channel;

    public function __construct(string $pushUrl, string $pushToken, string $channel)
    {
        $this->pushUrl   = rtrim($pushUrl, '/');
        $this->pushToken = $pushToken;
        $this->channel   = $channel ?: 'default';
    }

    public static function fromOptions(): self
    {
        require_once __DIR__ . '/../OPTIONS/optiosconect.php';
        $opt = mylocal_options();
        return new self(
            (string) $opt->get('openclaw.push_url', ''),
            (string) $opt->get('openclaw.push_token', ''),
            (string) $opt->get('openclaw.push_channel', 'default')
        );
    }

    public static function isConfigured(): bool
    {
        require_once __DIR__ . '/../OPTIONS/optiosconect.php';
        return (bool) mylocal_options()->get('openclaw.push_url', '');
    }

    /**
     * Envía un mensaje de texto al agente OpenClaw.
     * El agente lo recibe como si fuera un mensaje entrante del usuario.
     */
    public function send(string $message, array $meta = []): array
    {
        if (!$this->pushUrl) {
            return ['success' => false, 'error' => 'openclaw.push_url no configurada'];
        }

        $payload = [
            'content' => $message,
            'channel' => $this->channel,
            'source'  => 'mylocal',
            'meta'    => $meta,
        ];

        return $this->post($this->pushUrl, (string) json_encode($payload));
    }

    /**
     * Envía un evento estructurado al agente OpenClaw.
     * Formatea el mensaje de forma legible para el agente.
     */
    public function pushEvent(string $eventName, array $data): array
    {
        $msg = self::formatEvent($eventName, $data);
        return $this->send($msg, ['event' => $eventName, 'data' => $data]);
    }

    /* ─── Formateo de eventos ──────────────────────────────────── */

    private static function formatEvent(string $event, array $data): string
    {
        return match ($event) {
            'pedido.creado' => sprintf(
                '[MyLocal] Nuevo pedido recibido. Código: %s | Cliente: %s',
                $data['codigo'] ?? '-',
                $data['cliente'] ?? '-'
            ),
            'cita.cancelada' => sprintf(
                '[MyLocal] Cita cancelada. Cliente: %s | Fecha: %s',
                $data['cliente'] ?? '-',
                $data['fecha_inicio'] ?? '-'
            ),
            'stock.bajo' => sprintf(
                '[MyLocal] ALERTA stock bajo. Producto: %s | Cantidad: %s (mínimo: %s)',
                $data['item'] ?? '-',
                $data['cantidad'] ?? '-',
                $data['umbral'] ?? '-'
            ),
            default => sprintf('[MyLocal] Evento: %s | %s', $event, json_encode($data)),
        };
    }

    /* ─── HTTP ───────────────────────────────────────────────────── */

    private function post(string $url, string $body): array
    {
        $headers = ['Content-Type: application/json'];
        if ($this->pushToken) {
            $headers[] = 'Authorization: Bearer ' . $this->pushToken;
        }

        if (function_exists('curl_init')) {
            return $this->postCurl($url, $body, $headers);
        }
        return $this->postStream($url, $body, $headers);
    }

    private function postCurl(string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 2,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) return ['success' => false, 'error' => 'curl: ' . $err];
        if ($code < 200 || $code >= 300) {
            return ['success' => false, 'error' => "HTTP {$code}", 'body' => substr((string) $resp, 0, 200)];
        }
        return ['success' => true, 'code' => $code];
    }

    private function postStream(string $url, string $body, array $headers): array
    {
        $ctx  = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", $headers),
                'content'       => $body,
                'timeout'       => 2,
                'ignore_errors' => true,
            ],
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) return ['success' => false, 'error' => 'sin respuesta de OpenClaw'];
        return ['success' => true];
    }
}
