<?php
namespace GCal;

/**
 * GoogleOAuthStore — persiste y renueva tokens OAuth2 de Google por local.
 *
 * Colección AxiDB: gcal_tokens   (un documento por localId)
 * Config requerida en OPTIONS (namespace 'gcal'):
 *   gcal.client_id     → Google Cloud Console → OAuth2 → Client ID
 *   gcal.client_secret → Google Cloud Console → OAuth2 → Client Secret
 *   gcal.redirect_uri  → URL de callback en tu dominio (debe coincidir con GCC)
 *
 * Flujo completo:
 *   1. getAuthUrl($localId)  → URL de Google para autorizar
 *   2. exchangeCode($localId, $code) → guarda access_token + refresh_token
 *   3. getAccessToken($localId) → devuelve token vigente (refresca si expiró)
 *   4. disconnect($localId)  → elimina tokens
 */
class GoogleOAuthStore
{
    private const COLLECTION  = 'gcal_tokens';
    private const AUTH_URL    = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL   = 'https://oauth2.googleapis.com/token';
    private const SCOPE       = 'https://www.googleapis.com/auth/calendar';
    private const MARGIN_SECS = 120; // renovar token 2 min antes de que expire

    // ──────────────────────────── Config ────────────────────────────

    private static function cfg(): array
    {
        if (!function_exists('data_get')) {
            throw new \RuntimeException('GoogleOAuthStore requiere data_get (lib.php)');
        }
        $cfg = data_get('config', 'gcal_settings') ?: [];
        foreach (['client_id', 'client_secret', 'redirect_uri'] as $key) {
            if (empty($cfg[$key])) {
                throw new \RuntimeException("Google Calendar no configurado: falta gcal_settings.$key en OPTIONS");
            }
        }
        return $cfg;
    }

    // ──────────────────────────── OAuth2 ────────────────────────────

    /**
     * Genera la URL de autorización de Google + state CSRF para el local.
     * El state se guarda en AxiDB para verificarlo en el callback.
     */
    public static function getAuthUrl(string $localId): string
    {
        $cfg   = self::cfg();
        $state = bin2hex(random_bytes(16));

        data_put(self::COLLECTION, $localId . '_state', [
            'id'         => $localId . '_state',
            'local_id'   => $localId,
            'state'      => $state,
            'expires_at' => time() + 600, // 10 min para completar el OAuth
        ], true);

        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => $cfg['client_id'],
            'redirect_uri'  => $cfg['redirect_uri'],
            'response_type' => 'code',
            'scope'         => self::SCOPE,
            'access_type'   => 'offline',
            'prompt'        => 'consent', // fuerza refresh_token aunque ya esté conectado
            'state'         => $localId . ':' . $state,
        ]);
    }

    /**
     * Intercambia el authorization code por access_token + refresh_token.
     * Verifica el state CSRF antes de llamar a Google.
     */
    public static function exchangeCode(string $localId, string $code, string $state): void
    {
        self::verifyState($localId, $state);

        $cfg  = self::cfg();
        $resp = self::tokenRequest([
            'code'          => $code,
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'redirect_uri'  => $cfg['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ]);

        self::persistTokens($localId, $resp);
    }

    // ──────────────────────────── Token ─────────────────────────────

    /** Devuelve un access_token vigente; renueva automáticamente si expiró. */
    public static function getAccessToken(string $localId): string
    {
        $doc = self::load($localId);
        if (!$doc) {
            throw new \RuntimeException("Google Calendar no conectado para el local $localId");
        }

        if ((int)($doc['expires_at'] ?? 0) - self::MARGIN_SECS < time()) {
            $doc = self::refresh($localId, $doc);
        }

        return (string)($doc['access_token'] ?? '');
    }

    /** Comprueba si el local tiene tokens válidos almacenados. */
    public static function hasToken(string $localId): bool
    {
        $doc = self::load($localId);
        return !empty($doc['refresh_token']);
    }

    /** Devuelve estado de conexión para mostrar en UI. */
    public static function status(string $localId): array
    {
        $doc = self::load($localId);
        if (!$doc || empty($doc['refresh_token'])) {
            return ['connected' => false];
        }
        return [
            'connected'  => true,
            'email'      => $doc['google_email'] ?? null,
            'calendar'   => $doc['calendar_id'] ?? 'primary',
            'expires_at' => $doc['expires_at'] ?? null,
        ];
    }

    /** Elimina los tokens del local (desconectar). */
    public static function disconnect(string $localId): void
    {
        if (function_exists('data_delete')) {
            data_delete(self::COLLECTION, $localId);
        }
    }

    // ──────────────────────────── Private ────────────────────────────

    private static function load(string $localId): ?array
    {
        if (!function_exists('data_get')) return null;
        return data_get(self::COLLECTION, $localId) ?: null;
    }

    private static function refresh(string $localId, array $doc): array
    {
        $cfg  = self::cfg();
        $resp = self::tokenRequest([
            'refresh_token' => $doc['refresh_token'],
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'grant_type'    => 'refresh_token',
        ]);

        // Google no siempre devuelve un nuevo refresh_token en el refresco
        if (empty($resp['refresh_token'])) {
            $resp['refresh_token'] = $doc['refresh_token'];
        }

        return self::persistTokens($localId, $resp);
    }

    private static function persistTokens(string $localId, array $tokenResp): array
    {
        if (empty($tokenResp['access_token'])) {
            throw new \RuntimeException('Google OAuth2: respuesta sin access_token — ' . json_encode($tokenResp));
        }

        $doc = array_merge(
            self::load($localId) ?? [],
            [
                'id'            => $localId,
                'local_id'      => $localId,
                'access_token'  => $tokenResp['access_token'],
                'refresh_token' => $tokenResp['refresh_token'] ?? (self::load($localId)['refresh_token'] ?? ''),
                'expires_at'    => time() + (int)($tokenResp['expires_in'] ?? 3600),
                'token_type'    => $tokenResp['token_type'] ?? 'Bearer',
                'google_email'  => $tokenResp['email'] ?? null,
                'calendar_id'   => 'primary',
                'updated_at'    => date('c'),
            ]
        );

        data_put(self::COLLECTION, $localId, $doc, true);
        return $doc;
    }

    private static function tokenRequest(array $params): array
    {
        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \RuntimeException("Google OAuth2 cURL error: $err");

        $resp = json_decode((string)$raw, true) ?: [];
        if ($code !== 200) {
            throw new \RuntimeException('Google OAuth2 error: ' . ($resp['error_description'] ?? $resp['error'] ?? "HTTP $code"));
        }

        return $resp;
    }

    private static function verifyState(string $localId, string $incomingState): void
    {
        if (!function_exists('data_get') || !function_exists('data_delete')) return;

        $doc = data_get(self::COLLECTION, $localId . '_state');
        if (!$doc || ($doc['expires_at'] ?? 0) < time()) {
            throw new \RuntimeException('OAuth2 state expirado o no encontrado');
        }

        $expected = $localId . ':' . ($doc['state'] ?? '');
        if (!hash_equals($expected, $incomingState)) {
            throw new \RuntimeException('OAuth2 state inválido (posible CSRF)');
        }

        data_delete(self::COLLECTION, $localId . '_state');
    }
}
