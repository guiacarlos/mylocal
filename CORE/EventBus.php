<?php
/**
 * EventBus — bus interno de eventos MyLocal.
 *
 * Permite que las capabilities emitan eventos sin acoplarse entre sí.
 * Los listeners se registran desde bootstrap o handlers; cada uno recibe
 * el array $data emitido y puede ignorar los campos que no necesita.
 *
 * Uso:
 *   EventBus::on('stock.bajo',   fn($d) => NotificationEngine::send(...));
 *   EventBus::on('pedido.creado', fn($d) => SomeModel::doSomething($d));
 *   EventBus::emit('stock.bajo', ['item' => 'paracetamol', 'cantidad' => 2]);
 *
 * Errores en listeners: loggeados via error_log, nunca burbujean al caller.
 */

declare(strict_types=1);

class EventBus
{
    /** @var array<string, callable[]> */
    private static array $listeners = [];

    /** Registra un listener para un evento. Múltiples listeners por evento. */
    public static function on(string $event, callable $handler): void
    {
        self::$listeners[$event][] = $handler;
    }

    /**
     * Emite un evento e invoca todos sus listeners.
     * Si un listener lanza excepción, se registra en error_log y se continúa.
     */
    public static function emit(string $event, array $data = []): void
    {
        $data['_event']      = $event;
        $data['_emitted_at'] = date('c');

        foreach (self::$listeners[$event] ?? [] as $handler) {
            try {
                $handler($data);
            } catch (\Throwable $e) {
                error_log("[EventBus] Listener error on '{$event}': " . $e->getMessage());
            }
        }
    }

    /** Elimina todos los listeners de un evento (útil en tests). */
    public static function reset(string $event = ''): void
    {
        if ($event === '') {
            self::$listeners = [];
        } else {
            unset(self::$listeners[$event]);
        }
    }

    /** Lista los eventos registrados (diagnóstico). */
    public static function registeredEvents(): array
    {
        return array_keys(self::$listeners);
    }
}
