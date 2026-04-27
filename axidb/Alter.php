<?php
/**
 * AxiDB - Axi\Alter: facade para los Ops de schema bajo una API unificada.
 *
 * Subsistema: sugar
 * Responsable: exponer los 9 Ops de Alter bajo metodos estaticos legibles
 *              tipo `Axi\Alter::table(...)`, `Axi\Alter::field(...)`, etc.
 * Uso:
 *     $db->execute(Axi\Alter::createTable('notas')->flags(['keep_versions' => true]));
 *     $db->execute(Axi\Alter::dropTable('tmp'));
 *     $db->execute(Axi\Alter::addField('notas')->field('pinned', 'bool', false, false));
 *     $db->execute(Axi\Alter::createIndex('users')->field('email', true));
 */

namespace Axi;

use Axi\Engine\Op\Alter\AddField;
use Axi\Engine\Op\Alter\AlterCollection;
use Axi\Engine\Op\Alter\CreateCollection;
use Axi\Engine\Op\Alter\CreateIndex;
use Axi\Engine\Op\Alter\DropCollection;
use Axi\Engine\Op\Alter\DropField;
use Axi\Engine\Op\Alter\DropIndex;
use Axi\Engine\Op\Alter\RenameCollection;
use Axi\Engine\Op\Alter\RenameField;

final class Alter
{
    public static function createTable(string $collection): CreateCollection
    {
        return new CreateCollection($collection);
    }

    public static function dropTable(string $collection): DropCollection
    {
        return new DropCollection($collection);
    }

    public static function alterTable(string $collection): AlterCollection
    {
        return new AlterCollection($collection);
    }

    public static function renameTable(string $from): RenameCollection
    {
        return new RenameCollection($from);
    }

    public static function addField(string $collection): AddField
    {
        return new AddField($collection);
    }

    public static function dropField(string $collection): DropField
    {
        return new DropField($collection);
    }

    public static function renameField(string $collection): RenameField
    {
        return new RenameField($collection);
    }

    public static function createIndex(string $collection): CreateIndex
    {
        return new CreateIndex($collection);
    }

    public static function dropIndex(string $collection): DropIndex
    {
        return new DropIndex($collection);
    }
}
