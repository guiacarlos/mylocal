import React, { useState, useEffect } from 'react';
import { Save, Shield, AlertCircle, CheckCircle, RefreshCw, Upload } from 'lucide-react';

const EP = '/axidb/api/axi.php';

function api(action, data = {}) {
    return fetch(EP, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action, data })
    }).then(r => r.json());
}

export default function FiscalAdmin({ localId }) {
    const [config, setConfig] = useState({
        nif: '', nombre_fiscal: '', domicilio_fiscal: '', cp: '',
        municipio: '', provincia: '', regimen_iva: 'general',
        serie_factura: 'R', modalidad_fiscal: 'ninguna',
        territorio_ticketbai: ''
    });
    const [logs, setLogs] = useState([]);
    const [queueCount, setQueueCount] = useState(0);
    const [saved, setSaved] = useState(false);

    useEffect(() => {
        api('get_fiscal_config', { local_id: localId }).then(r => {
            if (r.success && r.data) setConfig(prev => ({ ...prev, ...r.data }));
        });
        api('get_fiscal_log', { local_id: localId }).then(r => {
            if (r.success) setLogs(r.data || []);
        });
        api('get_fiscal_queue_count', { local_id: localId }).then(r => {
            if (r.success) setQueueCount(r.data?.count || 0);
        });
    }, [localId]);

    const handleSave = async () => {
        const res = await api('save_fiscal_config', { ...config, local_id: localId });
        if (res.success) { setSaved(true); setTimeout(() => setSaved(false), 2000); }
    };

    const handleRetry = async () => {
        await api('process_fiscal_queue', { local_id: localId });
        api('get_fiscal_queue_count', { local_id: localId }).then(r => {
            if (r.success) setQueueCount(r.data?.count || 0);
        });
    };

    const set = (k, v) => setConfig({ ...config, [k]: v });

    return (
        <div className="fiscal-admin">
            <h3><Shield size={16} /> Configuracion fiscal</h3>

            <div className="form-section">
                <label>NIF *</label>
                <input value={config.nif} onChange={e => set('nif', e.target.value)} placeholder="B12345678" />
                <label>Nombre fiscal *</label>
                <input value={config.nombre_fiscal} onChange={e => set('nombre_fiscal', e.target.value)} />
                <label>Domicilio fiscal</label>
                <input value={config.domicilio_fiscal} onChange={e => set('domicilio_fiscal', e.target.value)} />
                <div style={{display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '8px'}}>
                    <div><label>CP</label><input value={config.cp} onChange={e => set('cp', e.target.value)} /></div>
                    <div><label>Municipio</label><input value={config.municipio} onChange={e => set('municipio', e.target.value)} /></div>
                    <div><label>Provincia</label><input value={config.provincia} onChange={e => set('provincia', e.target.value)} /></div>
                </div>
                <label>Regimen IVA</label>
                <select value={config.regimen_iva} onChange={e => set('regimen_iva', e.target.value)}>
                    <option value="general">General</option>
                    <option value="recargo_equivalencia">Recargo de equivalencia</option>
                    <option value="exento">Exento</option>
                </select>
                <label>Serie factura</label>
                <input value={config.serie_factura} onChange={e => set('serie_factura', e.target.value)} maxLength={3} />
                <label>Modalidad fiscal</label>
                <select value={config.modalidad_fiscal} onChange={e => set('modalidad_fiscal', e.target.value)}>
                    <option value="ninguna">Sin facturacion electronica</option>
                    <option value="verifactu">Verifactu (AEAT)</option>
                    <option value="ticketbai">TicketBAI (Pais Vasco / Navarra)</option>
                </select>
                {config.modalidad_fiscal === 'ticketbai' && (
                    <>
                        <label>Territorio</label>
                        <select value={config.territorio_ticketbai} onChange={e => set('territorio_ticketbai', e.target.value)}>
                            <option value="">Seleccionar...</option>
                            <option value="bizkaia">Bizkaia</option>
                            <option value="gipuzkoa">Gipuzkoa</option>
                            <option value="araba">Araba</option>
                            <option value="navarra">Navarra</option>
                        </select>
                    </>
                )}
                <label>Certificado digital (.pfx o .pem)</label>
                <div className="cert-upload">
                    <input type="file" accept=".pfx,.p12,.pem" />
                    <p style={{fontSize: '0.8rem', color: '#666'}}>El certificado se almacena en STORAGE/.vault/cert/ (excluido de git)</p>
                </div>
                <button className="btn-primary" onClick={handleSave}>
                    <Save size={14} /> {saved ? 'Guardado' : 'Guardar configuracion'}
                </button>
            </div>

            <div className="fiscal-status">
                <h4>Estado del servicio</h4>
                {queueCount > 0 ? (
                    <div className="status-warning">
                        <AlertCircle size={16} /> {queueCount} envio(s) en cola
                        <button onClick={handleRetry}><RefreshCw size={14} /> Reintentar</button>
                    </div>
                ) : (
                    <div className="status-ok"><CheckCircle size={16} /> Sin envios pendientes</div>
                )}
            </div>

            <div className="fiscal-log">
                <h4>Ultimos envios</h4>
                {logs.slice(0, 10).map((log, i) => (
                    <div key={i} className="log-entry">
                        <span>{log.timestamp}</span>
                        <span className={log.estado === 'Correcto' ? 'status-ok' : 'status-error'}>
                            {log.estado}
                        </span>
                        {log.csv && <span>CSV: {log.csv}</span>}
                    </div>
                ))}
                {logs.length === 0 && <p>No hay envios registrados.</p>}
            </div>
        </div>
    );
}
