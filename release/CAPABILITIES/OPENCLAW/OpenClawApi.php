<?php
/**
 * OpenClawApi — dispatcher de acciones OPENCLAW.
 *
 * openclaw_manifest  → devuelve el skill manifest (público, para importar en OpenClaw)
 * openclaw_call      → OpenClaw llama aquí con una herramienta + params (auth por X-MyLocal-Skill-Key)
 * openclaw_status    → estado de la integración (requiere admin)
 * openclaw_event_push → empuja un evento manualmente al agente (requiere admin)
 */

declare(strict_types=1);

namespace OpenClaw;

function handle_openclaw_capability(string $action, array $req, array $user, array $headers): array
{
    return match ($action) {
        'openclaw_manifest'   => _oc_manifest(),
        'openclaw_call'       => _oc_call($req, $headers),
        'openclaw_status'     => _oc_status($user),
        'openclaw_event_push' => _oc_event_push($req, $user),
        default               => ['success' => false, 'error' => "Acción desconocida: {$action}"],
    };
}

function _oc_manifest(): array
{
    return ['success' => true, 'manifest' => OpenClawSkillManifest::get()];
}

function _oc_call(array $req, array $headers): array
{
    // Validar clave del skill (header X-MyLocal-Skill-Key o req.skill_key)
    $providedKey = $headers['X-MyLocal-Skill-Key']
        ?? $headers['x-mylocal-skill-key']
        ?? $req['skill_key']
        ?? '';

    if (!OpenClawSkillExecutor::validateKey($providedKey)) {
        http_response_code(401);
        return ['success' => false, 'error' => 'Clave de skill inválida o no configurada'];
    }

    $tool   = s_str($req['tool'] ?? '', 60);
    $params = (array) ($req['params'] ?? []);

    if ($tool === '') return ['success' => false, 'error' => 'tool requerido'];

    // Usuario sintetico del agente. El admin configura el rol y el local_id
    // por defecto en OPTIONS; sin esa config el agente actua como visitante
    // anonimo y los handlers aplicaran sus chequeos de role por accion.
    require_once __DIR__ . '/../OPTIONS/optiosconect.php';
    $opt = mylocal_options();
    $agentUser = [
        'id'       => 'u_openclaw_agent',
        'role'     => (string) $opt->get('openclaw.agent_role', 'admin'),
        'local_id' => (string) $opt->get('openclaw.default_local_id', ''),
    ];

    try {
        return OpenClawSkillExecutor::execute($tool, $params, $agentUser);
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function _oc_status(array $user): array
{
    $role = $user['role'] ?? '';
    if (!in_array($role, ['admin', 'superadmin', 'administrador'], true)) {
        return ['success' => false, 'error' => 'Acceso no autorizado'];
    }
    require_once __DIR__ . '/../OPTIONS/optiosconect.php';
    $opt = mylocal_options();
    return [
        'success'             => true,
        'skill_configured'    => $opt->get('openclaw.skill_api_key', '') !== '',
        'push_configured'     => $opt->get('openclaw.push_url', '') !== '',
        'push_url'            => $opt->get('openclaw.push_url', ''),
        'push_channel'        => $opt->get('openclaw.push_channel', 'default'),
        'default_local_id'    => $opt->get('openclaw.default_local_id', ''),
        'tools_available'     => array_column(
            OpenClawSkillManifest::get()['tools'],
            'name'
        ),
    ];
}

function _oc_event_push(array $req, array $user): array
{
    $role = $user['role'] ?? '';
    if (!in_array($role, ['admin', 'superadmin', 'administrador'], true)) {
        return ['success' => false, 'error' => 'Acceso no autorizado'];
    }
    if (!OpenClawPushClient::isConfigured()) {
        return ['success' => false, 'error' => 'openclaw.push_url no configurada'];
    }

    $event   = s_str($req['event'] ?? '', 80);
    $message = s_str($req['message'] ?? '', 2000);

    if ($message === '' && $event === '') {
        return ['success' => false, 'error' => 'message o event requerido'];
    }

    $client = OpenClawPushClient::fromOptions();
    if ($message !== '') {
        return $client->send($message, ['manual' => true]);
    }
    return $client->pushEvent($event, (array) ($req['data'] ?? []));
}
