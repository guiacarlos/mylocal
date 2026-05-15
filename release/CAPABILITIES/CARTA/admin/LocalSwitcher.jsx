import React, { useState, useEffect } from 'react';
import { ChevronDown, Store } from 'lucide-react';

const EP = '/axidb/api/axi.php';
function api(action, data = {}) {
    return fetch(EP, { method: 'POST', headers: { 'Content-Type': 'application/json' },
        credentials: 'include', body: JSON.stringify({ action, data }) }).then(r => r.json());
}

export default function LocalSwitcher({ activeLocalId, onSwitch }) {
    const [locales, setLocales] = useState([]);
    const [open, setOpen] = useState(false);

    useEffect(() => {
        api('list_locales', {}).then(r => {
            if (r.success) setLocales(r.data || []);
        });
    }, []);

    if (locales.length <= 1) return null;

    const active = locales.find(l => l.id === activeLocalId);

    return (
        <div style={{ position: 'relative', display: 'inline-block' }}>
            <button onClick={() => setOpen(!open)}
                style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '6px 12px', border: '1px solid #ddd', borderRadius: 6, background: '#fff', cursor: 'pointer' }}>
                <Store size={14} />
                <span>{active?.nombre || 'Seleccionar local'}</span>
                <ChevronDown size={14} />
            </button>
            {open && (
                <div style={{ position: 'absolute', top: '100%', left: 0, background: '#fff', border: '1px solid #ddd', borderRadius: 6, zIndex: 100, minWidth: 200, boxShadow: '0 4px 12px rgba(0,0,0,.1)' }}>
                    {locales.map(l => (
                        <div key={l.id} onClick={() => { onSwitch(l.id); setOpen(false); }}
                            style={{ padding: '8px 12px', cursor: 'pointer', background: l.id === activeLocalId ? '#f0f0f0' : '#fff' }}>
                            {l.nombre}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
