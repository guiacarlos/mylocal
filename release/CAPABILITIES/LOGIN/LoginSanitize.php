<?php
namespace Login;

/**
 * LoginSanitize - sanitizadores load-bearing.
 *
 * id() es la unica defensa contra path traversal cuando un valor recibido
 * por API se usa como nombre de fichero. Si esto se debilita, un atacante
 * puede leer/escribir archivos arbitrarios via collection IDs. Lanza
 * RuntimeException ante input invalido para que el dispatcher devuelva
 * un error claro y nunca avance con un valor envenenado.
 *
 * Esta es la fuente canonica desde el paso 2 de la migracion. Los
 * wrappers globales s_id / s_str / s_email / s_int en lib.php delegan
 * aqui.
 */
class LoginSanitize
{
    public static function id(string $v): string
    {
        $v = preg_replace('/[^a-zA-Z0-9_\-]/', '', $v) ?? '';
        if ($v === '' || str_contains($v, '..')) {
            throw new \RuntimeException('Identificador invalido');
        }
        return $v;
    }

    public static function str(?string $v, int $max = 2000): string
    {
        $v = (string) ($v ?? '');
        $v = str_replace(["\0"], '', $v);
        if (mb_strlen($v) > $max) $v = mb_substr($v, 0, $max);
        return trim($v);
    }

    public static function email(?string $v): string
    {
        $v = strtolower(trim((string) ($v ?? '')));
        if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Email invalido');
        }
        if (strlen($v) > 254) throw new \RuntimeException('Email demasiado largo');
        return $v;
    }

    public static function int($v, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
    {
        $i = (int) $v;
        if ($i < $min || $i > $max) throw new \RuntimeException('Numero fuera de rango');
        return $i;
    }
}
