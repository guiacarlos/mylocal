import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useDashboard } from '../../../components/dashboard/DashboardContext';
import { getPaciente, listInteracciones, addInteraccion, type Paciente, type Interaccion } from '../services/clinica.service';
import { ArrowLeft, PlusCircle } from 'lucide-react';

const TIPOS = ['nota', 'llamada', 'visita', 'email', 'whatsapp'] as const;

function fmtTs(ts: string): string {
    if (!ts) return '';
    const d = new Date(ts);
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

export function HistorialPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { client } = useDashboard();

    const [paciente, setPaciente] = useState<Paciente | null>(null);
    const [interacciones, setInteracciones] = useState<Interaccion[]>([]);
    const [loading, setLoading] = useState(true);
    const [tipo, setTipo] = useState<string>('nota');
    const [nota, setNota] = useState('');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        if (!id) return;
        setLoading(true);
        Promise.all([
            getPaciente(client, id),
            listInteracciones(client, id),
        ]).then(([p, inters]) => {
            setPaciente(p);
            setInteracciones(inters);
        }).catch(() => setPaciente(null)).finally(() => setLoading(false));
    }, [client, id]);

    async function handleAdd() {
        if (!nota.trim() || !id) { setError('La nota no puede estar vacía.'); return; }
        setSaving(true); setError('');
        try {
            const i = await addInteraccion(client, id, tipo, nota.trim());
            setInteracciones(prev => [i, ...prev]);
            setNota('');
        } catch (e: unknown) { setError(e instanceof Error ? e.message : 'Error al guardar.'); }
        finally { setSaving(false); }
    }

    if (loading) return <div className="db-card"><div className="db-ia-status"><div className="db-ia-dot" />Cargando…</div></div>;

    if (!paciente) return (
        <div className="db-card">
            <p style={{ color: 'var(--sp-text-muted)' }}>Paciente no encontrado.</p>
            <button className="db-btn db-btn--ghost" onClick={() => navigate('/dashboard/pacientes')}><ArrowLeft size={14} /> Volver</button>
        </div>
    );

    return (
        <div className="db-card">
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 16 }}>
                <button className="db-btn db-btn--ghost" onClick={() => navigate('/dashboard/pacientes')}><ArrowLeft size={14} /></button>
                <div>
                    <div className="db-card-title" style={{ marginBottom: 0 }}>{paciente.nombre}</div>
                    <div style={{ fontSize: 13, color: 'var(--sp-text-muted)' }}>
                        {[paciente.telefono, paciente.email].filter(Boolean).join(' · ')}
                    </div>
                </div>
            </div>

            {paciente.notas && <p style={{ fontSize: 13, background: 'var(--sp-bg-soft)', padding: '8px 12px', borderRadius: 6, marginBottom: 16 }}>{paciente.notas}</p>}

            <div style={{ display: 'flex', gap: 8, alignItems: 'flex-start', marginBottom: 20 }}>
                <select className="cl-input" style={{ width: 130 }} value={tipo} onChange={e => setTipo(e.target.value)}>
                    {TIPOS.map(t => <option key={t} value={t}>{t.charAt(0).toUpperCase() + t.slice(1)}</option>)}
                </select>
                <input className="cl-input" style={{ flex: 1 }} placeholder="Escribe una nota, llamada o visita…" value={nota} onChange={e => setNota(e.target.value)} onKeyDown={e => e.key === 'Enter' && handleAdd()} />
                <button className="db-btn db-btn--primary" disabled={saving} onClick={handleAdd}><PlusCircle size={15} /></button>
            </div>
            {error && <p style={{ color: '#dc2626', fontSize: 13, marginTop: -12, marginBottom: 8 }}>{error}</p>}

            {interacciones.length === 0 && (
                <p style={{ color: 'var(--sp-text-muted)', fontSize: 13 }}>Sin historial. Añade la primera nota.</p>
            )}

            <div className="cl-timeline">
                {interacciones.map(i => (
                    <div key={i.id} className="cl-tl-item">
                        <div className="cl-tl-dot" />
                        <div className="cl-tl-meta">{fmtTs(i.ts)} · <strong>{i.tipo}</strong></div>
                        <div className="cl-tl-body">{i.nota}</div>
                    </div>
                ))}
            </div>
        </div>
    );
}
