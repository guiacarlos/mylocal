/**
 * PedidosPage - vista en tiempo real de pedidos por mesa.
 *
 * Estado actual: placeholder. Se completara cuando se integre el TPV
 * con polling 3s contra get_table_order (ya existente). Para Ola 3 mostramos
 * lista de mesas con su estado guardado (libre/pidiendo/esperando/pagada).
 */

import { useEffect, useState } from 'react';
import { useDashboard, LOCAL_ID } from '../../components/dashboard/DashboardContext';
import { listMesas, type Mesa } from '../../services/sala.service';

const ESTADO_LABELS: Record<string, string> = {
    libre: 'Libre',
    pidiendo: 'Pidiendo',
    esperando: 'Esperando',
    pagada: 'Pagada',
};
const ESTADO_COLORS: Record<string, string> = {
    libre: '#e5e7eb',
    pidiendo: '#fde68a',
    esperando: '#fed7aa',
    pagada: '#bbf7d0',
};

export function PedidosPage() {
    const { client } = useDashboard();
    const [mesas, setMesas] = useState<Mesa[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let cancelled = false;
        async function load() {
            const all = await listMesas(client, { localId: LOCAL_ID }).catch(() => []);
            if (!cancelled) {
                setMesas(all);
                setLoading(false);
            }
        }
        load();
        // Polling 3s (cuando el TPV escriba mesa.estado, este componente lo vera).
        const id = setInterval(load, 3000);
        return () => { cancelled = true; clearInterval(id); };
    }, [client]);

    return (
        <div className="db-card">
            <div className="db-card-title">Pedidos en tiempo real</div>
            <div className="db-card-sub">
                Estado actual de cada mesa. Se actualiza cada 3 segundos.
            </div>

            {loading && <div className="db-ia-status"><div className="db-ia-dot" />Cargando mesas…</div>}

            {!loading && mesas.length === 0 && (
                <p style={{ color: 'var(--sp-text-muted)' }}>
                    No hay mesas configuradas. Crea zonas y mesas en la pestaña <strong>Mesas</strong>.
                </p>
            )}

            {!loading && mesas.length > 0 && (
                <div className="db-pedidos-grid">
                    {mesas.map(m => {
                        const estado = m.estado ?? 'libre';
                        return (
                            <article key={m.id} className="db-pedido-card" style={{ borderLeftColor: ESTADO_COLORS[estado] ?? '#e5e7eb' }}>
                                <div className="db-pedido-num">Mesa {m.numero}</div>
                                <div className="db-pedido-cap">{m.capacidad} pax</div>
                                <div className="db-pedido-estado" style={{ background: ESTADO_COLORS[estado] }}>
                                    {ESTADO_LABELS[estado] ?? estado}
                                </div>
                            </article>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
