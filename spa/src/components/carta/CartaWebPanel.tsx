/**
 * CartaWebPanel - editor en el dashboard de la pestaña "Web".
 *
 * Permite al hostelero elegir plantilla + color de la carta digital
 * publica. La selección se guarda en local.web_template / web_color
 * y se aplica inmediatamente en /carta cuando un cliente escanea el QR.
 *
 * Auto-save: cualquier cambio dispara updateLocal sin necesidad de boton.
 *
 * Layout:
 *   ┌─────────────────────────────────────────┐
 *   │ [Moderna] [Minimal] [Premium]           │
 *   │ ┌────────┬──────────────────────────┐   │
 *   │ │ Color  │  Preview iframe-like     │   │
 *   │ │ Claro  │  (mobile mockup)         │   │
 *   │ │ Oscuro │  CartaWebPreview embed   │   │
 *   │ │ B.Roto │                          │   │
 *   │ └────────┴──────────────────────────┘   │
 *   └─────────────────────────────────────────┘
 */

import { useEffect, useState } from 'react';
import { CartaWebPreview } from './CartaWebPreview';
import type { CartaCategoria, CartaProducto } from '../../services/carta.service';
import {
    updateLocal,
    type LocalInfo,
    type WebTemplate,
    type WebColor,
} from '../../services/local.service';
import type { SynaxisClient } from '../../synaxis';

interface Props {
    client: SynaxisClient;
    local: LocalInfo | null;
    categorias: CartaCategoria[];
    productos: CartaProducto[];
    onLocalChanged?: (updated: LocalInfo) => void;
}

const TEMPLATES: Array<{ id: WebTemplate; nombre: string; desc: string }> = [
    { id: 'moderna', nombre: 'Moderna',  desc: 'Hero arriba · nav sticky · footer con redes' },
    { id: 'minimal', nombre: 'Minimal',  desc: 'Sin imagen · tipografía · logo en footer' },
    { id: 'premium', nombre: 'Premium',  desc: 'Header con logo+redes · hero al final' },
];

const COLORS: Array<{ id: WebColor; nombre: string; swatch: string }> = [
    { id: 'claro',       nombre: 'Claro',       swatch: '#fafafa' },
    { id: 'oscuro',      nombre: 'Oscuro',      swatch: '#0f0f0f' },
    { id: 'blanco_roto', nombre: 'Blanco roto', swatch: '#f5efe6' },
];

export function CartaWebPanel({ client, local, categorias, productos, onLocalChanged }: Props) {
    const [template, setTemplate] = useState<WebTemplate>(local?.web_template ?? 'moderna');
    const [color, setColor]       = useState<WebColor>(local?.web_color ?? 'claro');
    const [saving, setSaving]     = useState(false);
    const [savedAt, setSavedAt]   = useState<number | null>(null);

    // Si el local cambia desde fuera (re-fetch), sincroniza el state.
    useEffect(() => {
        if (local?.web_template) setTemplate(local.web_template);
        if (local?.web_color) setColor(local.web_color);
    }, [local?.id, local?.web_template, local?.web_color]);

    async function persist(patch: Partial<Pick<LocalInfo, 'web_template' | 'web_color'>>) {
        if (!local?.id) return;
        setSaving(true);
        try {
            const updated = await updateLocal(client, local.id, patch);
            onLocalChanged?.(updated);
            setSavedAt(Date.now());
        } catch (e) {
            console.error('[CartaWebPanel] save fallo:', e);
        } finally {
            setSaving(false);
        }
    }

    function pickTemplate(t: WebTemplate) {
        setTemplate(t);
        persist({ web_template: t });
    }
    function pickColor(c: WebColor) {
        setColor(c);
        persist({ web_color: c });
    }

    const empty = productos.length === 0;
    const recentlySaved = savedAt && (Date.now() - savedAt < 2000);

    return (
        <div className="db-card">
            <div className="db-card-title">Carta digital web</div>
            <div className="db-card-sub">
                Así verán los clientes la carta al escanear el QR. Cambios se guardan al instante.
            </div>

            <nav className="pdf-tabs" role="tablist">
                {TEMPLATES.map(t => (
                    <button
                        key={t.id}
                        role="tab"
                        aria-selected={template === t.id}
                        className={`pdf-tab${template === t.id ? ' pdf-tab--active' : ''}`}
                        onClick={() => pickTemplate(t.id)}
                    >
                        <span className="pdf-tab-name">{t.nombre}</span>
                        <span className="pdf-tab-desc">{t.desc}</span>
                    </button>
                ))}
            </nav>

            <div className="pdf-stage">
                <aside className="pdf-colors" aria-label="Tema de color">
                    <div className="pdf-colors-label">Tema de color</div>
                    {COLORS.map(c => (
                        <button
                            key={c.id}
                            className={`pdf-color${color === c.id ? ' pdf-color--active' : ''}`}
                            onClick={() => pickColor(c.id)}
                            title={c.nombre}
                        >
                            <span className="pdf-color-swatch" style={{ background: c.swatch }} />
                            <span className="pdf-color-name">{c.nombre}</span>
                        </button>
                    ))}
                    <div className="cw-save-status">
                        {saving ? 'Guardando…' : recentlySaved ? '✓ Guardado' : ''}
                    </div>
                </aside>

                <div className="pdf-canvas">
                    {empty ? (
                        <div className="pdf-empty">
                            <p>Importa una carta primero (pestaña <strong>Importar</strong>).</p>
                            <p className="pdf-empty-sub">Cuando haya productos verás la previsualización aquí.</p>
                        </div>
                    ) : (
                        <CartaWebPreview
                            template={template}
                            color={color}
                            local={local}
                            categorias={categorias}
                            productos={productos}
                            embedded
                        />
                    )}
                </div>
            </div>
        </div>
    );
}
