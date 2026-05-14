import { useEffect, useState } from 'react';
import { useLogistica } from '../context/LogisticaContext';
import { listEntregasDia, listVehiculos, listPedidos, asignarEntrega, type Entrega, type Vehiculo, type Pedido } from '../services/delivery.service';
import { ChevronLeft, ChevronRight, Truck } from 'lucide-react';

function toISO(d: Date): string { return d.toISOString().slice(0, 10); }
function fmtFecha(iso: string): string {
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
}

export function EntregasPage() {
    const { client, localId } = useLogistica();
    const [fecha, setFecha] = useState(toISO(new Date()));
    const [entregas, setEntregas] = useState<Entrega[]>([]);
    const [vehiculos, setVehiculos] = useState<Vehiculo[]>([]);
    const [pedidos, setPedidos] = useState<Pedido[]>([]);
    const [loading, setLoading] = useState(true);
    const [asignando, setAsignando] = useState(false);
    const [selPedido, setSelPedido] = useState('');
    const [selVehiculo, setSelVehiculo] = useState('');
    const [error, setError] = useState('');

    useEffect(() => {
        setLoading(true);
        Promise.all([
            listEntregasDia(client, localId, fecha),
            listVehiculos(client, localId),
            listPedidos(client, localId, 'preparando'),
        ]).then(([e, v, p]) => {
            setEntregas(e);
            setVehiculos(v.filter(v => v.estado === 'activo'));
            setPedidos(p);
        }).catch(() => {}).finally(() => setLoading(false));
    }, [client, fecha]);

    function changeDay(n: number) {
        const d = new Date(fecha);
        d.setDate(d.getDate() + n);
        setFecha(toISO(d));
    }

    async function handleAsignar() {
        if (!selPedido || !selVehiculo) { setError('Selecciona pedido y vehículo.'); return; }
        setAsignando(true); setError('');
        try {
            const e = await asignarEntrega(client, selPedido, selVehiculo, fecha);
            setEntregas(prev => [...prev, e]);
            setSelPedido(''); setSelVehiculo('');
        } catch (e: unknown) { setError(e instanceof Error ? e.message : 'Error.'); }
        finally { setAsignando(false); }
    }

    const vMap = Object.fromEntries(vehiculos.map(v => [v.id, v]));
    const pMap = Object.fromEntries(pedidos.map(p => [p.id, p]));

    return (
        <div className="lg-card">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                <div>
                    <div className="lg-card-title">Entregas del día</div>
                    <div className="lg-card-sub">{fmtFecha(fecha)} · {entregas.length} asignada{entregas.length !== 1 ? 's' : ''}</div>
                </div>
                <div style={{ display: 'flex', gap: 8 }}>
                    <button className="lg-btn lg-btn--ghost" onClick={() => changeDay(-1)}><ChevronLeft size={16} /></button>
                    <button className="lg-btn lg-btn--ghost" onClick={() => setFecha(toISO(new Date()))}>Hoy</button>
                    <button className="lg-btn lg-btn--ghost" onClick={() => changeDay(1)}><ChevronRight size={16} /></button>
                </div>
            </div>

            <div className="lg-form" style={{ marginBottom: 16 }}>
                <div className="lg-form-row">
                    <div>
                        <label className="lg-label">Pedido (preparando)</label>
                        <select className="lg-input" value={selPedido} onChange={e => setSelPedido(e.target.value)}>
                            <option value="">— seleccionar —</option>
                            {pedidos.map(p => <option key={p.id} value={p.id}>{p.codigo_seguimiento} · {p.cliente}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="lg-label">Vehículo</label>
                        <select className="lg-input" value={selVehiculo} onChange={e => setSelVehiculo(e.target.value)}>
                            <option value="">— seleccionar —</option>
                            {vehiculos.map(v => <option key={v.id} value={v.id}>{v.matricula} · {v.conductor}</option>)}
                        </select>
                    </div>
                </div>
                {error && <p style={{ color: '#dc2626', fontSize: 13 }}>{error}</p>}
                <div>
                    <button className="lg-btn lg-btn--primary" disabled={asignando} onClick={handleAsignar}>
                        <Truck size={14} /> {asignando ? 'Asignando…' : 'Asignar entrega'}
                    </button>
                </div>
            </div>

            {loading && <div className="lg-status"><div className="lg-dot" />Cargando entregas…</div>}

            {!loading && entregas.length === 0 && (
                <p style={{ color: 'var(--lg-text-muted)', fontSize: 13, textAlign: 'center', padding: '16px 0' }}>Sin entregas asignadas para este día.</p>
            )}

            {!loading && entregas.map(e => {
                const v = vMap[e.vehiculo_id];
                const p = pMap[e.pedido_id];
                return (
                    <div key={e.id} className="lg-vehiculo-row">
                        <Truck size={16} color="var(--lg-accent)" />
                        <div style={{ flex: 1 }}>
                            <div style={{ fontWeight: 500, fontSize: 'var(--lg-text-sm)' }}>
                                {v ? `${v.matricula} · ${v.conductor}` : e.vehiculo_id}
                            </div>
                            <div style={{ fontSize: 'var(--lg-text-xs)', color: 'var(--lg-text-muted)' }}>
                                {p ? `${p.codigo_seguimiento} · ${p.cliente} · ${p.direccion}` : e.pedido_id}
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
