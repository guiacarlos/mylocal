import { useEffect, useState } from 'react';
import { useDashboard, LOCAL_ID } from '../../../components/dashboard/DashboardContext';
import { listCitas, createCita, cancelCita, type Cita } from '../services/clinica.service';
import { ChevronLeft, ChevronRight, Plus, X } from 'lucide-react';

const DAY_NAMES = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

function weekStart(d: Date): Date {
    const day = d.getDay() || 7;
    const s = new Date(d);
    s.setDate(d.getDate() - (day - 1));
    s.setHours(0, 0, 0, 0);
    return s;
}
function addDays(d: Date, n: number): Date {
    const r = new Date(d);
    r.setDate(r.getDate() + n);
    return r;
}
function toISO(d: Date): string { return d.toISOString().slice(0, 10); }
function fmtTime(iso: string): string { return iso.slice(11, 16); }
function fmtDate(d: Date): string { return `${d.getDate()}/${d.getMonth() + 1}`; }
function isToday(d: Date): boolean { return toISO(d) === toISO(new Date()); }

interface NuevaCita { cliente: string; telefono: string; inicio: string; fin: string; notas: string; }
const EMPTY: NuevaCita = { cliente: '', telefono: '', inicio: '', fin: '', notas: '' };

export function AgendaPage() {
    const { client } = useDashboard();
    const [base, setBase] = useState(() => weekStart(new Date()));
    const [citas, setCitas] = useState<Cita[]>([]);
    const [loading, setLoading] = useState(true);
    const [form, setForm] = useState(false);
    const [data, setData] = useState<NuevaCita>(EMPTY);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    const since = base;
    const until = addDays(base, 6);

    useEffect(() => {
        setLoading(true);
        listCitas(client, LOCAL_ID, since.toISOString(), addDays(until, 1).toISOString())
            .then(setCitas).catch(() => setCitas([])).finally(() => setLoading(false));
    }, [client, base]);

    const days = Array.from({ length: 7 }, (_, i) => addDays(base, i));

    function citasForDay(d: Date): Cita[] {
        const day = toISO(d);
        return citas.filter(c => c.inicio.slice(0, 10) === day).sort((a, b) => a.inicio.localeCompare(b.inicio));
    }

    async function handleCreate() {
        if (!data.cliente || !data.inicio || !data.fin) { setError('Cliente, inicio y fin son obligatorios.'); return; }
        setSaving(true); setError('');
        try {
            const c = await createCita(client, { local_id: LOCAL_ID, recurso_id: 'r_default', ...data });
            setCitas(prev => [...prev, c]);
            setData(EMPTY); setForm(false);
        } catch (e: unknown) { setError(e instanceof Error ? e.message : 'Error al crear cita.'); }
        finally { setSaving(false); }
    }

    async function handleCancel(id: string) {
        try { const c = await cancelCita(client, id); setCitas(prev => prev.map(x => x.id === id ? c : x)); }
        catch (_) { /* silencioso */ }
    }

    return (
        <div className="db-card">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                <div>
                    <div className="db-card-title">Agenda semanal</div>
                    <div className="db-card-sub">{fmtDate(since)} — {fmtDate(until)}</div>
                </div>
                <div className="db-btn-group">
                    <button className="db-btn db-btn--ghost" onClick={() => setBase(b => addDays(b, -7))}><ChevronLeft size={16} /></button>
                    <button className="db-btn db-btn--ghost" onClick={() => setBase(weekStart(new Date()))}>Hoy</button>
                    <button className="db-btn db-btn--ghost" onClick={() => setBase(b => addDays(b, 7))}><ChevronRight size={16} /></button>
                    <button className="db-btn db-btn--primary" onClick={() => setForm(f => !f)}><Plus size={15} /> Nueva cita</button>
                </div>
            </div>

            {form && (
                <div className="cl-form">
                    <div className="cl-form-row">
                        <div><label className="cl-label">Cliente</label><input className="cl-input" placeholder="Nombre" value={data.cliente} onChange={e => setData(d => ({ ...d, cliente: e.target.value }))} /></div>
                        <div><label className="cl-label">Teléfono</label><input className="cl-input" placeholder="+34..." value={data.telefono} onChange={e => setData(d => ({ ...d, telefono: e.target.value }))} /></div>
                    </div>
                    <div className="cl-form-row">
                        <div><label className="cl-label">Inicio</label><input className="cl-input" type="datetime-local" value={data.inicio} onChange={e => setData(d => ({ ...d, inicio: e.target.value + ':00+00:00' }))} /></div>
                        <div><label className="cl-label">Fin</label><input className="cl-input" type="datetime-local" value={data.fin} onChange={e => setData(d => ({ ...d, fin: e.target.value + ':00+00:00' }))} /></div>
                    </div>
                    <div><label className="cl-label">Notas</label><input className="cl-input" placeholder="Opcional" value={data.notas} onChange={e => setData(d => ({ ...d, notas: e.target.value }))} /></div>
                    {error && <p style={{ color: '#dc2626', fontSize: 13 }}>{error}</p>}
                    <div className="db-btn-group">
                        <button className="db-btn db-btn--primary" disabled={saving} onClick={handleCreate}>{saving ? 'Guardando…' : 'Crear cita'}</button>
                        <button className="db-btn db-btn--ghost" onClick={() => { setForm(false); setError(''); }}>Cancelar</button>
                    </div>
                </div>
            )}

            {loading && <div className="db-ia-status"><div className="db-ia-dot" />Cargando citas…</div>}

            {!loading && (
                <div className="cl-week-days" style={{ marginTop: 8 }}>
                    {days.map((d, i) => {
                        const dc = citasForDay(d);
                        return (
                            <div key={i} className={`cl-day-col${isToday(d) ? ' cl-day-today' : ''}`}>
                                <div className="cl-day-name">{DAY_NAMES[i]}</div>
                                <div className="cl-day-date">{fmtDate(d)}</div>
                                {dc.length === 0 && <div style={{ fontSize: 11, color: 'var(--sp-text-muted)' }}>Sin citas</div>}
                                {dc.map(c => (
                                    <div key={c.id} className={`cl-cita-chip cl-cita-chip--${c.estado}`}>
                                        <span className="cl-cita-time">{fmtTime(c.inicio)}</span> {c.cliente}
                                        {c.estado !== 'cancelada' && (
                                            <button title="Cancelar" style={{ float: 'right', background: 'none', border: 'none', cursor: 'pointer', padding: 0 }} onClick={() => handleCancel(c.id)}><X size={10} /></button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
