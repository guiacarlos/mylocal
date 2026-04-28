#!/usr/bin/env bash
# AxiDB - rollback del flip (Fase 5 - Integración Socolá).
#
# Restaura el .htaccess al estado previo al flip y, si se da el nombre,
# tambien restaura el snapshot de AxiDB. Cronometra para validar el SLO
# "<5 min" del plan.
#
# USO:
#   bash axidb/migration/rollback.sh                                  # auto-detecta el ultimo .bak
#   bash axidb/migration/rollback.sh /ruta/.htaccess.bak.YYYY-...
#   bash axidb/migration/rollback.sh /ruta/.htaccess.bak.YYYY-... <snapshot-name>
#
# Si solo se da el .bak, restaura htaccess (suele ser suficiente).
# Si se da el snapshot, ejecuta tambien `axi backup restore`.

set -e

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
BAK="${1:-}"
SNAP="${2:-}"

T0=$(date +%s)

# 1. Si no se pasa BAK explicito, busca el mas reciente.
if [ -z "$BAK" ]; then
    BAK="$(ls -1t "$DIR"/.htaccess.bak.* 2>/dev/null | head -n1 || true)"
    if [ -z "$BAK" ]; then
        echo "ERROR: no encuentro ningun .htaccess.bak.* en $DIR"
        echo "Pasa la ruta del backup como primer argumento."
        exit 2
    fi
    echo "Usando backup mas reciente: $BAK"
fi

# 2. Restaura htaccess.
if [ ! -f "$BAK" ]; then
    echo "ERROR: $BAK no existe."
    exit 2
fi

echo "==> [$(date +%H:%M:%S)] Restaurando .htaccess desde $BAK"
cp "$BAK" "$DIR/.htaccess"
echo "    ok"

# 3. Smoke test post-rollback.
echo "==> [$(date +%H:%M:%S)] Smoke test"
HOST="${AXI_TEST_HOST:-http://localhost}"
legacy=$(curl -sS -X POST -H 'Content-Type: application/json' \
    -d '{"action":"list_products"}' "$HOST/acide/index.php" | head -c 200 || true)
echo "    /acide/ responde: ${legacy:0:80}..."

# Verifica que /axidb/api/ ya NO funciona (esperamos 404 o vacio).
axi_code=$(curl -sS -o /dev/null -w '%{http_code}' -X POST \
    -H 'Content-Type: application/json' -d '{"op":"ping"}' \
    "$HOST/axidb/api/axi.php" || echo "?")
echo "    /axidb/api/ HTTP=${axi_code} (esperado: 404 si el rollback fue exitoso)"

# 4. Restaurar snapshot si se especifica.
if [ -n "$SNAP" ]; then
    echo "==> [$(date +%H:%M:%S)] Restaurando snapshot: $SNAP"
    php "$DIR/axidb/cli/main.php" backup restore "$SNAP"
fi

T1=$(date +%s)
DT=$((T1 - T0))
echo ""
echo "==> Rollback completo en ${DT}s (SLO: <300s)"
if [ $DT -gt 300 ]; then
    echo "    AVISO: ${DT}s supera el SLO documentado de 5 min."
fi
