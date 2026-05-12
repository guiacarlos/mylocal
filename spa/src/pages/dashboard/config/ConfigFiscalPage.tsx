/**
 * Configuracion → Datos fiscales — NIF, razon social, direccion fiscal.
 *
 * Necesarios para facturas. Antes de Stripe live (Ola 8) deben estar completos.
 */

import { useState } from 'react';
import { useDashboard } from '../../../components/dashboard/DashboardContext';
import { updateLocal } from '../../../services/local.service';

type Field = 'nif' | 'razon_social' | 'direccion_fiscal';

const FIELDS: Array<{ key: Field; label: string; placeholder: string }> = [
    { key: 'razon_social',     label: 'Razón social',     placeholder: 'Bar de Lola S.L.' },
    { key: 'nif',              label: 'NIF / CIF',        placeholder: 'B12345678' },
    { key: 'direccion_fiscal', label: 'Dirección fiscal', placeholder: 'Calle Mayor 1, 30001 Murcia' },
];

export function ConfigFiscalPage() {
    const { client, local, setLocal } = useDashboard();
    const [editing, setEditing] = useState<Field | null>(null);
    const [draft, setDraft] = useState('');
    const [saving, setSaving] = useState(false);

    async function save(field: Field) {
        if (!local?.id) { setEditing(null); return; }
        const current = ((local[field] ?? '') as string).trim();
        const next = draft.trim();
        if (next === current) { setEditing(null); return; }
        setSaving(true);
        try {
            const updated = await updateLocal(client, local.id, { [field]: next });
            setLocal(updated);
            setEditing(null);
        } finally { setSaving(false); }
    }

    const complete = FIELDS.every(f => ((local?.[f.key] ?? '') as string).trim() !== '');

    return (
        <div className="db-card">
            <div className="db-card-title">Datos fiscales</div>
            <div className="db-card-sub">
                Necesarios para emitir facturas. Imprescindibles antes de activar pagos en producción.
            </div>

            {!complete && (
                <div className="db-warning">
                    Faltan campos por rellenar. Los pagos reales no se podrán activar sin estos datos.
                </div>
            )}

            {FIELDS.map(f => {
                const isEditing = editing === f.key;
                const value = ((local?.[f.key] ?? '') as string).trim();
                return (
                    <div key={f.key} className="sm-local-row">
                        <label className="sm-local-label">{f.label}</label>
                        {isEditing ? (
                            <input
                                className="sm-local-input"
                                value={draft}
                                autoFocus
                                placeholder={f.placeholder}
                                onChange={e => setDraft(e.target.value)}
                                onBlur={() => save(f.key)}
                                onKeyDown={e => {
                                    if (e.key === 'Enter') save(f.key);
                                    if (e.key === 'Escape') setEditing(null);
                                }}
                                disabled={saving}
                            />
                        ) : (
                            <button
                                className="sm-local-value"
                                onClick={() => { setDraft(value); setEditing(f.key); }}
                                title="Click para editar"
                            >
                                {value || <span className="sm-local-placeholder">{f.placeholder} · click para editar</span>}
                            </button>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
