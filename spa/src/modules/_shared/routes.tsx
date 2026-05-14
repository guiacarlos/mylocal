/**
 * Registry tipado del modulo "_shared" — el nucleo generico que cualquier
 * vertical carga junto a su modulo de sector.
 *
 * Mantenido en paralelo con manifest.json: si manifest cita "X", aqui
 * tiene que existir export `X` en COMPONENTS. El validateManifest no lo
 * comprueba (porque manifest es declarativo y este es de runtime); lo
 * comprueba el render: si falta una entrada, route-builder loguea error.
 */

import type { ComponentType } from 'react';

import { Home } from './pages/Home';
import { LegalPage } from './pages/LegalPage';
import { WikiIndex, WikiArticle } from './pages/WikiPage';
import { Checkout } from './pages/Checkout';

import { ConfigPage } from './pages/dashboard/ConfigPage';
import { ConfigGeneralPage } from './pages/dashboard/config/ConfigGeneralPage';
import { ConfigIdentidadPage } from './pages/dashboard/config/ConfigIdentidadPage';
import { ConfigIdiomasPage } from './pages/dashboard/config/ConfigIdiomasPage';
import { ConfigHorariosPage } from './pages/dashboard/config/ConfigHorariosPage';
import { ConfigFiscalPage } from './pages/dashboard/config/ConfigFiscalPage';
import { ConfigEquipoPage } from './pages/dashboard/config/ConfigEquipoPage';

import { FacturacionPage } from './pages/dashboard/FacturacionPage';
import { FacturacionPlanPage } from './pages/dashboard/facturacion/FacturacionPlanPage';
import { FacturacionHistoricoPage } from './pages/dashboard/facturacion/FacturacionHistoricoPage';
import { FacturacionMetodosPage } from './pages/dashboard/facturacion/FacturacionMetodosPage';

import { CuentaPage } from './pages/dashboard/CuentaPage';
import { CuentaPerfilPage } from './pages/dashboard/cuenta/CuentaPerfilPage';
import { CuentaPasswordPage } from './pages/dashboard/cuenta/CuentaPasswordPage';
import { CuentaSesionesPage } from './pages/dashboard/cuenta/CuentaSesionesPage';
import { CuentaCerrarPage } from './pages/dashboard/cuenta/CuentaCerrarPage';

import manifestData from './manifest.json';
import { validateManifest } from '../../app/config';

export const COMPONENTS: Record<string, ComponentType> = {
    Home,
    LegalPage,
    WikiIndex,
    WikiArticle,
    Checkout,
    ConfigPage,
    ConfigGeneralPage,
    ConfigIdentidadPage,
    ConfigIdiomasPage,
    ConfigHorariosPage,
    ConfigFiscalPage,
    ConfigEquipoPage,
    FacturacionPage,
    FacturacionPlanPage,
    FacturacionHistoricoPage,
    FacturacionMetodosPage,
    CuentaPage,
    CuentaPerfilPage,
    CuentaPasswordPage,
    CuentaSesionesPage,
    CuentaCerrarPage,
};

export const manifest = validateManifest(manifestData);
