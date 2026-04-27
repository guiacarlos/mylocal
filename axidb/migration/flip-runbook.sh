#!/usr/bin/env bash
# AxiDB - flip runbook (Fase 5 - Integración Socolá).
#
# Aplica el bloque coexistencia AxiDB sobre el .htaccess de producción.
# Por defecto corre en modo --dry-run. Solo con --apply toca el archivo.
# Cronometra cada paso para validar el SLO de "<5 min" del plan.
#
# USO:
#   bash axidb/migration/flip-runbook.sh                 # dry-run (sin tocar nada)
#   bash axidb/migration/flip-runbook.sh --apply         # aplica el flip
#   bash axidb/migration/flip-runbook.sh --apply --dir /var/www/socola
#
# REVERTIR: bash axidb/migration/rollback.sh
#
# Idempotente: si el bloque AxiDB ya esta en .htaccess, el script no lo
# duplica.

set -e

# Defaults
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
APPLY=0
PRE_BACKUP_NAME="pre-flip-$(date +%Y-%m-%d-%H%M%S)"

# Parse args
while [ $# -gt 0 ]; do
    case "$1" in
        --apply) APPLY=1; shift;;
        --dir)   DIR="$2"; shift 2;;
        --help|-h)
            sed -n '2,15p' "$0"; exit 0;;
        *) echo "Argumento desconocido: $1"; exit 2;;
    esac
done

HTAC="$DIR/.htaccess"
PATCH="$DIR/axidb/migration/htaccess.patch"

step() { echo ""; echo "==> [$(date +%H:%M:%S)] $1"; }

step "0. Pre-vuelo"
echo "   working dir: $DIR"
echo "   .htaccess:   $HTAC"
echo "   modo:        $([ $APPLY -eq 1 ] && echo APPLY || echo DRY-RUN)"

if [ ! -f "$HTAC" ]; then
    echo "   ERROR: $HTAC no existe."
    exit 2
fi
if [ ! -f "$PATCH" ]; then
    echo "   ERROR: $PATCH no existe."
    exit 2
fi

T0=$(date +%s)

# ---------------------------------------------------------------------------
step "1. Snapshot AxiDB pre-flip ($PRE_BACKUP_NAME)"
if [ $APPLY -eq 1 ]; then
    php "$DIR/axidb/cli/main.php" backup create "$PRE_BACKUP_NAME"
    echo "   ok"
else
    echo "   (dry-run) saltado"
fi

# ---------------------------------------------------------------------------
step "2. Backup .htaccess"
BAK="$HTAC.bak.$(date +%Y-%m-%d-%H%M%S)"
if [ $APPLY -eq 1 ]; then
    cp "$HTAC" "$BAK"
    echo "   guardado en $BAK"
else
    echo "   (dry-run) iria a $BAK"
fi

# ---------------------------------------------------------------------------
step "3. Aplicar bloque AxiDB"
# Si ya esta presente, no duplicamos.
if grep -q '^RewriteRule\s*\^axidb/api/' "$HTAC"; then
    echo "   ya esta aplicado — bloque AxiDB detectado en $HTAC"
else
    if [ $APPLY -eq 1 ]; then
        # Inserta el bloque exacto del patch tras la regla legacy.
        TMP="$(mktemp)"
        awk '
            /^RewriteRule\s*\^acide\// && !inserted {
                print
                print ""
                print "# AxiDB v1 (Fase 5) - alias coexistencia con /acide/."
                print "RewriteRule ^axidb/api/(.*)$ axidb/api/$1 [END,QSA]"
                inserted = 1
                next
            }
            { print }
        ' "$HTAC" > "$TMP"
        mv "$TMP" "$HTAC"
        echo "   bloque insertado tras la regla ^acide/"
    else
        echo "   (dry-run) insertaria el bloque tras: RewriteRule ^acide/(.*)\$ CORE/\$1 [END,QSA]"
    fi
fi

# ---------------------------------------------------------------------------
step "4. Smoke test post-flip"
if [ $APPLY -eq 1 ]; then
    # Pequena espera por si el caching del htaccess es agresivo
    sleep 1
    HOST="${AXI_TEST_HOST:-http://localhost}"
    legacy=$(curl -s -X POST -H 'Content-Type: application/json' \
        -d '{"action":"list_products"}' "$HOST/acide/index.php" | head -c 200)
    axi=$(curl -s -X POST -H 'Content-Type: application/json' \
        -d '{"op":"ping"}' "$HOST/axidb/api/axi.php" | head -c 200)
    echo "   /acide/  : ${legacy:0:80}..."
    echo "   /axidb/  : ${axi:0:80}..."
else
    echo "   (dry-run) curl saltado"
fi

# ---------------------------------------------------------------------------
T1=$(date +%s)
DT=$((T1 - T0))
step "5. Done"
echo "   tiempo total: ${DT}s (SLO: <300s)"
if [ $APPLY -eq 1 ]; then
    echo "   .htaccess.bak: $BAK"
    echo "   snapshot:      $PRE_BACKUP_NAME"
    echo ""
    echo "   Si algo va mal: bash axidb/migration/rollback.sh \"$BAK\" \"$PRE_BACKUP_NAME\""
else
    echo ""
    echo "   Para aplicar: anade --apply a la linea de comando."
fi
