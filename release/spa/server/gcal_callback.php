<?php
/**
 * Callback OAuth2 de Google Calendar.
 *
 * Google redirige aquí tras autorizar: GET ?code=...&state=localId:nonce
 * 1. Intercambia el code por tokens.
 * 2. Guarda tokens en AxiDB (colección gcal_tokens).
 * 3. Redirige al dashboard con ?gcal=connected o ?gcal_error=...
 *
 * La redirect_uri configurada en Google Cloud Console debe apuntar aquí:
 *   https://tu-dominio.com/server/gcal_callback.php
 */

define('CORE_ROOT',    dirname(__DIR__, 2));
define('AXI_ROOT',     CORE_ROOT . '/axidb');
define('STORAGE_ROOT', CORE_ROOT . '/STORAGE');

require_once CORE_ROOT . '/axidb/axi.php';
require_once CORE_ROOT . '/CAPABILITIES/GCAL/GoogleOAuthStore.php';

$code  = (string) ($_GET['code']  ?? '');
$state = (string) ($_GET['state'] ?? '');
$error = (string) ($_GET['error'] ?? '');

// Google canceló el flujo
if ($error !== '') {
    header('Location: /dashboard?gcal_error=' . urlencode($error));
    exit;
}

if ($code === '' || $state === '') {
    header('Location: /dashboard?gcal_error=missing_params');
    exit;
}

// state = "localId:nonce"
$parts   = explode(':', $state, 2);
$localId = $parts[0] ?? '';

if ($localId === '') {
    header('Location: /dashboard?gcal_error=invalid_state');
    exit;
}

try {
    \GCal\GoogleOAuthStore::exchangeCode($localId, $code, $state);
    header('Location: /dashboard?gcal=connected');
} catch (\Throwable $e) {
    error_log('[gcal_callback] ' . $e->getMessage());
    header('Location: /dashboard?gcal_error=' . urlencode($e->getMessage()));
}
exit;
