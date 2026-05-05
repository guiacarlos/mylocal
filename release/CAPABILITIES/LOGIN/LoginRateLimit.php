<?php
namespace Login;

require_once __DIR__ . '/LoginSanitize.php';

/**
 * LoginRateLimit - control de tasa por IP+scope.
 *
 * Buckets persistidos en STORAGE/data/_rl/<scope>/<ip>.json. Misma ruta
 * que usaba lib.php::rl_check, asi los buckets activos sobreviven a la
 * migracion.
 *
 * Si el limite se supera emite HTTP 429 + JSON {success:false} via resp()
 * y termina. El handler nunca llega a ejecutarse.
 *
 * IMPORTANTE: depende de la constante DATA_ROOT y de la funcion resp()
 * declaradas en spa/server/lib.php. Esta capability se carga DESPUES de
 * lib.php; si se llama antes, lanza LogicException para fallar fuerte
 * en lugar de saltarse el rate-limit silenciosamente.
 */
class LoginRateLimit
{
    public static function check(string $scope, int $perMinute): void
    {
        if ($perMinute <= 0) return;
        if (!defined('DATA_ROOT')) {
            throw new \LogicException('LoginRateLimit requiere DATA_ROOT (lib.php)');
        }
        $ip = preg_replace(
            '/[^0-9a-fA-F:.]/',
            '_',
            (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')
        ) ?: 'unknown';
        $dir = DATA_ROOT . '/_rl/' . LoginSanitize::id($scope);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file = $dir . '/' . $ip . '.json';

        $now = time();
        $window = $now - ($now % 60);
        $state = ['window' => $window, 'count' => 0];
        if (file_exists($file)) {
            $raw = file_get_contents($file);
            $parsed = json_decode($raw, true);
            if (is_array($parsed)) {
                $state = ($parsed['window'] ?? 0) === $window ? $parsed : $state;
            }
        }
        $state['count'] = ((int) $state['count']) + 1;
        file_put_contents($file, json_encode($state), LOCK_EX);

        if ($state['count'] > $perMinute) {
            http_response_code(429);
            if (function_exists('resp')) {
                resp(false, null, 'Rate limit: demasiadas peticiones');
            }
            exit;
        }
    }
}
