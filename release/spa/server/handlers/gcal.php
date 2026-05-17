<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../../../CAPABILITIES/GCAL/GoogleOAuthStore.php';

namespace GCalHandler;

function handle_gcal(string $action, array $req, array $user): array
{
    $localId = (string) ($req['local_id'] ?? $user['local_id'] ?? $user['id'] ?? '');
    if ($localId === '') {
        return ['success' => false, 'error' => 'local_id requerido'];
    }

    switch ($action) {
        case 'gcal_oauth_start':
            return ['auth_url' => \GCal\GoogleOAuthStore::getAuthUrl($localId)];

        case 'gcal_status':
            return \GCal\GoogleOAuthStore::status($localId);

        case 'gcal_disconnect':
            \GCal\GoogleOAuthStore::disconnect($localId);
            return ['disconnected' => true];

        default:
            return ['success' => false, 'error' => "Acción gcal desconocida: $action"];
    }
}
