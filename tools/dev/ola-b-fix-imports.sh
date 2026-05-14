#!/usr/bin/env bash
# Ola B - parchea imports tras mover paginas genericas a modules/_shared/.
#
# Profundidades CORRECTAS (re-contadas tras un primer pase erroneo que
# sobreestimo en 1 nivel):
#   modules/_shared/pages/X.tsx                          -> 3 niveles a src/
#   modules/_shared/pages/dashboard/X.tsx                -> 4 niveles a src/
#   modules/_shared/pages/dashboard/<sub>/X.tsx          -> 5 niveles a src/
#
# Estrategia: usar Python para reescribir relative imports en cada fichero
# segun la profundidad del propio fichero. Idempotente: aplicar dos veces
# produce el mismo resultado (calcula la profundidad correcta absoluta).

set -euo pipefail

python3 - <<'PY'
import os
import re
from pathlib import Path

ROOT = Path('spa/src')
SHARED = ROOT / 'modules' / '_shared' / 'pages'

# Mapas: cada subcarpeta apunta a "modulos top-level" hijos directos de src/.
# Solo reescribimos imports a estos targets; el resto se respeta.
TOP_LEVEL = {
    'hooks', 'services', 'components', 'synaxis', 'types', 'styles', 'app',
    'modules',
}

# Regex que captura: from '<dots>/<top>/<rest>'   o  from "<dots>/<top>/<rest>"
PATTERN = re.compile(
    r"""(from\s+['"])((?:\.\./)+)([A-Za-z_][\w-]*)(/?[^'"]*)(['"])"""
)

def fix_file(path: Path) -> None:
    # Profundidad de path respecto a spa/src/
    rel = path.relative_to(ROOT)
    depth = len(rel.parts) - 1  # restamos el propio fichero
    if depth <= 0:
        return  # archivo a nivel raiz, no tiene "../"

    correct_prefix = '../' * depth

    text = path.read_text(encoding='utf-8')

    def rewrite(m: re.Match) -> str:
        head, dots, top, rest, quote = m.group(1), m.group(2), m.group(3), m.group(4), m.group(5)
        if top not in TOP_LEVEL:
            return m.group(0)  # import a un sibling de la propia carpeta, no tocar
        return f"{head}{correct_prefix}{top}{rest}{quote}"

    new = PATTERN.sub(rewrite, text)
    if new != text:
        path.write_text(new, encoding='utf-8')
        print(f"  fixed {rel}")

for tsx in SHARED.rglob('*.tsx'):
    fix_file(tsx)
PY

echo "OK"
