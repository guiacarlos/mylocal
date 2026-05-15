<?php
/**
 * AxiDB - Op\Delete: baja de documento.
 *
 * Subsistema: engine/op
 * Responsable: soft-delete por defecto (marca _deletedAt); hard-delete con flag.
 * Entrada:    collection, id; hard (opcional).
 * Salida:     Result con {deleted: true, id}.
 */

namespace Axi\Engine\Op;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Result;

class Delete extends Operation
{
    public const OP_NAME = 'delete';

    public function id(string $id): self
    {
        $this->params['id'] = $id;
        return $this;
    }

    public function hard(bool $hard = true): self
    {
        $this->params['hard'] = $hard;
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        $hasId   = isset($this->params['id']);
        $hasExpr = isset($this->params['where_expr']);
        if (!$hasId && !$hasExpr) {
            throw new AxiException("Delete: requiere 'id' o 'where_expr' (AxiSQL).", AxiException::VALIDATION_FAILED);
        }
        if ($hasId && (!\is_string($this->params['id']) || $this->params['id'] === '')) {
            throw new AxiException("Delete: 'id' debe ser string no vacio.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        if (isset($this->params['where_expr'])) {
            return $this->executeWhere($engine);
        }

        $storage = $engine->getService('storage');
        $id      = $this->params['id'];

        if (!empty($this->params['hard'])) {
            $result = $storage->delete($this->collection, $id);
            return Result::ok(['deleted' => (bool) $result, 'id' => $id, 'hard' => true]);
        }

        // Soft delete. _deletedAt es campo reservado del sistema -> queda en claro
        // aunque la coleccion sea cifrada (Vault::encryptDoc lo respeta).
        $storage->update($this->collection, $id, ['_deletedAt' => \date('c')]);
        return Result::ok(['deleted' => true, 'id' => $id, 'hard' => false]);
    }

    private function executeWhere(object $engine): Result
    {
        $storage = $engine->getService('storage');
        $all     = $storage->list($this->collection);
        $hard    = !empty($this->params['hard']);
        $deleted = 0;
        $ids     = [];
        foreach ($all as $doc) {
            if (!\Axi\Sql\WhereEvaluator::matches($doc, $this->params['where_expr'])) {
                continue;
            }
            $id = $doc['_id'] ?? $doc['id'] ?? null;
            if ($id === null) { continue; }
            if ($hard) {
                $storage->delete($this->collection, $id);
            } else {
                $storage->update($this->collection, $id, ['_deletedAt' => \date('c')]);
            }
            $ids[] = $id;
            $deleted++;
        }
        return Result::ok(['deleted' => $deleted, 'ids' => $ids, 'hard' => $hard]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'delete',
            synopsis:    'Axi\\Op\\Delete(collection) ->id("...") [->hard()]',
            description: 'Baja de documento. Por defecto soft-delete (marca _deletedAt). hard() borra el archivo.',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true],
                ['name' => 'id',         'type' => 'string', 'required' => true],
                ['name' => 'hard',       'type' => 'bool',   'required' => false, 'default' => false],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Op\\Delete('notas'))->id('abc123'));"],
                ['lang' => 'json',   'code' => '{"op":"delete","collection":"notas","id":"abc123"}'],
                ['lang' => 'axisql', 'code' => "DELETE FROM notas WHERE id = 'abc123'"],
                ['lang' => 'cli',    'code' => 'axi delete notas abc123 [--hard]'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED,  'when' => 'id ausente o vacio.'],
                ['code' => AxiException::DOCUMENT_NOT_FOUND, 'when' => 'id no existe en la coleccion.'],
            ],
            related: ['Update', 'Insert', 'Select'],
        );
    }
}
