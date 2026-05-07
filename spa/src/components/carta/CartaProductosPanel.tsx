import React, { useRef, useState } from 'react';
import type { SynaxisClient } from '../../synaxis';
import type { CartaCategoria, CartaProducto } from '../../services/carta.service';
import {
    generarDescripcion,
    sugerirAlergenos,
    generarPromocion,
    enhanceImage,
    uploadCartaSource,
} from '../../services/carta.service';

interface Props {
    client: SynaxisClient;
    categorias: CartaCategoria[];
    productos: CartaProducto[];
    onProductoUpdated: (updated: CartaProducto) => void;
}

type AiOp = 'desc' | 'alerg' | 'promo' | 'enhance';
type Draft = { nombre: string; descripcion: string; precio: string };

export function CartaProductosPanel({ client, categorias, productos, onProductoUpdated }: Props) {
    const [loading, setLoading] = useState<Record<string, AiOp | null>>({});
    const [msgs, setMsgs] = useState<Record<string, string>>({});
    const [activeEnhanceId, setActiveEnhanceId] = useState<string | null>(null);
    const [editingId, setEditingId] = useState<string | null>(null);
    const [editDraft, setEditDraft] = useState<Draft>({ nombre: '', descripcion: '', precio: '' });
    const enhanceRef = useRef<HTMLInputElement>(null);

    const getCatNombre = (id: string) => categorias.find(c => c.id === id)?.nombre ?? id;

    function setLoad(id: string, op: AiOp | null) {
        setLoading(prev => ({ ...prev, [id]: op }));
    }

    function setMsg(id: string, msg: string) {
        setMsgs(prev => ({ ...prev, [id]: msg }));
    }

    async function persist(p: CartaProducto, data: Partial<CartaProducto>) {
        await client.execute({ action: 'update', collection: 'carta_productos', id: p.id, data });
        onProductoUpdated({ ...p, ...data });
    }

    function startEdit(p: CartaProducto) {
        setEditingId(p.id);
        setEditDraft({ nombre: p.nombre, descripcion: p.descripcion ?? '', precio: String(p.precio) });
    }

    function cancelEdit() {
        setEditingId(null);
    }

    async function saveEdit(p: CartaProducto) {
        const precio = parseFloat(editDraft.precio);
        const data = {
            nombre: editDraft.nombre.trim(),
            descripcion: editDraft.descripcion.trim(),
            precio: isNaN(precio) ? p.precio : precio,
        };
        try {
            await persist(p, data);
            setEditingId(null);
            setMsg(p.id, 'Guardado');
        } catch (e: unknown) {
            setMsg(p.id, e instanceof Error ? e.message : 'Error al guardar');
        }
    }

    async function handleDesc(p: CartaProducto) {
        setLoad(p.id, 'desc');
        setMsg(p.id, 'Generando...');
        try {
            const desc = await generarDescripcion(client, p.nombre, []);
            if (desc) {
                await persist(p, { descripcion: desc });
                setMsg(p.id, desc);
            } else {
                setMsg(p.id, 'Sin respuesta');
            }
        } catch (e: unknown) {
            setMsg(p.id, e instanceof Error ? e.message : 'Error');
        }
        setLoad(p.id, null);
    }

    async function handleAlerg(p: CartaProducto) {
        setLoad(p.id, 'alerg');
        setMsg(p.id, 'Detectando...');
        try {
            const res = await sugerirAlergenos(client, p.nombre, []);
            const list = (res.data as { alergenos: string[] })?.alergenos ?? [];
            if (list.length) await persist(p, { alergenos: list });
            setMsg(p.id, list.length ? list.join(', ') : 'Sin alergenos detectados');
        } catch (e: unknown) {
            setMsg(p.id, e instanceof Error ? e.message : 'Error');
        }
        setLoad(p.id, null);
    }

    async function handlePromo(p: CartaProducto) {
        setLoad(p.id, 'promo');
        setMsg(p.id, 'Creando promo...');
        try {
            const promo = await generarPromocion(client, p.nombre, p.descripcion);
            if (promo) {
                await persist(p, { texto_promocional: promo });
                setMsg(p.id, promo);
            } else {
                setMsg(p.id, 'Sin respuesta');
            }
        } catch (e: unknown) {
            setMsg(p.id, e instanceof Error ? e.message : 'Error');
        }
        setLoad(p.id, null);
    }

    async function handleEnhanceFile(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        const id = activeEnhanceId;
        if (!file || !id) return;
        const p = productos.find(x => x.id === id);
        if (!p) return;
        setLoad(id, 'enhance');
        setMsg(id, 'Mejorando imagen...');
        try {
            const { file_path } = await uploadCartaSource(file);
            const url = await enhanceImage(client, file_path);
            if (url) {
                await persist(p, { imagen_url: url });
                setMsg(id, 'Imagen actualizada');
            } else {
                setMsg(id, 'Error al mejorar imagen');
            }
        } catch (err: unknown) {
            setMsg(id, err instanceof Error ? err.message : 'Error');
        }
        setLoad(id, null);
        e.target.value = '';
    }

    if (productos.length === 0) {
        return <p style={{ color: 'var(--sp-text-muted)', fontSize: 'var(--sp-text-sm)' }}>Sin productos. Importa tu carta primero.</p>;
    }

    return (
        <>
            <input type="file" accept="image/*" style={{ display: 'none' }} ref={enhanceRef} onChange={handleEnhanceFile} />
            {productos.map(p => {
                const isEditing = editingId === p.id;
                return (
                    <div key={p.id} className="db-list-item" style={{ flexWrap: 'wrap', gap: 8 }}>
                        {isEditing ? (
                            <div style={{ flex: 1, minWidth: 200, display: 'flex', flexDirection: 'column', gap: 6 }}>
                                <input
                                    className="db-input"
                                    value={editDraft.nombre}
                                    placeholder="Nombre"
                                    onChange={e => setEditDraft(d => ({ ...d, nombre: e.target.value }))}
                                />
                                <input
                                    className="db-input"
                                    value={editDraft.descripcion}
                                    placeholder="Descripcion"
                                    onChange={e => setEditDraft(d => ({ ...d, descripcion: e.target.value }))}
                                />
                                <input
                                    className="db-input"
                                    value={editDraft.precio}
                                    placeholder="Precio"
                                    style={{ maxWidth: 120 }}
                                    onChange={e => setEditDraft(d => ({ ...d, precio: e.target.value }))}
                                />
                                <div className="db-btn-group">
                                    <button className="db-btn db-btn--primary" onClick={() => saveEdit(p)}>Guardar</button>
                                    <button className="db-btn db-btn--ghost" onClick={cancelEdit}>Cancelar</button>
                                </div>
                            </div>
                        ) : (
                            <div style={{ minWidth: 200, flex: 1 }}>
                                <div className="db-list-label">{p.nombre}</div>
                                <div className="db-list-meta">{getCatNombre(p.categoria_id)} — {p.precio.toFixed(2)} €</div>
                                {p.descripcion && <div className="db-list-meta" style={{ marginTop: 2 }}>{p.descripcion}</div>}
                                {msgs[p.id] && <div style={{ fontSize: 'var(--sp-text-xs)', color: '#92651a', marginTop: 4 }}>{msgs[p.id]}</div>}
                            </div>
                        )}
                        {!isEditing && (
                            <div className="db-btn-group">
                                <button className="db-btn db-btn--ghost" onClick={() => startEdit(p)}>Editar</button>
                                <button className="db-btn db-btn--ghost" disabled={!!loading[p.id]} onClick={() => handleDesc(p)}>
                                    {loading[p.id] === 'desc' ? '...' : 'Descripcion'}
                                </button>
                                <button className="db-btn db-btn--ghost" disabled={!!loading[p.id]} onClick={() => handleAlerg(p)}>
                                    {loading[p.id] === 'alerg' ? '...' : 'Alergenos'}
                                </button>
                                <button className="db-btn db-btn--ghost" disabled={!!loading[p.id]} onClick={() => handlePromo(p)}>
                                    {loading[p.id] === 'promo' ? '...' : 'Promo'}
                                </button>
                                <button className="db-btn db-btn--ghost" disabled={!!loading[p.id]} onClick={() => { setActiveEnhanceId(p.id); enhanceRef.current?.click(); }}>
                                    {loading[p.id] === 'enhance' ? '...' : 'Varita'}
                                </button>
                            </div>
                        )}
                    </div>
                );
            })}
        </>
    );
}
