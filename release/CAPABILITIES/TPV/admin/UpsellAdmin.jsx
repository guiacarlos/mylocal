import React, { useState, useEffect } from 'react';
import { Plus, Save, Trash2, ToggleLeft, ToggleRight } from 'lucide-react';

const EP = '/axidb/api/axi.php';

function api(action, data = {}) {
    return fetch(EP, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action, data })
    }).then(r => r.json());
}

export default function UpsellAdmin({ localId }) {
    const [rules, setRules] = useState([]);
    const [categorias, setCategorias] = useState([]);
    const [productos, setProductos] = useState([]);
    const [form, setForm] = useState({
        si_producto: '', si_categoria: '', si_no_categoria: '',
        sugerir_producto: '', mensaje: '', activa: true
    });

    useEffect(() => {
        api('list_categorias', { local_id: localId }).then(r => {
            if (r.success) setCategorias(r.data || []);
        });
        api('list_productos', { local_id: localId }).then(r => {
            if (r.success) setProductos(r.data || []);
        });
    }, [localId]);

    const addRule = () => {
        if (!form.sugerir_producto) return;
        const prod = productos.find(p => p.id === form.sugerir_producto);
        setRules([...rules, {
            ...form,
            sugerir_nombre: prod?.nombre || '',
            sugerir_precio: prod?.precio || 0
        }]);
        setForm({ si_producto: '', si_categoria: '', si_no_categoria: '',
            sugerir_producto: '', mensaje: '', activa: true });
    };

    const removeRule = (index) => {
        setRules(rules.filter((_, i) => i !== index));
    };

    const toggleRule = (index) => {
        const updated = [...rules];
        updated[index].activa = !updated[index].activa;
        setRules(updated);
    };

    return (
        <div className="upsell-admin">
            <h3>Reglas de sugerencia</h3>
            <div className="form-section">
                <label>Si el cliente tiene producto:</label>
                <select value={form.si_producto} onChange={e => setForm({...form, si_producto: e.target.value})}>
                    <option value="">Cualquiera</option>
                    {productos.map(p => <option key={p.id} value={p.id}>{p.nombre}</option>)}
                </select>
                <label>Si la categoria NO esta en el carrito:</label>
                <select value={form.si_no_categoria} onChange={e => setForm({...form, si_no_categoria: e.target.value})}>
                    <option value="">N/A</option>
                    {categorias.map(c => <option key={c.id} value={c.id}>{c.nombre}</option>)}
                </select>
                <label>Sugerir producto:</label>
                <select value={form.sugerir_producto} onChange={e => setForm({...form, sugerir_producto: e.target.value})}>
                    <option value="">Seleccionar...</option>
                    {productos.map(p => <option key={p.id} value={p.id}>{p.nombre} ({p.precio} EUR)</option>)}
                </select>
                <label>Mensaje:</label>
                <input value={form.mensaje} placeholder="Tambien te recomendamos..."
                    onChange={e => setForm({...form, mensaje: e.target.value})} />
                <button className="btn-primary" onClick={addRule}><Plus size={14} /> Anadir regla</button>
            </div>
            <div className="list-section">
                <h4>Reglas activas ({rules.length})</h4>
                {rules.map((rule, i) => (
                    <div key={i} className="list-item">
                        <span style={{opacity: rule.activa ? 1 : 0.5}}>
                            Sugerir: {rule.sugerir_nombre || rule.sugerir_producto}
                            {rule.si_producto && ' (si tiene ' + rule.si_producto + ')'}
                        </span>
                        <div>
                            <button onClick={() => toggleRule(i)}>
                                {rule.activa ? <ToggleRight size={14} /> : <ToggleLeft size={14} />}
                            </button>
                            <button onClick={() => removeRule(i)}><Trash2 size={14} /></button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
