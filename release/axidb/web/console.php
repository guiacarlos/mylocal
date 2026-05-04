<?php
/**
 * AxiDB - Console web entry gate (Fase 6).
 *
 * Subsistema: web
 * Responsable: copia funcional de index.php pero sirviendo console.html.
 *              Misma logica de gate (web.enabled + require_auth opcional).
 *              Inyecta window.AXI_DASHBOARD_CFG igual que index.php.
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
    echo "AxiDB console disabled. Set web.enabled = true in axidb/web/config.json to enable.\n";
    exit;
}

if (!empty($cfg['require_auth'])) {
    $hasCookie = !empty($_COOKIE['acide_session']);
    $hasBearer = isset($_SERVER['HTTP_AUTHORIZATION']) && \str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ');
    if (!$hasCookie && !$hasBearer) {
        \http_response_code(401);
        \header('Content-Type: text/plain; charset=UTF-8');
        echo "AxiDB console: auth required.\n";
        exit;
    }
}

\header('Content-Type: text/html; charset=UTF-8');
\header("X-Content-Type-Options: nosniff");
\header("X-Frame-Options: SAMEORIGIN");

$publicCfg = [
    'default_collection' => $cfg['default_collection'] ?? null,
    'api_endpoint'       => '/axidb/api/axi.php',
];
$cfgJson = \json_encode($publicCfg, JSON_UNESCAPED_UNICODE);

$html = \file_get_contents(__DIR__ . '/console.html');
echo \str_replace('{{ AXI_DASHBOARD_CFG }}', $cfgJson, $html);
