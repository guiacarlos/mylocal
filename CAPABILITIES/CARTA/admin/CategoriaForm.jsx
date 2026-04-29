import React, { useState } from 'react';
import { Plus, Save, Trash2, Edit3 } from 'lucide-react';

export default function CategoriaForm({ localId, categorias, onSave, api }) {
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ nombre: '', icono_texto: '', nombre_i18n: {} });

    const resetForm = () => {
        setForm({ nombre: '', icono_texto: '', nombre_i18n: {} });
        setEditing(null);
    };

    const handleSave = async () => {
        if (!form.nombre.trim()) return;
        const action = editing ? 'update_categoria' : 'create_categoria';
        const payload = { ...form, local_id: localId };
        if (editing) payload.id = editing;
        const res = await api(action, payload);
        if (res.success) { resetForm(); onSave(); }
    };

    const handleEdit = (cat) => {
        setEditing(cat.id);
        setForm({
            nombre: cat.nombre,
            icono_texto: cat.icono_texto || '',
            nombre_i18n: cat.nombre_i18n || {}
        });
    };

    const handleDelete = async (id) => {
        const res = await api('delete_categoria', { id });
        if (res.success) onSave();
    };

    return (
        <div className="categoria-form-panel">
            <div className="form-section">
                <h4>{editing ? 'Editar categoria' : 'Nueva categoria'}</h4>
                <label>Nombre (ES) *</label>
                <input value={form.nombre} onChange={e => setForm({...form, nombre: e.target.value})} />
                <label>Icono texto (max 2 chars)</label>
                <input value={form.icono_texto} maxLength={2}
                    onChange={e => setForm({...form, icono_texto: e.target.value})} />
                <label>Nombre EN</label>
                <input value={form.nombre_i18n.en || ''}
                    onChange={e => setForm({...form, nombre_i18n: {...form.nombre_i18n, en: e.target.value}})} />
                <label>Nombre FR</label>
                <input value={form.nombre_i18n.fr || ''}
                    onChange={e => setForm({...form, nombre_i18n: {...form.nombre_i18n, fr: e.target.value}})} />
                <label>Nombre DE</label>
                <input value={form.nombre_i18n.de || ''}
                    onChange={e => setForm({...form, nombre_i18n: {...form.nombre_i18n, de: e.target.value}})} />
                <div className="form-actions">
                    <button className="btn-primary" onClick={handleSave}>
                        <Save size={14} /> {editing ? 'Guardar' : 'Crear'}
                    </button>
                    {editing && <button className="btn-secondary" onClick={resetForm}>Cancelar</button>}
                </div>
            </div>
            <div className="list-section">
                <h4>Categorias ({categorias.length})</h4>
                {categorias.map(cat => (
                    <div key={cat.id} className="list-item">
                        <span>{cat.icono_texto} {cat.nombre}</span>
                        <div>
                            <button onClick={() => handleEdit(cat)}><Edit3 size={14} /></button>
                            <button onClick={() => handleDelete(cat.id)}><Trash2 size={14} /></button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
