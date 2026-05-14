/**
 * Configuracion → Horarios — rangos por dia de semana.
 *
 * Estructura: { lun: [{from, to}, ...], mar: [...], ... }. Cada dia puede
 * tener varios tramos (turno de comida, turno de cena). El cliente del QR
 * vera el horario y si esta abierto AHORA.
 */

import { useState } from 'react';
import { useDashboard } from '../../../../../components/dashboard/DashboardContext';
import { updateLocal, type DiaSemana, type Horarios, type HorarioTramo } from '../../../../../services/local.service';

const DIAS: Array<{ id: DiaSemana; label: string }> = [
    { id: 'lun', label: 'Lunes' },
    { id: 'mar', label: 'Martes' },
    { id: 'mie', label: 'Miércoles' },
    { id: 'jue', label: 'Jueves' },
    { id: 'vie', label: 'Viernes' },
    { id: 'sab', label: 'Sábado' },
    { id: 'dom', label: 'Domingo' },
];

const DEFAULT_TRAMO: HorarioTramo = { from: '13:00', to: '16:00' };

export function ConfigHorariosPage() {
    const { client, local, setLocal } = useDashboard();
    const [saving, setSaving] = useState(false);

    const horarios: Horarios = local?.horarios ?? {};

    async function save(next: Horarios) {
        if (!local?.id) return;
        setSaving(true);
        try {
            const updated = await updateLocal(client, local.id, { horarios: next });
            setLocal(updated);
        } finally { setSaving(false); }
    }

    function addTramo(dia: DiaSemana) {
        const next = { ...horarios, [dia]: [...(horarios[dia] ?? []), { ...DEFAULT_TRAMO }] };
        save(next);
    }
    function removeTramo(dia: DiaSemana, idx: number) {
        const arr = (horarios[dia] ?? []).filter((_, i) => i !== idx);
        const next = { ...horarios, [dia]: arr };
        if (arr.length === 0) delete next[dia];
        save(next);
    }
    function updateTramo(dia: DiaSemana, idx: number, patch: Partial<HorarioTramo>) {
        const arr = (horarios[dia] ?? []).map((t, i) => i === idx ? { ...t, ...patch } : t);
        save({ ...horarios, [dia]: arr });
    }

    return (
        <div className="db-card">
            <div className="db-card-title">Horarios de apertura</div>
            <div className="db-card-sub">
                Define los tramos en que el local está abierto. Puedes añadir varios
                por día (ej. comida + cena). Lo verán los clientes en la carta pública.
            </div>

            <div className="db-horarios">
                {DIAS.map(d => {
                    const tramos = horarios[d.id] ?? [];
                    return (
                        <div key={d.id} className="db-horario-row">
                            <div className="db-horario-day">{d.label}</div>
                            <div className="db-horario-tramos">
                                {tramos.length === 0 && <span className="db-horario-closed">Cerrado</span>}
                                {tramos.map((t, i) => (
                                    <div key={i} className="db-horario-tramo">
                                        <input
                                            type="time"
                                            value={t.from}
                                            onChange={e => updateTramo(d.id, i, { from: e.target.value })}
                                        />
                                        <span>–</span>
                                        <input
                                            type="time"
                                            value={t.to}
                                            onChange={e => updateTramo(d.id, i, { to: e.target.value })}
                                        />
                                        <button
                                            className="db-horario-del"
                                            onClick={() => removeTramo(d.id, i)}
                                            aria-label="Eliminar tramo"
                                            title="Eliminar tramo"
                                        >×</button>
                                    </div>
                                ))}
                                <button
                                    className="db-btn db-btn--ghost db-btn--sm"
                                    onClick={() => addTramo(d.id)}
                                >+ Tramo</button>
                            </div>
                        </div>
                    );
                })}
            </div>

            {saving && <div className="db-save-status">Guardando…</div>}
        </div>
    );
}
