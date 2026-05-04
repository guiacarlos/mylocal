<?php
/**
 * AxiDB - Op\Insert: alta de documento en una coleccion.
 *
 * Subsistema: engine/op
 * Responsable: escribir un nuevo documento. Si se da id, se usa; si no, se
 *              genera uno ordenable por tiempo (ULID-like).
 * Entrada:    collection, data (obligatorios); id (opcional).
 * Salida:     Result con el documento persistido (incluye _id, _version,
 *             _createdAt, _updatedAt generados por el motor).
 */

namespace Axi\Engine\Op;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Result;

class Insert extends Operation
{
    public const OP_NAME = 'insert';

    public function data(array $data): self
    {
        $this->params['data'] = $data;
        return $this;
    }

    public function id(string $id): self
    {
        $this->params['id'] = $id;
        return $this;
    }

    public function validate(): void
    {
        $this->requireCollection();

        if (!isset($this->params['data']) || !\is_array($this->params['data'])) {
            throw new AxiException(
                "Insert: param 'data' requerido y debe ser array asociativo.",
                AxiException::VALIDATION_FAILED
            );
        }
        if ($this->params['data'] === []) {
            throw new AxiException(
                "Insert: 'data' no puede estar vacio.",
                AxiException::VALIDATION_FAILED
            );
        }
        if (isset($this->params['id']) && !\is_string($this->params['id'])) {
            throw new AxiException(
                "Insert: 'id' debe ser string si se proporciona.",
                AxiException::VALIDATION_FAILED
            );
        }
    }

    public function execute(object $engine): Result
    {
        $storage = $engine->getService('storage');
        if (!$storage) {
            throw new AxiException("StorageManager service no disponible.", AxiException::INTERNAL_ERROR);
        }

        $id = $this->params['id'] ?? self::generateId();
        $data = \array_merge(['_id' => $id], $this->params['data']);

        // Cifrado transparente: si la coleccion tiene flag encrypted, ciframos.
        $meta = $engine->getService('meta');
        if ($meta && ($meta->readMeta($this->collection)['flags']['encrypted'] ?? false)) {
            $vault = $engine->getService('vault');
            $data  = $vault->encryptDoc($data);
        }

        $doc = $storage->update($this->collection, $id, $data);

        // Si el doc persistido viene cifrado, lo desciframos para devolverlo en claro al cliente.
        if (isset($doc['_enc'])) {
            $vault = $engine->getService('vault');
            $doc = $vault->decryptDoc($doc);
        }
        if (!isset($doc['_id'])) {
            $doc['_id'] = $id;
        }

        return Result::ok($doc);
    }

    /**
     * Id ordenable por tiempo: YYYYMMDDHHMMSS + 8 hex random.
     * Fase 1.4 lo sustituye por Axi\Engine\IdGenerator con ULID completo.
     */
    private static function generateId(): string
    {
        return \date('YmdHis') . \bin2hex(\random_bytes(4));
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'insert',
            synopsis:    'Axi\\Op\\Insert(collection) ->data([...]) [->id("...")]',
            description: 'Alta de un nuevo documento. Si se omite el id, el motor lo genera (ordenable por tiempo).',
            params: [
                ['name' => 'collection', 'type' => 'string', 'required' => true,  'description' => 'Nombre de la coleccion destino.'],
                ['name' => 'data',       'type' => 'object', 'required' => true,  'description' => 'Mapa field=>value con los datos del nuevo documento.'],
                ['name' => 'id',         'type' => 'string', 'required' => false, 'description' => 'Id explicito. Si se omite, se genera automaticamente.'],
            ],
            examples: [
                ['lang' => 'php',    'code' => "\$db->execute((new Axi\\Op\\Insert('notas'))\n    ->data(['title' => 'test', 'body' => 'hola']));"],
                ['lang' => 'json',   'code' => '{"op":"insert","collection":"notas","data":{"title":"test","body":"hola"}}'],
                ['lang' => 'axisql', 'code' => "INSERT INTO notas (title, body) VALUES ('test', 'hola')"],
                ['lang' => 'cli',    'code' => 'axi insert notas --data \'{"title":"test","body":"hola"}\''],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'data ausente/vacia, id no-string, collection vacia.'],
                ['code' => AxiException::CONFLICT,          'when' => 'id especificado ya existe y modo strict.'],
            ],
            related: ['Update', 'Delete', 'Select'],
        );
    }
}
