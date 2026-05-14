import { useEffect, useState } from 'react';
import { useAsesoria } from '../context/AsesoriaContext';
import { listClientes, createCliente, type Cliente } from '../services/asesoria.service';
import { UserPlus, Search } from 'lucide-react';

const REGIMENES = ['General', 'Módulos', 'Simplificado', 'Recargo de Equivalencia', 'Autónomo', 'Sociedad'];

function avatar(nombre: string) {
    return nombre.trim().split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase() || '?';
}

const EMPTY = { nombre: '', email: '', telefono: '', nif: '', regimen: 'General', notas: '' };

export function ClientesPage() {
    const { client, localId } = useAsesoria();
    const [clientes, setClientes] = useState<Cliente[]>([]);
    const [loading, setLoading] = useState(true);
    const [q, setQ] = useState('');
    const [form, setForm] = useState(false);
    const [data, setData] = useState(EMPTY);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        setLoading(true);
        listClientes(client, localId).then(setClientes).catch(() => setClientes([])).finally(() => setLoading(false));
    }, [client]);

    const filtered = clientes.filter(c => {
        if (!q) return true;
        const s = q.toLowerCase();
        return (c.nombre ?? '').toLowerCase().includes(s) ||
               (c.nif ?? '').toLowerCase().includes(s) ||
               (c.email ?? '').toLowerCase().includes(s);
    });

    async function handleCreate() {
        if (!data.nombre) { setError('El nombre es obligatorio.'); return; }
        setSaving(true); setError('');
        try {
            const c = await createCliente(client, localId, data);
            setClientes(prev => [c, ...prev]);
            setData(EMPTY); setForm(false);
        } catch (e: unknown) { setError(e instanceof Error ? e.message : 'Error.'); }
        finally { setSaving(false); }
    }

    return (
        <div className="as-card">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                <div>
                    <div className="as-card-title">Clientes</div>
                    <div className="as-card-sub">{clientes.length} cliente{clientes.length !== 1 ? 's' : ''}</div>
                </div>
                <button className="as-btn as-btn--primary" onClick={() => setForm(f => !f)}><UserPlus size={15} /> Nuevo cliente</button>
            </div>

            {form && (
                <div className="as-form">
                    <div className="as-form-row">
                        <div><label className="as-label">Nombre *</label><input className="as-input" placeholder="Nombre completo o razón social" value={data.nombre} onChange={e => setData(d => ({ ...d, nombre: e.target.value }))} /></div>
                        <div><label className="as-label">NIF / CIF</label><input className="as-input" placeholder="12345678A" value={data.nif} onChange={e => setData(d => ({ ...d, nif: e.target.value.toUpperCase() }))} /></div>
                    </div>
                    <div className="as-form-row">
                        <div><label className="as-label">Régimen fiscal</label>
                            <select className="as-input" value={data.regimen} onChange={e => setData(d => ({ ...d, regimen: e.target.value }))}>
                                {REGIMENES.map(r => <option key={r} value={r}>{r}</option>)}
                            </select>
                        </div>
                        <div><label className="as-label">Teléfono</label><input className="as-input" value={data.telefono} onChange={e => setData(d => ({ ...d, telefono: e.target.value }))} /></div>
                    </div>
                    <div><label className="as-label">Email</label><input className="as-input" type="email" value={data.email} onChange={e => setData(d => ({ ...d, email: e.target.value }))} /></div>
                    <div><label className="as-label">Notas</label><input className="as-input" value={data.notas} onChange={e => setData(d => ({ ...d, notas: e.target.value }))} /></div>
                    {error && <p style={{ color: '#dc2626', fontSize: 13 }}>{error}</p>}
                    <div style={{ display: 'flex', gap: 8 }}>
                        <button className="as-btn as-btn--primary" disabled={saving} onClick={handleCreate}>{saving ? 'Guardando…' : 'Crear'}</button>
                        <button className="as-btn as-btn--ghost" onClick={() => { setForm(false); setError(''); }}>Cancelar</button>
                    </div>
                </div>
            )}

            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 12 }}>
                <Search size={15} color="var(--as-text-muted)" />
                <input className="as-input" style={{ flex: 1 }} placeholder="Buscar por nombre, NIF, email…" value={q} onChange={e => setQ(e.target.value)} />
            </div>

            {loading && <div className="as-status"><div className="as-dot" />Cargando clientes…</div>}

            {!loading && filtered.length === 0 && (
                <div style={{ textAlign: 'center', padding: '32px 0', color: 'var(--as-text-muted)' }}>
                    {q ? 'Sin resultados.' : 'No hay clientes. Añade el primero.'}
                </div>
            )}

            {!loading && filtered.map(c => (
                <div key={c.id} className="as-cli-row">
                    <div className="as-cli-avatar">{avatar(c.nombre)}</div>
                    <div style={{ flex: 1 }}>
                        <div className="as-cli-name">{c.nombre}</div>
                        <div className="as-cli-meta">
                            {[c.regimen, c.telefono, c.email].filter(Boolean).join(' · ')}
                        </div>
                    </div>
                    {c.nif && <span className="as-cli-nif">{c.nif}</span>}
                </div>
            ))}
        </div>
    );
}
