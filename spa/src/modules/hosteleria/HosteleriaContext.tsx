/**
 * HosteleriaContext - estado especifico del modulo hosteleria.
 *
 * Carga categorias + productos del local activo y los expone a las
 * sub-paginas del dashboard de hosteleria (Carta, Pdf, Web, Importar).
 * Vive AISLADO del DashboardContext generico: si manana se activa otro
 * sector (clinica, logistica, ...) sin hosteleria, este Provider no se
 * monta y nada se carga.
 *
 * El Provider se exporta tambien desde routes.tsx para que Dashboard.tsx
 * lo envuelva automaticamente cuando hosteleria esta en los modulos
 * activos. Las paginas hosteleras consumen via useHosteleria().
 */

import { createContext, useContext, useEffect, useState } from 'react';
import type { ReactNode } from 'react';

import { useSynaxisClient } from '../../hooks/useSynaxis';
import {
    listCategorias,
    listProductos,
    type CartaCategoria,
    type CartaProducto,
} from './services/carta.service';
import { useDashboard } from '../../components/dashboard/DashboardContext';

interface HosteleriaCtx {
    categorias: CartaCategoria[];
    productos: CartaProducto[];
    setProductos: (p: CartaProducto[]) => void;
    /** Re-fetcha categorias y productos. Util tras importar carta o
     *  editar productos en bloque. */
    reload: () => Promise<void>;
    loading: boolean;
}

const Ctx = createContext<HosteleriaCtx | null>(null);

export function useHosteleria(): HosteleriaCtx {
    const c = useContext(Ctx);
    if (!c) throw new Error('useHosteleria fuera de HosteleriaProvider');
    return c;
}

export function HosteleriaProvider({ children }: { children: ReactNode }) {
    const client = useSynaxisClient();
    // El local activo lo gestiona DashboardContext (es generico, no
    // hosteleria). Aqui solo leemos su id para saber por que local
    // estamos pidiendo carta.
    const { local } = useDashboard();
    const [categorias, setCategorias] = useState<CartaCategoria[]>([]);
    const [productos, setProductos] = useState<CartaProducto[]>([]);
    const [loading, setLoading] = useState(true);

    async function reload(): Promise<void> {
        if (!local?.id) return;
        setLoading(true);
        try {
            const [cats, prods] = await Promise.all([
                listCategorias(client, local.id).catch(() => []),
                listProductos(client, local.id).catch(() => []),
            ]);
            setCategorias(cats);
            setProductos(prods);
        } finally {
            setLoading(false);
        }
    }

    // Se dispara cuando local llega (o cambia). Si local todavia es null
    // (DashboardProvider aun cargando), esperamos sin marcar loading.
    useEffect(() => {
        if (!local?.id) return;
        reload();
    }, [local?.id]);

    const value: HosteleriaCtx = { categorias, productos, setProductos, reload, loading };
    return <Ctx.Provider value={value}>{children}</Ctx.Provider>;
}
