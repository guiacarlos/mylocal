import React, { useEffect, useState } from 'react';

const STORAGE_KEY = 'cookie_consent';
const VERSION = 1;

function loadConsent() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        const obj = JSON.parse(raw);
        if (obj.version !== VERSION) return null;
        return obj;
    } catch (e) { return null; }
}

function saveConsent(c) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ ...c, version: VERSION, ts: Date.now() }));
    document.dispatchEvent(new CustomEvent('mylocal:consent', { detail: c }));
}

export default function CookieBanner() {
    const [open, setOpen] = useState(false);
    const [config, setConfig] = useState(false);
    const [analytics, setAnalytics] = useState(false);

    useEffect(() => {
        const c = loadConsent();
        if (!c) setOpen(true); else setAnalytics(!!c.analytics);
    }, []);

    const acceptAll = () => {
        saveConsent({ technical: true, analytics: true });
        setOpen(false);
    };
    const rejectOptional = () => {
        saveConsent({ technical: true, analytics: false });
        setOpen(false);
    };
    const saveCustom = () => {
        saveConsent({ technical: true, analytics });
        setOpen(false);
    };

    if (!open) return null;

    return (
        <div className="sp-cookie">
            <div className="sp-cookie__inner">
                <div className="sp-cookie__text">
                    <strong>Cookies en MyLocal.</strong> Usamos cookies tecnicas necesarias
                    para que la web funcione y, si lo aceptas, cookies analiticas anonimas
                    para mejorar el producto. Puedes cambiar tu eleccion en cualquier momento
                    desde el pie de pagina. <a href="/legal/cookies">Mas info</a>
                </div>
                {!config && (
                    <div className="sp-cookie__actions">
                        <button className="sp-cookie__btn sp-cookie__btn--ghost" onClick={() => setConfig(true)}>Configurar</button>
                        <button className="sp-cookie__btn sp-cookie__btn--ghost" onClick={rejectOptional}>Solo necesarias</button>
                        <button className="sp-cookie__btn sp-cookie__btn--primary" onClick={acceptAll}>Aceptar todo</button>
                    </div>
                )}
                {config && (
                    <div className="sp-cookie__config">
                        <label className="sp-cookie__row">
                            <input type="checkbox" checked disabled />
                            <span><strong>Tecnicas (siempre activas).</strong> Sesion, carrito, recordar tu eleccion.</span>
                        </label>
                        <label className="sp-cookie__row">
                            <input type="checkbox" checked={analytics} onChange={e => setAnalytics(e.target.checked)} />
                            <span><strong>Analiticas anonimas.</strong> Estadisticas de uso para mejorar el panel.</span>
                        </label>
                        <div className="sp-cookie__actions">
                            <button className="sp-cookie__btn sp-cookie__btn--ghost" onClick={() => setConfig(false)}>Atras</button>
                            <button className="sp-cookie__btn sp-cookie__btn--primary" onClick={saveCustom}>Guardar eleccion</button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
