/**
 * Registro tipado de componentes referenciados por manifest.json.
 *
 * Por que asi: el manifest declara rutas y items de navegacion como cadenas
 * (`"component": "CartaPage"`) para poder ser leido por humanos y por el
 * loader generico. Pero React necesita el componente real para renderizar.
 * Este modulo expone el mapa `COMPONENTS` que el runtime consulta sin
 * resolver strings en tiempo de ejecucion (zero eval, zero dynamic require).
 *
 * Reglas:
 *   - Si el manifest cita `"X"`, este fichero DEBE exportar `X` en COMPONENTS.
 *   - Si una pagina nueva entra en el manifest, primero se anade aqui.
 *   - Los CSS especificos del sector se importan AQUI (side-effect) para
 *     que solo se carguen cuando el modulo esta activo. El dashboard
 *     generico (db-styles.css) ya no los conoce.
 *   - Provider opcional: si el modulo necesita estado compartido entre
 *     sus paginas (carta, productos, etc.), se exporta como `Provider`.
 *     Dashboard.tsx lo envuelve por encima del Outlet.
 */

import type { ComponentType, FC, ReactNode } from 'react';

import { Carta } from './pages/Carta';
import { MesaQR } from './pages/MesaQR';
import { TPV } from './pages/TPV';
import { CartaPage } from './pages/CartaPage';
import { CartaImportarPage } from './pages/CartaImportarPage';
import { CartaProductosPage } from './pages/CartaProductosPage';
import { CartaPdfPage } from './pages/CartaPdfPage';
import { CartaWebPage } from './pages/CartaWebPage';
import { MesasPage } from './pages/MesasPage';
import { PedidosPage } from './pages/PedidosPage';

import { HosteleriaProvider } from './HosteleriaContext';

// Side-effect: CSS especifico del sector. Solo entra en el bundle cuando
// este modulo se importa (= cuando hosteleria es uno de los modulos activos).
import './components/sala/sala-wizard.css';
import './components/carta/carta-pdf.css';
import './components/carta/carta-web.css';

import manifestData from './manifest.json';
import { validateManifest } from '../../app/config';

export const COMPONENTS: Record<string, ComponentType> = {
    Carta,
    MesaQR,
    TPV,
    CartaPage,
    CartaImportarPage,
    CartaProductosPage,
    CartaPdfPage,
    CartaWebPage,
    MesasPage,
    PedidosPage,
};

export const manifest = validateManifest(manifestData);

/**
 * Provider que el shell del dashboard envuelve antes de pintar las
 * paginas de este modulo. Aporta el estado compartido de hosteleria
 * (categorias + productos pre-cargados) sin contaminar el shell generico.
 */
export const Provider: FC<{ children: ReactNode }> = HosteleriaProvider;
