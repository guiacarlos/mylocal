import React, { useState, useEffect, useCallback } from 'react';
import { Send, Search, Bell, ChevronRight, Plus, Minus } from 'lucide-react';

const EP = '/axidb/api/axi.php';
function api(action, data = {}) {
    return fetch(EP, { method: 'POST', headers: { 'Content-Type': 'application/json' },
        credentials: 'include', body: JSON.stringify({ action, data }) }).then(r => r.json());
}

export default function ComanderoApp({ localId }) {
    const [mesas, setMesas] = useState([]);
    const [selectedMesa, setSelectedMesa] = useState(null);
    const [productos, setProductos] = useState([]);
    const [comanda, setComanda] = useState([]);
    const [search, setSearch] = useState('');
    const [notifications, setNotifications] = useState([]);
    const [view, setView] = useState('mesas');

    useEffect(() => {
        api('list_mesas', { local_id: localId }).then(r => { if (r.success) setMesas(r.data || []); });
        api('list_productos', { local_id: localId }).then(r => { if (r.success) setProductos(r.data || []); });
    }, [localId]);

    useEffect(() => {
        const interval = setInterval(() => {
            api('get_kitchen_orders', {}).then(r => {
                if (r.success) {
                    const ready = (r.data || []).filter(i => i.estado_cocina === 'listo');
                    setNotifications(ready);
                }
            });
        }, 5000);
        return () => clearInterval(interval);
    }, []);

    const addToComanda = (prod) => {
        const existing = comanda.find(i => i.producto_id === prod.id);
        if (existing) {
            setComanda(comanda.map(i => i.producto_id === prod.id ? { ...i, cantidad: i.cantidad + 1 } : i));
        } else {
            setComanda([...comanda, { producto_id: prod.id, nombre: prod.nombre, precio: prod.precio, cantidad: 1, nota: '' }]);
        }
    };

    const enviarComanda = async () => {
        if (!selectedMesa || comanda.length === 0) return;
        const slug = selectedMesa.zona_nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-') + '-' + selectedMesa.numero;
        const items = comanda.map(i => ({ id: i.producto_id, name: i.nombre, quantity: i.cantidad, price: i.precio, notes: i.nota }));
        const total = comanda.reduce((s, i) => s + i.precio * i.cantidad, 0);
        const res = await api('process_external_order', { table_slug: slug, items, total });
        if (res.success) { setComanda([]); setView('mesas'); }
    };

    const filteredProds = search
        ? productos.filter(p => p.nombre.toLowerCase().includes(search.toLowerCase()))
        : productos;

    if (view === 'comanda' && selectedMesa) {
        return (
            <div style={{ padding: '1rem', maxWidth: 500, margin: '0 auto' }}>
                <h2>Mesa {selectedMesa.numero} - {selectedMesa.zona_nombre}</h2>
                <div style={{ position: 'relative', marginBottom: '1rem' }}>
                    <Search size={16} style={{ position: 'absolute', left: 8, top: 10 }} />
                    <input value={search} onChange={e => setSearch(e.target.value)}
                        placeholder="Buscar producto..." style={{ width: '100%', paddingLeft: 32 }} />
                </div>
                <div style={{ maxHeight: 200, overflow: 'auto', marginBottom: '1rem' }}>
                    {filteredProds.map(p => (
                        <div key={p.id} onClick={() => addToComanda(p)}
                            style={{ display: 'flex', justifyContent: 'space-between', padding: '8px', borderBottom: '1px solid #eee', cursor: 'pointer' }}>
                            <span>{p.nombre}</span>
                            <span>{p.precio.toFixed(2)} EUR</span>
                        </div>
                    ))}
                </div>
                {comanda.length > 0 && (
                    <div style={{ borderTop: '2px solid #000', paddingTop: '1rem' }}>
                        <h3>Comanda</h3>
                        {comanda.map((item, i) => (
                            <div key={i} style={{ display: 'flex', justifyContent: 'space-between', padding: '4px 0' }}>
                                <span>{item.cantidad}x {item.nombre}</span>
                                <span>{(item.precio * item.cantidad).toFixed(2)}</span>
                            </div>
                        ))}
                        <button className="btn-primary" onClick={enviarComanda} style={{ width: '100%', marginTop: '1rem' }}>
                            <Send size={14} /> Enviar a cocina
                        </button>
                    </div>
                )}
                <button onClick={() => setView('mesas')} style={{ marginTop: '1rem' }}>Volver a mesas</button>
            </div>
        );
    }

    return (
        <div style={{ padding: '1rem', maxWidth: 500, margin: '0 auto' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                <h2>Mis mesas</h2>
                {notifications.length > 0 && (
                    <div style={{ background: '#fef3c7', padding: '4px 12px', borderRadius: 20 }}>
                        <Bell size={14} /> {notifications.length} listos
                    </div>
                )}
            </div>
            {mesas.filter(m => m.activa !== false).map(mesa => (
                <div key={mesa.id} onClick={() => { setSelectedMesa(mesa); setView('comanda'); setComanda([]); setSearch(''); }}
                    style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '12px', borderBottom: '1px solid #eee', cursor: 'pointer' }}>
                    <div>
                        <strong>Mesa {mesa.numero}</strong>
                        <span style={{ marginLeft: 8, color: '#666' }}>{mesa.zona_nombre}</span>
                    </div>
                    <ChevronRight size={16} />
                </div>
            ))}
        </div>
    );
}
