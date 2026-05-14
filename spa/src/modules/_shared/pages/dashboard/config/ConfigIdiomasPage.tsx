/**
 * Configuracion → Idiomas — qué idiomas ofrece la carta digital.
 *
 * Los idiomas activos se sirven al cliente del QR; la auto-traduccion IA
 * se aplica al cargar la carta (pendiente Ola 4/SEO).
 */

import { useState } from 'react';
import { useDashboard } from '../../../../../components/dashboard/DashboardContext';
import { updateLocal, type Idioma } from '../../../../../services/local.service';

const IDIOMAS: Array<{ id: Idioma; label: string; bandera: string }> = [
    { id: 'es', label: 'Español',  bandera: '🇪🇸' },
    { id: 'en', label: 'English',  bandera: '🇬🇧' },
    { id: 'fr', label: 'Français', bandera: '🇫🇷' },
    { id: 'de', label: 'Deutsch',  bandera: '🇩🇪' },
    { id: 'pt', label: 'Português',bandera: '🇵🇹' },
    { id: 'it', label: 'Italiano', bandera: '🇮🇹' },
];

export function ConfigIdiomasPage() {
    const { client, local, setLocal } = useDashboard();
    const [saving, setSaving] = useState(false);

    const activos: Idioma[] = local?.idiomas ?? ['es'];

    async function toggle(id: Idioma) {
        if (!local?.id) return;
        // Espanol siempre obligatorio
        if (id === 'es' && activos.includes('es')) return;

        const next: Idioma[] = activos.includes(id)
            ? activos.filter(x => x !== id)
            : [...activos, id];
        // Mantener espanol primero
        if (!next.includes('es')) next.unshift('es');

        setSaving(true);
        try {
            const updated = await updateLocal(client, local.id, { idiomas: next });
            setLocal(updated);
        } finally { setSaving(false); }
    }

    return (
        <div className="db-card">
            <div className="db-card-title">Idiomas de la carta</div>
            <div className="db-card-sub">
                Marca los idiomas en los que ofreces tu carta. El cliente verá un selector
                en la carta pública. Español está activo por defecto.
            </div>

            <div className="db-toggle-grid">
                {IDIOMAS.map(i => {
                    const on = activos.includes(i.id);
                    const isLocked = i.id === 'es';
                    return (
                        <button
                            key={i.id}
                            className={`db-toggle-card${on ? ' db-toggle-card--on' : ''}`}
                            onClick={() => toggle(i.id)}
                            disabled={saving || isLocked}
                            aria-pressed={on}
                        >
                            <span className="db-toggle-flag" aria-hidden>{i.bandera}</span>
                            <span className="db-toggle-label">{i.label}</span>
                            <span className="db-toggle-state">{on ? 'Activo' : 'Inactivo'}</span>
                            {isLocked && <span className="db-toggle-lock">por defecto</span>}
                        </button>
                    );
                })}
            </div>

            <p className="db-form-hint" style={{ marginTop: 16 }}>
                La traducción de la carta a otros idiomas se hace con IA bajo demanda
                cuando el cliente cambia de idioma. No traducimos todo al activarlo.
            </p>

            {saving && <div className="db-save-status">Guardando…</div>}
        </div>
    );
}
