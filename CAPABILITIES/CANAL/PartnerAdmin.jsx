import React, { useState, useEffect } from 'react';
import { Plus, Save, Trash2, Edit3, Users, Store } from 'lucide-react';

const EP = '/axidb/api/axi.php';
function api(action, data = {}) {
    return fetch(EP, { method: 'POST', headers: { 'Content-Type': 'application/json' },
        credentials: 'include', body: JSON.stringify({ action, data }) }).then(r => r.json());
}

export default function PartnerAdmin() {
    const [partners, setPartners] = useState([]);
    const [form, setForm] = useState({ nombre_empresa: '', contacto: '', comision_pct: '' });
    const [editing, setEditing] = useState(null);

    useEffect(() => {
        api('list_partners', {}).then(r => { if (r.success) setPartners(r.data || []); });
    }, []);

    const handleSave = async () => {
        if (!form.nombre_empresa) return;
        const action = editing ? 'update_partner' : 'create_partner';
        const payload = { ...form, comision_pct: parseFloat(form.comision_pct) || 0 };
        if (editing) payload.id = editing;
        const res = await api(action, payload);
        if (res.success) {
            setForm({ nombre_empresa: '', contacto: '', comision_pct: '' });
            setEditing(null);
            api('list_partners', {}).then(r => { if (r.success) setPartners(r.data || []); });
        }
    };

    const editPartner = (p) => {
        setEditing(p.id);
        setForm({ nombre_empresa: p.nombre_empresa, contacto: p.contacto, comision_pct: String(p.comision_pct || '') });
    };

    return (
        <div className="partner-admin">
            <h3><Users size={16} /> Gestion de Partners</h3>
            <div className="form-section">
                <h4>{editing ? 'Editar partner' : 'Nuevo partner'}</h4>
                <label>Empresa</label>
                <input value={form.nombre_empresa} onChange={e => setForm({...form, nombre_empresa: e.target.value})} />
                <label>Contacto</label>
                <input value={form.contacto} onChange={e => setForm({...form, contacto: e.target.value})} />
                <label>Comision (%)</label>
                <input type="number" step="0.1" value={form.comision_pct}
                    onChange={e => setForm({...form, comision_pct: e.target.value})} />
                <button className="btn-primary" onClick={handleSave}>
                    <Save size={14} /> {editing ? 'Guardar' : 'Crear partner'}
                </button>
            </div>
            <div className="list-section">
                <h4>Partners ({partners.length})</h4>
                {partners.map(p => (
                    <div key={p.id} className="list-item" style={{ display: 'flex', justifyContent: 'space-between', padding: '8px 0', borderBottom: '1px solid #eee' }}>
                        <div>
                            <strong>{p.nombre_empresa}</strong>
                            <span style={{ marginLeft: 8, color: '#666' }}>{p.comision_pct}%</span>
                            <span style={{ marginLeft: 8, color: '#999' }}>{(p.locales_asignados || []).length} locales</span>
                        </div>
                        <button onClick={() => editPartner(p)}><Edit3 size={14} /></button>
                    </div>
                ))}
            </div>
        </div>
    );
}
