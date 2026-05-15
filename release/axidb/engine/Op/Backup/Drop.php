<?php
/**
 * AxiDB - Op\Backup\Drop: elimina un snapshot.
 *
 * Subsistema: engine/op/backup
 * Entrada: name (req).
 * Salida: Result con {dropped: bool, name}.
 */

namespace Axi\Engine\Op\Backup;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Drop extends Operation
{
    public const OP_NAME = 'backup.drop';

    public function name(string $name): self
    {
        $this->params['name'] = $name;
        return $this;
    }

    public function validate(): void
    {
        if (empty($this->params['name']) || !\is_string($this->params['name'])) {
            throw new AxiException("Backup\\Drop: 'name' requerido.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $store = $engine->getService('backup');
        if (!$store) {
            throw new AxiException("Backup: servicio no disponible.", AxiException::INTERNAL_ERROR);
        }
        $ok = $store->drop($this->params['name']);
        if (!$ok) {
            throw new AxiException(
                "Backup\\Drop: snapshot '{$this->params['name']}' no existe.",
                AxiException::DOCUMENT_NOT_FOUND
            );
        }
        return Result::ok(['dropped' => true, 'name' => $this->params['name']]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'backup.drop',
            synopsis:    'Axi\\Op\\Backup\\Drop() ->name("...")',
            description: 'Elimina un snapshot del filesystem. Operacion destructiva, sin papelera.',
            params: [
                ['name' => 'name', 'type' => 'string', 'required' => true],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Backup\\Drop())->name('old-2025'));"],
                ['lang' => 'json', 'code' => '{"op":"backup.drop","name":"old-2025"}'],
                ['lang' => 'cli',  'code' => 'axi backup drop old-2025'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED,  'when' => 'name ausente.'],
                ['code' => AxiException::DOCUMENT_NOT_FOUND, 'when' => 'snapshot no existe.'],
            ],
            related: ['Create', 'List_', 'Restore'],
        );
    }
}
