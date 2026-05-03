/**
 * CartaImportWizard — flujo OCR de 4 pasos:
 *   1. Subir PDF o foto de la carta actual
 *   2. Extraer texto (Gemini Vision)
 *   3. Estructurar en categorías + productos
 *   4. Revisar e importar
 */

import React, { useRef, useState } from 'react';
import { useSynaxisClient } from '../../hooks/useSynaxis';
import {
    uploadCartaSource,
    ocrExtract,
    ocrParse,
    type CartaStructured,
} from '../../services/carta.service';

type Step = 'upload' | 'extracting' | 'parsing' | 'review' | 'done' | 'error';

interface Props {
    localId?: string;
    onDone?: () => void;
}

export function CartaImportWizard({ localId = 'default', onDone }: Props) {
    const client = useSynaxisClient();
    const inputRef = useRef<HTMLInputElement>(null);
    const [step, setStep] = useState<Step>('upload');
    const [statusMsg, setStatusMsg] = useState('');
    const [carta, setCarta] = useState<CartaStructured | null>(null);
    const [over, setOver] = useState(false);

    async function process(file: File) {
        try {
            // Paso 1 — subir archivo
            setStep('extracting');
            setStatusMsg('Subiendo archivo...');
            const { file_path } = await uploadCartaSource(file);

            // Paso 2 — OCR
            setStatusMsg('Leyendo carta con IA...');
            const extractRes = await ocrExtract(client, file_path);
            if (!extractRes.success) throw new Error(extractRes.error ?? 'Error OCR');
            const rawText = (extractRes.data as { text: string }).text;

            // Paso 3 — parsear estructura
            setStep('parsing');
            setStatusMsg('Estructurando categorías y precios...');
            const structured = await ocrParse(client, rawText);
            if (!structured) throw new Error('No se pudo estructurar la carta');

            setCarta(structured);
            setStep('review');
        } catch (e: unknown) {
            setStatusMsg(e instanceof Error ? e.message : 'Error desconocido');
            setStep('error');
        }
    }

    async function handleImport() {
        if (!carta) return;
        setStep('extracting');
        setStatusMsg('Guardando carta en local...');
        try {
            for (const cat of carta.categorias) {
                const catId = `cat_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`;
                await client.execute({
                    action: 'create',
                    collection: 'carta_categorias',
                    data: { id: catId, local_id: localId, nombre: cat.nombre, orden: 0, disponible: true },
                });
                for (const p of cat.productos) {
                    const pId = `prod_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`;
                    await client.execute({
                        action: 'create',
                        collection: 'carta_productos',
                        data: {
                            id: pId, local_id: localId, categoria_id: catId,
                            nombre: p.nombre, descripcion: p.descripcion,
                            precio: p.precio, alergenos: [], disponible: true,
                            origen_import: 'ocr',
                        },
                    });
                }
            }
            setStep('done');
            onDone?.();
        } catch (e: unknown) {
            setStatusMsg(e instanceof Error ? e.message : 'Error guardando');
            setStep('error');
        }
    }

    function handleDrop(e: React.DragEvent) {
        e.preventDefault();
        setOver(false);
        const file = e.dataTransfer.files[0];
        if (file) process(file);
    }

    function handleFile(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (file) process(file);
    }

    const totalProductos = carta?.categorias.reduce((s, c) => s + c.productos.length, 0) ?? 0;

    if (step === 'upload') return (
        <div>
            <div
                className={`db-dropzone${over ? ' db-dropzone--over' : ''}`}
                onDragOver={e => { e.preventDefault(); setOver(true); }}
                onDragLeave={() => setOver(false)}
                onDrop={handleDrop}
                onClick={() => inputRef.current?.click()}
            >
                <div style={{ fontSize: 32, marginBottom: 8 }}>+</div>
                <div style={{ fontWeight: 600 }}>Sube tu carta actual</div>
                <div className="db-dropzone-hint">PDF, JPG, PNG o WebP. La IA extrae platos y precios.</div>
                <input ref={inputRef} type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" style={{ display: 'none' }} onChange={handleFile} />
            </div>
        </div>
    );

    if (step === 'extracting' || step === 'parsing') return (
        <div className="db-ia-status">
            <div className="db-ia-dot" />
            {statusMsg}
        </div>
    );

    if (step === 'error') return (
        <div>
            <p style={{ color: '#DC2626', marginBottom: 12 }}>{statusMsg}</p>
            <button className="db-btn db-btn--ghost" onClick={() => setStep('upload')}>Intentar de nuevo</button>
        </div>
    );

    if (step === 'done') return (
        <div>
            <p style={{ color: '#166534', fontWeight: 600 }}>Carta importada correctamente.</p>
            <button className="db-btn db-btn--ghost" style={{ marginTop: 12 }} onClick={() => setStep('upload')}>Importar otra</button>
        </div>
    );

    // Review
    return (
        <div>
            <p className="db-card-sub">
                Se han detectado <strong>{carta!.categorias.length} categorías</strong> y <strong>{totalProductos} productos</strong>.
                Revisa y confirma la importación.
            </p>
            <div style={{ maxHeight: 360, overflowY: 'auto', border: '1px solid var(--sp-border)', borderRadius: 8, marginBottom: 16 }}>
                <table className="db-result-table">
                    <thead>
                        <tr><th>Producto</th><th>Precio</th></tr>
                    </thead>
                    <tbody>
                        {carta!.categorias.map(cat => (
                            <React.Fragment key={cat.nombre}>
                                <tr className="db-cat-header">
                                    <td colSpan={2}>{cat.nombre}</td>
                                </tr>
                                {cat.productos.map((p, i) => (
                                    <tr key={i}>
                                        <td>{p.nombre}{p.descripcion ? <span className="db-list-meta"> — {p.descripcion}</span> : null}</td>
                                        <td style={{ whiteSpace: 'nowrap' }}>{p.precio > 0 ? `${p.precio.toFixed(2)} €` : '—'}</td>
                                    </tr>
                                ))}
                            </React.Fragment>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="db-btn-group">
                <button className="db-btn db-btn--primary" onClick={handleImport}>Importar carta</button>
                <button className="db-btn db-btn--ghost" onClick={() => setStep('upload')}>Cancelar</button>
            </div>
        </div>
    );
}
