<?php
namespace QR;

/**
 * QrTokenGenerator - genera tokens cortos no adivinables para mesas.
 *
 * Diseño:
 *  - 16 caracteres hex (8 bytes random) = 64 bits de entropía.
 *  - URL final: <slug>.mylocal.es/m/<token>
 *  - El token NO contiene el id de la mesa para que no se pueda inferir
 *    el orden ni hacer enumeration attacks.
 *  - Si alguien copia un QR (foto del cartel), el dueño puede regenerar
 *    el token en un clic y el QR antiguo deja de funcionar.
 *
 * Uso:
 *   $tok = QrTokenGenerator::generate();   // "a3f9c2e7b1d04568"
 *   $url = QrTokenGenerator::buildUrl('elbar', $tok, 'mylocal.es');
 */
class QrTokenGenerator
{
    /** Genera un token hex de 16 chars. */
    public static function generate(): string
    {
        return bin2hex(random_bytes(8));
    }

    /** Valida que un token tiene el formato correcto. */
    public static function isValid(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{16}$/', $token);
    }

    /**
     * Construye la URL pública de la mesa.
     * Si $domain es null, usa una ruta relativa que funciona en cualquier
     * deploy (raíz, subdirectorio, IP local, etc.).
     */
    public static function buildUrl(string $localSlug, string $token, ?string $domain = null): string
    {
        if (!self::isValid($token)) {
            throw new \InvalidArgumentException('Token QR inválido');
        }
        if ($domain) {
            return "https://{$localSlug}.{$domain}/m/{$token}";
        }
        // Fallback agnóstico: ruta relativa al dominio actual.
        return "/m/{$token}";
    }
}
