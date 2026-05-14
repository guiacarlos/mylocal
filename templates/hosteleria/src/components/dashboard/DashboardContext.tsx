/**
 * DashboardContext - estado generico del dashboard (cero hosteleria).
 *
 * Carga el LOCAL (entidad del establecimiento, comun a cualquier vertical:
 * un restaurante, una clinica, un taller, una asesoria). Cada sub-pagina
 * generica (Config, Cuenta, Facturacion) lo consume sin saber del sector.
 *
 * Estado especifico de un sector (carta y productos para hosteleria, citas
 * para clinica, pedidos para logistica, etc.) vive en el Provider de su
 * propio modulo (ver modules/hosteleria/HosteleriaContext.tsx). Dashboard.tsx
 * los compone.
 */

import { createContext, useContext, useEffect, useState } from 'react';
import type { ReactNode } from 'react';

import { useSynaxisClient } from '../../hooks/useSynaxis';
import { getLocal, type LocalInfo } from '../../services/local.service';
import type { SynaxisClient } from '../../synaxis';

export const LOCAL_ID = 'l_default';

interface DashboardCtx {
    client: SynaxisClient;
    local: LocalInfo | null;
    setLocal: (l: LocalInfo) => void;
    /** Re-fetcha el local. No toca estado de otros providers. */
    reload: () => Promise<void>;
    loading: boolean;
}

const Ctx = createContext<DashboardCtx | null>(null);

export function useDashboard(): DashboardCtx {
    const c = useContext(Ctx);
    if (!c) throw new Error('useDashboard fuera de DashboardProvider');
    return c;
}

export function DashboardProvider({ children }: { children: ReactNode }) {
    const client = useSynaxisClient();
    const [local, setLocal] = useState<LocalInfo | null>(null);
    const [loading, setLoading] = useState(true);

    async function reload(): Promise<void> {
        setLoading(true);
        try {
            const info = await getLocal(client, LOCAL_ID).catch(() => null);
            if (info) setLocal(info);
        } finally {
            setLoading(false);
        }
    }

    // Bootstrap idempotente + primera carga del local.
    useEffect(() => {
        client.execute({ action: 'bootstrap_local', data: {} })
            .catch(e => console.warn('[Dashboard] bootstrap_local fallo:', e))
            .finally(() => reload());
    }, []);

    const value: DashboardCtx = { client, local, setLocal, reload, loading };
    return <Ctx.Provider value={value}>{children}</Ctx.Provider>;
}
