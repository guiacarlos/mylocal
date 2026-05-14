import { useEffect, useState } from 'react';
import { useAsesoria } from '../context/AsesoriaContext';
import { listVencimientos, createVencimiento, cancelarVencimiento, type Vencimiento } from '../services/asesoria.service';
import { CalendarDays, Plus, X } from 'lucide-react';

function fmtFecha(iso: string): string {
    return new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
}

function estadoVcto(v: Vencimiento): string {
    if (v.estado === 'cancelada') return 'cancelada';
    if (v.estado === 'completada') return 'completada';
    const ahora = new Date().toISOString();
    return v.inicio < ahora ? 'vencida' : 'pendiente';
}

const EMPTY = { cliente: '', inicio: '', fin: '', notas: '' };

export function CalendarioFiscalPage() {
    const { client, localId } = useAsesoria();
    const [vencimientos, setVencimientos] = useState<Vencimiento[]>([]);
    const [loading, setLoading] = useState(true);
    const [form, setForm] = useState(false);
    const [data, setData] = useState(EMPTY);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        setLoading(true);
        listVencimientos(client, localId).then(setVencimientos).catch(() => setVencimientos([])).finally(() => setLoading(false));
    }, [client]);

    async function handleCreate() {
        if (!data.cliente || !data.inicio || !data.fin) { setError('Cliente, fecha inicio y fin son obligatorios.'); return; }
        setSaving(true); setError('');
        try {
            const v = await createVencimiento(client, localId, {
                cliente: data.cliente,
                inicio: data.inicio + ':00+00:00',
                fin: data.fin + ':00+00:00',
                notas: data.notas,
            });
            setVencimientos(prev => [v, ...prev]);
            setData(EMPTY); setForm(false);
        } catch (e: unknown) { setError(e instanceof Error ? e.message : 'Error.'); }
        finally { setSaving(false); }
    }

    async function handleCancel(id: string) {
        try {
            const v = await cancelarVencimiento(client, id);
            setVencimientos(prev => prev.map(x => x.id === id ? v : x));
        } catch (_) { /* silencioso */ }
    }

    const sorted = [...vencimientos].sort((a, b) => a.inicio.localeCompare(b.inicio));

    return (
        <div className="as-card">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                <div>
                    <div className="as-card-title">Calendario fiscal</div>
                    <div className="as-card-sub">Vencimientos y obligaciones tributarias por cliente.</div>
                </div>
                <button className="as-btn as-btn--primary" onClick={() => setForm(f => !f)}><Plus size={15} /> Nuevo vencimiento</button>
            </div>

            {form && (
                <div className="as-form">
                    <div><label className="as-label">Cliente / Obligación *</label><input className="as-input" placeholder="Ej: ACME S.L. — IVA trimestral" value={data.cliente} onChange={e => setData(d => ({ ...d, cliente: e.target.value }))} /></div>
                    <div className="as-form-row">
                        <div><label className="as-label">Fecha vencimiento *</label><input className="as-input" type="datetime-local" value={data.inicio} onChange={e => setData(d => ({ ...d, inicio: e.target.value }))} /></div>
                        <div><label className="as-label">Fecha límite *</label><input className="as-input" type="datetime-local" value={data.fin} onChange={e => setData(d => ({ ...d, fin: e.target.value }))} /></div>
                    </div>
                    <div><label className="as-label">Notas</label><input className="as-input" placeholder="Modelo 303, importe estimado…" value={data.notas} onChange={e => setData(d => ({ ...d, notas: e.target.value }))} /></div>
                    {error && <p style={{ color: '#dc2626', fontSize: 13 }}>{error}</p>}
                    <div style={{ display: 'flex', gap: 8 }}>
                        <button className="as-btn as-btn--primary" disabled={saving} onClick={handleCreate}>{saving ? 'Guardando…' : 'Crear'}</button>
                        <button className="as-btn as-btn--ghost" onClick={() => { setForm(false); setError(''); }}>Cancelar</button>
                    </div>
                </div>
            )}

            {loading && <div className="as-status"><div className="as-dot" />Cargando vencimientos…</div>}

            {!loading && sorted.length === 0 && (
                <div style={{ textAlign: 'center', padding: '32px 0', color: 'var(--as-text-muted)' }}>
                    <CalendarDays size={32} style={{ opacity: 0.3, marginBottom: 8 }} />
                    <p style={{ fontSize: 13 }}>Sin vencimientos. Añade el primero.</p>
                </div>
            )}

            {!loading && sorted.map(v => {
                const est = estadoVcto(v);
                return (
                    <div key={v.id} className="as-vcto-row">
                        <div className="as-vcto-fecha">{fmtFecha(v.inicio)}</div>
                        <div className="as-vcto-label">
                            {v.cliente}
                            {v.notas && <div style={{ fontSize: 11, color: 'var(--as-text-muted)' }}>{v.notas}</div>}
                        </div>
                        <span className={`as-vcto-estado as-vcto-${est}`}>{est}</span>
                        {est !== 'cancelada' && est !== 'completada' && (
                            <button className="as-btn as-btn--ghost as-btn--sm" title="Cancelar" onClick={() => handleCancel(v.id)}>
                                <X size={11} />
                            </button>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
