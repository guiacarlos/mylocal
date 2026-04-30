import React, { useState, useEffect } from 'react';
import { BarChart3, TrendingUp, Clock, Download } from 'lucide-react';

const EP = '/axidb/api/axi.php';
function api(action, data = {}) {
    return fetch(EP, { method: 'POST', headers: { 'Content-Type': 'application/json' },
        credentials: 'include', body: JSON.stringify({ action, data }) }).then(r => r.json());
}

export default function AnalyticsPanel({ localId }) {
    const [rango, setRango] = useState('mes');
    const [ticket, setTicket] = useState({});
    const [rotacion, setRotacion] = useState({});
    const [ranking, setRanking] = useState([]);
    const [franjas, setFranjas] = useState([]);

    const getRangoFechas = () => {
        const hasta = new Date().toISOString();
        const desde = new Date();
        if (rango === 'dia') desde.setDate(desde.getDate() - 1);
        else if (rango === 'semana') desde.setDate(desde.getDate() - 7);
        else desde.setMonth(desde.getMonth() - 1);
        return { desde: desde.toISOString(), hasta };
    };

    useEffect(() => {
        const { desde, hasta } = getRangoFechas();
        api('ticket_medio', { local_id: localId, desde, hasta }).then(r => { if (r.success) setTicket(r.data || {}); });
        api('rotacion_mesas', { local_id: localId, desde, hasta }).then(r => { if (r.success) setRotacion(r.data || {}); });
        api('productos_ranking', { local_id: localId, desde, hasta }).then(r => { if (r.success) setRanking(r.data || []); });
        api('franjas_ocupacion', { local_id: localId }).then(r => { if (r.success) setFranjas(r.data || []); });
    }, [localId, rango]);

    const exportCsv = async () => {
        const res = await api('export_ventas_csv', { local_id: localId, ...getRangoFechas() });
        if (res.success) {
            const blob = new Blob([res.data.csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'ventas_' + localId + '.csv'; a.click();
            URL.revokeObjectURL(url);
        }
    };

    const peakHour = franjas.length > 0 ? franjas.indexOf(Math.max(...franjas)) : 0;

    return (
        <div className="analytics-panel">
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                <h3><BarChart3 size={16} /> Analitica</h3>
                <div style={{ display: 'flex', gap: 8 }}>
                    {['dia', 'semana', 'mes'].map(r => (
                        <button key={r} className={rango === r ? 'active' : ''} onClick={() => setRango(r)}>
                            {r.charAt(0).toUpperCase() + r.slice(1)}
                        </button>
                    ))}
                    <button onClick={exportCsv}><Download size={14} /> CSV</button>
                </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '1rem', marginBottom: '1.5rem' }}>
                <div className="kpi-card">
                    <div className="kpi-label">Ticket medio</div>
                    <div className="kpi-value">{(ticket.ticket_medio || 0).toFixed(2)} EUR</div>
                    <div className="kpi-sub">{ticket.total_sesiones || 0} sesiones</div>
                </div>
                <div className="kpi-card">
                    <div className="kpi-label">Rotacion mesas</div>
                    <div className="kpi-value">{rotacion.tiempo_medio_min || 0} min</div>
                    <div className="kpi-sub">tiempo medio</div>
                </div>
                <div className="kpi-card">
                    <div className="kpi-label">Total facturado</div>
                    <div className="kpi-value">{(ticket.total_facturado || 0).toFixed(2)} EUR</div>
                </div>
                <div className="kpi-card">
                    <div className="kpi-label">Franja pico</div>
                    <div className="kpi-value">{peakHour}:00h</div>
                </div>
            </div>

            <h4>Ranking productos</h4>
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                    <tr style={{ borderBottom: '2px solid #ddd', textAlign: 'left' }}>
                        <th style={{ padding: 6 }}>#</th>
                        <th style={{ padding: 6 }}>Producto</th>
                        <th style={{ padding: 6 }}>Unidades</th>
                        <th style={{ padding: 6 }}>Importe</th>
                    </tr>
                </thead>
                <tbody>
                    {ranking.slice(0, 15).map((p, i) => (
                        <tr key={i} style={{ borderBottom: '1px solid #eee' }}>
                            <td style={{ padding: 6 }}>{i + 1}</td>
                            <td style={{ padding: 6 }}>{p.nombre}</td>
                            <td style={{ padding: 6 }}>{p.unidades}</td>
                            <td style={{ padding: 6 }}>{(p.importe || 0).toFixed(2)} EUR</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
