<?php
/**
 * OpenClawSkillExecutor — proxy entre OpenClaw y las acciones MyLocal.
 *
 * NO tiene lista de herramientas hardcodeada. OpenClaw llama con el nombre
 * de una acción MyLocal existente y sus parámetros, y este executor la
 * reenvía al dispatcher interno validando auth y permisos.
 *
 * Flujo:
 *   OpenClaw → POST openclaw_call { tool: "tarea_create", params: {...} }
 *   → validar X-MyLocal-Skill-Key
 *   → verificar que la acción está en la whitelist de OPTIONS openclaw.allowed_actions
 *   → llamar internamente al handler correspondiente
 *   → devolver resultado
 *
 * La whitelist openclaw.allowed_actions la define el administrador
 * de cada despliegue — no hay lista por defecto. Sin configurar: nada permitido.
 */

declare(strict_types=1);

namespace OpenClaw;

class OpenClawSkillExecutor
{
    public static function validateKey(string $providedKey): bool
    {
        require_once __DIR__ . '/../OPTIONS/optiosconect.php';
        $expected = (string) mylocal_options()->get('openclaw.skill_api_key', '');
        if ($expected === '' || $providedKey === '') return false;
        return hash_equals($expected, $providedKey);
    }

    /**
     * Ejecuta una acción MyLocal en nombre de OpenClaw.
     *
     * @param string $action  Nombre de la acción MyLocal (ej: "tarea_create")
     * @param array  $params  Parámetros de la acción
     * @param array  $agentUser Usuario sintético con rol configurado en OPTIONS
     */
    public static function execute(string $action, array $params, array $agentUser): array
    {
        // Verificar que la acción está en la whitelist del admin
        if (!self::isAllowed($action)) {
            return [
                'success' => false,
                'error'   => "Acción '{$action}' no está en openclaw.allowed_actions para este despliegue.",
                'hint'    => 'El administrador debe añadirla a OPTIONS > openclaw.allowed_actions',
            ];
        }

        // El dispatcher interno maneja todo lo demás igual que una petición normal
        return self::dispatch($action, $params, $agentUser);
    }

    /**
     * Comprueba si la acción está en la whitelist del administrador.
     * Sin lista configurada → nada está permitido (fail-safe).
     */
    private static function isAllowed(string $action): bool
    {
        require_once __DIR__ . '/../OPTIONS/optiosconect.php';
        $allowed = mylocal_options()->get('openclaw.allowed_actions', []);
        if (!is_array($allowed) || empty($allowed)) return false;
        return in_array($action, $allowed, true);
    }

    /**
     * Dispatcher interno — llama al handler PHP correspondiente a la acción.
     * Reutiliza exactamente la misma lógica que spa/server/index.php,
     * sin duplicar código ni crear otra capa de traducción.
     *
     * Las acciones disponibles son las que ya existen en ALLOWED_ACTIONS
     * del index.php — no hay nuevas acciones específicas de OpenClaw.
     */
    private static function dispatch(string $action, array $params, array $user): array
    {
        // Incluir lib.php para funciones helper (data_get, s_id, etc.)
        $serverDir = realpath(__DIR__ . '/../../spa/server') ?: '';
        if (!function_exists('data_get')) {
            require_once $serverDir . '/lib.php';
        }

        $handlersDir = $serverDir . '/handlers';

        try {
            // Acciones de TAREAS
            if (str_starts_with($action, 'tarea_')) {
                require_once $handlersDir . '/tareas.php';
                return \Tareas\handle_tareas($action, $params, $user);
            }
            // Acciones de DELIVERY
            if (in_array($action, ['pedido_create','pedido_list','pedido_get','pedido_estado',
                'vehiculo_create','vehiculo_list','vehiculo_update',
                'entrega_asignar','entrega_list_dia','incidencia_add'], true)) {
                require_once $handlersDir . '/delivery.php';
                return \Delivery\handle_delivery_admin($action, $params, $user);
            }
            // Acciones de CITAS
            if (str_starts_with($action, 'cita_')) {
                require_once $handlersDir . '/citas.php';
                return \Citas\handle_citas($action, $params, $user);
            }
            // Acciones de CRM
            if (str_starts_with($action, 'crm_')) {
                require_once $handlersDir . '/crm.php';
                return \CRM\handle_crm($action, $params, $user);
            }
            // Acciones de NOTIFICACIONES
            if (str_starts_with($action, 'notif_')) {
                require_once $handlersDir . '/notificaciones.php';
                return \Notificaciones\handle_notificaciones($action, $params, $user);
            }
            // Acciones de CARTA (hostelería)
            if (in_array($action, ['list_cartas','list_categorias','list_productos',
                'get_local','list_cartas'], true)) {
                require_once $handlersDir . '/carta.php';
                return handle_carta($action, $params, $user);
            }

            return ['success' => false, 'error' => "No hay dispatcher para la acción: {$action}"];

        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
