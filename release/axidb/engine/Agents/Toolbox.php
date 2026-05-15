<?php
/**
 * AxiDB - Agents\Toolbox: sandbox que ejecuta Ops en nombre de un agente.
 *
 * Subsistema: engine/agents
 * Responsable: validar que el Op solicitado este declarado en agent.tools y
 *              despacharlo a $engine->execute([...]) tal cual lo haria un
 *              cliente HTTP. Anota cada llamada en el AuditLog y, si el Op
 *              es un Batch con >10 writes, dispara backup.create previo
 *              (regla §6.4 de seguridad agentica).
 *
 *              No hay polimorfismo: cualquier herramienta del agente es una
 *              entrada del catalogo de Ops. Anadir una "tool" nueva = anadir
 *              una Op al catalogo y permitirsela al agente.
 */

namespace Axi\Engine\Agents;

use Axi\Engine\AxiException;

final class Toolbox
{
    public const BATCH_AUTOSNAPSHOT_THRESHOLD = 10;
    private const WRITE_OPS = ['insert', 'update', 'delete', 'create_collection',
        'drop_collection', 'alter_collection', 'rename_collection',
        'add_field', 'drop_field', 'rename_field', 'create_index', 'drop_index'];

    public function __construct(
        private object    $engine,
        private ?AuditLog $audit = null
    ) {}

    /**
     * Ejecuta una Op en nombre del agente. Devuelve el array de respuesta del
     * motor (success/data/error/code/duration_ms).
     *
     * @throws AxiException si la Op no esta en el sandbox del agente.
     */
    public function call(Agent $agent, string $opName, array $params = []): array
    {
        if (!$agent->canExecute($opName)) {
            $denied = [
                'success' => false,
                'error'   => "agent '{$agent->id}' no tiene permiso para '{$opName}'",
                'code'    => AxiException::FORBIDDEN,
            ];
            $this->audit?->record($agent->id, $opName, $params, $denied);
            throw new AxiException(
                "Toolbox: el agente '{$agent->id}' no tiene permiso para ejecutar '{$opName}'. tools={" . \implode(',', $agent->tools) . "}.",
                AxiException::FORBIDDEN
            );
        }

        // Pre-snapshot automatico: si es Batch con >N writes, snapshot antes.
        $snapshotName = null;
        if ($opName === 'batch' && $this->isHeavyBatch($params)) {
            $snapshotName = $this->autoSnapshot($agent->id);
        }

        $request = \array_merge(['op' => $opName], $params);
        $response = $this->engine->execute($request);

        $this->audit?->record($agent->id, $opName, $params, $response, $snapshotName);
        return $response;
    }

    /** True si el batch tiene > THRESHOLD operaciones de escritura. */
    public function isHeavyBatch(array $params): bool
    {
        $ops = $params['ops'] ?? [];
        if (!\is_array($ops)) { return false; }
        $writes = 0;
        foreach ($ops as $sub) {
            $name = \is_array($sub) ? ($sub['op'] ?? '') : '';
            if (\in_array($name, self::WRITE_OPS, true)) {
                $writes++;
                if ($writes > self::BATCH_AUTOSNAPSHOT_THRESHOLD) { return true; }
            }
        }
        return false;
    }

    /** Crea un snapshot pre-batch usando la Op backup.create. Devuelve el name o null si fallo. */
    private function autoSnapshot(string $agentId): ?string
    {
        $name = 'auto-pre-batch-' . \date('Ymd-His') . '-' . \substr($agentId, -6);
        $resp = $this->engine->execute(['op' => 'backup.create', 'name' => $name]);
        return ($resp['success'] ?? false) ? $name : null;
    }

    /**
     * Lista de Ops permitidas por defecto a un agente "lector" (read-only).
     * Util para crear agentes seguros sin pasar la lista a mano.
     */
    public static function readOnlyTools(): array
    {
        return ['select', 'count', 'exists', 'describe', 'schema', 'ping', 'help'];
    }

    /** Tools de escritura: solo si confias en el LLM o lo limitas. */
    public static function writeTools(): array
    {
        return ['insert', 'update', 'delete', 'batch'];
    }
}
