import React, { useState, useEffect, useCallback } from 'react';
import { Check, Clock, AlertTriangle } from 'lucide-react';

const EP = '/axidb/api/axi.php';
function api(action, data = {}) {
    return fetch(EP, { method: 'POST', headers: { 'Content-Type': 'application/json' },
        credentials: 'include', body: JSON.stringify({ action, data }) }).then(r => r.json());
}

export default function KitchenDisplay({ yellowMin = 10, redMin = 20 }) {
    const [orders, setOrders] = useState([]);
    const [pin, setPin] = useState('');
    const [authenticated, setAuthenticated] = useState(false);
    const [configPin, setConfigPin] = useState('1234');

    const refresh = useCallback(() => {
        api('get_kitchen_orders', {}).then(r => {
            if (r.success) setOrders(r.data || []);
        });
    }, []);

    useEffect(() => {
        if (!authenticated) return;
        refresh();
        const interval = setInterval(refresh, 5000);
        return () => clearInterval(interval);
    }, [authenticated, refresh]);

    const markReady = async (id) => {
        await api('mark_item_ready', { id });
        refresh();
    };

    const getColor = (createdAt) => {
        const mins = (Date.now() - new Date(createdAt).getTime()) / 60000;
        if (mins >= redMin) return '#fecaca';
        if (mins >= yellowMin) return '#fef3c7';
        return '#f0fdf4';
    };

    const getMinutes = (createdAt) => {
        return Math.round((Date.now() - new Date(createdAt).getTime()) / 60000);
    };

    if (!authenticated) {
        return (
            <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh', background: '#1a1a1a' }}>
                <div style={{ textAlign: 'center', color: '#fff' }}>
                    <h2>Cocina</h2>
                    <p>Introduce el PIN</p>
                    <input type="password" value={pin} onChange={e => setPin(e.target.value)}
                        style={{ fontSize: '2rem', width: 150, textAlign: 'center', padding: 12 }}
                        onKeyDown={e => { if (e.key === 'Enter' && pin === configPin) setAuthenticated(true); }} />
                    <br />
                    <button onClick={() => { if (pin === configPin) setAuthenticated(true); }}
                        style={{ marginTop: 16, padding: '12px 32px', fontSize: '1rem' }}>Entrar</button>
                </div>
            </div>
        );
    }

    const byMesa = {};
    orders.forEach(o => {
        const key = o.sesion_id || 'sin-mesa';
        if (!byMesa[key]) byMesa[key] = [];
        byMesa[key].push(o);
    });

    return (
        <div style={{ padding: '1rem', background: '#111', minHeight: '100vh', color: '#fff' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '1rem' }}>
                <h2>Cocina - {orders.length} items pendientes</h2>
                <span style={{ opacity: 0.5 }}>{new Date().toLocaleTimeString()}</span>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))', gap: '12px' }}>
                {Object.entries(byMesa).map(([sesionId, items]) => (
                    <div key={sesionId} style={{ background: '#222', borderRadius: 8, padding: '12px' }}>
                        <h3 style={{ marginBottom: 8, borderBottom: '1px solid #444', paddingBottom: 8 }}>
                            Comanda {sesionId.substring(0, 8)}
                        </h3>
                        {items.map(item => (
                            <div key={item.id} style={{
                                display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                                padding: '8px', marginBottom: 4, borderRadius: 4,
                                background: getColor(item.created_at), color: '#000'
                            }}>
                                <div>
                                    <strong>{item.cantidad}x {item.nombre_producto}</strong>
                                    {item.nota && <div style={{ fontSize: '0.8rem', color: '#666' }}>{item.nota}</div>}
                                </div>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                    <span style={{ fontSize: '0.8rem' }}>
                                        <Clock size={12} /> {getMinutes(item.created_at)}m
                                    </span>
                                    <button onClick={() => markReady(item.id)}
                                        style={{ background: '#22c55e', color: '#fff', border: 'none', borderRadius: 4, padding: '6px 12px', cursor: 'pointer' }}>
                                        <Check size={14} /> Listo
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                ))}
            </div>
            {orders.length === 0 && (
                <div style={{ textAlign: 'center', padding: '4rem', opacity: 0.5 }}>
                    <p style={{ fontSize: '1.5rem' }}>Sin pedidos pendientes</p>
                </div>
            )}
        </div>
    );
}
