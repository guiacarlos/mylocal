import React, { useState } from 'react';
import { Save, Clock } from 'lucide-react';

export default function KdsConfig({ config, onSave }) {
    const [yellow, setYellow] = useState(config?.yellowMin || 10);
    const [red, setRed] = useState(config?.redMin || 20);
    const [pin, setPin] = useState(config?.pin || '1234');

    const handleSave = () => {
        onSave({ yellowMin: parseInt(yellow), redMin: parseInt(red), pin });
    };

    return (
        <div className="kds-config">
            <h4><Clock size={16} /> Configuracion KDS</h4>
            <label>Alerta amarilla (minutos)</label>
            <input type="number" min="1" value={yellow} onChange={e => setYellow(e.target.value)} />
            <label>Alerta roja (minutos)</label>
            <input type="number" min="1" value={red} onChange={e => setRed(e.target.value)} />
            <label>PIN de acceso cocina</label>
            <input type="password" value={pin} onChange={e => setPin(e.target.value)} maxLength={6} />
            <button className="btn-primary" onClick={handleSave}><Save size={14} /> Guardar</button>
        </div>
    );
}
