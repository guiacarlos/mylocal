#!/usr/bin/env bash
# AxiDB demo 03 — Agente respondiendo consulta de datos reales (Fase 6)
# Tiempo: ~1 min. Sin API key — usa NoopLlm offline.
#
# Que muestra:
#   - El agente detecta la intencion del prompt.
#   - Despacha al Op pertinente (count, select, ...).
#   - Devuelve answer + observacion estructurada.
#   - El audit log queda escrito en disco.
#
# Uso: bash axidb/docs/demos/03-agent-real-data.sh

set -e
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
TMP="$ROOT/_demo_agent_tmp"
mkdir -p "$TMP/STORAGE"

cleanup() { rm -rf "$TMP"; }
trap cleanup EXIT

cat <<'EOF' >"$TMP/agent.php"
<?php
require __DIR__ . '/../axidb/axi.php';

$db = Axi(['data_root' => __DIR__ . '/STORAGE']);

echo ">> Inserto 5 productos de prueba...\n";
foreach ([
    ['name' => 'cafe',      'price' => 2.5, 'stock' => 30],
    ['name' => 'te',        'price' => 1.8, 'stock' => 5],
    ['name' => 'croissant', 'price' => 1.2, 'stock' => 8],
    ['name' => 'tostada',   'price' => 1.5, 'stock' => 25],
    ['name' => 'agua',      'price' => 0.8, 'stock' => 100],
] as $p) {
    $db->execute(['op' => 'insert', 'collection' => 'products', 'data' => $p]);
}

echo "\n>> Pregunta 1: count products\n";
$r = $db->execute(['op' => 'ai.ask', 'prompt' => 'count products']);
echo "   answer:      " . $r['data']['answer'] . "\n";
echo "   observation: " . json_encode($r['data']['observation']['data']) . "\n";

echo "\n>> Pregunta 2: list products limit 3\n";
$r = $db->execute(['op' => 'ai.ask', 'prompt' => 'list products limit 3']);
echo "   answer: " . $r['data']['answer'] . "\n";
foreach ($r['data']['observation']['data']['items'] ?? [] as $p) {
    echo "    - {$p['name']} ({$p['price']}€, stock {$p['stock']})\n";
}

echo "\n>> Pregunta 3: ping\n";
$r = $db->execute(['op' => 'ai.ask', 'prompt' => 'ping']);
echo "   answer: " . $r['data']['answer'] . "\n";
echo "   engine status: " . ($r['data']['observation']['data']['status'] ?? '?') . "\n";

echo "\n>> Pregunta 4: help select  (consulta el HelpEntry del Op)\n";
$r = $db->execute(['op' => 'ai.ask', 'prompt' => 'help select']);
$h = $r['data']['observation']['data'] ?? [];
echo "   description: " . substr($h['description'] ?? '', 0, 80) . "...\n";
echo "   #params:    " . count($h['params'] ?? []) . "\n";

echo "\n>> Crear un agente persistente con sandbox limitado...\n";
$r = $db->execute(['op' => 'ai.new_agent',
    'name' => 'reviewer',
    'role' => 'Eres un revisor de productos.',
    'tools' => ['select', 'count'],
    'budget' => ['max_steps' => 3]]);
$agentId = $r['data']['id'];
echo "   agent_id: $agentId\n";

echo "\n>> Mandar mensaje a su inbox y procesarlo...\n";
$db->execute(['op' => 'ai.attach', 'to' => $agentId,
    'subject' => 'check', 'body' => 'count products']);
$r = $db->execute(['op' => 'ai.run_agent', 'agent_id' => $agentId]);
echo "   answer:        " . $r['data']['answer'] . "\n";
echo "   steps usados: " . $r['data']['steps'] . "\n";
echo "   status:       " . $r['data']['status'] . "\n";

echo "\n>> Audit log (ultimas 5 entradas):\n";
$r = $db->execute(['op' => 'ai.audit', 'limit' => 5]);
foreach ($r['data']['entries'] as $row) {
    echo sprintf("   [%s] %-6s op=%-8s success=%s\n",
        substr($row['ts'], 11, 8),
        substr($row['actor'], -10),
        $row['op'],
        $row['success'] ? 'true' : 'false');
}

echo "\n>> Audit log path: " . $r['data']['path'] . "\n";
EOF

echo "===================================================================="
echo " Demo 03 — Agente IA con NoopLlm (offline)"
echo "===================================================================="
cd "$TMP"
php agent.php

echo ""
echo "===================================================================="
echo " Listo. Para usar Groq/Gemini/Claude/Ollama, configura la API key"
echo " correspondiente y crea el agente con --llm groq:llama-3.1-8b-instant"
echo " (ver docs/guide/05-agentes.md)."
echo "===================================================================="
