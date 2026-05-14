import { useEffect, useState } from 'react';
import { useAsesoria } from '../context/AsesoriaContext';
import { listTareas, createTarea, moverTarea, deleteTarea, type Tarea } from '../services/asesoria.service';
import { Plus, ChevronRight, ChevronLeft, Trash2 } from 'lucide-react';

const COLUMNAS: { estado: Tarea['estado']; label: string }[] = [
    { estado: 'pendiente', label: 'Pendiente' },
    { estado: 'en_curso',  label: 'En curso' },
    { estado: 'hecho',     label: 'Hecho' },
];

const EMPTY = { titulo: '', prioridad: 'media' as Tarea['prioridad'] };

export function TareasPage() {
    const { client, localId } = useAsesoria();
    const [tareas, setTareas] = useState<Tarea[]>([]);
    const [loading, setLoading] = useState(true);
    const [form, setForm] = useState(false);
    const [data, setData] = useState(EMPTY);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        setLoading(true);
        listTareas(client, localId).then(setTareas).catch(() => setTareas([])).finally(() => setLoading(false));
    }, [client]);

    async function handleCreate() {
        if (!data.titulo.trim()) { setError('El título es obligatorio.'); return; }
        setSaving(true); setError('');
        try {
            const t = await createTarea(client, localId, data);
            setTareas(prev => [t, ...prev]);
            setData(EMPTY); setForm(false);
        } catch (e: unknown) { setError(e instanceof Error ? e.message : 'Error.'); }
        finally { setSaving(false); }
    }

    async function mover(tarea: Tarea, dir: 1 | -1) {
        const idx = COLUMNAS.findIndex(c => c.estado === tarea.estado);
        const next = COLUMNAS[idx + dir];
        if (!next) return;
        const updated = await moverTarea(client, tarea.id, next.estado);
        setTareas(prev => prev.map(t => t.id === tarea.id ? updated : t));
    }

    async function eliminar(id: string) {
        await deleteTarea(client, id);
        setTareas(prev => prev.filter(t => t.id !== id));
    }

    return (
        <div className="as-card">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                <div>
                    <div className="as-card-title">Tareas</div>
                    <div className="as-card-sub">{tareas.filter(t => t.estado !== 'hecho').length} activa{tareas.filter(t => t.estado !== 'hecho').length !== 1 ? 's' : ''}</div>
                </div>
                <button className="as-btn as-btn--primary" onClick={() => setForm(f => !f)}><Plus size={15} /> Nueva tarea</button>
            </div>

            {form && (
                <div className="as-form">
                    <div className="as-form-row">
                        <div><label className="as-label">Título *</label><input className="as-input" placeholder="Describe la tarea…" value={data.titulo} onChange={e => setData(d => ({ ...d, titulo: e.target.value }))} onKeyDown={e => e.key === 'Enter' && handleCreate()} /></div>
                        <div><label className="as-label">Prioridad</label>
                            <select className="as-input" value={data.prioridad} onChange={e => setData(d => ({ ...d, prioridad: e.target.value as Tarea['prioridad'] }))}>
                                <option value="alta">Alta</option>
                                <option value="media">Media</option>
                                <option value="baja">Baja</option>
                            </select>
                        </div>
                    </div>
                    {error && <p style={{ color: '#dc2626', fontSize: 13 }}>{error}</p>}
                    <div style={{ display: 'flex', gap: 8 }}>
                        <button className="as-btn as-btn--primary" disabled={saving} onClick={handleCreate}>{saving ? 'Guardando…' : 'Crear'}</button>
                        <button className="as-btn as-btn--ghost" onClick={() => { setForm(false); setError(''); }}>Cancelar</button>
                    </div>
                </div>
            )}

            {loading && <div className="as-status"><div className="as-dot" />Cargando tareas…</div>}

            {!loading && (
                <div className="as-kanban" style={{ marginTop: 16 }}>
                    {COLUMNAS.map((col, colIdx) => (
                        <div key={col.estado} className="as-kanban-col">
                            <div className="as-kanban-title">
                                {col.label} <span style={{ fontWeight: 400 }}>({tareas.filter(t => t.estado === col.estado).length})</span>
                            </div>
                            {tareas.filter(t => t.estado === col.estado).map(t => (
                                <div key={t.id} className="as-tarea-card">
                                    <div style={{ display: 'flex', alignItems: 'flex-start', gap: 4 }}>
                                        <span className={`as-prio-dot as-prio-${t.prioridad}`} style={{ marginTop: 5 }} />
                                        <div className="as-tarea-titulo" style={{ flex: 1 }}>{t.titulo}</div>
                                    </div>
                                    <div style={{ display: 'flex', gap: 4, marginTop: 8 }}>
                                        {colIdx > 0 && (
                                            <button className="as-btn as-btn--ghost as-btn--sm" onClick={() => mover(t, -1)}><ChevronLeft size={12} /></button>
                                        )}
                                        {colIdx < COLUMNAS.length - 1 && (
                                            <button className="as-btn as-btn--ghost as-btn--sm" onClick={() => mover(t, 1)}><ChevronRight size={12} /></button>
                                        )}
                                        <button className="as-btn as-btn--ghost as-btn--sm" onClick={() => eliminar(t.id)} title="Eliminar"><Trash2 size={11} /></button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
