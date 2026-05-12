/**
 * Dashboard - shell del panel del hostelero.
 *
 * Estructura:
 *   /dashboard                 -> redirige a /dashboard/carta
 *   /dashboard/carta           -> CartaPage (tabs Importar/Productos/PDF/Web)
 *   /dashboard/mesas           -> MesasPage (sala + zonas + mesas + local)
 *   /dashboard/pedidos         -> PedidosPage (tiempo real, polling 3s)
 *   /dashboard/config          -> ConfigPage (sub-tabs General/Identidad/.../Equipo)
 *   /dashboard/facturacion     -> FacturacionPage (Plan/Histórico/Métodos)
 *   /dashboard/cuenta          -> CuentaPage (Perfil/Password/Sesiones/Cerrar)
 *
 * Sidebar fijo + header sticky con breadcrumbs + Outlet para sub-paginas.
 * Estado compartido (local, productos, categorias) via DashboardContext.
 */

import { Routes, Route, Navigate, useNavigate } from 'react-router-dom';
import '../styles/db-styles.css';
import '../styles/checkout.css';
import { DashboardLayout } from '../components/dashboard/DashboardLayout';
import { DashboardProvider, useDashboard } from '../components/dashboard/DashboardContext';
import { logout } from '../services/auth.service';
import { useSynaxisClient } from '../hooks/useSynaxis';

import { CartaPage } from './dashboard/CartaPage';
import { CartaImportarPage } from './dashboard/CartaImportarPage';
import { CartaProductosPage } from './dashboard/CartaProductosPage';
import { CartaPdfPage } from './dashboard/CartaPdfPage';
import { CartaWebPage } from './dashboard/CartaWebPage';
import { MesasPage } from './dashboard/MesasPage';
import { PedidosPage } from './dashboard/PedidosPage';
import { ConfigPage } from './dashboard/ConfigPage';
import { ConfigGeneralPage } from './dashboard/config/ConfigGeneralPage';
import { ConfigIdentidadPage } from './dashboard/config/ConfigIdentidadPage';
import { ConfigIdiomasPage } from './dashboard/config/ConfigIdiomasPage';
import { ConfigHorariosPage } from './dashboard/config/ConfigHorariosPage';
import { ConfigFiscalPage } from './dashboard/config/ConfigFiscalPage';
import { ConfigEquipoPage } from './dashboard/config/ConfigEquipoPage';
import { FacturacionPage } from './dashboard/FacturacionPage';
import { FacturacionPlanPage } from './dashboard/facturacion/FacturacionPlanPage';
import { FacturacionHistoricoPage } from './dashboard/facturacion/FacturacionHistoricoPage';
import { FacturacionMetodosPage } from './dashboard/facturacion/FacturacionMetodosPage';
import { CuentaPage } from './dashboard/CuentaPage';
import { CuentaPerfilPage } from './dashboard/cuenta/CuentaPerfilPage';
import { CuentaPasswordPage } from './dashboard/cuenta/CuentaPasswordPage';
import { CuentaSesionesPage } from './dashboard/cuenta/CuentaSesionesPage';
import { CuentaCerrarPage } from './dashboard/cuenta/CuentaCerrarPage';

function DashboardShell() {
    const { local } = useDashboard();
    const client = useSynaxisClient();
    const navigate = useNavigate();

    async function handleLogout() {
        try { await logout(client); } catch (_) {}
        navigate('/');
    }

    return (
        <Routes>
            <Route element={<DashboardLayout local={local} onLogout={handleLogout} />}>
                <Route index element={<Navigate to="carta" replace />} />

                <Route path="carta" element={<CartaPage />}>
                    <Route index element={<Navigate to="importar" replace />} />
                    <Route path="importar"  element={<CartaImportarPage />} />
                    <Route path="productos" element={<CartaProductosPage />} />
                    <Route path="pdf"       element={<CartaPdfPage />} />
                    <Route path="web"       element={<CartaWebPage />} />
                </Route>

                <Route path="mesas"   element={<MesasPage />} />
                <Route path="pedidos" element={<PedidosPage />} />

                <Route path="config" element={<ConfigPage />}>
                    <Route index element={<Navigate to="general" replace />} />
                    <Route path="general"   element={<ConfigGeneralPage />} />
                    <Route path="identidad" element={<ConfigIdentidadPage />} />
                    <Route path="idiomas"   element={<ConfigIdiomasPage />} />
                    <Route path="horarios"  element={<ConfigHorariosPage />} />
                    <Route path="fiscal"    element={<ConfigFiscalPage />} />
                    <Route path="equipo"    element={<ConfigEquipoPage />} />
                </Route>

                <Route path="facturacion" element={<FacturacionPage />}>
                    <Route index element={<Navigate to="plan" replace />} />
                    <Route path="plan"      element={<FacturacionPlanPage />} />
                    <Route path="historico" element={<FacturacionHistoricoPage />} />
                    <Route path="metodos"   element={<FacturacionMetodosPage />} />
                </Route>

                <Route path="cuenta" element={<CuentaPage />}>
                    <Route index element={<Navigate to="perfil" replace />} />
                    <Route path="perfil"   element={<CuentaPerfilPage />} />
                    <Route path="password" element={<CuentaPasswordPage />} />
                    <Route path="sesiones" element={<CuentaSesionesPage />} />
                    <Route path="cerrar"   element={<CuentaCerrarPage />} />
                </Route>
            </Route>
        </Routes>
    );
}

export function Dashboard() {
    return (
        <DashboardProvider>
            <DashboardShell />
        </DashboardProvider>
    );
}
