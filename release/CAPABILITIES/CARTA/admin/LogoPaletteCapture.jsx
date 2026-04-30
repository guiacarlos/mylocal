import React, { useState, useRef } from 'react';
import { Image as ImageIcon, Palette } from 'lucide-react';

const EP = '/axidb/api/axi.php';

function postFile(action, file, extra = {}) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('action', action);
    Object.keys(extra).forEach(k => fd.append(k, extra[k]));
    return fetch(EP, { method: 'POST', body: fd, credentials: 'include' }).then(r => r.json());
}

export default function LogoPaletteCapture({ localId, onPalette }) {
    const [logoUrl, setLogoUrl] = useState('');
    const [palette, setPalette] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const inputRef = useRef(null);

    const handleFile = async (file) => {
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            setError('Sube un PNG o JPG'); return;
        }
        setError(''); setLoading(true);
        const up = await postFile('upload_logo', file, { local_id: localId });
        if (!up.success) { setError(up.error || 'Error al subir'); setLoading(false); return; }
        setLogoUrl(up.url);
        const pal = await fetch(EP, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ action: 'extract_palette', data: { logo_path: up.path } })
        }).then(r => r.json());
        setLoading(false);
        if (!pal.success) { setError(pal.error || 'No se pudo analizar'); return; }
        setPalette(pal.data);
        onPalette && onPalette({ logo_url: up.url, ...pal.data });
    };

    return (
        <div className="db-ai-uploader">
            <div
                className="db-ai-uploader__dropzone"
                onClick={() => inputRef.current && inputRef.current.click()}
                role="button" tabIndex={0}
            >
                {logoUrl
                    ? <img src={logoUrl} alt="logo" style={{ maxHeight: 80, maxWidth: '100%' }} />
                    : <ImageIcon className="db-ai-uploader__icon" />}
                <div className="db-ai-uploader__title">{logoUrl ? 'Logo cargado' : 'Sube tu logo'}</div>
                <div className="db-ai-uploader__hint">
                    {logoUrl ? 'Haz clic para reemplazar' : 'PNG, JPG o WEBP - opcional'}
                </div>
                <input
                    ref={inputRef} type="file" hidden
                    accept="image/png,image/jpeg,image/webp"
                    onChange={e => handleFile(e.target.files && e.target.files[0])}
                />
            </div>
            {loading && (
                <div className="db-ai-uploader__status">
                    <span className="db-ai-spinner"></span>
                    <span>Extrayendo paleta de colores...</span>
                </div>
            )}
            {error && (
                <div className="db-ai-uploader__status db-ai-uploader__status--err">{error}</div>
            )}
            {palette && (
                <div className="db-palette" aria-label="Paleta detectada">
                    <Palette size={14} style={{ color: 'var(--ai-text-dim)' }} />
                    {palette.paleta.map((c, i) => (
                        <span key={i} className="db-palette__swatch" style={{ background: c }} title={c} />
                    ))}
                    <span className="db-palette__label">
                        Principal {palette.sugerencia.color_principal}
                    </span>
                </div>
            )}
        </div>
    );
}
