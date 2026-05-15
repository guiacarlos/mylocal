import React, { useState, useEffect } from 'react';
import { Save, ToggleLeft, ToggleRight, Link, Copy } from 'lucide-react';

const EP = '/axidb/api/axi.php';
function api(action, data = {}) {
    return fetch(EP, { method: 'POST', headers: { 'Content-Type': 'application/json' },
        credentials: 'include', body: JSON.stringify({ action, data }) }).then(r => r.json());
}

const PLATFORMS = [
    { id: 'glovo', name: 'Glovo' },
    { id: 'ubereats', name: 'Uber Eats' },
    { id: 'justeat', name: 'Just Eat' }
];

export default function DeliveryAdmin({ localId, localSlug }) {
    const [configs, setConfigs] = useState({});

    const getWebhookUrl = (platform) => {
        const base = window.location.origin;
        return base + '/delivery/webhook/' + platform + '/' + (localSlug || localId);
    };

    const togglePlatform = (platform) => {
        setConfigs(prev => ({
            ...prev,
            [platform]: { ...prev[platform], active: !(prev[platform]?.active) }
        }));
    };

    const setSecret = (platform, secret) => {
        setConfigs(prev => ({
            ...prev,
            [platform]: { ...prev[platform], secret }
        }));
    };

    const copyUrl = (url) => {
        navigator.clipboard.writeText(url);
    };

    return (
        <div className="delivery-admin">
            <h3>Integraciones Delivery</h3>
            <p style={{ color: '#666', marginBottom: '1rem' }}>
                Configura cada plataforma proporcionando el webhook URL y el secret HMAC.
            </p>

            {PLATFORMS.map(p => (
                <div key={p.id} style={{ border: '1px solid #ddd', borderRadius: 8, padding: '1rem', marginBottom: '1rem' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.75rem' }}>
                        <h4>{p.name}</h4>
                        <button onClick={() => togglePlatform(p.id)}>
                            {configs[p.id]?.active ? <ToggleRight size={20} color="#22c55e" /> : <ToggleLeft size={20} />}
                        </button>
                    </div>

                    <label>Webhook URL (proporcionala a {p.name})</label>
                    <div style={{ display: 'flex', gap: 8, marginBottom: 8 }}>
                        <input value={getWebhookUrl(p.id)} readOnly style={{ flex: 1, background: '#f5f5f5' }} />
                        <button onClick={() => copyUrl(getWebhookUrl(p.id))}><Copy size={14} /></button>
                    </div>

                    <label>Secret HMAC</label>
                    <input type="password" value={configs[p.id]?.secret || ''}
                        onChange={e => setSecret(p.id, e.target.value)}
                        placeholder="Secret proporcionado por la plataforma" />
                </div>
            ))}

            <button className="btn-primary">
                <Save size={14} /> Guardar configuracion
            </button>
        </div>
    );
}
