/**
 * SalaMapa - vista de gestion de zonas y mesas.
 *
 * Modelo simple:
 *   - Header con URL del QR default del local (todos los clientes ven la
 *     misma carta) + botones "Anadir estancia" / "Imprimir QRs".
 *   - Cada zona: nombre editable inline, contador de mesas, boton borrar.
 *     Mesas en grid: numero + capacidad. Click abre modal para editar.
 *   - Modal mesa: numero, capacidad, URL especifica (modo avanzado), borrar.
 *
 * El QR default del local se imprime UNA vez y vale para todas las mesas
 * cuando el hostelero solo quiere publicar la carta online.
 */

import { useEffect, useState } from 'react';
import { useSynaxisClient } from '../../hooks/useSynaxis';
import {
    listMesas,
    createMesa,
    updateMesa,
    deleteMesa,
    createZona,
    updateZona,
    deleteZona,
    buildMesaUrl,
    buildLocalCartaUrl,
    type Mesa,
    type SalaResumen,
    type Zona,
} from '../../services/sala.service';
import { SalaQrSheet } from './SalaQrSheet';
import { LocalQrPoster } from './LocalQrPoster';
import { getLocal, updateLocal, localDisplayName, type LocalInfo } from '../../services/local.service';

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
    const [showPoster, setShowPoster] = useState(false);
    const [editingZonaId, setEditingZonaId] = useState<string | null>(null);
    const [editingZonaName, setEditingZonaName] = useState('');

    const [local, setLocal] = useState<LocalInfo | null>(null);
    const [editingNombre, setEditingNombre] = useState(false);
    const [editingTelefono, setEditingTelefono] = useState(false);
    const [draftNombre, setDraftNombre] = useState('');
    const [draftTelefono, setDraftTelefono] = useState('');

    async function reload() {
        setLoading(true);
        try {
            const [all, info] = await Promise.all([
                listMesas(client, { localId }),
                getLocal(client, localId),
            ]);
            setMesas(all);
            setLocal(info);
        } finally {
            setLoading(false);
        }
    }
    useEffect(() => { reload(); }, [localId]);

    async function saveLocalField(field: 'nombre' | 'telefono', value: string) {
        const trimmed = value.trim();
        const current = (local?.[field] ?? '').trim();
        if (trimmed === current) return;
        setBusy(true);
        try {
            const updated = await updateLocal(client, localId, { [field]: trimmed });
            setLocal(updated);
        } finally {
            setBusy(false);
        }
    }

    async function handleAddZona() {
        const nombre = prompt('Nombre de la nueva estancia:', 'Nueva zona');
        if (!nombre || !nombre.trim()) return;
        setBusy(true);
        try {
            await createZona(client, { local_id: localId, nombre: nombre.trim(), icono: 'utensils' });
            onChange();
        } finally {
            setBusy(false);
        }
    }

    async function handleRenameZona(z: Zona) {
        if (!editingZonaName.trim() || editingZonaName === z.nombre) {
            setEditingZonaId(null);
            return;
        }
        setBusy(true);
        try {
            await updateZona(client, z.id, { nombre: editingZonaName.trim() });
            setEditingZonaId(null);
            onChange();
        } finally {
            setBusy(false);
        }
    }

    async function handleDeleteZona(z: Zona) {
        const mesasZona = mesas.filter(m => m.zone_id === z.id);
        const msg = mesasZona.length > 0
            ? `Borrar "${z.nombre}" y sus ${mesasZona.length} mesas?`
            : `Borrar "${z.nombre}"?`;
        if (!confirm(msg)) return;
        setBusy(true);
        try {
            for (const m of mesasZona) await deleteMesa(client, m.id);
            await deleteZona(client, z.id);
            await reload();
            onChange();
        } finally {
            setBusy(false);
        }
    }

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

    async function handleDelete() {
        if (!selected) return;
        if (!confirm(`Borrar Mesa ${selected.numero}?`)) return;
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

    async function handleNumero(nuevo: string) {
        if (!selected || !nuevo.trim()) return;
        const updated = await updateMesa(client, selected.id, { numero: nuevo.trim() });
        setSelected(updated);
        await reload();
    }

    if (showSheet) {
        return (
            <SalaQrSheet
                localNombre={localDisplayName(local)}
                zonas={resumen.zonas}
                mesas={mesas}
                onClose={() => setShowSheet(false)}
            />
        );
    }

    if (showPoster) {
        return <LocalQrPoster local={local} onClose={() => setShowPoster(false)} />;
    }

    const cartaUrl = buildLocalCartaUrl();
    const zonaPorId = new Map(resumen.zonas.map(z => [z.id, z]));
    const zonaDeSelected = selected ? zonaPorId.get(selected.zone_id) : null;
    const nombreLocal = (local?.nombre ?? '').trim();
    const telefonoLocal = (local?.telefono ?? '').trim();

    return (
        <div>
            <div className="sm-local-card">
                <div className="sm-local-row">
                    <label className="sm-local-label">Nombre del local</label>
                    {editingNombre ? (
                        <input
                            className="sm-local-input"
                            value={draftNombre}
                            autoFocus
                            placeholder="Bar de Lola"
                            onChange={e => setDraftNombre(e.target.value)}
                            onBlur={() => { saveLocalField('nombre', draftNombre); setEditingNombre(false); }}
                            onKeyDown={e => {
                                if (e.key === 'Enter') { saveLocalField('nombre', draftNombre); setEditingNombre(false); }
                                if (e.key === 'Escape') setEditingNombre(false);
                            }}
                        />
                    ) : (
                        <button
                            className="sm-local-value"
                            onClick={() => { setDraftNombre(nombreLocal); setEditingNombre(true); }}
                            title="Click para editar"
                        >{nombreLocal || <span className="sm-local-placeholder">Sin nombre · click para editar</span>}</button>
                    )}
                </div>
                <div className="sm-local-row">
                    <label className="sm-local-label">Telefono</label>
                    {editingTelefono ? (
                        <input
                            className="sm-local-input"
                            value={draftTelefono}
                            autoFocus
                            placeholder="+34 600 000 000"
                            onChange={e => setDraftTelefono(e.target.value)}
                            onBlur={() => { saveLocalField('telefono', draftTelefono); setEditingTelefono(false); }}
                            onKeyDown={e => {
                                if (e.key === 'Enter') { saveLocalField('telefono', draftTelefono); setEditingTelefono(false); }
                                if (e.key === 'Escape') setEditingTelefono(false);
                            }}
                        />
                    ) : (
                        <button
                            className="sm-local-value"
                            onClick={() => { setDraftTelefono(telefonoLocal); setEditingTelefono(true); }}
                            title="Click para editar"
                        >{telefonoLocal || <span className="sm-local-placeholder">Sin telefono · click para editar</span>}</button>
                    )}
                </div>
                <div className="sm-local-row">
                    <label className="sm-local-label">URL publica</label>
                    <code className="sm-url">{cartaUrl}</code>
                    <button
                        className="db-btn db-btn--ghost db-btn--sm"
                        onClick={() => navigator.clipboard?.writeText(cartaUrl)}
                    >Copiar</button>
                </div>
                <div className="sm-local-actions">
                    <button className="db-btn db-btn--primary" onClick={() => setShowPoster(true)}>
                        QR principal del local
                    </button>
                </div>
            </div>

            <div className="sm-header">
                <div>
                    <div className="db-card-title">Tu sala</div>
                    <div className="db-card-sub">
                        {resumen.zonas.length} estancias · {resumen.mesas_total} mesas
                    </div>
                </div>
                <div className="sm-header-actions">
                    <button className="db-btn db-btn--ghost" onClick={handleAddZona} disabled={busy}>
                        + Estancia
                    </button>
                    {resumen.mesas_total > 0 && (
                        <button className="db-btn db-btn--ghost" onClick={() => setShowSheet(true)}>
                            Imprimir QRs por mesa
                        </button>
                    )}
                </div>
            </div>

            {loading && <div className="db-ia-status"><div className="db-ia-dot" />Cargando…</div>}

            {!loading && resumen.zonas.map(z => {
                const mesasZona = mesas.filter(m => m.zone_id === z.id);
                const isEditing = editingZonaId === z.id;
                return (
                    <div key={z.id} className="sm-zona-block">
                        <div className="sm-zona-header">
                            {isEditing ? (
                                <input
                                    className="sm-zona-input"
                                    value={editingZonaName}
                                    autoFocus
                                    onChange={e => setEditingZonaName(e.target.value)}
                                    onBlur={() => handleRenameZona(z)}
                                    onKeyDown={e => {
                                        if (e.key === 'Enter') handleRenameZona(z);
                                        if (e.key === 'Escape') setEditingZonaId(null);
                                    }}
                                />
                            ) : (
                                <h3
                                    className="sm-zona-title"
                                    onClick={() => { setEditingZonaId(z.id); setEditingZonaName(z.nombre); }}
                                    title="Click para renombrar"
                                >{z.nombre}</h3>
                            )}
                            <div className="sm-zona-meta">
                                <span className="sm-zona-count">{mesasZona.length} mesas</span>
                                <button
                                    className="sm-zona-del"
                                    onClick={() => handleDeleteZona(z)}
                                    disabled={busy}
                                    title="Borrar estancia"
                                >×</button>
                            </div>
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
                                aria-label="Anadir mesa"
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
                                <label>Numero</label>
                                <input
                                    type="text"
                                    defaultValue={selected.numero}
                                    onBlur={e => handleNumero(e.target.value)}
                                />
                            </div>
                            <div className="sm-field">
                                <label>Capacidad</label>
                                <input
                                    type="number" min={1} max={20}
                                    value={selected.capacidad}
                                    onChange={e => handleCapacidad(parseInt(e.target.value) || 1)}
                                />
                            </div>
                            <div className="sm-field">
                                <label>URL especifica (modo pedidos por mesa)</label>
                                <code className="sm-url">{buildMesaUrl(selected, zonaDeSelected?.nombre)}</code>
                                <button
                                    className="db-btn db-btn--ghost db-btn--sm"
                                    onClick={() => navigator.clipboard?.writeText(buildMesaUrl(selected, zonaDeSelected?.nombre))}
                                    style={{ marginTop: 6 }}
                                >Copiar</button>
                            </div>
                            <div className="db-btn-group" style={{ marginTop: 18 }}>
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
