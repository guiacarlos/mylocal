/**
 * LocalConfigCard - panel de configuracion del local.
 *
 * Edicion inline: click en un valor -> input -> Enter/blur guarda.
 * Auto-save al server, sin botones de "Guardar" separados.
 *
 * Es componente generico (no es de hosteleria): se reutiliza en cualquier
 * vertical para editar los datos del establecimiento. La URL publica del
 * sitio se recibe por prop (publicUrl) porque cada vertical la construye
 * a partir de su propio routing; si no se pasa, no se muestra esa fila.
 *
 * Campos:
 *   nombre, tagline, telefono, direccion, email, web,
 *   instagram, facebook, tiktok, whatsapp, copyright
 */

import { useState } from 'react';
import { useSynaxisClient } from '../../hooks/useSynaxis';
import {
    updateLocal,
    type LocalInfo,
} from '../../services/local.service';
import './local-config.css';

interface Props {
    local: LocalInfo | null;
    onChange?: (updated: LocalInfo) => void;
    /** Si true, los campos secundarios (redes sociales, copyright)
     *  aparecen plegados tras un boton "Mostrar mas". Util cuando el
     *  componente se usa como tarjeta secundaria de una pantalla con
     *  foco principal distinto (p.ej. el mapa de mesas). */
    collapsibleAdvanced?: boolean;
    /** URL publica del sitio que ofrece el local. La calcula el caller
     *  (cada vertical sabe que ruta sirve su carta/web/etc). Si no se
     *  pasa, la fila "URL publica" no se renderiza. */
    publicUrl?: string;
}

type FieldKey = 'nombre' | 'tagline' | 'telefono' | 'direccion' | 'email' | 'web'
              | 'instagram' | 'facebook' | 'tiktok' | 'whatsapp' | 'copyright';

interface FieldDef {
    key: FieldKey;
    label: string;
    placeholder: string;
    advanced?: boolean;
    type?: string;
}

const FIELDS: FieldDef[] = [
    { key: 'nombre',    label: 'Nombre del local', placeholder: 'Bar de Lola' },
    { key: 'tagline',   label: 'Tagline',          placeholder: 'Cocina mediterránea de mercado' },
    { key: 'telefono',  label: 'Teléfono',         placeholder: '+34 600 000 000', type: 'tel' },
    { key: 'direccion', label: 'Dirección',        placeholder: 'Calle Mayor 1, Murcia' },
    { key: 'email',     label: 'Email',            placeholder: 'reservas@barlola.com', type: 'email' },
    { key: 'web',       label: 'Web',              placeholder: 'https://barlola.com', advanced: true },
    { key: 'instagram', label: 'Instagram',        placeholder: 'barlola',            advanced: true },
    { key: 'facebook',  label: 'Facebook',         placeholder: 'BarLolaOficial',     advanced: true },
    { key: 'tiktok',    label: 'TikTok',           placeholder: 'barlola',            advanced: true },
    { key: 'whatsapp',  label: 'WhatsApp',         placeholder: '34600000000',        advanced: true },
    { key: 'copyright', label: 'Copyright footer', placeholder: '© 2026 Bar de Lola', advanced: true },
];

export function LocalConfigCard({ local, onChange, collapsibleAdvanced = false, publicUrl }: Props) {
    const client = useSynaxisClient();
    const [editing, setEditing] = useState<FieldKey | null>(null);
    const [draft, setDraft] = useState<string>('');
    const [busy, setBusy] = useState(false);
    const [showAdvanced, setShowAdvanced] = useState(!collapsibleAdvanced);

    function startEdit(field: FieldKey) {
        const v = (local?.[field] ?? '') as string;
        setDraft(v);
        setEditing(field);
    }

    async function saveField(field: FieldKey) {
        if (!local?.id) { setEditing(null); return; }
        const current = ((local[field] ?? '') as string).trim();
        const next = draft.trim();
        if (next === current) { setEditing(null); return; }
        setBusy(true);
        try {
            const updated = await updateLocal(client, local.id, { [field]: next });
            onChange?.(updated);
            setEditing(null);
        } catch (e) {
            console.error('[LocalConfig] save fallo:', e);
        } finally {
            setBusy(false);
        }
    }

    function renderRow(field: FieldDef) {
        const isEditing = editing === field.key;
        const value = ((local?.[field.key] ?? '') as string).trim();
        return (
            <div key={field.key} className="sm-local-row">
                <label className="sm-local-label">{field.label}</label>
                {isEditing ? (
                    <input
                        className="sm-local-input"
                        type={field.type ?? 'text'}
                        value={draft}
                        autoFocus
                        placeholder={field.placeholder}
                        onChange={e => setDraft(e.target.value)}
                        onBlur={() => saveField(field.key)}
                        onKeyDown={e => {
                            if (e.key === 'Enter') saveField(field.key);
                            if (e.key === 'Escape') setEditing(null);
                        }}
                        disabled={busy}
                    />
                ) : (
                    <button
                        className="sm-local-value"
                        onClick={() => startEdit(field.key)}
                        title="Click para editar"
                    >
                        {value || <span className="sm-local-placeholder">{field.placeholder} · click para editar</span>}
                    </button>
                )}
            </div>
        );
    }

    const basics = FIELDS.filter(f => !f.advanced);
    const advanced = FIELDS.filter(f => f.advanced);

    return (
        <div className="sm-local-card">
            {basics.map(renderRow)}

            {publicUrl && (
                <div className="sm-local-row">
                    <label className="sm-local-label">URL pública</label>
                    <code className="sm-url">{publicUrl}</code>
                    <button
                        className="db-btn db-btn--ghost db-btn--sm"
                        onClick={() => navigator.clipboard?.writeText(publicUrl)}
                    >Copiar</button>
                </div>
            )}

            {collapsibleAdvanced && (
                <button
                    className="sm-local-toggle"
                    onClick={() => setShowAdvanced(!showAdvanced)}
                >
                    {showAdvanced ? '— Ocultar redes sociales y copyright' : '+ Mostrar redes sociales y copyright'}
                </button>
            )}
            {showAdvanced && advanced.map(renderRow)}
        </div>
    );
}
