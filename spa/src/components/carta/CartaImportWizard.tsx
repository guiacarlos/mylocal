/**
 * CartaImportWizard — sube PDF o imagen, el servidor extrae y estructura la carta.
 */

import React, { useRef, useState } from 'react';
import { useSynaxisClient } from '../../hooks/useSynaxis';
import {
    importCartaFromFile,
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
            setStep('extracting');
            setStatusMsg('Procesando carta con IA...');
            const structured = await importCartaFromFile(file);
            setCarta(structured);
            setStep('review');
        } catch (e: unknown) {
            setStatusMsg(humanizeError(e));
            setStep('error');
        }
    }

    function humanizeError(e: unknown): string {
        const raw = e instanceof Error ? e.message : String(e);
        const lower = raw.toLowerCase();

        // Ambos motores fallaron: IA local + Gemini
        if (lower.includes('ia local') && lower.includes('gemini')) {
            const isQuota = lower.includes('429') || lower.includes('quota');
            return isQuota
                ? 'El servidor IA local no responde y Gemini tiene la cuota agotada. Comprueba que el servidor en ai.miaplic.com este en marcha.'
                : 'El servidor IA local no responde y Gemini tampoco pudo procesar el archivo. Comprueba el estado del servidor o intentalo de nuevo.';
        }
        // Solo Gemini con rate limit (IA local no configurada)
        if (lower.includes('429') || lower.includes('quota') || lower.includes('exceeded')) {
            return 'Gemini ha alcanzado su limite de uso. Comprueba que el servidor IA local (ai.miaplic.com) este en marcha, o espera unos minutos.';
        }
        // PDF sin conversor instalado
        if (lower.includes('poppler') || lower.includes('imagick') || lower.includes('ghostscript')) {
            return 'El servidor no tiene instalado el conversor de PDF. Instala poppler-utils o sube la carta como imagen (JPG/PNG).';
        }
        if (lower.includes('network') || lower.includes('failed to fetch') || lower.includes('econnrefused')) {
            return 'No se pudo conectar con el servidor. Comprueba tu conexion e intentalo de nuevo.';
        }
        if (lower.includes('timeout') || lower.includes('timed out')) {
            return 'La IA tardo demasiado en responder. Prueba con una imagen mas pequena o un PDF mas corto.';
        }
        if (lower.includes('500') || lower.includes('internal server')) {
            return 'El servidor tuvo un fallo. Intenta con otra imagen o PDF; si persiste, contacta con soporte.';
        }
        if (raw.length > 200) return raw.slice(0, 200) + '…';
        return raw;
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
            setStatusMsg(humanizeError(e));
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
            <div className="db-review-head">
                <p className="db-card-sub" style={{ margin: 0 }}>
                    Se han detectado <strong>{carta!.categorias.length} categorías</strong> y <strong>{totalProductos} productos</strong>.
                    Revisa y confirma la importación.
                </p>
                <div className="db-btn-group db-review-actions">
                    <button className="db-btn db-btn--ghost" onClick={() => setStep('upload')}>Cancelar</button>
                    <button className="db-btn db-btn--primary" onClick={handleImport}>Guardar</button>
                </div>
            </div>
            <div style={{ maxHeight: 420, overflowY: 'auto', border: '1px solid var(--sp-border)', borderRadius: 8 }}>
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
        </div>
    );
}
