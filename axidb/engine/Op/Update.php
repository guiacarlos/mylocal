<?php
/**
 * AxiDB - Op\Update: modifica un documento existente.
 *
 * Subsistema: engine/op
 * Entrada:    collection, id, data; replace (opcional, default false = merge).
 * Salida:     Result con el documento resultante (post-merge o post-replace).
 */

namespace Axi\Engine\Op;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Result;

class Update extends Operation
{
    public const OP_NAME = 'update';

    public function id(string $id): self
    {
        $this->params['id'] = $id;
        return $this;
    }

    public function data(array $data): self
    {
        $this->params['data'] = $data;
        return $this;
    }

    public function replace(bool $replace = true): self
    {
        $this->params['replace'] = $replace;
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();
        if (!isset($this->params['data']) || !\is_array($this->params['data'])) {
            throw new AxiException("Update: 'data' requerido (array).", AxiException::VALIDATION_FAILED);
        }
        $hasId   = isset($this->params['id']);
        $hasExpr = isset($this->params['where_expr']);
        if (!$hasId && !$hasExpr) {
            throw new AxiException("Update: requiere 'id' o 'where_expr' (AxiSQL).", AxiException::VALIDATION_FAILED);
        }
        if ($hasId && (!\is_string($this->params['id']) || $this->params['id'] === '')) {
            throw new AxiException("Update: 'id' debe ser string no vacio.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        if (isset($this->params['where_expr'])) {
            return $this->executeWhere($engine);
        }

        $storage = $engine->getService('storage');
        $meta    = $engine->getService('meta');
        $vault   = $engine->getService('vault');
        $encrypted = $meta && ($meta->readMeta($this->collection)['flags']['encrypted'] ?? false);

        $id   = $this->params['id'];
        $data = $this->params['data'];

        // En colecciones cifradas: leer en claro -> mergear -> re-cifrar entero.
        if ($encrypted) {
            $existing = $storage->read($this->collection, $id);
            if (\is_array($existing)) {
                $existing = $vault->decryptDoc($existing);
            } else {
                $existing = ['_id' => $id];
            }
            if (!empty($this->params['replace'])) {
                $merged = \array_merge(['_id' => $id], $data);
            } else {
                $merged = \array_merge($existing, $data, ['_id' => $id]);
            }
            $payload = $vault->encryptDoc($merged);
            $payload['_REPLACE_'] = true;
            $persisted = $storage->update($this->collection, $id, $payload);
            return Result::ok($vault->decryptDoc($persisted));
        }

        if (!empty($this->params['replace'])) {
            $data['_REPLACE_'] = true;
        }
        $doc = $storage->update($this->collection, $id, $data);
        if (!isset($doc['_id'])) {
            $doc['_id'] = $id;
        }
        return Result::ok($doc);
    }

    private function executeWhere(object $engine): Result
    {
        $storage = $engine->getService('storage');
        $meta    = $engine->getService('meta');
        $vault   = $engine->getService('vault');
        $encrypted = $meta && ($meta->readMeta($this->collection)['flags']['encrypted'] ?? false);

        $all = $storage->list($this->collection);
        // Para evaluar where_expr sobre el contenido real, descifra primero si aplica.
        if ($encrypted) {
            $all = \array_map(fn($d) => $vault->decryptDoc($d), $all);
        }

        $updated = 0;
        $ids     = [];
        foreach ($all as $doc) {
            if (!\Axi\Sql\WhereEvaluator::matches($doc, $this->params['where_expr'])) {
                continue;
            }
            $id = $doc['_id'] ?? $doc['id'] ?? null;
            if ($id === null) { continue; }
            $payload = $this->params['data'];
            if (!empty($this->params['replace'])) {
                $payload['_REPLACE_'] = true;
            }
            // Re-cifrar el merge resultante si la coleccion lo requiere.
            if ($encrypted) {
                $merged = \array_merge($doc, $payload, ['_id' => $id]);
                unset($merged['_REPLACE_']);
                $payload = $vault->encryptDoc($merged);
                $payload['_REPLACE_'] = true;             // sustituir al disco
            }
            $storage->update($this->collection, $id, $payload);
            $ids[] = $id;
            $updated++;
        }
        return Result::ok(['updated' => $updated, 'ids' => $ids]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'update',
            synopsis:    'Axi\\Op\\Update(collection) ->id("...") ->data([...]) [->replace()]',
            description: 'Modifica un documento existente. Por defecto hace merge con los campos nuevos; con replace() sustituye el documento entero.',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true],
                ['name' => 'id',         'type' => 'string', 'required' => true],
                ['name' => 'data',       'type' => 'object', 'required' => true],
                ['name' => 'replace',    'type' => 'bool',   'required' => false, 'default' => false, 'description' => 'true = sustituye; false = merge.'],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Op\\Update('notas'))\n    ->id('abc123')->data(['body' => 'nuevo']));"],
                ['lang' => 'json',   'code' => '{"op":"update","collection":"notas","id":"abc123","data":{"body":"nuevo"}}'],
                ['lang' => 'axisql', 'code' => "UPDATE notas SET body = 'nuevo' WHERE id = 'abc123'"],
                ['lang' => 'cli',    'code' => 'axi update notas abc123 --set \'{"body":"nuevo"}\''],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED,  'when' => 'id o data ausentes.'],
                ['code' => AxiException::DOCUMENT_NOT_FOUND, 'when' => 'id no existe y modo strict.'],
            ],
            related: ['Insert', 'Delete', 'Select'],
        );
    }
}
