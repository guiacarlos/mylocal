<?php
/**
 * OpenClaudeListeners — registra los listeners por defecto del EventBus
 * que usan OpenClaudeClient como asistente transversal.
 *
 * Llama a self::register() una vez desde el bootstrap de la API.
 * Si openclaude no está configurado → los handlers envían notif por
 * Noop driver (silencioso) sin llamar a la API externa.
 *
 * Eventos manejados:
 *   stock.bajo      — alerta de stock crítico (clínica, hostelería)
 *   pedido.creado   — confirmación interna de nuevo pedido (logística)
 *   cita.cancelada  — notificación de cancelación (clínica, asesoria)
 */

declare(strict_types=1);

namespace AI;

class OpenClaudeListeners
{
    public static function register(): void
    {
        \EventBus::on('stock.bajo',     [self::class, 'onStockBajo']);
        \EventBus::on('pedido.creado',  [self::class, 'onPedidoCreado']);
        \EventBus::on('cita.cancelada', [self::class, 'onCitaCancelada']);
    }

    /**
     * stock.bajo — genera un resumen de alerta y lo envía al admin.
     *
     * $data esperado: { item, cantidad, umbral, local_id, admin_email? }
     */
    public static function onStockBajo(array $data): void
    {
        $item     = $data['item']     ?? 'producto';
        $cantidad = $data['cantidad'] ?? 0;
        $umbral   = $data['umbral']   ?? 5;
        $email    = $data['admin_email'] ?? '';

        $asunto = "Alerta de stock bajo: {$item}";
        $cuerpo = "El stock de <strong>{$item}</strong> ha caído a {$cantidad} unidades "
                . "(umbral: {$umbral}). Por favor, realiza un nuevo pedido.";

        if (OpenClaudeClient::isEnabled()) {
            $ai = OpenClaudeClient::fromOptions();
            $res = $ai->complete(
                "Escribe una alerta breve (2 frases) para el responsable del almacén "
                . "indicando que el stock de '{$item}' es {$cantidad} (mínimo {$umbral}). "
                . "Solo el texto del mensaje, sin encabezados.",
                'Eres un asistente de gestión de inventario conciso y profesional.'
            );
            if ($res['success']) {
                $texto = $ai->extractText($res);
                if ($texto) $cuerpo = nl2br(htmlspecialchars($texto));
            }
        }

        if ($email) {
            self::sendNotif($email, $asunto, $cuerpo, ['event' => 'stock.bajo', 'item' => $item]);
        }
    }

    /**
     * pedido.creado — log interno; si está activo Claude, genera referencia.
     *
     * $data esperado: { pedido_id, codigo, cliente, local_id }
     */
    public static function onPedidoCreado(array $data): void
    {
        if (!OpenClaudeClient::isEnabled()) return;

        $codigo  = $data['codigo']  ?? '';
        $cliente = $data['cliente'] ?? '';
        if (!$codigo) return;

        // Solo loggea — no envía email para no ser ruidoso
        error_log("[OpenClaude] pedido.creado código={$codigo} cliente={$cliente}");
    }

    /**
     * cita.cancelada — notifica al admin si hay email configurado.
     *
     * $data esperado: { cita_id, cliente, fecha_inicio, motivo?, admin_email? }
     */
    public static function onCitaCancelada(array $data): void
    {
        $cliente = $data['cliente']      ?? '';
        $fecha   = $data['fecha_inicio'] ?? '';
        $email   = $data['admin_email']  ?? '';

        $asunto = 'Cita cancelada: ' . $cliente;
        $cuerpo = "La cita de <strong>{$cliente}</strong> prevista para {$fecha} ha sido cancelada.";

        if (OpenClaudeClient::isEnabled()) {
            $ai  = OpenClaudeClient::fromOptions();
            $res = $ai->complete(
                "Escribe un mensaje breve (1-2 frases) notificando que la cita de '{$cliente}' "
                . "del {$fecha} fue cancelada. Solo el texto.",
                'Eres un asistente de recepción de clínica, amable y conciso.'
            );
            if ($res['success']) {
                $texto = $ai->extractText($res);
                if ($texto) $cuerpo = nl2br(htmlspecialchars($texto));
            }
        }

        if ($email) {
            self::sendNotif($email, $asunto, $cuerpo, ['event' => 'cita.cancelada', 'cliente' => $cliente]);
        }
    }

    /* ─── helpers ────────────────────────────────────────────── */

    private static function sendNotif(string $to, string $subject, string $body, array $meta): void
    {
        $notifFile = __DIR__ . '/../NOTIFICACIONES/NotificationEngine.php';
        $driversDir = __DIR__ . '/../NOTIFICACIONES/drivers/';

        if (!class_exists(\Notificaciones\NotificationEngine::class)) {
            if (file_exists($notifFile)) {
                require_once $driversDir . 'NoopDriver.php';
                require_once $driversDir . 'EmailDriver.php';
                require_once $driversDir . 'WhatsAppDriver.php';
                require_once __DIR__ . '/../NOTIFICACIONES/Template.php';
                require_once $notifFile;
            } else {
                error_log("[OpenClaude] NotificationEngine no disponible — evento: {$meta['event']}");
                return;
            }
        }

        \Notificaciones\NotificationEngine::send($to, $subject, $body, $meta);
    }
}
