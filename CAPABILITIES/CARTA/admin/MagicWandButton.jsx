import React, { useState } from 'react';
import { Wand2 } from 'lucide-react';

const EP = '/axidb/api/axi.php';

function api(action, data = {}) {
    return fetch(EP, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action, data })
    }).then(r => r.json());
}

export default function MagicWandButton({ imageUrl, onEnhanced, label = 'Mejorar foto' }) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const run = async () => {
        if (!imageUrl) return;
        setError(''); setLoading(true);
        const r = await api('enhance_image_sync', { image_url: imageUrl });
        setLoading(false);
        if (!r.success) { setError(r.error || 'Error'); return; }
        onEnhanced && onEnhanced(r.data.url);
    };

    return (
        <>
            <button
                className="db-magic-btn"
                onClick={run} disabled={loading || !imageUrl}
                title="Aplica iluminacion, contraste y bokeh automaticos"
            >
                {loading ? <span className="db-ai-spinner" /> : <Wand2 size={11} />}
                <span>{loading ? 'Aplicando...' : label}</span>
            </button>
            {error && <span className="db-ai-uploader__status db-ai-uploader__status--err">{error}</span>}
        </>
    );
}
