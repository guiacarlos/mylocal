<?php
/**
 * SubdomainManager — identifica el local activo en cada request.
 *
 * Prioridad:
 *   1. Header X-Local-Id  (override dev / admin)
 *   2. Subdominio *.mylocal.es
 *   3. Default "mylocal"  (landing corporativa)
 *
 * Define la constante CURRENT_LOCAL_SLUG una sola vez.
 * Expone la función global get_current_local_id().
 */

class SubdomainManager
{
    private static ?string $resolved = null;

    public static function detect(): string
    {
        if (self::$resolved !== null) return self::$resolved;

        // 1. Header de override (dev / admin)
        $h = $_SERVER['HTTP_X_LOCAL_ID'] ?? '';
        if ($h !== '') {
            $s = self::sanitize($h);
            if ($s !== '') return self::commit($s);
        }

        // 2. Subdominio .mylocal.es
        $host = strtolower(trim($_SERVER['HTTP_HOST'] ?? ''));
        if (preg_match('/^([a-z0-9][a-z0-9\-]{1,29})\.mylocal\.es$/', $host, $m)) {
            if ($m[1] !== 'www') return self::commit($m[1]);
        }

        // 3. Default: landing corporativa
        return self::commit('mylocal');
    }

    private static function sanitize(string $s): string
    {
        $s = strtolower(trim($s));
        return preg_match('/^[a-z][a-z0-9\-]{1,29}$/', $s) ? $s : '';
    }

    private static function commit(string $slug): string
    {
        self::$resolved = $slug;
        if (!defined('CURRENT_LOCAL_SLUG')) define('CURRENT_LOCAL_SLUG', $slug);
        return $slug;
    }
}

function get_current_local_id(): string
{
    return defined('CURRENT_LOCAL_SLUG') ? CURRENT_LOCAL_SLUG : SubdomainManager::detect();
}
