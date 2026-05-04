import React, { useState } from 'react';
import { Trash2, Plus, Sparkles, ShieldAlert } from 'lucide-react';
import MagicWandButton from './MagicWandButton';

const EP = '/axidb/api/axi.php';

function api(action, data = {}) {
    return fetch(EP, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action, data })
    }).then(r => r.json());
}

export default function AIRevisionPanel({ initialData, localId, onSaved }) {
    const [cats, setCats] = useState(() => {
        const arr = (initialData && initialData.categorias) || [];
        return arr.map(c => ({
            ...c,
            productos: (c.productos || []).map(p => ({
                ...p,
                alergenos: p.alergenos || [],
                imagen_url: p.imagen_url || ''
            }))
        }));
    });
    const [saving, setSaving] = useState(false);
    const [busyCell, setBusyCell] = useState(null);

    const updateCat = (i, patch) => {
        setCats(prev => prev.map((c, idx) => idx === i ? { ...c, ...patch } : c));
    };
    const updateProd = (ci, pi, patch) => {
        setCats(prev => prev.map((c, idx) => idx !== ci ? c : ({
            ...c,
            productos: c.productos.map((p, j) => j === pi ? { ...p, ...patch } : p)
        })));
    };
    const removeProd = (ci, pi) => {
        setCats(prev => prev.map((c, idx) => idx !== ci ? c : ({
            ...c,
            productos: c.productos.filter((_, j) => j !== pi)
        })));
    };
    const addProd = (ci) => {
        setCats(prev => prev.map((c, idx) => idx !== ci ? c : ({
            ...c,
            productos: [...c.productos, { nombre: '', precio: 0, descripcion: '', alergenos: [] }]
        })));
    };

    const generarDescripcion = async (ci, pi) => {
        const p = cats[ci].productos[pi];
        if (!p.nombre) return;
        setBusyCell(`d-${ci}-${pi}`);
        const r = await api('ai_generar_descripcion', { nombre: p.nombre, ingredientes: p.descripcion ? p.descripcion.split(',').map(s => s.trim()) : [] });
        setBusyCell(null);
        if (r.success && r.data && r.data.descripcion) {
            updateProd(ci, pi, { descripcion: r.data.descripcion });
        }
    };

    const detectarAlergenos = async (ci, pi) => {
        const p = cats[ci].productos[pi];
        setBusyCell(`a-${ci}-${pi}`);
        const r = await api('ai_sugerir_alergenos', { nombre: p.nombre, ingredientes: p.descripcion ? p.descripcion.split(',').map(s => s.trim()) : [] });
        setBusyCell(null);
        if (r.success && r.data) {
            updateProd(ci, pi, { alergenos: r.data.alergenos || [], alergenos_revisados: true });
        }
    };

    const guardar = async () => {
        setSaving(true);
        const r = await api('importar_carta_estructurada', { local_id: localId, categorias: cats });
        setSaving(false);
        if (r.success) onSaved && onSaved(r.data);
    };

    return (
        <div className="db-ai-review">
            {cats.map((c, ci) => (
                <div className="db-ai-review__cat" key={ci}>
                    <div className="db-ai-review__cat-head">
                        <input
                            className="db-ai-review__cat-name"
                            value={c.nombre || ''}
                            onChange={e => updateCat(ci, { nombre: e.target.value })}
                            placeholder="Nombre de la categoria"
                        />
                        <button className="db-magic-btn" onClick={() => addProd(ci)}>
                            <Plus size={11} /><span>Plato</span>
                        </button>
                    </div>
                    {(c.productos || []).map((p, pi) => (
                        <div key={pi}>
                            <div className="db-ai-review__row">
                                <input
                                    className="db-ai-review__name"
                                    value={p.nombre || ''}
                                    placeholder="Nombre del plato"
                                    onChange={e => updateProd(ci, pi, { nombre: e.target.value })}
                                />
                                <input
                                    className="db-ai-review__price"
                                    type="number" step="0.01"
                                    value={p.precio || 0}
                                    onChange={e => updateProd(ci, pi, { precio: parseFloat(e.target.value) || 0 })}
                                />
                                <button className="db-ai-review__del" onClick={() => removeProd(ci, pi)}>
                                    <Trash2 size={14} />
                                </button>
                            </div>
                            <div className="db-ai-review__sub">
                                <button
                                    className="db-magic-btn"
                                    onClick={() => generarDescripcion(ci, pi)}
                                    disabled={busyCell === `d-${ci}-${pi}` || !p.nombre}
                                >
                                    {busyCell === `d-${ci}-${pi}` ? <span className="db-ai-spinner" /> : <Sparkles size={11} />}
                                    <span>Descripcion IA</span>
                                </button>
                                <button
                                    className="db-magic-btn"
                                    onClick={() => detectarAlergenos(ci, pi)}
                                    disabled={busyCell === `a-${ci}-${pi}`}
                                >
                                    {busyCell === `a-${ci}-${pi}` ? <span className="db-ai-spinner" /> : <ShieldAlert size={11} />}
                                    <span>Alergenos</span>
                                </button>
                                <MagicWandButton
                                    imageUrl={p.imagen_url}
                                    onEnhanced={(url) => updateProd(ci, pi, { imagen_mejorada_url: url })}
                                />
                                {p.descripcion && <span className="db-ai-review__chip">{p.descripcion.slice(0, 40)}...</span>}
                                {(p.alergenos || []).map(a => (
                                    <span key={a} className="db-ai-review__chip db-ai-review__chip--alergeno">{a}</span>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            ))}
            <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                <button className="db-magic-btn" onClick={guardar} disabled={saving}>
                    {saving ? 'Guardando...' : 'Guardar carta completa'}
                </button>
            </div>
        </div>
    );
}
