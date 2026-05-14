import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useClinica } from '../context/ClinicaContext';
import { listPacientes, createPaciente, type Paciente } from '../services/clinica.service';
import { UserPlus, Search } from 'lucide-react';

interface Nuevo { nombre: string; email: string; telefono: string; notas: string; }
const EMPTY: Nuevo = { nombre: '', email: '', telefono: '', notas: '' };

function avatar(nombre: string): string {
    return nombre.trim().split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase() || '?';
}

export function PacientesPage() {
    const { client, localId } = useClinica();
    const navigate = useNavigate();
    const [pacientes, setPacientes] = useState<Paciente[]>([]);
    const [loading, setLoading] = useState(true);
    const [q, setQ] = useState('');
    const [form, setForm] = useState(false);
    const [data, setData] = useState<Nuevo>(EMPTY);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        setLoading(true);
        listPacientes(client, localId).then(setPacientes).catch(() => setPacientes([])).finally(() => setLoading(false));
    }, [client]);

    const filtered = pacientes.filter(p => {
        if (!q) return true;
        const s = q.toLowerCase();
        return (p.nombre ?? '').toLowerCase().includes(s) ||
               (p.email ?? '').toLowerCase().includes(s) ||
               (p.telefono ?? '').includes(s);
    });

    async function handleCreate() {
        if (!data.nombre) { setError('El nombre es obligatorio.'); return; }
        setSaving(true); setError('');
        try {
            const p = await createPaciente(client, localId, data);
            if (p.duplicate_of) {
                navigate(`/pacientes/${p.id}`);
                return;
            }
            setPacientes(prev => [p, ...prev]);
            setData(EMPTY); setForm(false);
        } catch (e: unknown) { setError(e instanceof Error ? e.message : 'Error al crear paciente.'); }
        finally { setSaving(false); }
    }

    return (
        <div className="db-card">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                <div>
                    <div className="db-card-title">Pacientes</div>
                    <div className="db-card-sub">{pacientes.length} registrado{pacientes.length !== 1 ? 's' : ''}</div>
                </div>
                <button className="db-btn db-btn--primary" onClick={() => setForm(f => !f)}><UserPlus size={15} /> Nuevo paciente</button>
            </div>

            {form && (
                <div className="cl-form">
                    <div className="cl-form-row">
                        <div><label className="cl-label">Nombre *</label><input className="cl-input" placeholder="Nombre completo" value={data.nombre} onChange={e => setData(d => ({ ...d, nombre: e.target.value }))} /></div>
                        <div><label className="cl-label">Teléfono</label><input className="cl-input" value={data.telefono} onChange={e => setData(d => ({ ...d, telefono: e.target.value }))} /></div>
                    </div>
                    <div><label className="cl-label">Email</label><input className="cl-input" type="email" value={data.email} onChange={e => setData(d => ({ ...d, email: e.target.value }))} /></div>
                    <div><label className="cl-label">Notas</label><input className="cl-input" value={data.notas} onChange={e => setData(d => ({ ...d, notas: e.target.value }))} /></div>
                    {error && <p style={{ color: '#dc2626', fontSize: 13 }}>{error}</p>}
                    <div style={{ display: 'flex', gap: 8 }}>
                        <button className="db-btn db-btn--primary" disabled={saving} onClick={handleCreate}>{saving ? 'Guardando…' : 'Crear'}</button>
                        <button className="db-btn db-btn--ghost" onClick={() => { setForm(false); setError(''); }}>Cancelar</button>
                    </div>
                </div>
            )}

            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 12 }}>
                <Search size={15} color="var(--cl-text-muted)" />
                <input className="cl-input" style={{ flex: 1 }} placeholder="Buscar…" value={q} onChange={e => setQ(e.target.value)} />
            </div>

            {loading && <div className="db-ia-status"><div className="db-ia-dot" />Cargando pacientes…</div>}

            {!loading && filtered.length === 0 && (
                <div style={{ textAlign: 'center', padding: '32px 0', color: 'var(--cl-text-muted)' }}>
                    {q ? 'Sin resultados.' : 'No hay pacientes aún.'}
                </div>
            )}

            {!loading && filtered.map(p => (
                <div key={p.id} className="cl-pac-row" onClick={() => navigate(`/pacientes/${p.id}`)}>
                    <div className="cl-pac-avatar">{avatar(p.nombre)}</div>
                    <div style={{ flex: 1 }}>
                        <div className="cl-pac-name">{p.nombre}</div>
                        <div className="cl-pac-meta">{[p.telefono, p.email].filter(Boolean).join(' · ')}</div>
                    </div>
                </div>
            ))}
        </div>
    );
}
