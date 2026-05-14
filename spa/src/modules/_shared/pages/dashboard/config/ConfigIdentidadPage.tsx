/**
 * Configuracion → Identidad — logo, tipo de negocio, descripcion.
 *
 * El tema visual (plantilla + color) se elige en Carta → Web. Aqui
 * mostramos un resumen y enlace para no duplicar UI.
 */

import { useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { useDashboard } from '../../../../../components/dashboard/DashboardContext';
import { updateLocal, uploadLocalImage } from '../../../../../services/local.service';

const TIPOS = ['Restaurante', 'Bar', 'Cafetería', 'Pastelería', 'Bistró', 'Taberna', 'Pizzería', 'Otro'];

export function ConfigIdentidadPage() {
    const { client, local, setLocal } = useDashboard();
    const fileRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [savingTipo, setSavingTipo] = useState(false);
    const [savingDesc, setSavingDesc] = useState(false);
    const [draftDesc, setDraftDesc] = useState(local?.descripcion ?? '');

    const heroUrl = local?.imagen_hero || '/MEDIA/hero.png';
    const tipo = local?.tipo_negocio ?? '';

    async function handleFile(e: React.ChangeEvent<HTMLInputElement>) {
        const f = e.target.files?.[0];
        if (!f || !local?.id) return;
        setUploading(true);
        try {
            const r = await uploadLocalImage(f, local.id);
            setLocal({ ...local, imagen_hero: r.url });
        } catch (err) {
            alert(err instanceof Error ? err.message : 'Error subiendo imagen');
        } finally {
            setUploading(false);
            e.target.value = '';
        }
    }

    async function handleTipo(t: string) {
        if (!local?.id) return;
        setSavingTipo(true);
        try {
            const updated = await updateLocal(client, local.id, { tipo_negocio: t });
            setLocal(updated);
        } finally { setSavingTipo(false); }
    }

    async function handleSaveDesc() {
        if (!local?.id || draftDesc === (local.descripcion ?? '')) return;
        setSavingDesc(true);
        try {
            const updated = await updateLocal(client, local.id, { descripcion: draftDesc });
            setLocal(updated);
        } finally { setSavingDesc(false); }
    }

    return (
        <div className="db-card">
            <div className="db-card-title">Identidad del local</div>
            <div className="db-card-sub">Logo, tipo de negocio y descripción corta para SEO y carta pública.</div>

            <div className="db-form-row">
                <label className="db-form-label">Imagen del local (logo o foto)</label>
                <div className="db-form-image">
                    <img src={heroUrl} alt="" className="db-form-image-preview" />
                    <button
                        className="db-btn db-btn--ghost"
                        onClick={() => fileRef.current?.click()}
                        disabled={uploading}
                    >{uploading ? 'Subiendo…' : 'Cambiar imagen'}</button>
                    <input ref={fileRef} type="file" accept="image/*" hidden onChange={handleFile} />
                </div>
                <p className="db-form-hint">JPG, PNG o WebP. Máx 5 MB. Se usa en cabecera de la carta web, en póster A4 y en el PDF.</p>
            </div>

            <div className="db-form-row">
                <label className="db-form-label">Tipo de negocio</label>
                <div className="db-chip-group">
                    {TIPOS.map(t => (
                        <button
                            key={t}
                            className={`db-chip${tipo === t ? ' db-chip--active' : ''}`}
                            onClick={() => handleTipo(t)}
                            disabled={savingTipo}
                        >{t}</button>
                    ))}
                </div>
            </div>

            <div className="db-form-row">
                <label className="db-form-label" htmlFor="desc">Descripción corta</label>
                <textarea
                    id="desc"
                    className="db-form-textarea"
                    rows={3}
                    maxLength={180}
                    value={draftDesc}
                    onChange={e => setDraftDesc(e.target.value)}
                    onBlur={handleSaveDesc}
                    placeholder="Cocina mediterránea con producto de proximidad y bodega de autor."
                />
                <p className="db-form-hint">Máx 180 caracteres. Aparece en meta description (SEO) y en footers.</p>
            </div>

            <div className="db-form-row db-form-link-row">
                <p>
                    El tema visual (plantilla y color de la carta digital) se elige en{' '}
                    <Link to="/dashboard/carta/web">Carta › Web</Link>.
                </p>
            </div>

            {savingDesc && <div className="db-save-status">Guardando…</div>}
        </div>
    );
}
