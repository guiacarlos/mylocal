<?php
/**
 * AxiDB - Clase motor ligera (modo embebido y fallback API).
 *
 * Subsistema: engine
 * Responsable: CRUD basal sobre colecciones JSON. Las acciones desconocidas
 *              se delegan al motor ACIDE legacy (scaffold, se sustituira por
 *              el Op model en Fase 1.3).
 * Entrada:    execute(array $request) -> array
 * Salida:     ['success' => bool, 'data' => mixed, 'error' => string|null]
 */

namespace Axi\Engine;

require_once __DIR__ . '/StorageManager.php';
require_once __DIR__ . '/QueryEngine.php';
require_once __DIR__ . '/Utils.php';

class Axi
{
    private array $services = [];

    public function __construct(array $config = [])
    {
        if (!\defined('AXI_ROOT')) {
            \define('AXI_ROOT', dirname(__DIR__));
        }
        if (!\defined('DATA_ROOT')) {
            \define('DATA_ROOT', $config['data_root'] ?? realpath(AXI_ROOT . '/../STORAGE'));
        }
        if (!\defined('STORAGE_ROOT')) {
            \define('STORAGE_ROOT', $config['storage_root'] ?? DATA_ROOT);
        }

        $this->services['storage'] = new StorageManager(DATA_ROOT, STORAGE_ROOT);
        $this->services['query']   = new \QueryEngine($this->services['storage']);
        $this->services['meta']    = new Schema\MetaStore(STORAGE_ROOT);
        $this->services['driver']  = new Storage\FsJsonDriver(STORAGE_ROOT);
        $this->services['vault']   = new Vault\Vault(\dirname(STORAGE_ROOT) . '/vault');
        $this->services['backup']  = new Backup\SnapshotStore(STORAGE_ROOT, \dirname(STORAGE_ROOT) . '/backups');
        $this->services['axi']     = $this;
        // Agents (Fase 6): se inicializa lazy a traves de getService('agents')
        // para evitar dependencia circular Manager <-> Axi en el constructor.
        $this->services['agents']  = null;

        $this->loadModules();
    }

    private function loadModules(): void
    {
        // Legacy VaultManager queda como servicio aparte ('legacy_vault') para
        // no chocar con Vault\Vault (Fase 3) que ya esta registrado en 'vault'.
        if (file_exists(__DIR__ . '/VaultManager.php')) {
            require_once __DIR__ . '/VaultManager.php';
            $this->services['legacy_vault'] = new \VaultManager(DATA_ROOT);
        }
    }

    public function getService(string $name)
    {
        if ($name === 'agents' && ($this->services['agents'] ?? null) === null) {
            $this->services['agents'] = new Agents\Manager(STORAGE_ROOT . '/_system/agents', $this);
        }
        return $this->services[$name] ?? null;
    }

    public function execute(array|Op\Operation $request): array
    {
        // Entrada 1: Operation PHP directa (embebido tipado)
        if ($request instanceof Op\Operation) {
            return $this->runOp($request)->toArray();
        }

        // Entrada 2: array con clave 'op' = Op model (via HTTP o construccion manual)
        if (isset($request['op'])) {
            return $this->dispatchOpByName((string) $request['op'], $request)->toArray();
        }

        // Entrada 3: contrato legacy {action, ...} = retrocompat ACIDE
        return $this->legacyExecute($request);
    }

    private function runOp(Op\Operation $op): Result
    {
        $t0 = microtime(true);
        try {
            $op->validate();
            $result = $op->execute($this);
            return $result->withDuration((microtime(true) - $t0) * 1000);
        } catch (AxiException $e) {
            return Result::fail($e->getMessage(), $e->getAxiCode(), (microtime(true) - $t0) * 1000);
        } catch (\Throwable $e) {
            return Result::fail($e->getMessage(), AxiException::INTERNAL_ERROR, (microtime(true) - $t0) * 1000);
        }
    }

    private function dispatchOpByName(string $opName, array $request): Result
    {
        $class = $this->resolveOpClass($opName);
        if ($class === null) {
            return Result::fail("Op desconocido: '{$opName}'.", AxiException::OP_UNKNOWN);
        }
        /** @var Op\Operation $op */
        $op = $class::fromArray($request);
        return $this->runOp($op);
    }

    /**
     * Catalogo canonico de Ops (Fase 1.3). Fase 1.8 anade validacion de coverage.
     * Publico para que el CLI, docs builder y Op\System\Help lo consuman.
     */
    public static function opRegistry(): array
    {
        return [
            // CRUD
            'select' => Op\Select::class,
            'insert' => Op\Insert::class,
            'update' => Op\Update::class,
            'delete' => Op\Delete::class,
            'count'  => Op\Count::class,
            'exists' => Op\Exists::class,
            'batch'  => Op\Batch::class,
            // Schema
            'create_collection' => Op\Alter\CreateCollection::class,
            'drop_collection'   => Op\Alter\DropCollection::class,
            'alter_collection'  => Op\Alter\AlterCollection::class,
            'rename_collection' => Op\Alter\RenameCollection::class,
            'add_field'         => Op\Alter\AddField::class,
            'drop_field'        => Op\Alter\DropField::class,
            'rename_field'      => Op\Alter\RenameField::class,
            'create_index'      => Op\Alter\CreateIndex::class,
            'drop_index'        => Op\Alter\DropIndex::class,
            // Sistema
            'ping'     => Op\System\Ping::class,
            'describe' => Op\System\Describe::class,
            'schema'   => Op\System\Schema::class,
            'explain'  => Op\System\Explain::class,
            'help'     => Op\System\Help::class,
            'sql'      => Op\System\Sql::class,
            // Migracion Socola (Fase 5): formaliza el bridge action legacy.
            'legacy.action' => Op\System\LegacyAction::class,
            // Vault (Fase 3)
            'vault.unlock' => Op\Vault\Unlock::class,
            'vault.lock'   => Op\Vault\Lock::class,
            'vault.status' => Op\Vault\Status::class,
            // Backup (Fase 3)
            'backup.create'  => Op\Backup\Create::class,
            'backup.restore' => Op\Backup\Restore::class,
            'backup.list'    => Op\Backup\ListSnapshots::class,
            'backup.drop'    => Op\Backup\Drop::class,
            // Auth
            'auth.login'       => Op\Auth\Login::class,
            'auth.logout'      => Op\Auth\Logout::class,
            'auth.create_user' => Op\Auth\CreateUser::class,
            'auth.grant_role'  => Op\Auth\GrantRole::class,
            'auth.revoke_role' => Op\Auth\RevokeRole::class,
            // AI (stubs Fase 1, implementacion Fase 6)
            'ai.ask'             => Op\Ai\Ask::class,
            'ai.new_agent'       => Op\Ai\NewAgent::class,
            'ai.new_micro_agent' => Op\Ai\NewMicroAgent::class,
            'ai.run_agent'       => Op\Ai\RunAgent::class,
            'ai.kill_agent'      => Op\Ai\KillAgent::class,
            'ai.list_agents'     => Op\Ai\ListAgents::class,
            'ai.broadcast'       => Op\Ai\Broadcast::class,
            'ai.attach'          => Op\Ai\Attach::class,
            'ai.audit'           => Op\Ai\Audit::class,
            // Join (sugar/stub): el Op pertenece al namespace root Axi\ pero se registra
            // aqui para que el dispatcher lo reconozca. Implementacion real en Fase 2.
            'join'               => \Axi\Join::class,
        ];
    }

    private function resolveOpClass(string $opName): ?string
    {
        $registry = self::opRegistry();
        return $registry[$opName] ?? null;
    }

    private function legacyExecute(array $request): array
    {
        $action = $request['action'] ?? '';
        try {
            switch ($action) {
                case 'health_check':
                    return ['success' => true, 'data' => $this->healthCheck()];
                case 'read':
                    return ['success' => true, 'data' => $this->services['storage']->read($request['collection'], $request['id'])];
                case 'update':
                case 'create':
                    return ['success' => true, 'data' => $this->services['storage']->update($request['collection'], $request['id'] ?? null, $request['data'] ?? [])];
                case 'list':
                    return ['success' => true, 'data' => $this->services['storage']->list($request['collection'])];
                case 'query':
                    return ['success' => true, 'data' => $this->services['query']->query($request['collection'], $request['params'] ?? [])];
                case 'delete':
                    return ['success' => true, 'data' => $this->services['storage']->delete($request['collection'], $request['id'])];
                default:
                    return $this->delegateToLegacy($request);
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function delegateToLegacy(array $request): array
    {
        $action = $request['action'] ?? '';
        if (file_exists(__DIR__ . '/ACIDE.php')) {
            if (!isset($this->services['acide'])) {
                require_once __DIR__ . '/ACIDE.php';
                $this->services['acide'] = new \ACIDE();
            }
            return $this->services['acide']->execute($request);
        }
        throw new \Exception("Accion no reconocida por AxiDB: {$action}");
    }

    public function healthCheck(): array
    {
        return [
            'status'    => 'online',
            'engine'    => 'AxiDB v1.0-dev',
            'timestamp' => date('c'),
            'storage'   => is_writable(DATA_ROOT) ? 'writeable' : 'readonly',
            'services'  => array_keys($this->services),
        ];
    }
}
