import React, { useState } from 'react';
import { ChevronRight, Check, Store } from 'lucide-react';

const EP = '/axidb/api/axi.php';
function api(action, data = {}) {
    return fetch(EP, { method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, data }) }).then(r => r.json());
}

export default function OnboardingPortal({ partnerId }) {
    const [step, setStep] = useState(1);
    const [form, setForm] = useState({
        nombre_local: '', slug: '', contacto_nombre: '', contacto_email: '',
        contacto_telefono: '', direccion: '', cp: '', municipio: ''
    });
    const [error, setError] = useState('');
    const [done, setDone] = useState(false);

    const set = (k, v) => setForm({ ...form, [k]: v });

    const generateSlug = (nombre) => {
        return nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    };

    const handleCreate = async () => {
        setError('');
        if (!form.nombre_local || !form.contacto_email) {
            setError('Nombre del local y email son obligatorios');
            return;
        }
        const slug = form.slug || generateSlug(form.nombre_local);
        const res = await api('create_local', { nombre: form.nombre_local, slug });
        if (res.success) {
            setDone(true);
        } else {
            setError(res.error || 'Error al crear el local');
        }
    };

    if (done) {
        return (
            <div style={{ maxWidth: 500, margin: '2rem auto', textAlign: 'center', padding: '2rem' }}>
                <Check size={48} color="#22c55e" />
                <h2>Local registrado</h2>
                <p>El local <strong>{form.nombre_local}</strong> ha sido creado correctamente.</p>
                <p>URL de la carta: <code>/carta/{form.slug || generateSlug(form.nombre_local)}</code></p>
                <p>Se ha enviado un email a <strong>{form.contacto_email}</strong> con las credenciales de acceso.</p>
            </div>
        );
    }

    return (
        <div style={{ maxWidth: 500, margin: '2rem auto', padding: '1rem' }}>
            <h2><Store size={20} /> Alta de local</h2>
            {partnerId && <p style={{ color: '#666' }}>Registrado a traves de partner</p>}
            {error && <div style={{ background: '#fef2f2', color: '#c00', padding: 8, borderRadius: 4, marginBottom: '1rem' }}>{error}</div>}

            {step === 1 && (
                <div>
                    <h3>Datos del local</h3>
                    <label>Nombre del local *</label>
                    <input value={form.nombre_local} onChange={e => set('nombre_local', e.target.value)} />
                    <label>URL personalizada</label>
                    <input value={form.slug} placeholder={generateSlug(form.nombre_local || 'mi-local')}
                        onChange={e => set('slug', e.target.value)} />
                    <label>Direccion</label>
                    <input value={form.direccion} onChange={e => set('direccion', e.target.value)} />
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 }}>
                        <div><label>CP</label><input value={form.cp} onChange={e => set('cp', e.target.value)} /></div>
                        <div><label>Municipio</label><input value={form.municipio} onChange={e => set('municipio', e.target.value)} /></div>
                    </div>
                    <button className="btn-primary" onClick={() => setStep(2)} style={{ marginTop: '1rem' }}>
                        Siguiente <ChevronRight size={14} />
                    </button>
                </div>
            )}

            {step === 2 && (
                <div>
                    <h3>Datos de contacto</h3>
                    <label>Nombre *</label>
                    <input value={form.contacto_nombre} onChange={e => set('contacto_nombre', e.target.value)} />
                    <label>Email *</label>
                    <input type="email" value={form.contacto_email} onChange={e => set('contacto_email', e.target.value)} />
                    <label>Telefono</label>
                    <input type="tel" value={form.contacto_telefono} onChange={e => set('contacto_telefono', e.target.value)} />
                    <div style={{ display: 'flex', gap: 8, marginTop: '1rem' }}>
                        <button className="btn-secondary" onClick={() => setStep(1)}>Atras</button>
                        <button className="btn-primary" onClick={handleCreate}>Crear local <Check size={14} /></button>
                    </div>
                </div>
            )}
        </div>
    );
}
