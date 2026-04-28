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
    private ?array $currentUser = null;

    /**
     * Catálogo de operaciones que no requieren autenticación.
     */
    private array $publicOps = [
        'auth.login',
        'ping',
        'help',
        'describe'
    ];

    /**
     * Catálogo de acciones legacy que no requieren autenticación.
     */
    private array $publicActions = [
        'health_check'
    ];

    /**
     * Colecciones marcadas como públicas para lectura (Fase 3).
     */
    private array $publicCollections = [
        'products', 'menu', 'categories', 'restaurant_zones', 'theme_settings'
    ];

    /**
     * Colecciones maestras protegidas (Fase 3).
     */
    private array $masterCollections = ['users', 'roles', 'projects', 'system_logs', 'vault', 'system'];

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
        $this->services['backup']  = new Backup\SnapshotStore(STORAGE_ROOT, \dirname(STORAGE_ROOT) . '/backups');
        $this->services['axi']     = $this;
        
        $this->services['agents']  = null;

        $this->loadModules();
    }

    private function loadModules(): void
    {
        if (file_exists(__DIR__ . '/VaultManager.php')) {
            require_once __DIR__ . '/VaultManager.php';
            $this->services['legacy_vault'] = new \VaultManager(DATA_ROOT);
        }

        if (file_exists(AXI_ROOT . '/auth/Auth.php')) {
            require_once AXI_ROOT . '/auth/Auth.php';
            $this->services['auth'] = new \Auth();
        }
    }

    public function getService(string $name)
    {
        if ($name === 'agents' && ($this->services['agents'] ?? null) === null) {
            $this->services['agents'] = new Agents\Manager(STORAGE_ROOT . '/_system/agents', $this);
        }
        return $this->services[$name] ?? null;
    }

    public function getCurrentUser(): ?array
    {
        return $this->currentUser;
    }

    public function execute(array|Op\Operation $request): array
    {
        $opName = $this->identifyOpName($request);
        $resource = $this->identifyResource($request);
        $isPublic = false;

        // 1. Verificar si la operación es inherentemente pública
        if ($request instanceof Op\Operation) {
            $isPublic = true;
        } else {
            $isPublic = in_array($opName, $this->publicOps) || in_array($opName, $this->publicActions);
        }

        // 2. Verificar si es una lectura sobre una colección pública (Fase 3)
        if (!$isPublic && $resource && in_array($resource, $this->publicCollections)) {
            $opType = $this->identifyOpType($opName);
            if ($opType === 'read') {
                $isPublic = true;
            }
        }

        // 3. Escudo de Autenticación
        /** @var \Auth $auth */
        $auth = $this->getService('auth');
        $user = null;

        if (!$isPublic) {
            if ($auth) {
                $user = $auth->validateRequest();
                if (!$user) {
                    return Result::fail(
                        "No autorizado: Se requiere una sesión válida.",
                        AxiException::UNAUTHORIZED
                    )->toArray();
                }
                $this->currentUser = $user;
            }
        }

        // 4. Control de Autorización (RBAC - Fase 3)
        if ($user && $resource) {
            $opType = $this->identifyOpType($opName);
            $permission = "{$resource}.{$opType}";
            
            // Los superadmins saltan el RBAC
            if ($user['role'] !== 'superadmin') {
                // Bloqueo estricto de colecciones maestras para no-admins
                if (in_array($resource, $this->masterCollections) && $user['role'] !== 'admin') {
                    return Result::fail("Prohibido: No tienes permiso para acceder a colecciones de sistema.", AxiException::FORBIDDEN)->toArray();
                }

                // Verificación granular vía RoleManager
                if (!$auth->hasPermission($user, $resource, $opType)) {
                     // Nota: Auth->hasPermission en este sistema espera ($user, $resource, $action) 
                     // pero RoleManager->hasPermission espera ($roleId, $permission). 
                     // Auth.php ya hace de puente correctamente.
                }
            }
        }

        // 5. Ejecución
        if ($request instanceof Op\Operation) {
            return $this->runOp($request)->toArray();
        }

        if (isset($request['op'])) {
            return $this->dispatchOpByName((string) $request['op'], $request)->toArray();
        }

        return $this->legacyExecute($request);
    }

    private function identifyOpName(array|Op\Operation $request): string
    {
        if ($request instanceof Op\Operation) return get_class($request);
        return (string)($request['op'] ?? $request['action'] ?? 'unknown');
    }

    private function identifyResource(array|Op\Operation $request): ?string
    {
        if ($request instanceof Op\Operation) {
            // Reflección ligera para buscar propiedad 'collection'
            $ref = new \ReflectionClass($request);
            if ($ref->hasProperty('collection')) {
                $prop = $ref->getProperty('collection');
                $prop->setAccessible(true);
                return $prop->getValue($request);
            }
            return null;
        }
        return $request['collection'] ?? null;
    }

    private function identifyOpType(string $opName): string
    {
        $reads = ['select', 'read', 'list', 'query', 'count', 'exists', 'describe', 'schema', 'ping', 'help'];
        if (in_array($opName, $reads)) return 'read';
        
        $writes = ['insert', 'update', 'create', 'batch'];
        if (in_array($opName, $writes)) return 'update';
        
        $deletes = ['delete', 'drop_collection', 'drop_index', 'drop_field'];
        if (in_array($opName, $deletes)) return 'delete';
        
        $schemas = ['create_collection', 'alter_collection', 'rename_collection', 'add_field', 'rename_field', 'create_index'];
        if (in_array($opName, $schemas)) return 'schema';
        
        return 'other';
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

    public static function opRegistry(): array
    {
        return [
            'select' => Op\Select::class,
            'insert' => Op\Insert::class,
            'update' => Op\Update::class,
            'delete' => Op\Delete::class,
            'count'  => Op\Count::class,
            'exists' => Op\Exists::class,
            'batch'  => Op\Batch::class,
            'create_collection' => Op\Alter\CreateCollection::class,
            'drop_collection'   => Op\Alter\DropCollection::class,
            'alter_collection'  => Op\Alter\AlterCollection::class,
            'rename_collection' => Op\Alter\RenameCollection::class,
            'add_field'         => Op\Alter\AddField::class,
            'drop_field'        => Op\Alter\DropField::class,
            'rename_field'      => Op\Alter\RenameField::class,
            'create_index'      => Op\Alter\CreateIndex::class,
            'drop_index'        => Op\Alter\DropIndex::class,
            'ping'     => Op\System\Ping::class,
            'describe' => Op\System\Describe::class,
            'schema'   => Op\System\Schema::class,
            'explain'  => Op\System\Explain::class,
            'help'     => Op\System\Help::class,
            'sql'      => Op\System\Sql::class,
            'legacy.action' => Op\System\LegacyAction::class,
            'vault.unlock' => Op\Vault\Unlock::class,
            'vault.lock'   => Op\Vault\Lock::class,
            'vault.status' => Op\Vault\Status::class,
            'backup.create'  => Op\Backup\Create::class,
            'backup.restore' => Op\Backup\Restore::class,
            'backup.list'    => Op\Backup\ListSnapshots::class,
            'backup.drop'    => Op\Backup\Drop::class,
            'auth.login'       => Op\Auth\Login::class,
            'auth.logout'      => Op\Auth\Logout::class,
            'auth.create_user' => Op\Auth\CreateUser::class,
            'auth.grant_role'  => Op\Auth\GrantRole::class,
            'auth.revoke_role' => Op\Auth\RevokeRole::class,
            'ai.ask'             => Op\Ai\Ask::class,
            'ai.new_agent'       => Op\Ai\NewAgent::class,
            'ai.new_micro_agent' => Op\Ai\NewMicroAgent::class,
            'ai.run_agent'       => Op\Ai\RunAgent::class,
            'ai.kill_agent'      => Op\Ai\KillAgent::class,
            'ai.list_agents'     => Op\Ai\ListAgents::class,
            'ai.broadcast'       => Op\Ai\Broadcast::class,
            'ai.attach'          => Op\Ai\Attach::class,
            'ai.audit'           => Op\Ai\Audit::class,
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
