<?php
/**
 * AxiDB - Op\Backup\Restore: extrae un snapshot al STORAGE_ROOT.
 *
 * Subsistema: engine/op/backup
 * Entrada:    name (req), dry_run (bool, default false).
 * Salida:     Result con {restored: int, files: [...], dry_run: bool}.
 */

namespace Axi\Engine\Op\Backup;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Restore extends Operation
{
    public const OP_NAME = 'backup.restore';

    public function spec(string $name, bool $dryRun = false): self
    {
        $this->params['name']    = $name;
        $this->params['dry_run'] = $dryRun;
        return $this;
    }

    public function validate(): void
    {
        if (empty($this->params['name']) || !\is_string($this->params['name'])) {
            throw new AxiException("Backup\\Restore: 'name' requerido.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $store = $engine->getService('backup');
        if (!$store) {
            throw new AxiException("Backup: servicio no disponible.", AxiException::INTERNAL_ERROR);
        }
        $report = $store->restore($this->params['name'], !empty($this->params['dry_run']));
        return Result::ok($report);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'backup.restore',
            synopsis:    'Axi\\Op\\Backup\\Restore() ->spec(name, dry_run?)',
            description: 'Extrae un snapshot al STORAGE actual. Con dry_run no escribe nada; solo lista los archivos que se restaurarian.',
            params: [
                ['name' => 'name',    'type' => 'string', 'required' => true],
                ['name' => 'dry_run', 'type' => 'bool',   'required' => false, 'default' => false],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Backup\\Restore())->spec('pre-migration'));"],
                ['lang' => 'json', 'code' => '{"op":"backup.restore","name":"pre-migration","dry_run":true}'],
                ['lang' => 'cli',  'code' => 'axi backup restore pre-migration --dry-run'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED,  'when' => 'name ausente.'],
                ['code' => AxiException::DOCUMENT_NOT_FOUND, 'when' => 'snapshot no existe.'],
                ['code' => AxiException::INTERNAL_ERROR,     'when' => 'data.zip ausente o corrupto.'],
            ],
            related: ['Create', 'List_'],
        );
    }
}
