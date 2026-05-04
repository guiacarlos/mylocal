import React, { useState, useEffect } from 'react';
import { Store, TrendingUp, Users, AlertCircle } from 'lucide-react';

const EP = '/axidb/api/axi.php';
function api(action, data = {}) {
    return fetch(EP, { method: 'POST', headers: { 'Content-Type': 'application/json' },
        credentials: 'include', body: JSON.stringify({ action, data }) }).then(r => r.json());
}

export default function MultiLocalDashboard() {
    const [locales, setLocales] = useState([]);
    const [kpis, setKpis] = useState({});
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        api('list_locales', {}).then(async (r) => {
            if (!r.success) { setLoading(false); return; }
            const locs = r.data || [];
            setLocales(locs);

            const kpiData = {};
            for (const local of locs) {
                const [resumen, ticket] = await Promise.all([
                    api('resumen_dia', { local_id: local.id }),
                    api('ticket_medio', { local_id: local.id })
                ]);
                kpiData[local.id] = {
                    total_dia: resumen.data?.total_dia || 0,
                    sesiones_dia: resumen.data?.sesiones_dia || 0,
                    ticket_medio: ticket.data?.ticket_medio || 0
                };
            }
            setKpis(kpiData);
            setLoading(false);
        });
    }, []);

    if (loading) return <div>Cargando dashboard multi-local...</div>;

    const totalGlobal = Object.values(kpis).reduce((s, k) => s + k.total_dia, 0);

    return (
        <div className="multilocal-dashboard">
            <h3><Store size={16} /> Dashboard Multi-Local</h3>
            <div style={{ display: 'flex', gap: '1rem', marginBottom: '1.5rem', flexWrap: 'wrap' }}>
                <div className="kpi-card">
                    <div className="kpi-label">Total global hoy</div>
                    <div className="kpi-value">{totalGlobal.toFixed(2)} EUR</div>
                </div>
                <div className="kpi-card">
                    <div className="kpi-label">Locales activos</div>
                    <div className="kpi-value">{locales.length}</div>
                </div>
            </div>
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                    <tr style={{ borderBottom: '2px solid #ddd', textAlign: 'left' }}>
                        <th style={{ padding: 8 }}>Local</th>
                        <th style={{ padding: 8 }}>Mesas hoy</th>
                        <th style={{ padding: 8 }}>Total dia</th>
                        <th style={{ padding: 8 }}>Ticket medio</th>
                    </tr>
                </thead>
                <tbody>
                    {locales.map(local => {
                        const k = kpis[local.id] || {};
                        return (
                            <tr key={local.id} style={{ borderBottom: '1px solid #eee' }}>
                                <td style={{ padding: 8, fontWeight: 600 }}>{local.nombre}</td>
                                <td style={{ padding: 8 }}>{k.sesiones_dia || 0}</td>
                                <td style={{ padding: 8 }}>{(k.total_dia || 0).toFixed(2)} EUR</td>
                                <td style={{ padding: 8 }}>{(k.ticket_medio || 0).toFixed(2)} EUR</td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
