<?php
namespace Login;

/**
 * LoginPasswordReset — recuperación de contraseña sin email transaccional.
 *
 * Flujo de operación:
 *   1. forgot_password: genera token de 6 dígitos, lo guarda en AxiDB 1 hora.
 *      El soporte (GestasAI) recupera el código y lo comparte con el hostelero
 *      por teléfono o WhatsApp.
 *   2. reset_password: valida token + email, actualiza password_hash.
 *
 * El token es de 6 dígitos (legible por voz/teléfono), no un hash largo.
 * Expira en 1 hora. Un token = un uso (se elimina tras usarse).
 */
class LoginPasswordReset
{
    private const COLLECTION = 'password_resets';
    private const TTL        = 3600; // 1 hora

    public static function requestReset(array $req): array
    {
        $email = LoginSanitize::email($req['data']['email'] ?? $req['email'] ?? null);
        if (!$email) {
            throw new \RuntimeException('Email requerido');
        }

        $user = LoginVault::findByEmail($email);
        // Siempre success — no revelar si el email existe
        if (!$user) {
            return ['ok' => true, 'message' => 'Si el email está registrado, recibirás el código por el canal de soporte.'];
        }

        $code      = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = time() + self::TTL;
        $id        = 'pr_' . bin2hex(random_bytes(8));

        if (function_exists('data_put')) {
            data_put(self::COLLECTION, $id, [
                'id'         => $id,
                'email'      => $email,
                'code'       => password_hash($code, PASSWORD_BCRYPT),
                'expires_at' => $expiresAt,
                'used'       => false,
            ], true);
        }

        // Intentar envío automático por WhatsApp si el usuario tiene teléfono
        // y WhatsApp está configurado en OPTIONS. Fallo silencioso: el flujo de
        // soporte manual sigue funcionando como fallback.
        $sentViaWhatsApp = false;
        $telefono = trim((string)($user['telefono'] ?? ''));
        if ($telefono !== '') {
            try {
                $waDriverFile = dirname(__DIR__) . '/NOTIFICACIONES/drivers/WhatsAppDriver.php';
                if (file_exists($waDriverFile)) {
                    require_once $waDriverFile;
                    $wa = \Notificaciones\Drivers\WhatsAppDriver::fromOptions();
                    $wa->send(
                        $telefono,
                        'Código de recuperación',
                        "Tu código para restablecer la contraseña de MyLocal es: *$code*\n\nExpira en 1 hora. No lo compartas con nadie."
                    );
                    $sentViaWhatsApp = true;
                }
            } catch (\Throwable $e) {
                error_log("[PasswordReset] WhatsApp falló: " . $e->getMessage());
            }
        }

        if (defined('APP_ENV') && APP_ENV === 'development') {
            error_log("[PasswordReset] Código para $email: $code (expira en 1h)");
        }

        $msg = $sentViaWhatsApp
            ? 'Código enviado a tu WhatsApp. Úsalo para restablecer la contraseña.'
            : 'Código generado. Contacta con soporte para recibirlo.';

        return ['ok' => true, 'message' => $msg];
    }

    public static function resetPassword(array $req): array
    {
        $email    = LoginSanitize::email($req['data']['email'] ?? $req['email'] ?? null);
        $code     = trim((string)($req['data']['code'] ?? $req['code'] ?? ''));
        $password = (string)($req['data']['password'] ?? $req['password'] ?? '');

        if (!$email || $code === '' || $password === '') {
            throw new \RuntimeException('Email, código y nueva contraseña son obligatorios');
        }

        LoginPasswords::assertStrength($password);

        $token = self::findValidToken($email, $code);
        if (!$token) {
            sleep(1); // timing attack mitigation
            throw new \RuntimeException('Código incorrecto o expirado');
        }

        // Marcar como usado antes de actualizar password (previene doble uso en race condition)
        if (function_exists('data_put')) {
            data_put(self::COLLECTION, $token['id'], array_merge($token, ['used' => true]), true);
        }

        $user = LoginVault::findByEmail($email);
        if (!$user) {
            throw new \RuntimeException('Usuario no encontrado');
        }

        LoginVault::updateHash($user['id'], password_hash($password, PASSWORD_ARGON2ID));

        // Eliminar el token usado
        if (function_exists('data_delete')) {
            data_delete(self::COLLECTION, $token['id']);
        }

        return ['ok' => true, 'message' => 'Contraseña actualizada. Ya puedes iniciar sesión.'];
    }

    private static function findValidToken(string $email, string $code): ?array
    {
        if (!function_exists('data_all')) {
            return null;
        }

        $now    = time();
        $resets = data_all(self::COLLECTION);

        foreach ($resets as $r) {
            if (($r['email'] ?? '') !== $email) continue;
            if (($r['used'] ?? true) === true) continue;
            if (($r['expires_at'] ?? 0) < $now) continue;
            if (!password_verify($code, $r['code'] ?? '')) continue;
            return $r;
        }

        return null;
    }
}
