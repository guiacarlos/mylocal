import React, { useState, useRef } from 'react';
import { Upload, FileText, CheckCircle2, AlertCircle } from 'lucide-react';

const EP = '/axidb/api/axi.php';

function postFile(action, file, extra = {}) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('action', action);
    Object.keys(extra).forEach(k => fd.append(k, extra[k]));
    return fetch(EP, { method: 'POST', body: fd, credentials: 'include' }).then(r => r.json());
}

function pollJob(jobId, onTick, intervalMs = 1500, timeoutMs = 90000) {
    const start = Date.now();
    return new Promise((resolve, reject) => {
        const tick = async () => {
            if (Date.now() - start > timeoutMs) return reject(new Error('Tiempo agotado'));
            try {
                const r = await fetch(EP, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ action: 'job_status', data: { id: jobId } })
                }).then(x => x.json());
                if (r.success && r.data) {
                    onTick && onTick(r.data);
                    if (r.data.state === 'done') return resolve(r.data);
                    if (r.data.state === 'failed') return reject(new Error(r.data.last_error || 'Job failed'));
                }
            } catch (e) { /* reintentar */ }
            setTimeout(tick, intervalMs);
        };
        tick();
    });
}

export default function ImportUploader({ localId, onParsed }) {
    const [drag, setDrag] = useState(false);
    const [state, setState] = useState('idle'); // idle | uploading | parsing | done | error
    const [progress, setProgress] = useState(0);
    const [message, setMessage] = useState('');
    const inputRef = useRef(null);

    const reset = () => { setState('idle'); setProgress(0); setMessage(''); };

    const handleFile = async (file) => {
        if (!file) return;
        const ok = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'].includes(file.type);
        if (!ok) { setState('error'); setMessage('Formato no soportado. Usa PDF, JPG, PNG o WEBP.'); return; }
        setState('uploading'); setProgress(15); setMessage('Subiendo archivo...');
        const upload = await postFile('upload_carta_source', file, { local_id: localId });
        if (!upload.success) { setState('error'); setMessage(upload.error || 'Error subiendo archivo'); return; }

        setState('parsing'); setProgress(40); setMessage('Leyendo tu carta...');
        const queue = await fetch(EP, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ action: 'enqueue_ocr', data: { file_path: upload.path, local_id: localId } })
        }).then(r => r.json());
        if (!queue.success) { setState('error'); setMessage(queue.error || 'Error encolando'); return; }

        try {
            const job = await pollJob(queue.id, (j) => {
                if (j.state === 'running') { setProgress(70); setMessage('Estructurando platos...'); }
            });
            setState('done'); setProgress(100); setMessage('Carta lista para revisar');
            onParsed && onParsed(job.result?.data || job.result);
        } catch (e) {
            setState('error'); setMessage(e.message || 'Error procesando');
        }
    };

    const onDrop = (e) => {
        e.preventDefault(); setDrag(false);
        if (e.dataTransfer.files && e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
    };
    const onDragOver = (e) => { e.preventDefault(); setDrag(true); };
    const onDragLeave = (e) => { e.preventDefault(); setDrag(false); };
    const onPick = (e) => { if (e.target.files && e.target.files[0]) handleFile(e.target.files[0]); };

    const showProgress = state === 'uploading' || state === 'parsing';

    return (
        <div className="db-ai-uploader">
            <div
                className={'db-ai-uploader__dropzone' + (drag ? ' db-ai-uploader__dropzone--active' : '')}
                onDrop={onDrop} onDragOver={onDragOver} onDragLeave={onDragLeave}
                onClick={() => inputRef.current && inputRef.current.click()}
                role="button" tabIndex={0}
            >
                {state === 'done'
                    ? <CheckCircle2 className="db-ai-uploader__icon" style={{ color: 'var(--ai-success)' }} />
                    : state === 'error'
                        ? <AlertCircle className="db-ai-uploader__icon" style={{ color: 'var(--ai-danger)' }} />
                        : <Upload className="db-ai-uploader__icon" />}
                <div className="db-ai-uploader__title">
                    {state === 'idle' && 'Sube tu carta actual'}
                    {state === 'uploading' && 'Subiendo...'}
                    {state === 'parsing' && 'Procesando con IA...'}
                    {state === 'done' && 'Listo'}
                    {state === 'error' && 'No ha sido posible'}
                </div>
                <div className="db-ai-uploader__hint">
                    {state === 'idle' && 'Arrastra una foto o PDF aqui (max 10 MB) - tambien puedes hacer clic'}
                    {state !== 'idle' && message}
                </div>
                <input
                    ref={inputRef} type="file" hidden
                    accept="application/pdf,image/jpeg,image/png,image/webp"
                    onChange={onPick}
                />
            </div>
            {showProgress && (
                <>
                    <div className="db-ai-uploader__progress">
                        <div className="db-ai-uploader__progress-bar" style={{ width: progress + '%' }} />
                    </div>
                    <div className="db-ai-uploader__status">
                        <span className="db-ai-spinner"></span>
                        <span>{message}</span>
                    </div>
                </>
            )}
            {state === 'done' && (
                <div className="db-ai-uploader__status db-ai-uploader__status--ok">
                    <FileText size={14} /> <span>Pasa al siguiente paso para revisar</span>
                </div>
            )}
            {state === 'error' && (
                <div className="db-ai-uploader__status db-ai-uploader__status--err">
                    <AlertCircle size={14} /> <span>{message} <button onClick={reset} className="db-magic-btn">Reintentar</button></span>
                </div>
            )}
        </div>
    );
}
