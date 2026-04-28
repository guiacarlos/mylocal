#!/usr/bin/env bash
# AxiDB demo 02 — Consola REPL ejecutando AxiSQL (Fase 2 + Fase 6)
# Tiempo: ~1 min.
#
# Que muestra:
#   - SELECT con WHERE + ORDER BY + LIMIT.
#   - INSERT y UPDATE via SQL.
#   - El mismo Op corriendo via {op:"sql"} crudo.
#
# Uso: bash axidb/docs/demos/02-axisql-console.sh

set -e
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
TMP="$ROOT/_demo_sql_tmp"
mkdir -p "$TMP/STORAGE"

cleanup() { rm -rf "$TMP"; }
trap cleanup EXIT

cat <<'EOF' >"$TMP/sql.php"
<?php
require __DIR__ . '/../axidb/axi.php';

$db = Axi(['data_root' => __DIR__ . '/STORAGE']);

function run($db, $label, $payload) {
    echo "\n>> $label\n";
    echo "   payload: " . json_encode($payload) . "\n";
    $r = $db->execute($payload);
    echo "   result:  " . json_encode($r['data'] ?? $r['error']) . "\n";
}

run($db, 'INSERT via AxiSQL', ['op' => 'sql',
    'query' => "INSERT INTO products (name, price, stock) VALUES ('cafe', 2.5, 30)"]);
run($db, 'INSERT via AxiSQL', ['op' => 'sql',
    'query' => "INSERT INTO products (name, price, stock) VALUES ('te', 1.8, 5)"]);
run($db, 'INSERT via AxiSQL', ['op' => 'sql',
    'query' => "INSERT INTO products (name, price, stock) VALUES ('croissant', 1.2, 20)"]);

run($db, 'SELECT con WHERE + ORDER BY + LIMIT',
    ['op' => 'sql',
     'query' => "SELECT name, price FROM products WHERE price < 3 ORDER BY price ASC LIMIT 5"]);

run($db, 'COUNT con WHERE',
    ['op' => 'sql', 'query' => "SELECT COUNT(*) FROM products WHERE stock < 10"]);

run($db, 'UPDATE con WHERE',
    ['op' => 'sql', 'query' => "UPDATE products SET stock = 50 WHERE name = 'cafe'"]);

run($db, 'Mismo SELECT pero via Op JSON crudo (sin SQL)',
    ['op' => 'select', 'collection' => 'products',
     'where' => [['field' => 'price', 'op' => '<', 'value' => 3]],
     'order_by' => [['field' => 'price', 'dir' => 'asc']],
     'limit' => 5]);

echo "\n>> En la consola web (axidb/web/console.html) los mismos comandos\n";
echo "   funcionan en modo 'sql:' y 'op:'. Ctrl+Enter ejecuta.\n";
EOF

echo "===================================================================="
echo " Demo 02 — AxiSQL REPL"
echo "===================================================================="
cd "$TMP"
php sql.php

echo ""
echo "===================================================================="
echo " Listo."
echo "===================================================================="
