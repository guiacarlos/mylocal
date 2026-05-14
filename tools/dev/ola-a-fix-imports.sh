#!/usr/bin/env bash
# Ola A — corrige los imports tras mover los archivos a modules/hosteleria/.
#
# Patrones por familia de archivos (cada familia tiene su profundidad de
# subida hasta spa/src/). No reescribe nada mas que los path strings.
# Idempotente: aplicar dos veces produce el mismo resultado.

set -euo pipefail

ROOT="spa/src"
M="$ROOT/modules/hosteleria"

# Lista de servicios que MOVIERON con el modulo (siguen siendo siblings).
MOVED_SVCS='(carta|sala|maitre|qr)'
# Lista de servicios que se quedaron en spa/src/services (genericos).
KEPT_SVCS='(local|auth|payments|subscriptions)'

# ── Componentes hosteleros: modules/hosteleria/components/{carta,sala}/* ──
# Profundidad ../../ pasaba a spa/src/. Ahora hay que subir 4: ../../../../
for f in "$M"/components/carta/*.tsx "$M"/components/sala/*.tsx; do
    [ -f "$f" ] || continue
    sed -E -i \
        -e "s#from '\\.\\./\\.\\./hooks/#from '../../../../hooks/#g" \
        -e "s#from '\\.\\./\\.\\./synaxis'#from '../../../../synaxis'#g" \
        -e "s#from '\\.\\./\\.\\./synaxis/#from '../../../../synaxis/#g" \
        -e "s#from '\\.\\./\\.\\./services/${KEPT_SVCS}\\.service'#from '../../../../services/\\1.service'#g" \
        "$f"
done

# ── Paginas hosteleras: modules/hosteleria/pages/* ──
# Antes vivian en spa/src/pages/ o spa/src/pages/dashboard/, profundidades
# ../ y ../../ respectivamente. Tras el movimiento todas estan al mismo
# nivel: modules/hosteleria/pages/. Reescribimos a la profundidad del nuevo
# emplazamiento (../../../ para spa/src/, ../ para sibling components/services).
for f in "$M"/pages/*.tsx; do
    [ -f "$f" ] || continue
    sed -E -i \
        -e "s#from '\\.\\./hooks/#from '../../../hooks/#g" \
        -e "s#from '\\.\\./\\.\\./hooks/#from '../../../hooks/#g" \
        -e "s#from '\\.\\./services/${KEPT_SVCS}\\.service'#from '../../../services/\\1.service'#g" \
        -e "s#from '\\.\\./\\.\\./services/${KEPT_SVCS}\\.service'#from '../../../services/\\1.service'#g" \
        -e "s#from '\\.\\./\\.\\./services/${MOVED_SVCS}\\.service'#from '../services/\\1.service'#g" \
        -e "s#from '\\.\\./\\.\\./components/(carta|sala)/#from '../components/\\1/#g" \
        -e "s#from '\\.\\./\\.\\./components/(dashboard|local)/#from '../../../components/\\1/#g" \
        -e "s#from '\\.\\./\\.\\./components/(Header|Footer|LoginModal|MarkdownView)'#from '../../../components/\\1'#g" \
        -e "s#from '\\.\\./synaxis'#from '../../../synaxis'#g" \
        -e "s#from '\\.\\./synaxis/#from '../../../synaxis/#g" \
        -e "s#from '\\.\\./components/(carta|sala)/#from '../components/\\1/#g" \
        "$f"
done

# ── Services hosteleros: modules/hosteleria/services/* ──
for f in "$M"/services/*.ts; do
    [ -f "$f" ] || continue
    sed -E -i \
        -e "s#from '\\.\\./synaxis'#from '../../../synaxis'#g" \
        -e "s#from '\\.\\./synaxis/#from '../../../synaxis/#g" \
        -e "s#from '\\.\\./types/#from '../../../types/#g" \
        "$f"
done

# ── Codigo OUT-OF-MODULE que importa archivos movidos ──

# App.tsx: rutas publicas que ahora viven en modules/hosteleria/pages/.
sed -E -i \
    -e "s#from '\\./pages/(Carta|MesaQR|TPV)'#from './modules/hosteleria/pages/\\1'#g" \
    "$ROOT/App.tsx"

# pages/Dashboard.tsx: importa todas las sub-pages.
sed -E -i \
    -e "s#from '\\./dashboard/(CartaPage|CartaImportarPage|CartaProductosPage|CartaPdfPage|CartaWebPage|MesasPage|PedidosPage)'#from '../modules/hosteleria/pages/\\1'#g" \
    "$ROOT/pages/Dashboard.tsx"

# DashboardContext: pasa a importar carta.service desde el modulo hostelero.
sed -E -i \
    -e "s#from '\\.\\./\\.\\./services/carta\\.service'#from '../../modules/hosteleria/services/carta.service'#g" \
    "$ROOT/components/dashboard/DashboardContext.tsx"

# DashboardHeader: idem para sala.service.
sed -E -i \
    -e "s#from '\\.\\./\\.\\./services/sala\\.service'#from '../../modules/hosteleria/services/sala.service'#g" \
    "$ROOT/components/dashboard/DashboardHeader.tsx"

# ConfigGeneralPage: LocalConfigCard se movio a components/local/.
sed -E -i \
    -e "s#from '\\.\\./\\.\\./\\.\\./components/sala/LocalConfigCard'#from '../../../components/local/LocalConfigCard'#g" \
    "$ROOT/pages/dashboard/config/ConfigGeneralPage.tsx"

# ── CSS @import paths en db-styles.css ──
sed -E -i \
    -e "s#url\\('\\.\\./components/sala/sala-wizard\\.css'\\)#url('../modules/hosteleria/components/sala/sala-wizard.css')#g" \
    -e "s#url\\('\\.\\./components/carta/carta-pdf\\.css'\\)#url('../modules/hosteleria/components/carta/carta-pdf.css')#g" \
    -e "s#url\\('\\.\\./components/carta/carta-web\\.css'\\)#url('../modules/hosteleria/components/carta/carta-web.css')#g" \
    "$ROOT/styles/db-styles.css"

echo "OK"
