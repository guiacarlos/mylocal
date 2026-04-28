#!/usr/bin/env bash
# AxiDB demo 01 — App Notas desde cero (Fase 4)
# Tiempo total estimado: ~2 min.
#
# Que muestra:
#   - Inicializar AxiDB embebido (sin DB server).
#   - CRUD basico via SDK fluent.
#   - Consulta con WHERE + ORDER BY + LIMIT.
#   - Persistencia en disco (los archivos JSON quedan visibles).
#
# Uso: bash axidb/docs/demos/01-notas-from-zero.sh

set -e
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
TMP="$ROOT/_demo_notas_tmp"
mkdir -p "$TMP/STORAGE"

cleanup() { rm -rf "$TMP"; }
trap cleanup EXIT

cat <<'EOF' >"$TMP/notas.php"
<?php
require __DIR__ . '/../axidb/axi.php';
use Axi\Sdk\Php\Client;

// Inicializa la engine con storage local antes de instanciar el SDK.
Axi(['data_root' => __DIR__ . '/STORAGE']);
$db = new Client();
$notas = $db->collection('notas');

echo ">> Insertando 3 notas...\n";
$notas->insert(['title' => 'Comprar pan',     'priority' => 1]);
$notas->insert(['title' => 'Llamar al banco', 'priority' => 3]);
$notas->insert(['title' => 'Pagar autonomos', 'priority' => 2]);

echo ">> Notas con priority >= 2, ordenadas por priority desc:\n";
$filtered = $db->execute(['op' => 'select', 'collection' => 'notas',
    'where'    => [['field' => 'priority', 'op' => '>=', 'value' => 2]],
    'order_by' => [['field' => 'priority', 'dir' => 'desc']]]);
foreach ($filtered['data']['items'] as $n) {
    echo "  - [{$n['priority']}] {$n['title']}\n";
}

echo ">> Conteo total: " .
    $db->execute(['op' => 'count', 'collection' => 'notas'])['data']['count'] . "\n";

echo ">> Editando la nota del banco...\n";
$banco = $db->execute(['op' => 'select', 'collection' => 'notas',
    'where' => [['field' => 'title', 'op' => '=', 'value' => 'Llamar al banco']]
])['data']['items'][0];
$db->execute(['op' => 'update', 'collection' => 'notas',
              'id' => $banco['_id'],
              'data' => ['priority' => 5]]);

echo ">> Resultado tras update (todas las notas):\n";
$all = $db->execute(['op' => 'select', 'collection' => 'notas',
    'order_by' => [['field' => 'priority', 'dir' => 'desc']]]);
foreach ($all['data']['items'] as $n) {
    echo "  - [{$n['priority']}] {$n['title']}\n";
}

echo ">> Archivos en disco:\n";
foreach (glob(__DIR__ . '/STORAGE/notas/*.json') as $f) {
    echo "  " . basename($f) . "\n";
}
EOF

echo "===================================================================="
echo " Demo 01 — App Notas desde cero"
echo "===================================================================="
cd "$TMP"
php notas.php

echo ""
echo "===================================================================="
echo " Listo. Archivos en disco eliminados al salir."
echo "===================================================================="
