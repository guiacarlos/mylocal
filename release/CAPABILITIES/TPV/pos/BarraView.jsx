import React, { useState, useEffect, useMemo } from 'react';
import { Search, Plus, Minus, ShoppingCart, CreditCard, Banknote, X } from 'lucide-react';

const EP = '/axidb/api/axi.php';
function api(action, data = {}) {
    return fetch(EP, { method: 'POST', headers: { 'Content-Type': 'application/json' },
        credentials: 'include', body: JSON.stringify({ action, data }) }).then(r => r.json());
}

export default function BarraView({ localId, onCheckout }) {
    const [productos, setProductos] = useState([]);
    const [categorias, setCategorias] = useState([]);
    const [cart, setCart] = useState([]);
    const [search, setSearch] = useState('');
    const [activeCat, setActiveCat] = useState(null);

    useEffect(() => {
        api('list_productos', { local_id: localId }).then(r => { if (r.success) setProductos(r.data || []); });
        api('list_categorias', { local_id: localId }).then(r => { if (r.success) setCategorias(r.data || []); });
    }, [localId]);

    const filtered = useMemo(() => {
        let items = productos.filter(p => p.disponible !== false);
        if (activeCat) items = items.filter(p => p.categoria_id === activeCat);
        if (search) {
            const s = search.toLowerCase();
            items = items.filter(p => (p.nombre || '').toLowerCase().includes(s));
        }
        return items;
    }, [productos, activeCat, search]);

    const addToCart = (prod) => {
        const existing = cart.find(i => i.id === prod.id);
        if (existing) {
            setCart(cart.map(i => i.id === prod.id ? { ...i, qty: i.qty + 1 } : i));
        } else {
            setCart([...cart, { id: prod.id, nombre: prod.nombre, precio: prod.precio, qty: 1 }]);
        }
    };

    const updateQty = (id, delta) => {
        setCart(cart.map(i => i.id === id ? { ...i, qty: Math.max(0, i.qty + delta) } : i).filter(i => i.qty > 0));
    };

    const total = cart.reduce((s, i) => s + i.precio * i.qty, 0);

    const handleCheckout = (metodo) => {
        if (cart.length === 0) return;
        api('create_payment', { metodo, importe: total, local_id: localId }).then(r => {
            if (r.success) { setCart([]); if (onCheckout) onCheckout(r.data); }
        });
    };

    return (
        <div className="barra-view" style={{ display: 'flex', height: '100%' }}>
            <div style={{ flex: 1, overflow: 'auto', padding: '1rem' }}>
                <div style={{ display: 'flex', gap: '8px', marginBottom: '1rem', flexWrap: 'wrap' }}>
                    <div style={{ position: 'relative', flex: 1, minWidth: 200 }}>
                        <Search size={16} style={{ position: 'absolute', left: 8, top: 10, color: '#999' }} />
                        <input value={search} onChange={e => setSearch(e.target.value)}
                            placeholder="Buscar producto..." style={{ width: '100%', paddingLeft: 32, padding: '8px 8px 8px 32px' }} />
                    </div>
                    <button className={!activeCat ? 'active' : ''} onClick={() => setActiveCat(null)}>Todo</button>
                    {categorias.map(c => (
                        <button key={c.id} className={activeCat === c.id ? 'active' : ''}
                            onClick={() => setActiveCat(c.id)}>{c.nombre}</button>
                    ))}
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(140px, 1fr))', gap: '12px' }}>
                    {filtered.map(p => (
                        <div key={p.id} onClick={() => addToCart(p)}
                            style={{ border: '1px solid #ddd', borderRadius: 8, padding: 12, cursor: 'pointer', textAlign: 'center' }}>
                            {p.imagen_url && <img src={p.imagen_url} alt="" style={{ width: '100%', height: 80, objectFit: 'cover', borderRadius: 4 }} />}
                            <div style={{ fontWeight: 600, marginTop: 4 }}>{p.nombre}</div>
                            <div style={{ color: '#333', fontSize: '1.1rem' }}>{p.precio.toFixed(2)} EUR</div>
                        </div>
                    ))}
                </div>
            </div>
            <div style={{ width: 300, borderLeft: '1px solid #ddd', padding: '1rem', display: 'flex', flexDirection: 'column' }}>
                <h3><ShoppingCart size={16} /> Venta rapida</h3>
                <div style={{ flex: 1, overflow: 'auto' }}>
                    {cart.map(item => (
                        <div key={item.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '6px 0', borderBottom: '1px solid #f0f0f0' }}>
                            <span style={{ flex: 1 }}>{item.nombre}</span>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
                                <button onClick={() => updateQty(item.id, -1)}><Minus size={14} /></button>
                                <span>{item.qty}</span>
                                <button onClick={() => updateQty(item.id, 1)}><Plus size={14} /></button>
                            </div>
                            <span style={{ width: 60, textAlign: 'right' }}>{(item.precio * item.qty).toFixed(2)}</span>
                        </div>
                    ))}
                </div>
                {cart.length > 0 && (
                    <>
                        <div style={{ fontWeight: 700, fontSize: '1.2rem', textAlign: 'right', padding: '12px 0', borderTop: '2px solid #000' }}>
                            {total.toFixed(2)} EUR
                        </div>
                        <div style={{ display: 'flex', gap: 8 }}>
                            <button className="btn-primary" onClick={() => handleCheckout('cash')} style={{ flex: 1 }}>
                                <Banknote size={14} /> Efectivo
                            </button>
                            <button className="btn-primary" onClick={() => handleCheckout('tarjeta')} style={{ flex: 1 }}>
                                <CreditCard size={14} /> Tarjeta
                            </button>
                        </div>
                        <button onClick={() => setCart([])} style={{ marginTop: 8, opacity: 0.6 }}>
                            <X size={14} /> Vaciar
                        </button>
                    </>
                )}
            </div>
        </div>
    );
}
