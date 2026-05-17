<?php
namespace Login;

/**
 * LoginRegister — registro de nuevos hosteleros (slug + usuario + local).
 *
 * Acciones:
 *   validate_slug   — pública — {available: bool, reason: string}
 *   register_local  — pública — crea usuario+local, devuelve sesión
 */

require_once __DIR__ . '/LoginCapability.php';

const RESERVED_SLUGS = [
    'admin', 'dashboard', 'api', 'www', 'mail', 'ftp', 'cdn',
    'acide', 'mylocal', 'demo', 'test', 'staging', 'panel',
    'support', 'help', 'docs', 'blog', 'shop', 'carta',
    'registro', 'acceder',
];

class LoginRegister
{
    private const SLUG_RE = '/^[a-z][a-z0-9\-]{2,30}$/';

    /**
     * Comprueba disponibilidad del slug.
     * Sin autenticación. Idempotente.
     */
    public static function validateSlug(string $slug): array
    {
        $slug = strtolower(trim($slug));

        if (!preg_match(self::SLUG_RE, $slug)) {
            return [
                'available' => false,
                'reason'    => 'formato_invalido',
                'hint'      => 'Minúsculas, números y guiones. 3-31 caracteres, empieza por letra.',
            ];
        }

        if (in_array($slug, RESERVED_SLUGS, true)) {
            return ['available' => false, 'reason' => 'reservado'];
        }

        // Comprueba colisión en AxiDB
        if (function_exists('data_all')) {
            foreach (data_all('locales') as $local) {
                if (($local['slug'] ?? '') === $slug) {
                    return ['available' => false, 'reason' => 'ocupado'];
                }
            }
        }

        return ['available' => true, 'reason' => 'ok'];
    }

    /**
     * Registro completo: usuario + local. Devuelve token de sesión.
     * Lanza RuntimeException con código legible en caso de error.
     */
    public static function registerLocal(array $req): array
    {
        LoginRateLimit::check('register_local', 5);

        $data     = $req['data'] ?? $req;
        $slug     = strtolower(trim((string) ($data['slug'] ?? '')));
        $email    = LoginSanitize::email($data['email'] ?? null);
        $password = (string) ($data['password'] ?? '');
        $nombre   = LoginSanitize::str((string) ($data['nombre'] ?? ''), 120);

        $slugCheck = self::validateSlug($slug);
        if (!$slugCheck['available']) {
            throw new \RuntimeException('slug_' . $slugCheck['reason']);
        }

        LoginPasswords::assertStrength($password);

        if (LoginVault::findByEmail($email)) {
            throw new \RuntimeException('email_ocupado');
        }

        // Crear usuario con rol hostelero
        $userId = 'u_' . bin2hex(random_bytes(8));
        $user = LoginVault::upsert([
            'id'            => $userId,
            'email'         => $email,
            'name'          => $nombre !== '' ? $nombre : explode('@', $email)[0],
            'role'          => 'hostelero',
            'password_hash' => LoginPasswords::hash($password),
        ]);
        unset($user['password_hash']);

        // Crear local en AxiDB
        $localId = 'l_' . bin2hex(random_bytes(8));
        if (function_exists('data_put')) {
            $localNombre = $nombre !== '' ? $nombre : ucfirst(str_replace('-', ' ', $slug));
            data_put('locales', $localId, [
                'id'            => $localId,
                'slug'          => $slug,
                'nombre'        => $localNombre,
                'owner_user_id' => $userId,
                'members'       => [['user_id' => $userId, 'role' => 'admin']],
                'plan'          => 'demo',
                'demo_started'  => date('c'),
                'created_at'    => date('c'),
                'updated_at'    => date('c'),
            ], true);
        }

        // Generar documentos legales automáticos (RGPD/LSSI) para el local
        $legalFile = realpath(__DIR__ . '/../../CAPABILITIES/LEGAL/LegalGenerator.php');
        if ($legalFile && file_exists($legalFile)) {
            require_once $legalFile;
            \Legal\LegalGenerator::generateForLocal(
                $localId,
                $localNombre,
                $email,
                $slug
            );
        }

        $sess = LoginSessions::issue($user);

        return [
            'user'     => $user,
            'token'    => $sess['token'],
            'local_id' => $localId,
            'slug'     => $slug,
        ];
    }
}
