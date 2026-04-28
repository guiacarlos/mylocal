<?php
/**
 * AxiDB - Dashboard web entry gate.
 *
 * Subsistema: web
 * Responsable: leer config.json y decidir si servir el dashboard o devolver 404.
 *              El switch 'enabled' protege la UI en produccion sin tener que
 *              borrar archivos. Default: enabled=false (seguro).
 */

declare(strict_types=1);

$cfgPath = __DIR__ . '/config.json';
$cfg = ['enabled' => false, 'require_auth' => false, 'default_collection' => null];
if (\is_file($cfgPath)) {
    $loaded = \json_decode(\file_get_contents($cfgPath), true);
    if (\is_array($loaded)) {
        $cfg = \array_merge($cfg, $loaded);
    }
}

if (empty($cfg['enabled'])) {
    \http_response_code(404);
    \header('Content-Type: text/plain; charset=UTF-8');
    echo "AxiDB dashboard disabled. Set web.enabled = true in axidb/web/config.json to enable.\n";
    exit;
}

if (!empty($cfg['require_auth'])) {
    // Requerimiento minimo v1: cookie acide_session presente o Authorization Bearer.
    $hasCookie = !empty($_COOKIE['acide_session']);
    $hasBearer = isset($_SERVER['HTTP_AUTHORIZATION']) && \str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ');
    if (!$hasCookie && !$hasBearer) {
        \http_response_code(401);
        \header('Content-Type: text/plain; charset=UTF-8');
        echo "AxiDB dashboard: auth required. Login first (POST /axidb/api/axi.php with auth.login op).\n";
        exit;
    }
}

// Servir el index.html como template renderizado.
\header('Content-Type: text/html; charset=UTF-8');
\header("X-Content-Type-Options: nosniff");
\header("X-Frame-Options: SAMEORIGIN");

// Inyectar config publico (sin secretos) en window.AXI_DASHBOARD_CFG.
$publicCfg = [
    'default_collection' => $cfg['default_collection'] ?? null,
    'api_endpoint'       => '/axidb/api/axi.php',
];
$cfgJson = \json_encode($publicCfg, JSON_UNESCAPED_UNICODE);

$html = \file_get_contents(__DIR__ . '/index.html');
echo \str_replace('{{ AXI_DASHBOARD_CFG }}', $cfgJson, $html);
