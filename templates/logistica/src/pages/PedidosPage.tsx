import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useLogistica } from '../context/LogisticaContext';
import { listPedidos, createPedido, type Pedido } from '../services/delivery.service';
import { Plus, Package } from 'lucide-react';

const ESTADOS = ['recibido', 'preparando', 'en_ruta', 'entregado', 'incidencia'] as const;
type Estado = typeof ESTADOS[number];

const EMPTY = { cliente: '', telefono: '', email: '', direccion: '', notas: '' };

function EstadoBadge({ estado }: { estado: string }) {
    return <span className={`lg-badge lg-badge--${estado}`}>{estado.replace('_', ' ')}</span>;
}

function fmtFecha(iso: string): string {
    return new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

export function PedidosPage() {
    const { client, localId } = useLogistica();
    const navigate = useNavigate();
    const [pedidos, setPedidos] = useState<Pedido[]>([]);
    const [loading, setLoading] = useState(true);
    const [filtro, setFiltro] = useState<Estado | ''>('');
    const [form, setForm] = useState(false);
    const [data, setData] = useState(EMPTY);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    function load(estado?: string) {
        setLoading(true);
        listPedidos(client, localId, estado || undefined)
            .then(setPedidos).catch(() => setPedidos([]))
            .finally(() => setLoading(false));
    }

    useEffect(() => { load(filtro); }, [client, filtro]);

    async function handleCreate() {
        if (!data.cliente || !data.direccion) { setError('Cliente y dirección son obligatorios.'); return; }
        setSaving(true); setError('');
        try {
            const p = await createPedido(client, localId, data);
            setPedidos(prev => [p, ...prev]);
            setData(EMPTY); setForm(false);
        } catch (e: unknown) { setError(e instanceof Error ? e.message : 'Error al crear.'); }
        finally { setSaving(false); }
    }

    return (
        <div className="lg-card">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                <div>
                    <div className="lg-card-title">Pedidos</div>
                    <div className="lg-card-sub">{pedidos.length} pedido{pedidos.length !== 1 ? 's' : ''}</div>
                </div>
                <button className="lg-btn lg-btn--primary" onClick={() => setForm(f => !f)}><Plus size={15} /> Nuevo pedido</button>
            </div>

            {form && (
                <div className="lg-form">
                    <div className="lg-form-row">
                        <div><label className="lg-label">Cliente *</label><input className="lg-input" placeholder="Nombre del cliente" value={data.cliente} onChange={e => setData(d => ({ ...d, cliente: e.target.value }))} /></div>
                        <div><label className="lg-label">Teléfono</label><input className="lg-input" value={data.telefono} onChange={e => setData(d => ({ ...d, telefono: e.target.value }))} /></div>
                    </div>
                    <div><label className="lg-label">Dirección de entrega *</label><input className="lg-input" value={data.direccion} onChange={e => setData(d => ({ ...d, direccion: e.target.value }))} /></div>
                    <div><label className="lg-label">Notas</label><input className="lg-input" value={data.notas} onChange={e => setData(d => ({ ...d, notas: e.target.value }))} /></div>
                    {error && <p style={{ color: '#dc2626', fontSize: 13 }}>{error}</p>}
                    <div style={{ display: 'flex', gap: 8 }}>
                        <button className="lg-btn lg-btn--primary" disabled={saving} onClick={handleCreate}>{saving ? 'Guardando…' : 'Crear pedido'}</button>
                        <button className="lg-btn lg-btn--ghost" onClick={() => { setForm(false); setError(''); }}>Cancelar</button>
                    </div>
                </div>
            )}

            <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap', marginBottom: 12 }}>
                <button className={`lg-btn lg-btn--ghost${filtro === '' ? ' active' : ''}`} style={{ fontSize: 12, padding: '4px 10px' }} onClick={() => setFiltro('')}>Todos</button>
                {ESTADOS.map(e => (
                    <button key={e} className={`lg-btn lg-btn--ghost${filtro === e ? ' active' : ''}`} style={{ fontSize: 12, padding: '4px 10px' }} onClick={() => setFiltro(e)}>
                        {e.replace('_', ' ')}
                    </button>
                ))}
            </div>

            {loading && <div className="lg-status"><div className="lg-dot" />Cargando pedidos…</div>}

            {!loading && pedidos.length === 0 && (
                <div style={{ textAlign: 'center', padding: '32px 0', color: 'var(--lg-text-muted)' }}>
                    <Package size={32} style={{ opacity: 0.3, marginBottom: 8 }} />
                    <p>{filtro ? `Sin pedidos en estado "${filtro}".` : 'No hay pedidos aún.'}</p>
                </div>
            )}

            {!loading && pedidos.map(p => (
                <div key={p.id} className="lg-pedido-row" onClick={() => navigate(`/pedidos/${p.id}`)}>
                    <span className="lg-pedido-codigo">{p.codigo_seguimiento}</span>
                    <div style={{ flex: 1, minWidth: 0 }}>
                        <div className="lg-pedido-cliente">{p.cliente}</div>
                        <div className="lg-pedido-dir">{p.direccion}</div>
                    </div>
                    <EstadoBadge estado={p.estado} />
                    <div style={{ fontSize: 11, color: 'var(--lg-text-muted)', flexShrink: 0 }}>{fmtFecha(p.created_at)}</div>
                </div>
            ))}
        </div>
    );
}
