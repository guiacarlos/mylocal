<?php
/**
 * OpenClawListeners — registra los listeners del EventBus que empujan
 * eventos de MyLocal al agente OpenClaw local.
 *
 * Si push_url no está configurado → listeners registrados pero silenciosos.
 * Llamar a register() una sola vez desde el handler del servidor.
 */

declare(strict_types=1);

namespace OpenClaw;

class OpenClawListeners
{
    public static function register(): void
    {
        \EventBus::on('pedido.creado',  [self::class, 'onPedidoCreado']);
        \EventBus::on('cita.cancelada', [self::class, 'onCitaCancelada']);
        \EventBus::on('stock.bajo',     [self::class, 'onStockBajo']);
    }

    public static function onPedidoCreado(array $data): void
    {
        if (!OpenClawPushClient::isConfigured()) return;
        OpenClawPushClient::fromOptions()->pushEvent('pedido.creado', $data);
    }

    public static function onCitaCancelada(array $data): void
    {
        if (!OpenClawPushClient::isConfigured()) return;
        OpenClawPushClient::fromOptions()->pushEvent('cita.cancelada', $data);
    }

    public static function onStockBajo(array $data): void
    {
        if (!OpenClawPushClient::isConfigured()) return;
        OpenClawPushClient::fromOptions()->pushEvent('stock.bajo', $data);
    }
}
