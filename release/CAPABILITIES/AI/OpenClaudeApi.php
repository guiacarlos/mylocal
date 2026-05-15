<?php
/**
 * OpenClaudeApi — expone openclaude_status y openclaude_complete al dispatcher.
 *
 * openclaude_status  → devuelve si el conector está activo y el modelo usado.
 * openclaude_complete → llama a Claude con un prompt (requiere auth de admin).
 */

declare(strict_types=1);

namespace AI;

function handle_openclaude(string $action, array $req, array $user): array
{
    return match ($action) {
        'openclaude_status'   => _openclaude_status(),
        'openclaude_complete' => _openclaude_complete($req, $user),
        default               => ['success' => false, 'error' => "Acción desconocida: {$action}"],
    };
}

function _openclaude_status(): array
{
    $enabled = OpenClaudeClient::isEnabled();
    if (!$enabled) {
        return ['success' => true, 'enabled' => false, 'message' => 'openclaude.api_key no configurada'];
    }
    require_once __DIR__ . '/../OPTIONS/optiosconect.php';
    $opt   = mylocal_options();
    $model = (string) $opt->get('openclaude.model', 'claude-haiku-4-5-20251001');
    return ['success' => true, 'enabled' => true, 'model' => $model];
}

function _openclaude_complete(array $req, array $user): array
{
    $role = $user['role'] ?? '';
    if (!in_array($role, ['admin', 'superadmin'], true)) {
        return ['success' => false, 'error' => 'Acceso no autorizado'];
    }
    if (!OpenClaudeClient::isEnabled()) {
        return ['success' => false, 'enabled' => false, 'error' => 'Conector OpenClaude no configurado'];
    }
    $prompt = trim((string) ($req['prompt'] ?? ''));
    if ($prompt === '') return ['success' => false, 'error' => 'prompt requerido'];

    $system    = isset($req['system']) ? (string) $req['system'] : null;
    $maxTokens = max(1, min(4096, (int) ($req['max_tokens'] ?? 1000)));

    $ai  = OpenClaudeClient::fromOptions();
    $res = $ai->complete($prompt, $system, $maxTokens);

    if (!$res['success']) return $res;

    return [
        'success' => true,
        'text'    => $ai->extractText($res),
        'model'   => $res['model'] ?? '',
        'usage'   => $res['usage'] ?? [],
    ];
}
