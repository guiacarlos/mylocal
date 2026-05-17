<?php
/**
 * Legales handler.
 *
 * Dos modos:
 *   local_id = '' | 'mylocal' → sirve documentos de empresa (GESTASAI / MyLocal)
 *   local_id = <slug>         → sirve documentos generados por LegalGenerator para ese local
 *
 * Acciones:
 *   get_legal          (pública) → contenido Markdown de un documento
 *   list_legales       (pública) → lista de documentos disponibles (sin contenido)
 *   regenerate_legales (auth)    → regenera los docs del local con los datos actuales
 */

declare(strict_types=1);

require_once realpath(__DIR__ . '/../../../CAPABILITIES/LEGAL/LegalGenerator.php');
require_once realpath(__DIR__ . '/../../../CAPABILITIES/LEGAL/CompanyLegal.php');

function handle_legales(string $action, array $req): array
{
    return match ($action) {
        'get_legal'          => legales_get($req),
        'list_legales'       => legales_list($req),
        'regenerate_legales' => legales_regenerate($req),
        default              => throw new RuntimeException("Acción legales no reconocida: $action"),
    };
}

/**
 * Resuelve un slug (ej. "lacocinadeana") al ID interno (ej. "l_abc123").
 * Necesario cuando el visitante llega desde el seed del subdominio,
 * que devuelve el slug, no el ID interno con el que se guardan los legales.
 */
function legales_resolve_local_id(string $localId): string
{
    if ($localId === '' || $localId === 'mylocal') return $localId;
    if (strncmp($localId, 'l_', 2) === 0) return $localId; // ya es ID interno
    foreach (data_all('locales') as $local) {
        if (($local['slug'] ?? '') === $localId) return (string)($local['id'] ?? $localId);
    }
    return $localId;
}

function legales_get(array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = s_str($data['local_id'] ?? '');
    $doc     = s_str($data['doc']      ?? '');

    if ($doc === '') throw new RuntimeException('doc obligatorio');

    if ($localId === '' || $localId === 'mylocal') {
        $company = \Legal\CompanyLegal::get($doc);
        if ($company) return $company;
        throw new RuntimeException('Documento de empresa no encontrado: ' . $doc);
    }

    // Resolver slug → ID interno (visitantes públicos llegan con el slug del subdominio)
    $localId = legales_resolve_local_id($localId);
    $legal = \Legal\LegalGenerator::get($localId, $doc);
    if (!$legal) throw new RuntimeException('Documento no encontrado');
    return $legal;
}

function legales_list(array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = s_str($data['local_id'] ?? '');

    if ($localId === '' || $localId === 'mylocal') {
        return ['items' => \Legal\CompanyLegal::list(), 'tipo' => 'company'];
    }

    $localId = legales_resolve_local_id($localId);
    return ['items' => \Legal\LegalGenerator::list($localId), 'tipo' => 'local'];
}

function legales_regenerate(array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = s_str($data['local_id'] ?? '');
    if ($localId === '') throw new RuntimeException('local_id obligatorio');

    $localId = legales_resolve_local_id($localId);
    $local   = data_get('locales', $localId);
    if (!$local) throw new RuntimeException('Local no encontrado');

    // Formatear dirección estructurada o string legacy
    $dir = $local['direccion'] ?? '';
    if (is_array($dir)) {
        $dir = trim(
            ($dir['calle']    ?? '') . ' ' . ($dir['numero']    ?? '') . ', ' .
            ($dir['cp']       ?? '') . ' ' . ($dir['ciudad']    ?? '') .
            (!empty($dir['provincia']) ? ' (' . $dir['provincia'] . ')' : '')
        );
    }

    \Legal\LegalGenerator::generateForLocal(
        $localId,
        (string) ($local['nombre']   ?? ''),
        (string) ($local['email']    ?? $local['owner_email'] ?? ''),
        (string) ($local['slug']     ?? $localId),
        (string) $dir,
        (string) ($local['telefono'] ?? '')
    );
    return ['ok' => true, 'docs' => \Legal\LegalGenerator::DOCS];
}
