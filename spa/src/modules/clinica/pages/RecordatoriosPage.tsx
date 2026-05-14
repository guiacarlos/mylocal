import { useEffect, useState } from 'react';
import { useDashboard, LOCAL_ID } from '../../../components/dashboard/DashboardContext';
import { listNotifLog, type NotifLog } from '../services/clinica.service';
import { CheckCircle, XCircle, Send } from 'lucide-react';

function fmtTs(ts: string): string {
    if (!ts) return '';
    const d = new Date(ts);
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

export function RecordatoriosPage() {
    const { client } = useDashboard();
    const [logs, setLogs] = useState<NotifLog[]>([]);
    const [loading, setLoading] = useState(true);
    const [dest, setDest] = useState('');
    const [asunto, setAsunto] = useState('');
    const [cuerpo, setCuerpo] = useState('');
    const [sending, setSending] = useState(false);
    const [sendErr, setSendErr] = useState('');

    function load() {
        setLoading(true);
        listNotifLog(client, LOCAL_ID).then(setLogs).catch(() => setLogs([])).finally(() => setLoading(false));
    }

    useEffect(load, [client]);

    async function handleSend() {
        if (!dest || !asunto) { setSendErr('Destinatario y asunto son obligatorios.'); return; }
        setSending(true); setSendErr('');
        try {
            const res = await client.execute({
                action: 'notif_send',
                data: { destinatario: dest, asunto, cuerpo, meta: { local_id: LOCAL_ID } },
            });
            if (!res.success) throw new Error(res.error ?? 'Error al enviar');
            setDest(''); setAsunto(''); setCuerpo('');
            load();
        } catch (e: unknown) { setSendErr(e instanceof Error ? e.message : 'Error.'); }
        finally { setSending(false); }
    }

    return (
        <div className="db-card">
            <div className="db-card-title">Recordatorios</div>
            <div className="db-card-sub">Historial de notificaciones enviadas a pacientes.</div>

            <div className="cl-form" style={{ marginBottom: 20 }}>
                <div className="cl-form-row">
                    <div><label className="cl-label">Destinatario</label><input className="cl-input" placeholder="email o teléfono" value={dest} onChange={e => setDest(e.target.value)} /></div>
                    <div><label className="cl-label">Asunto</label><input className="cl-input" placeholder="Recordatorio de cita" value={asunto} onChange={e => setAsunto(e.target.value)} /></div>
                </div>
                <div><label className="cl-label">Mensaje</label><input className="cl-input" placeholder="Texto de la notificación" value={cuerpo} onChange={e => setCuerpo(e.target.value)} /></div>
                {sendErr && <p style={{ color: '#dc2626', fontSize: 13 }}>{sendErr}</p>}
                <div>
                    <button className="db-btn db-btn--primary" disabled={sending} onClick={handleSend}><Send size={14} /> {sending ? 'Enviando…' : 'Enviar recordatorio'}</button>
                </div>
            </div>

            {loading && <div className="db-ia-status"><div className="db-ia-dot" />Cargando historial…</div>}

            {!loading && logs.length === 0 && (
                <p style={{ color: 'var(--sp-text-muted)', fontSize: 13 }}>
                    No se han enviado recordatorios todavía.
                </p>
            )}

            {!loading && logs.map(l => (
                <div key={l.id} className="db-list-item">
                    <div style={{ flex: 1 }}>
                        <div className="db-list-label">{l.asunto}</div>
                        <div className="db-list-meta">{l.destinatario} · {fmtTs(l.ts)} · {l.driver}</div>
                    </div>
                    {l.enviado
                        ? <span className="cl-notif-ok"><CheckCircle size={14} style={{ verticalAlign: 'middle' }} /> Enviado</span>
                        : <span className="cl-notif-fail"><XCircle size={14} style={{ verticalAlign: 'middle' }} /> Fallido</span>
                    }
                </div>
            ))}
        </div>
    );
}
