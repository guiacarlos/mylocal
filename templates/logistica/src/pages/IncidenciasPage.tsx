import { useEffect, useState } from 'react';
import { useLogistica } from '../context/LogisticaContext';
import { listPedidos, addIncidencia, type Pedido, type Incidencia } from '../services/delivery.service';
import { AlertTriangle, PlusCircle } from 'lucide-react';

const TIPOS_INCIDENCIA = ['daño', 'retraso', 'dirección_incorrecta', 'cliente_ausente', 'devolucion', 'otro'];

function fmtFecha(iso: string): string {
    return new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

export function IncidenciasPage() {
    const { client, localId } = useLogistica();
    const [pedidos, setPedidos] = useState<Pedido[]>([]);
    const [incidencias, setIncidencias] = useState<Incidencia[]>([]);
    const [loading, setLoading] = useState(true);
    const [selPedido, setSelPedido] = useState('');
    const [tipo, setTipo] = useState('otro');
    const [descripcion, setDescripcion] = useState('');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        setLoading(true);
        listPedidos(client, localId, 'incidencia')
            .then(setPedidos).catch(() => setPedidos([]))
            .finally(() => setLoading(false));
    }, [client]);

    async function handleAdd() {
        if (!selPedido || !descripcion.trim()) { setError('Pedido y descripción son obligatorios.'); return; }
        setSaving(true); setError('');
        try {
            const i = await addIncidencia(client, selPedido, tipo, descripcion.trim());
            setIncidencias(prev => [i, ...prev]);
            setSelPedido(''); setDescripcion('');
        } catch (e: unknown) { setError(e instanceof Error ? e.message : 'Error.'); }
        finally { setSaving(false); }
    }

    return (
        <div className="lg-card">
            <div className="lg-card-title">Incidencias</div>
            <div className="lg-card-sub">Registra problemas en las entregas y gestiona su resolución.</div>

            <div className="lg-form" style={{ marginBottom: 20 }}>
                <div className="lg-form-row">
                    <div>
                        <label className="lg-label">Pedido afectado</label>
                        <select className="lg-input" value={selPedido} onChange={e => setSelPedido(e.target.value)}>
                            <option value="">— buscar pedido —</option>
                            {pedidos.map(p => <option key={p.id} value={p.id}>{p.codigo_seguimiento} · {p.cliente}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="lg-label">Tipo</label>
                        <select className="lg-input" value={tipo} onChange={e => setTipo(e.target.value)}>
                            {TIPOS_INCIDENCIA.map(t => <option key={t} value={t}>{t.replace('_', ' ')}</option>)}
                        </select>
                    </div>
                </div>
                <div>
                    <label className="lg-label">Descripción</label>
                    <input className="lg-input" placeholder="Describe el problema…" value={descripcion} onChange={e => setDescripcion(e.target.value)} onKeyDown={e => e.key === 'Enter' && handleAdd()} />
                </div>
                {error && <p style={{ color: '#dc2626', fontSize: 13 }}>{error}</p>}
                <div>
                    <button className="lg-btn lg-btn--danger" disabled={saving} onClick={handleAdd}>
                        <PlusCircle size={14} /> {saving ? 'Registrando…' : 'Registrar incidencia'}
                    </button>
                </div>
            </div>

            {loading && <div className="lg-status"><div className="lg-dot" />Cargando…</div>}

            {!loading && incidencias.length === 0 && (
                <div style={{ textAlign: 'center', padding: '24px 0', color: 'var(--lg-text-muted)' }}>
                    <AlertTriangle size={28} style={{ opacity: 0.3, marginBottom: 8 }} />
                    <p style={{ fontSize: 13 }}>Sin incidencias registradas. Cuando aparezcan, se listarán aquí.</p>
                </div>
            )}

            {incidencias.map(i => (
                <div key={i.id} className="lg-inc-row">
                    <div className="lg-inc-tipo">{i.tipo.replace('_', ' ')}</div>
                    <div className="lg-inc-desc">{i.descripcion}</div>
                    <div className="lg-inc-meta">{fmtFecha(i.created_at)} · Pedido: {i.pedido_id.slice(0, 10)}…</div>
                </div>
            ))}
        </div>
    );
}
