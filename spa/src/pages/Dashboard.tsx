/**
 * Dashboard - shell del panel. Config-driven y manifest-driven.
 *
 * Composicion:
 *   ConfigProvider (de main.tsx) -> ACTIVE_MODULES + tenant config
 *      |
 *   DashboardProvider (estado generico: local del establecimiento)
 *      |
 *   Provider de cada modulo de sector (estado especifico del vertical)
 *      |
 *   DashboardLayout -> Sidebar (items fusionados) + Header (crumbs/publicLink fusionados)
 *      |
 *   Routes (dashboard_routes fusionados)
 *
 * Anadir un sector nuevo NO requiere editar nada de aqui: el modulo se
 * incluye via config.json + modules-registry y el shell lo recoge solo.
 */

import { Routes, Route, Navigate, useNavigate } from 'react-router-dom';
import type { ReactNode, FC } from 'react';

import '../styles/db-styles.css';
import '../styles/checkout.css';
import { DashboardLayout } from '../components/dashboard/DashboardLayout';
import { DashboardProvider, useDashboard } from '../components/dashboard/DashboardContext';
import { logout } from '../services/auth.service';
import { useSynaxisClient } from '../hooks/useSynaxis';
import { useActiveModules, useTenantConfig, useComponentRegistry } from '../app/ConfigContext';
import { renderRoutes } from '../app/route-builder';

/** Compone N Providers anidados (en orden). Permite que cada modulo
 *  aporte un Provider sin hacer un arbol cableado a mano. */
function composeProviders(providers: FC<{ children: ReactNode }>[], children: ReactNode): ReactNode {
    return providers.reduceRight((acc, P) => <P>{acc}</P>, children);
}

function DashboardShell() {
    const { local } = useDashboard();
    const client = useSynaxisClient();
    const navigate = useNavigate();
    const config = useTenantConfig();
    const modules = useActiveModules();
    const registry = useComponentRegistry();

    const sidebarItems = modules.flatMap(m => m.manifest.dashboard_nav ?? []);
    const dashboardRoutes = modules.flatMap(m => m.manifest.dashboard_routes ?? []);

    // Fusion de crumb_labels de todos los modulos. Si dos modulos definen
    // la misma clave, el primero (sector) gana sobre _shared.
    const crumbLabels: Record<string, string> = {};
    for (let i = modules.length - 1; i >= 0; i--) {
        Object.assign(crumbLabels, modules[i].manifest.crumb_labels ?? {});
    }

    // Public link del primer modulo que lo declare (tipicamente el sector).
    const publicLinkSpec = modules.flatMap(m =>
        m.manifest.public_link ? [m.manifest.public_link] : [],
    )[0];
    const publicLink = publicLinkSpec && typeof window !== 'undefined' ? {
        url: `${window.location.protocol}//${window.location.host}${publicLinkSpec.route}`,
        label: publicLinkSpec.label,
        title: publicLinkSpec.title,
    } : undefined;

    // Landing por defecto: primer item del sidebar fusionado.
    const defaultLanding = (() => {
        const first = sidebarItems[0];
        if (!first) return 'config';
        return first.to.replace(/^\/dashboard\//, '').replace(/\/.*$/, '');
    })();

    async function handleLogout() {
        try { await logout(client); } catch (_) { /* ya saliendo, sin reintento */ }
        navigate('/');
    }

    return (
        <Routes>
            <Route element={
                <DashboardLayout
                    local={local}
                    plan={config.plan}
                    onLogout={handleLogout}
                    items={sidebarItems}
                    brandLogoSrc={config.logo_path}
                    brandAlt={config.nombre}
                    crumbLabels={crumbLabels}
                    publicLink={publicLink}
                />
            }>
                <Route index element={<Navigate to={defaultLanding} replace />} />
                {renderRoutes(dashboardRoutes, registry)}
            </Route>
        </Routes>
    );
}

export function Dashboard() {
    const modules = useActiveModules();
    const moduleProviders = modules.flatMap(m => m.Provider ? [m.Provider] : []);
    return (
        <DashboardProvider>
            {composeProviders(moduleProviders, <DashboardShell />)}
        </DashboardProvider>
    );
}
