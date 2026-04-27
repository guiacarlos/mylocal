#!/usr/bin/env bash
# AxiDB - empaqueta axidb/ en un zip distribuible (Fase 5).
#
# Uso: bash axidb/migration/build-axidb-zip.sh [version]
#
# Genera: axidb-<version>.zip en el directorio actual.
# Excluye: tests/_tmp_*, docs (re-generables), runtime data (vault/, backups/).

set -e

VERSION="${1:-v1.0-dev}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
OUT="axidb-${VERSION}.zip"

cd "$REPO_ROOT"

if ! [ -d axidb ]; then
    echo "ERROR: 'axidb/' no existe en $REPO_ROOT" >&2
    exit 2
fi

echo "Empaquetando axidb/ -> $OUT (version: $VERSION)"
echo ""

# Limpiar zip anterior si existe.
[ -f "$OUT" ] && rm "$OUT"

zip -r "$OUT" axidb \
    -x "axidb/tests/_tmp*/*" \
    -x "axidb/tests/_tmp*" \
    -x "axidb/docs/api/*" \
    -x "axidb/web/config.json" \
    > /dev/null

# Anadir un README/CHANGELOG en el root del zip.
TMP_README=$(mktemp)
cat > "$TMP_README" <<EOF
AxiDB ${VERSION}

Estructura del paquete:
  axidb/
    axi.php           - punto de entrada embebido
    api/axi.php       - gateway HTTP
    engine/           - motor (Op model + Storage + Vault + Backup)
    sdk/php/          - cliente PHP (Embedded + HTTP transports)
    sql/              - compilador AxiSQL
    cli/, bin/        - terminal
    web/              - dashboard vanilla (deshabilitado por default)
    examples/         - notas, portfolio, remote-client, hello.php
    docs/standard/    - specs formales (op-model, wire-protocol, storage-format)
    docs/guide/       - tutoriales

Instalar:
  1. unzip ${OUT}
  2. Sube axidb/ a tu hosting PHP, junto a tu codigo.
  3. require 'axidb/axi.php' y empieza a usar.

Para servir el dashboard web:
  Edita axidb/web/config.json y pon enabled=true.

Mas info: axidb/docs/guide/01-quickstart.md
EOF
zip "$OUT" --junk-paths "$TMP_README" > /dev/null
zipnote "$OUT" | sed 's/=tmp\..*/=README.txt/' | zipnote -w "$OUT" 2>/dev/null || true
rm "$TMP_README"

echo ""
echo "Hecho:"
ls -lh "$OUT" | awk '{print "  " $0}'
echo ""
echo "Para subir a un hosting:"
echo "  1. unzip $OUT"
echo "  2. Sube /axidb a la raiz del hosting o adyacente a tu app."
echo "  3. require 'axidb/axi.php' desde tu codigo PHP."
