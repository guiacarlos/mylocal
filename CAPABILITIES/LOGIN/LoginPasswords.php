<?php
namespace Login;

require_once __DIR__ . '/../OPTIONS/optionsLogin.php';

/**
 * LoginPasswords - hash, verify, policy, dummy_hash.
 *
 * dummy_hash() devuelve un hash precalculado para gastar el mismo tiempo
 * de CPU cuando el email no existe, evitando timing attacks que filtren
 * la existencia de usuarios.
 *
 * Stub: usara los parametros de \Options\optionsLogin cuando se active.
 */
class LoginPasswords
{
    public static function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_ARGON2ID, [
            'memory_cost' => \Options\optionsLogin::ARGON2_MEMORY_COST,
            'time_cost'   => \Options\optionsLogin::ARGON2_TIME_COST,
            'threads'     => \Options\optionsLogin::ARGON2_THREADS,
        ]);
    }

    public static function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => \Options\optionsLogin::ARGON2_MEMORY_COST,
            'time_cost'   => \Options\optionsLogin::ARGON2_TIME_COST,
            'threads'     => \Options\optionsLogin::ARGON2_THREADS,
        ]);
    }

    public static function dummyHash(): string
    {
        // Hash precalculado fijo. NO recalcular en cada request: si esa funcion
        // tarda, abre un canal de timing inverso. Usamos un Argon2id estatico
        // del mismo coste que produce hash() para gastar mismo tiempo en verify
        // sin pagar el hash en cada login fallido.
        return '$argon2id$v=19$m=65536,t=4,p=1$ZHVtbXlzYWx0ZGVsbW9t$Wq3iZ5h3Bh5d3cN9uPzGnhsv7Zz3M2pTLq0c8U+Zkm8';
    }

    public static function policyOk(string $plain): bool
    {
        if (strlen($plain) < \Options\optionsLogin::PASSWORD_MIN_LENGTH) return false;
        $classes = 0;
        if (preg_match('/[a-z]/', $plain)) $classes++;
        if (preg_match('/[A-Z]/', $plain)) $classes++;
        if (preg_match('/[0-9]/', $plain)) $classes++;
        if (preg_match('/[^a-zA-Z0-9]/', $plain)) $classes++;
        return $classes >= \Options\optionsLogin::PASSWORD_MIN_CLASSES;
    }

    /**
     * Verifica que el password cumple la politica. Lanza RuntimeException con
     * mensaje claro en caso de fallo. Usado en register publico.
     */
    public static function assertStrength(string $pw): void
    {
        $min = \Options\optionsLogin::PASSWORD_MIN_LENGTH;
        if (\strlen($pw) < $min) {
            throw new \RuntimeException("Contrasena minima $min caracteres");
        }
        if (\strlen($pw) > 200) {
            throw new \RuntimeException('Contrasena demasiado larga');
        }
        $classes = 0;
        if (preg_match('/[a-z]/', $pw)) $classes++;
        if (preg_match('/[A-Z]/', $pw)) $classes++;
        if (preg_match('/[0-9]/', $pw)) $classes++;
        if (preg_match('/[^A-Za-z0-9]/', $pw)) $classes++;
        $minClasses = \Options\optionsLogin::PASSWORD_MIN_CLASSES;
        if ($classes < $minClasses) {
            throw new \RuntimeException(
                "Contrasena debil (al menos $minClasses de: minusculas, mayusculas, numeros, simbolos)"
            );
        }
        $common = ['password', '12345678', 'qwertyuiop', 'letmein123', 'admin12345', 'socola2026'];
        if (\in_array(strtolower($pw), $common, true)) {
            throw new \RuntimeException('Contrasena en lista de contrasenas comunes');
        }
    }
}
