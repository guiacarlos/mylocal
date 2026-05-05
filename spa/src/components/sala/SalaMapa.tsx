/**
 * SalaMapa - vista de gestion de zonas y mesas (post-wizard).
 *
 * Muestra cada zona con sus mesas en grid. Permite:
 *   - Ver el QR de una mesa (link copiable).
 *   - Regenerar el QR (cambiar token, invalida el anterior).
 *   - Anadir mesa a una zona.
 *   - Cambiar capacidad de una mesa.
 *   - Borrar mesa (soft).
 *
 * Diseno: cada zona es un bloque, las mesas dentro son tarjetas
 * cuadradas pequeñas. Al hacer click en una mesa se abre un modal
 * con detalle.
 */

import { useEffect, useState } from 'react';
import { useSynaxisClient } from '../../hooks/useSynaxis';
import {
    listMesas,
    createMesa,
    updateMesa,
    deleteMesa,
    regenerateMesaQr,
    buildMesaUrl,
    type Mesa,
    type SalaResumen,
} from '../../services/sala.service';
import { SalaQrSheet } from './SalaQrSheet';

interface Props {
    localId: string;
    resumen: SalaResumen;
    onChange: () => void;
}

export function SalaMapa({ localId, resumen, onChange }: Props) {
    const client = useSynaxisClient();
    const [mesas, setMesas] = useState<Mesa[]>([]);
    const [loading, setLoading] = useState(true);
    const [selected, setSelected] = useState<Mesa | null>(null);
    const [busy, setBusy] = useState(false);
    const [showSheet, setShowSheet] = useState(false);

    async function reload() {
        setLoading(true);
        try {
            const all = await listMesas(client, { localId });
            setMesas(all);
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => { reload(); }, [localId]);

    async function handleAddMesa(zoneId: string) {
        setBusy(true);
        try {
            const mesasZona = mesas.filter(m => m.zone_id === zoneId);
            const maxNum = mesasZona.reduce((max, m) => Math.max(max, parseInt(m.numero) || 0), 0);
            await createMesa(client, {
                local_id: localId,
                zone_id: zoneId,
                numero: String(maxNum + 1),
                capacidad: 4,
            });
            await reload();
            onChange();
        } finally {
            setBusy(false);
        }
    }

    async function handleRegenerate() {
        if (!selected) return;
        setBusy(true);
        try {
            const updated = await regenerateMesaQr(client, selected.id);
            setSelected(updated);
            await reload();
        } finally {
            setBusy(false);
        }
    }

    async function handleDelete() {
        if (!selected) return;
        if (!confirm(`¿Borrar Mesa ${selected.numero}? Sus QRs dejarán de funcionar.`)) return;
        setBusy(true);
        try {
            await deleteMesa(client, selected.id);
            setSelected(null);
            await reload();
            onChange();
        } finally {
            setBusy(false);
        }
    }

    async function handleCapacidad(nueva: number) {
        if (!selected) return;
        const updated = await updateMesa(client, selected.id, { capacidad: nueva });
        setSelected(updated);
        await reload();
    }

    if (showSheet) {
        return (
            <SalaQrSheet
                localNombre={localId}
                zonas={resumen.zonas}
                mesas={mesas}
                onClose={() => setShowSheet(false)}
            />
        );
    }

    return (
        <div>
            <div className="sm-zona-header" style={{ marginBottom: 0 }}>
                <div>
                    <div className="db-card-title">Tu sala</div>
                    <div className="db-card-sub">
                        {resumen.zonas.length} zonas · {resumen.mesas_total} mesas
                    </div>
                </div>
                {resumen.mesas_total > 0 && (
                    <button
                        className="db-btn db-btn--ghost"
                        onClick={() => setShowSheet(true)}
                        disabled={loading}
                    >Imprimir QRs</button>
                )}
            </div>

            {loading && <div className="db-ia-status"><div className="db-ia-dot" />Cargando…</div>}

            {!loading && resumen.zonas.map(z => {
                const mesasZona = mesas.filter(m => m.zone_id === z.id);
                return (
                    <div key={z.id} className="sm-zona-block">
                        <div className="sm-zona-header">
                            <h3 className="sm-zona-title">{z.nombre}</h3>
                            <span className="sm-zona-count">{mesasZona.length} mesas</span>
                        </div>
                        <div className="sm-mesas-grid">
                            {mesasZona.map(m => (
                                <button key={m.id} className="sm-mesa-card" onClick={() => setSelected(m)}>
                                    <div className="sm-mesa-numero">{m.numero}</div>
                                    <div className="sm-mesa-cap">{m.capacidad} pax</div>
                                </button>
                            ))}
                            <button
                                className="sm-mesa-card sm-mesa-card--add"
                                onClick={() => handleAddMesa(z.id)}
                                disabled={busy}
                                aria-label="Añadir mesa"
                            >
                                <div className="sm-mesa-add">+</div>
                            </button>
                        </div>
                    </div>
                );
            })}

            {selected && (
                <div className="sm-modal-overlay" onClick={() => setSelected(null)}>
                    <div className="sm-modal" onClick={e => e.stopPropagation()}>
                        <div className="sm-modal-header">
                            <h3>Mesa {selected.numero}</h3>
                            <button className="sm-modal-close" onClick={() => setSelected(null)}>×</button>
                        </div>
                        <div className="sm-modal-body">
                            <div className="sm-field">
                                <label>Capacidad</label>
                                <input
                                    type="number" min={1} max={20}
                                    value={selected.capacidad}
                                    onChange={e => handleCapacidad(parseInt(e.target.value) || 1)}
                                />
                            </div>
                            <div className="sm-field">
                                <label>URL del QR</label>
                                <code className="sm-url">{buildMesaUrl(selected)}</code>
                                <button
                                    className="db-btn db-btn--ghost"
                                    onClick={() => navigator.clipboard?.writeText(buildMesaUrl(selected))}
                                    style={{ marginTop: 6 }}
                                >Copiar</button>
                            </div>
                            <div className="db-btn-group" style={{ marginTop: 18 }}>
                                <button className="db-btn db-btn--ghost" onClick={handleRegenerate} disabled={busy}>
                                    Regenerar QR
                                </button>
                                <button className="db-btn db-btn--danger" onClick={handleDelete} disabled={busy}>
                                    Borrar mesa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
