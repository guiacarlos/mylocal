<?php
/**
 * AxiDB - Op\System\Ping: health check.
 *
 * Subsistema: engine/op/system
 * Salida:     Result con {status, engine, timestamp, storage, services}.
 */

namespace Axi\Engine\Op\System;

use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Ping extends Operation
{
    public const OP_NAME = 'ping';

    public function validate(): void
    {
        // No requiere params.
    }

    public function execute(object $engine): Result
    {
        if (\method_exists($engine, 'healthCheck')) {
            return Result::ok($engine->healthCheck());
        }
        return Result::ok([
            'status'    => 'online',
            'engine'    => 'AxiDB v1.0-dev',
            'timestamp' => \date('c'),
        ]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'ping',
            synopsis:    'Axi\\Op\\System\\Ping()',
            description: 'Health check del motor. Devuelve estado y servicios activos. Sin params.',
            params:      [],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute(new Axi\\Op\\System\\Ping());"],
                ['lang' => 'json', 'code' => '{"op":"ping"}'],
                ['lang' => 'cli',  'code' => 'axi ping'],
            ],
            errors:  [],
            related: ['Describe', 'Schema'],
        );
    }
}
