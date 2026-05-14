/**
 * SalaWizard - configura zonas y mesas en 4 pasos.
 *
 * Paso 1: preset rapido (barra / salon / salon+terraza / completo).
 * Paso 2: numero de mesas por zona (slider).
 * Paso 3: revision (renombrar mesas, ajustar capacidad).
 * Paso 4: resumen + finalizar.
 *
 * No depende del Dashboard padre. Recibe localId y onDone callback.
 */

import { useState } from 'react';
import { useSynaxisClient } from '../../../../hooks/useSynaxis';
import {
    createZonasPreset,
    createMesasBatch,
    type Zona,
    type Mesa,
    type Preset,
} from '../../services/sala.service';

type Step = 'preset' | 'mesas' | 'revisar' | 'done' | 'error';

interface Props {
    localId?: string;
    onDone?: (resumen: { zonas: Zona[]; mesas: Mesa[] }) => void;
}

interface MesaPlan {
    zoneId: string;
    zoneNombre: string;
    cantidad: number;
    capacidad: number;
}

const PRESETS: { id: Preset; titulo: string; descripcion: string; emoji: string }[] = [
    { id: 'barra',         titulo: 'Solo barra',     descripcion: 'Bar pequeño, una sola zona de servicio.', emoji: '🍺' },
    { id: 'salon',         titulo: 'Solo salón',     descripcion: 'Restaurante con un único comedor.',       emoji: '🍽️' },
    { id: 'salon_terraza', titulo: 'Salón + Terraza', descripcion: 'Lo más habitual: dos zonas.',             emoji: '☀️' },
    { id: 'completo',      titulo: 'Completo',       descripcion: 'Salón, Terraza, Barra y Reservado.',      emoji: '🏛️' },
];

export function SalaWizard({ localId = 'default', onDone }: Props) {
    const client = useSynaxisClient();
    const [step, setStep] = useState<Step>('preset');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [zonas, setZonas] = useState<Zona[]>([]);
    const [planes, setPlanes] = useState<MesaPlan[]>([]);
    const [mesasCreadas, setMesasCreadas] = useState<Mesa[]>([]);

    async function handlePreset(preset: Preset) {
        setBusy(true); setError(null);
        try {
            const created = await createZonasPreset(client, preset, localId);
            setZonas(created);
            setPlanes(created.map(z => ({
                zoneId: z.id,
                zoneNombre: z.nombre,
                cantidad: 8,
                capacidad: 4,
            })));
            setStep('mesas');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Error creando zonas');
            setStep('error');
        } finally {
            setBusy(false);
        }
    }

    function updatePlan(idx: number, patch: Partial<MesaPlan>) {
        setPlanes(prev => prev.map((p, i) => i === idx ? { ...p, ...patch } : p));
    }

    async function handleCrearMesas() {
        setBusy(true); setError(null);
        try {
            const all: Mesa[] = [];
            for (const plan of planes) {
                if (plan.cantidad < 1) continue;
                const created = await createMesasBatch(client, {
                    zoneId: plan.zoneId,
                    cantidad: plan.cantidad,
                    capacidad: plan.capacidad,
                    localId,
                });
                all.push(...created);
            }
            setMesasCreadas(all);
            setStep('revisar');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Error creando mesas');
            setStep('error');
        } finally {
            setBusy(false);
        }
    }

    function handleFinalizar() {
        setStep('done');
        onDone?.({ zonas, mesas: mesasCreadas });
    }

    if (step === 'error') {
        return (
            <div className="db-card">
                <h3 className="db-card-title">Algo no fue bien</h3>
                <p style={{ color: '#DC2626' }}>{error}</p>
                <button className="db-btn db-btn--ghost" onClick={() => setStep('preset')}>Volver al inicio</button>
            </div>
        );
    }

    if (step === 'preset') {
        return (
            <div>
                <h2 className="db-card-title">Configura tu sala en 30 segundos</h2>
                <p className="db-card-sub">Elige cómo está organizado tu local. Podrás ajustar cualquier detalle después.</p>
                <div className="sw-preset-grid">
                    {PRESETS.map(p => (
                        <button
                            key={p.id}
                            className="sw-preset-card"
                            onClick={() => handlePreset(p.id)}
                            disabled={busy}
                        >
                            <div className="sw-preset-emoji">{p.emoji}</div>
                            <div className="sw-preset-titulo">{p.titulo}</div>
                            <div className="sw-preset-desc">{p.descripcion}</div>
                        </button>
                    ))}
                </div>
                {busy && <p className="db-card-sub" style={{ marginTop: 12 }}>Creando zonas…</p>}
            </div>
        );
    }

    if (step === 'mesas') {
        const totalMesas = planes.reduce((s, p) => s + p.cantidad, 0);
        return (
            <div>
                <h2 className="db-card-title">¿Cuántas mesas en cada zona?</h2>
                <p className="db-card-sub">Las numeramos automáticamente del 1 al N. Podrás renombrarlas después.</p>
                <div className="sw-zonas-list">
                    {planes.map((plan, i) => (
                        <div key={plan.zoneId} className="sw-zona-row">
                            <div className="sw-zona-nombre">{plan.zoneNombre}</div>
                            <div className="sw-zona-controls">
                                <label className="sw-control">
                                    <span>Mesas</span>
                                    <input
                                        type="number" min={0} max={50}
                                        value={plan.cantidad}
                                        onChange={e => updatePlan(i, { cantidad: parseInt(e.target.value) || 0 })}
                                    />
                                </label>
                                <label className="sw-control">
                                    <span>Capacidad</span>
                                    <input
                                        type="number" min={1} max={20}
                                        value={plan.capacidad}
                                        onChange={e => updatePlan(i, { capacidad: parseInt(e.target.value) || 4 })}
                                    />
                                </label>
                            </div>
                        </div>
                    ))}
                </div>
                <div className="db-btn-group" style={{ marginTop: 16 }}>
                    <button className="db-btn db-btn--primary" onClick={handleCrearMesas} disabled={busy || totalMesas === 0}>
                        {busy ? 'Creando…' : `Crear ${totalMesas} mesas`}
                    </button>
                    <button className="db-btn db-btn--ghost" onClick={() => setStep('preset')} disabled={busy}>
                        Atrás
                    </button>
                </div>
            </div>
        );
    }

    if (step === 'revisar') {
        return (
            <div>
                <h2 className="db-card-title">Listo. Aquí tienes tu sala</h2>
                <p className="db-card-sub">
                    Se han creado <strong>{zonas.length} zonas</strong> y <strong>{mesasCreadas.length} mesas</strong>.
                    Cada mesa tiene su QR único listo para imprimir.
                </p>
                <div className="sw-resumen">
                    {zonas.map(z => {
                        const m = mesasCreadas.filter(x => x.zone_id === z.id);
                        return (
                            <div key={z.id} className="sw-resumen-zona">
                                <div className="sw-resumen-titulo">{z.nombre}</div>
                                <div className="sw-resumen-meta">{m.length} mesas</div>
                            </div>
                        );
                    })}
                </div>
                <div className="db-btn-group" style={{ marginTop: 16 }}>
                    <button className="db-btn db-btn--primary" onClick={handleFinalizar}>
                        Continuar
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="db-card">
            <h2 className="db-card-title">Tu sala está configurada</h2>
            <p className="db-card-sub">Ya puedes imprimir los QRs y empezar a operar.</p>
        </div>
    );
}
