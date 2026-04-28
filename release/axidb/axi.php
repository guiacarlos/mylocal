<?php
/**
 * AxiDB - Helper de entrada (modo embebido).
 *
 * Subsistema: entrypoint
 * Responsable: registrar autoloader PSR-4 para namespace Axi\* y exponer
 *              la funcion global Axi() que devuelve un singleton del motor.
 * Uso:        require 'axidb/axi.php'; $db = Axi(['data_root' => '...']);
 */

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'Axi\\')) {
        return;
    }
    $parts = explode('\\', substr($class, 4));           // quita 'Axi\'
    if ($parts === []) {
        return;
    }
    $first = strtolower(array_shift($parts));            // 'Engine' -> 'engine'
    $tail  = $parts === [] ? '' : '/' . implode('/', $parts);
    $path  = __DIR__ . '/' . $first . $tail . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/engine/Axi.php';

use Axi\Engine\Axi;

function Axi(array $config = []): Axi
{
    static $instance = null;
    if ($instance === null) {
        $instance = new Axi($config);
    }
    return $instance;
}
