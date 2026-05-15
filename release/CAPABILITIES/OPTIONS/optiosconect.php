<?php
/**
 * optiosconect.php - alias / punto de entrada solicitado por el usuario.
 * Carga el conector real de OPTIONS y expone una funcion helper global
 * `mylocal_options()` para acceder a la configuracion desde cualquier
 * parte sin tener que importar la clase.
 *
 * Uso:
 *   require_once __DIR__ . '/CAPABILITIES/OPTIONS/optiosconect.php';
 *   $key = mylocal_options()->get('ai.api_key');
 */

require_once __DIR__ . '/OptionsConnector.php';

if (!function_exists('mylocal_options')) {
    /**
     * Singleton del conector de opciones.
     */
    function mylocal_options(?string $storageRoot = null): \OPTIONS\OptionsConnector
    {
        static $instance = null;
        if ($instance === null || $storageRoot !== null) {
            $instance = new \OPTIONS\OptionsConnector($storageRoot);
        }
        return $instance;
    }
}
