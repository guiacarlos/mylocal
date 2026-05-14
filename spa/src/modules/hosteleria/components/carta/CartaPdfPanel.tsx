/**
 * CartaPdfPanel - panel "Carta fisica en PDF" del dashboard.
 *
 * Layout:
 *   - Tabs arriba: Minimalista / Clasica / Moderna
 *   - Sidebar izquierda: selector de color de fondo
 *   - Centro/derecha: preview en vivo (CartaPreview) con el contenido REAL
 *   - Boton "Descargar PDF" abajo
 *
 * Lo que ve el hostelero aqui es exactamente lo que saldra en el PDF.
 */

import { useState } from 'react';
import { CartaPreview, type PdfTemplate, type PdfBgColor } from './CartaPreview';
import type { CartaCategoria, CartaProducto } from '../../services/carta.service';
import { uploadLocalImage, type LocalInfo } from '../../../../services/local.service';

interface Props {
    local: LocalInfo | null;
    categorias: CartaCategoria[];
    productos: CartaProducto[];
    onDownload: (template: PdfTemplate, bgColor: PdfBgColor) => Promise<void> | void;
    onLocalChanged?: (updated: LocalInfo) => void;
    downloading?: boolean;
}

const TEMPLATES: Array<{ id: PdfTemplate; nombre: string; desc: string }> = [
    { id: 'minimalista', nombre: 'Minimalista', desc: 'Sans-serif, limpio, dos columnas' },
    { id: 'clasica',     nombre: 'Clásica',     desc: 'Serif, orlas, tradicional' },
    { id: 'moderna',     nombre: 'Moderna',     desc: 'Bloques de color, asimétrico' },
];

const COLORS: Array<{ id: PdfBgColor; nombre: string; swatch: string }> = [
    { id: 'blanco',  nombre: 'Blanco',  swatch: '#ffffff' },
    { id: 'negro',   nombre: 'Negro',   swatch: '#1a1a1a' },
    { id: 'naranja', nombre: 'Naranja', swatch: '#FF6B35' },
    { id: 'rojo',    nombre: 'Rojo',    swatch: '#B91C1C' },
    { id: 'azul',    nombre: 'Azul',    swatch: '#1E3A8A' },
];

export function CartaPdfPanel({ local, categorias, productos, onDownload, onLocalChanged, downloading }: Props) {
    const [template, setTemplate] = useState<PdfTemplate>('minimalista');
    const [bgColor, setBgColor]   = useState<PdfBgColor>('blanco');
    const [uploading, setUploading] = useState(false);

    const empty = productos.length === 0;

    async function handleUploadImage(file: File) {
        if (!local?.id) return;
        setUploading(true);
        try {
            const r = await uploadLocalImage(file, local.id);
            // Actualizar local con la nueva URL para que el preview reaccione al instante
            onLocalChanged?.({ ...local, imagen_hero: r.url });
        } catch (e) {
            const msg = e instanceof Error ? e.message : 'Error subiendo imagen';
            alert(msg);
        } finally {
            setUploading(false);
        }
    }

    return (
        <div className="db-card">
            <div className="db-card-title">Carta física en PDF</div>
            <div className="db-card-sub">
                Elige plantilla y color. Lo que ves abajo es lo que saldrá en el PDF.
            </div>

            <nav className="pdf-tabs" role="tablist">
                {TEMPLATES.map(t => (
                    <button
                        key={t.id}
                        role="tab"
                        aria-selected={template === t.id}
                        className={`pdf-tab${template === t.id ? ' pdf-tab--active' : ''}`}
                        onClick={() => setTemplate(t.id)}
                    >
                        <span className="pdf-tab-name">{t.nombre}</span>
                        <span className="pdf-tab-desc">{t.desc}</span>
                    </button>
                ))}
            </nav>

            <div className="pdf-stage">
                <aside className="pdf-colors" aria-label="Color de fondo">
                    <div className="pdf-colors-label">Color de fondo</div>
                    {COLORS.map(c => (
                        <button
                            key={c.id}
                            className={`pdf-color${bgColor === c.id ? ' pdf-color--active' : ''}`}
                            onClick={() => setBgColor(c.id)}
                            title={c.nombre}
                            aria-label={`Fondo ${c.nombre}`}
                        >
                            <span
                                className="pdf-color-swatch"
                                style={{ background: c.swatch }}
                            />
                            <span className="pdf-color-name">{c.nombre}</span>
                        </button>
                    ))}
                </aside>

                <div className="pdf-canvas">
                    {empty ? (
                        <div className="pdf-empty">
                            <p>Importa una carta primero (pestaña <strong>Importar</strong>) o crea productos manualmente.</p>
                            <p className="pdf-empty-sub">Cuando haya productos verás la previsualización aquí.</p>
                        </div>
                    ) : (
                        <CartaPreview
                            template={template}
                            bgColor={bgColor}
                            local={local}
                            categorias={categorias}
                            productos={productos}
                            onUploadImage={handleUploadImage}
                        />
                    )}
                    {uploading && <div className="pdf-uploading">Subiendo imagen…</div>}
                </div>
            </div>

            <div className="pdf-actions">
                <button
                    className="db-btn db-btn--primary"
                    disabled={empty || downloading}
                    onClick={() => onDownload(template, bgColor)}
                >
                    {downloading ? 'Generando…' : 'Descargar PDF'}
                </button>
                {empty && <span className="pdf-actions-hint">Importa productos antes de generar el PDF.</span>}
            </div>
        </div>
    );
}
