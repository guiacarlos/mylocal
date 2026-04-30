import React, { useState } from 'react';
import { Plus, Save, Trash2, Edit3, QrCode } from 'lucide-react';

export default function MesasAdmin({ localId, mesas, onSave, api }) {
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ zona_nombre: '', numero: '', capacidad: '4' });

    const resetForm = () => { setForm({ zona_nombre: '', numero: '', capacidad: '4' }); setEditing(null); };

    const handleSave = async () => {
        if (!form.zona_nombre.trim() || !form.numero) return;
        const action = editing ? 'update_mesa' : 'create_mesa';
        const payload = {
            ...form,
            local_id: localId,
            numero: parseInt(form.numero, 10),
            capacidad: parseInt(form.capacidad, 10) || 4
        };
        if (editing) payload.id = editing;
        const res = await api(action, payload);
        if (res.success) { resetForm(); onSave(); }
    };

    const handleEdit = (mesa) => {
        setEditing(mesa.id);
        setForm({
            zona_nombre: mesa.zona_nombre,
            numero: String(mesa.numero),
            capacidad: String(mesa.capacidad || 4)
        });
    };

    const handleDelete = async (id) => {
        const res = await api('delete_mesa', { id });
        if (res.success) onSave();
    };

    const zonas = [...new Set(mesas.map(m => m.zona_nombre))];

    return (
        <div className="mesas-admin-panel">
            <div className="form-section">
                <h4>{editing ? 'Editar mesa' : 'Nueva mesa'}</h4>
                <label>Zona *</label>
                <input value={form.zona_nombre} placeholder="Terraza, Salon, Barra..."
                    onChange={e => setForm({...form, zona_nombre: e.target.value})}
                    list="zonas-list" />
                <datalist id="zonas-list">
                    {zonas.map(z => <option key={z} value={z} />)}
                </datalist>
                <label>Numero *</label>
                <input type="number" min="1" value={form.numero}
                    onChange={e => setForm({...form, numero: e.target.value})} />
                <label>Capacidad</label>
                <input type="number" min="1" value={form.capacidad}
                    onChange={e => setForm({...form, capacidad: e.target.value})} />
                <div className="form-actions">
                    <button className="btn-primary" onClick={handleSave}>
                        <Save size={14} /> {editing ? 'Guardar' : 'Crear'}
                    </button>
                    {editing && <button className="btn-secondary" onClick={resetForm}>Cancelar</button>}
                </div>
            </div>
            <div className="list-section">
                <h4>Mesas ({mesas.length})</h4>
                {zonas.map(zona => (
                    <div key={zona} className="zona-group">
                        <h5>{zona}</h5>
                        {mesas.filter(m => m.zona_nombre === zona).map(mesa => (
                            <div key={mesa.id} className="list-item">
                                <span>
                                    Mesa {mesa.numero} (cap. {mesa.capacidad})
                                    {mesa.activa === false && ' [Inactiva]'}
                                </span>
                                <div>
                                    {mesa.qr_url && (
                                        <a href={mesa.qr_url} target="_blank" rel="noopener noreferrer"
                                            title="Ver QR">
                                            <QrCode size={14} />
                                        </a>
                                    )}
                                    <button onClick={() => handleEdit(mesa)}><Edit3 size={14} /></button>
                                    <button onClick={() => handleDelete(mesa.id)}><Trash2 size={14} /></button>
                                </div>
                            </div>
                        ))}
                    </div>
                ))}
                {mesas.length === 0 && <p>No hay mesas configuradas.</p>}
            </div>
        </div>
    );
}
