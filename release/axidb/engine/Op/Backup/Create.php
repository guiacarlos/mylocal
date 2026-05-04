<?php
/**
 * AxiDB - Op\Backup\Create: crea snapshot full o incremental.
 *
 * Subsistema: engine/op/backup
 * Entrada:    name (req), incremental (bool, default false), base (string, opcional).
 * Salida:     Result con el manifest del snapshot creado.
 */

namespace Axi\Engine\Op\Backup;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Create extends Operation
{
    public const OP_NAME = 'backup.create';

    public function spec(string $name, bool $incremental = false, ?string $base = null): self
    {
        $this->params['name']        = $name;
        $this->params['incremental'] = $incremental;
        if ($base !== null) {
            $this->params['base'] = $base;
        }
        return $this;
    }

    public function validate(): void
    {
        if (empty($this->params['name']) || !\is_string($this->params['name'])) {
            throw new AxiException("Backup\\Create: 'name' requerido.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $store = $engine->getService('backup');
        if (!$store) {
            throw new AxiException("Backup: servicio no disponible.", AxiException::INTERNAL_ERROR);
        }

        $base = null;
        if (!empty($this->params['incremental'])) {
            $base = $this->params['base'] ?? $this->latestSnapshot($store);
            if ($base === null) {
                throw new AxiException(
                    "Backup\\Create: incremental requiere un snapshot base previo (no hay ninguno).",
                    AxiException::VALIDATION_FAILED
                );
            }
        }

        $manifest = $store->create($this->params['name'], $base);
        return Result::ok($manifest->toArray());
    }

    private function latestSnapshot(object $store): ?string
    {
        $list = $store->listSnapshots();
        if ($list === []) {
            return null;
        }
        // Ordenar por timestamp del manifest; usamos el ultimo.
        \usort($list, function ($a, $b) use ($store) {
            return $store->readManifest($a)->ts <=> $store->readManifest($b)->ts;
        });
        return \end($list);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'backup.create',
            synopsis:    'Axi\\Op\\Backup\\Create() ->spec(name, incremental?, base?)',
            description: 'Crea un snapshot zip del STORAGE actual. Sin --incremental hace full; con incremental + base toma solo docs cambiados desde la ts del base. Si no se da base, usa el ultimo snapshot disponible.',
            params: [
                ['name' => 'name',        'type' => 'string', 'required' => true,  'description' => 'Nombre del snapshot. Solo [A-Za-z0-9_\-.], max 81 chars.'],
                ['name' => 'incremental', 'type' => 'bool',   'required' => false, 'default' => false],
                ['name' => 'base',        'type' => 'string', 'required' => false, 'description' => 'Nombre del snapshot base (solo si incremental).'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Backup\\Create())->spec('pre-migration'));"],
                ['lang' => 'json', 'code' => '{"op":"backup.create","name":"daily","incremental":true}'],
                ['lang' => 'cli',  'code' => 'axi backup create pre-migration  |  axi backup create daily --incremental'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'name vacio/invalido o incremental sin snapshot base disponible.'],
                ['code' => AxiException::CONFLICT,          'when' => 'snapshot con ese nombre ya existe.'],
            ],
            related: ['Restore', 'List_', 'Drop'],
        );
    }
}
