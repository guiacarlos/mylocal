import React, { useState } from 'react';
import { Plus, Save, Trash2, Edit3 } from 'lucide-react';
import AlergensSelector from './AlergensSelector';

const IVA_TIPOS = [
    { id: 'reducido_10', label: 'Reducido 10%' },
    { id: 'superreducido_4', label: 'Superreducido 4%' },
    { id: 'general_21', label: 'General 21%' },
    { id: 'exento', label: 'Exento 0%' }
];

const emptyForm = {
    nombre: '', descripcion: '', precio: '', categoria_id: '',
    iva_tipo: 'reducido_10', alergenos: [], disponible: true,
    nombre_i18n: {}, descripcion_i18n: {},
    precio_franja: { desayuno: '', almuerzo: '', cena: '' }
};

export default function ProductoCartaForm({ localId, productos, categorias, onSave, api }) {
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ ...emptyForm });

    const resetForm = () => { setForm({ ...emptyForm }); setEditing(null); };

    const handleSave = async () => {
        if (!form.nombre.trim() || !form.categoria_id || form.precio === '') return;
        const action = editing ? 'update_producto' : 'create_producto';
        const payload = {
            ...form,
            local_id: localId,
            precio: parseFloat(form.precio) || 0,
            precio_franja: {
                desayuno: form.precio_franja.desayuno ? parseFloat(form.precio_franja.desayuno) : null,
                almuerzo: form.precio_franja.almuerzo ? parseFloat(form.precio_franja.almuerzo) : null,
                cena: form.precio_franja.cena ? parseFloat(form.precio_franja.cena) : null
            }
        };
        if (editing) payload.id = editing;
        const res = await api(action, payload);
        if (res.success) { resetForm(); onSave(); }
    };

    const handleEdit = (p) => {
        setEditing(p.id);
        setForm({
            nombre: p.nombre, descripcion: p.descripcion || '',
            precio: String(p.precio), categoria_id: p.categoria_id,
            iva_tipo: p.iva_tipo || 'reducido_10',
            alergenos: p.alergenos || [], disponible: p.disponible !== false,
            nombre_i18n: p.nombre_i18n || {}, descripcion_i18n: p.descripcion_i18n || {},
            precio_franja: {
                desayuno: p.precio_franja?.desayuno ? String(p.precio_franja.desayuno) : '',
                almuerzo: p.precio_franja?.almuerzo ? String(p.precio_franja.almuerzo) : '',
                cena: p.precio_franja?.cena ? String(p.precio_franja.cena) : ''
            }
        });
    };

    const handleDelete = async (id) => {
        const res = await api('delete_producto', { id });
        if (res.success) onSave();
    };

    const set = (k, v) => setForm({ ...form, [k]: v });

    return (
        <div className="producto-form-panel">
            <div className="form-section">
                <h4>{editing ? 'Editar producto' : 'Nuevo producto'}</h4>
                <label>Nombre (ES) *</label>
                <input value={form.nombre} onChange={e => set('nombre', e.target.value)} />
                <label>Categoria *</label>
                <select value={form.categoria_id} onChange={e => set('categoria_id', e.target.value)}>
                    <option value="">Seleccionar...</option>
                    {categorias.map(c => <option key={c.id} value={c.id}>{c.nombre}</option>)}
                </select>
                <label>Precio (EUR) *</label>
                <input type="number" step="0.01" min="0" value={form.precio}
                    onChange={e => set('precio', e.target.value)} />
                <label>Descripcion</label>
                <textarea value={form.descripcion} onChange={e => set('descripcion', e.target.value)} rows={2} />
                <label>IVA</label>
                <select value={form.iva_tipo} onChange={e => set('iva_tipo', e.target.value)}>
                    {IVA_TIPOS.map(t => <option key={t.id} value={t.id}>{t.label}</option>)}
                </select>
                <AlergensSelector selected={form.alergenos} onChange={v => set('alergenos', v)} />
                <label className="checkbox-label">
                    <input type="checkbox" checked={form.disponible}
                        onChange={e => set('disponible', e.target.checked)} />
                    Disponible
                </label>
                <details>
                    <summary>Precios por franja (opcional)</summary>
                    <label>Desayuno</label>
                    <input type="number" step="0.01" value={form.precio_franja.desayuno}
                        onChange={e => set('precio_franja', {...form.precio_franja, desayuno: e.target.value})} />
                    <label>Almuerzo</label>
                    <input type="number" step="0.01" value={form.precio_franja.almuerzo}
                        onChange={e => set('precio_franja', {...form.precio_franja, almuerzo: e.target.value})} />
                    <label>Cena</label>
                    <input type="number" step="0.01" value={form.precio_franja.cena}
                        onChange={e => set('precio_franja', {...form.precio_franja, cena: e.target.value})} />
                </details>
                <details>
                    <summary>Traducciones (opcional)</summary>
                    <label>Nombre EN</label>
                    <input value={form.nombre_i18n.en || ''}
                        onChange={e => set('nombre_i18n', {...form.nombre_i18n, en: e.target.value})} />
                    <label>Nombre FR</label>
                    <input value={form.nombre_i18n.fr || ''}
                        onChange={e => set('nombre_i18n', {...form.nombre_i18n, fr: e.target.value})} />
                    <label>Nombre DE</label>
                    <input value={form.nombre_i18n.de || ''}
                        onChange={e => set('nombre_i18n', {...form.nombre_i18n, de: e.target.value})} />
                </details>
                <div className="form-actions">
                    <button className="btn-primary" onClick={handleSave}>
                        <Save size={14} /> {editing ? 'Guardar' : 'Crear'}
                    </button>
                    {editing && <button className="btn-secondary" onClick={resetForm}>Cancelar</button>}
                </div>
            </div>
            <div className="list-section">
                <h4>Productos ({productos.length})</h4>
                {productos.map(p => (
                    <div key={p.id} className="list-item">
                        <span>{p.nombre} - {p.precio.toFixed(2)}EUR</span>
                        <div>
                            <button onClick={() => handleEdit(p)}><Edit3 size={14} /></button>
                            <button onClick={() => handleDelete(p.id)}><Trash2 size={14} /></button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
