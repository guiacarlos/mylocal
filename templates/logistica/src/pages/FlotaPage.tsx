import { useEffect, useState } from 'react';
import { useLogistica } from '../context/LogisticaContext';
import { listVehiculos, createVehiculo, updateVehiculo, type Vehiculo } from '../services/delivery.service';
import { Truck, Plus, ToggleLeft, ToggleRight } from 'lucide-react';

const EMPTY = { matricula: '', conductor: '', modelo: '' };

export function FlotaPage() {
    const { client, localId } = useLogistica();
    const [vehiculos, setVehiculos] = useState<Vehiculo[]>([]);
    const [loading, setLoading] = useState(true);
    const [form, setForm] = useState(false);
    const [data, setData] = useState(EMPTY);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        setLoading(true);
        listVehiculos(client, localId).then(setVehiculos).catch(() => setVehiculos([])).finally(() => setLoading(false));
    }, [client]);

    async function handleCreate() {
        if (!data.matricula) { setError('Matrícula obligatoria.'); return; }
        setSaving(true); setError('');
        try {
            const v = await createVehiculo(client, localId, data);
            setVehiculos(prev => [...prev, v]);
            setData(EMPTY); setForm(false);
        } catch (e: unknown) { setError(e instanceof Error ? e.message : 'Error.'); }
        finally { setSaving(false); }
    }

    async function toggleEstado(v: Vehiculo) {
        const nuevoEstado = v.estado === 'activo' ? 'inactivo' : 'activo';
        try {
            const updated = await updateVehiculo(client, v.id, { estado: nuevoEstado });
            setVehiculos(prev => prev.map(x => x.id === v.id ? updated : x));
        } catch (_) { /* silencioso */ }
    }

    return (
        <div className="lg-card">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                <div>
                    <div className="lg-card-title">Flota</div>
                    <div className="lg-card-sub">{vehiculos.filter(v => v.estado === 'activo').length} activo{vehiculos.filter(v => v.estado === 'activo').length !== 1 ? 's' : ''} de {vehiculos.length}</div>
                </div>
                <button className="lg-btn lg-btn--primary" onClick={() => setForm(f => !f)}><Plus size={15} /> Añadir vehículo</button>
            </div>

            {form && (
                <div className="lg-form">
                    <div className="lg-form-row">
                        <div><label className="lg-label">Matrícula *</label><input className="lg-input" placeholder="1234-ABC" value={data.matricula} onChange={e => setData(d => ({ ...d, matricula: e.target.value.toUpperCase() }))} /></div>
                        <div><label className="lg-label">Conductor</label><input className="lg-input" placeholder="Nombre del conductor" value={data.conductor} onChange={e => setData(d => ({ ...d, conductor: e.target.value }))} /></div>
                    </div>
                    <div><label className="lg-label">Modelo</label><input className="lg-input" placeholder="Furgoneta, moto, coche…" value={data.modelo} onChange={e => setData(d => ({ ...d, modelo: e.target.value }))} /></div>
                    {error && <p style={{ color: '#dc2626', fontSize: 13 }}>{error}</p>}
                    <div style={{ display: 'flex', gap: 8 }}>
                        <button className="lg-btn lg-btn--primary" disabled={saving} onClick={handleCreate}>{saving ? 'Guardando…' : 'Añadir'}</button>
                        <button className="lg-btn lg-btn--ghost" onClick={() => { setForm(false); setError(''); }}>Cancelar</button>
                    </div>
                </div>
            )}

            {loading && <div className="lg-status"><div className="lg-dot" />Cargando flota…</div>}

            {!loading && vehiculos.length === 0 && (
                <div style={{ textAlign: 'center', padding: '32px 0', color: 'var(--lg-text-muted)' }}>
                    <Truck size={32} style={{ opacity: 0.3, marginBottom: 8 }} />
                    <p>Sin vehículos. Añade el primero.</p>
                </div>
            )}

            {!loading && vehiculos.map(v => (
                <div key={v.id} className="lg-vehiculo-row">
                    <span className="lg-vehiculo-matricula">{v.matricula}</span>
                    <div style={{ flex: 1 }}>
                        <div className="lg-vehiculo-conductor">{v.conductor || <em style={{ color: 'var(--lg-text-soft)' }}>Sin conductor</em>}</div>
                        <div className="lg-vehiculo-modelo">{v.modelo}</div>
                    </div>
                    <button
                        className="lg-btn lg-btn--ghost"
                        style={{ padding: '4px 8px', fontSize: 12 }}
                        title={v.estado === 'activo' ? 'Desactivar' : 'Activar'}
                        onClick={() => toggleEstado(v)}
                    >
                        {v.estado === 'activo'
                            ? <><ToggleRight size={16} color="#22c55e" /> Activo</>
                            : <><ToggleLeft size={16} color="#94a3b8" /> Inactivo</>
                        }
                    </button>
                </div>
            ))}
        </div>
    );
}
