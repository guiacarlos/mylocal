/**
 * DashboardContext - estado compartido entre sub-paginas del dashboard.
 *
 * Una unica carga inicial (bootstrap_local + listCategorias + listProductos +
 * getLocal) sirve a TODAS las sub-paginas. Cada sub-pagina puede pedir reload
 * o setLocal directo cuando hace cambios optimistas.
 */

import { createContext, useContext, useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { useSynaxisClient } from '../../hooks/useSynaxis';
import { getLocal, type LocalInfo } from '../../services/local.service';
import {
    listCategorias,
    listProductos,
    type CartaCategoria,
    type CartaProducto,
} from '../../services/carta.service';
import type { SynaxisClient } from '../../synaxis';

export const LOCAL_ID = 'l_default';

interface DashboardCtx {
    client: SynaxisClient;
    local: LocalInfo | null;
    categorias: CartaCategoria[];
    productos: CartaProducto[];
    setLocal: (l: LocalInfo) => void;
    setProductos: (p: CartaProducto[]) => void;
    reload: () => void;
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
    const [categorias, setCategorias] = useState<CartaCategoria[]>([]);
    const [productos, setProductos] = useState<CartaProducto[]>([]);
    const [loading, setLoading] = useState(true);

    async function reload() {
        setLoading(true);
        try {
            const [cats, prods, info] = await Promise.all([
                listCategorias(client, LOCAL_ID).catch(() => []),
                listProductos(client, LOCAL_ID).catch(() => []),
                getLocal(client, LOCAL_ID).catch(() => null),
            ]);
            setCategorias(cats);
            setProductos(prods);
            if (info) setLocal(info);
        } finally {
            setLoading(false);
        }
    }

    // Bootstrap idempotente + primera carga
    useEffect(() => {
        client.execute({ action: 'bootstrap_local', data: {} })
            .catch(e => console.warn('[Dashboard] bootstrap_local fallo:', e))
            .finally(() => reload());
    }, []);

    const value: DashboardCtx = {
        client,
        local,
        categorias,
        productos,
        setLocal,
        setProductos,
        reload,
        loading,
    };

    return <Ctx.Provider value={value}>{children}</Ctx.Provider>;
}
