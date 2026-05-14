/**
 * ConfigContext - expone el TenantConfig + los modulos resueltos al arbol React.
 *
 * Lo provee main.tsx al renderizar <App>, una sola vez. Los descendientes
 * (App.tsx, Dashboard.tsx, etc.) leen via useTenantConfig() y useActiveModules().
 *
 * Asi NADIE hardcodea ACTIVE_MODULES, ni el logo, ni el color, ni el modulo.
 * Cambiar config.json + recargar = otra app.
 */

import { createContext, useContext } from 'react';
import type { ReactNode } from 'react';

import type { TenantConfig } from './config-loader';
import type { ResolvedModule } from './modules-registry';

interface ConfigCtx {
    config: TenantConfig;
    /** Orden de aplicacion: [sector, _shared]. El sector va PRIMERO porque
     *  su nav debe aparecer al principio del sidebar; _shared aporta la cola
     *  generica (Config, Facturacion, Cuenta). */
    modules: ResolvedModule[];
    /** Mapa global componente-string -> Component, fusion de COMPONENTS
     *  de todos los modulos. Lo consume route-builder.tsx para resolver
     *  el "component" declarado en cada manifest. */
    registry: Record<string, React.ComponentType>;
}

const Ctx = createContext<ConfigCtx | null>(null);

export function useTenantConfig(): TenantConfig {
    const c = useContext(Ctx);
    if (!c) throw new Error('useTenantConfig fuera de ConfigProvider');
    return c.config;
}

export function useActiveModules(): ResolvedModule[] {
    const c = useContext(Ctx);
    if (!c) throw new Error('useActiveModules fuera de ConfigProvider');
    return c.modules;
}

export function useComponentRegistry(): Record<string, React.ComponentType> {
    const c = useContext(Ctx);
    if (!c) throw new Error('useComponentRegistry fuera de ConfigProvider');
    return c.registry;
}

interface ProviderProps {
    config: TenantConfig;
    modules: ResolvedModule[];
    children: ReactNode;
}

export function ConfigProvider({ config, modules, children }: ProviderProps) {
    const registry: Record<string, React.ComponentType> = {};
    for (const m of modules) Object.assign(registry, m.COMPONENTS);
    return <Ctx.Provider value={{ config, modules, registry }}>{children}</Ctx.Provider>;
}
