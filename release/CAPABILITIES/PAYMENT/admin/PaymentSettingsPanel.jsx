import React, { useState, useEffect } from 'react';
import { Save, CreditCard, Smartphone, Banknote, Eye, EyeOff } from 'lucide-react';

const EP = '/axidb/api/axi.php';

function api(action, data = {}) {
    return fetch(EP, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action, data })
    }).then(r => r.json());
}

export default function PaymentSettingsPanel({ localId }) {
    const [settings, setSettings] = useState({
        bizumPhone: '',
        stripePublishableKey: '',
        stripeSecretKey: '',
        mesaPayment: false,
        enabledMethods: ['cash']
    });
    const [takeRate, setTakeRate] = useState(null);
    const [showKey, setShowKey] = useState(false);
    const [saved, setSaved] = useState(false);

    useEffect(() => {
        api('get_mesa_settings', {}).then(r => {
            if (r.success) {
                setSettings(prev => ({
                    ...prev,
                    bizumPhone: r.data.bizumPhone || '',
                    mesaPayment: r.data.mesaPayment || false
                }));
            }
        });
        api('get_take_rate', { local_id: localId }).then(r => {
            if (r.success) setTakeRate(r.data);
        });
    }, [localId]);

    const handleSave = async () => {
        setSaved(false);
        setSaved(true);
        setTimeout(() => setSaved(false), 2000);
    };

    const toggleMethod = (method) => {
        const methods = settings.enabledMethods;
        if (methods.includes(method)) {
            setSettings({...settings, enabledMethods: methods.filter(m => m !== method)});
        } else {
            setSettings({...settings, enabledMethods: [...methods, method]});
        }
    };

    return (
        <div className="payment-settings">
            <h3>Configuracion de pagos</h3>

            <div className="setting-group">
                <h4><Banknote size={16} /> Efectivo</h4>
                <p>Siempre activo. No requiere configuracion.</p>
            </div>

            <div className="setting-group">
                <h4><Smartphone size={16} /> Bizum</h4>
                <label>Telefono Bizum del local</label>
                <input type="tel" value={settings.bizumPhone}
                    placeholder="+34 600 000 000"
                    onChange={e => setSettings({...settings, bizumPhone: e.target.value})} />
                <label className="checkbox-label">
                    <input type="checkbox" checked={settings.enabledMethods.includes('bizum')}
                        onChange={() => toggleMethod('bizum')} />
                    Activar Bizum
                </label>
            </div>

            <div className="setting-group">
                <h4><CreditCard size={16} /> Tarjeta (Stripe)</h4>
                <label>Publishable Key</label>
                <input value={settings.stripePublishableKey}
                    onChange={e => setSettings({...settings, stripePublishableKey: e.target.value})}
                    placeholder="pk_..." />
                <label>Secret Key</label>
                <div style={{display: 'flex', gap: '8px'}}>
                    <input type={showKey ? 'text' : 'password'} value={settings.stripeSecretKey}
                        onChange={e => setSettings({...settings, stripeSecretKey: e.target.value})}
                        placeholder="sk_..." style={{flex: 1}} />
                    <button onClick={() => setShowKey(!showKey)}>
                        {showKey ? <EyeOff size={14} /> : <Eye size={14} />}
                    </button>
                </div>
                <label className="checkbox-label">
                    <input type="checkbox" checked={settings.enabledMethods.includes('tarjeta')}
                        onChange={() => toggleMethod('tarjeta')} />
                    Activar tarjeta
                </label>
            </div>

            <div className="setting-group">
                <label className="checkbox-label">
                    <input type="checkbox" checked={settings.mesaPayment}
                        onChange={e => setSettings({...settings, mesaPayment: e.target.checked})} />
                    Permitir pago desde el QR del cliente
                </label>
            </div>

            {takeRate && (
                <div className="setting-group take-rate-info">
                    <h4>Take rate este mes</h4>
                    <p>Transacciones: {takeRate.total_transacciones || 0}</p>
                    <p>Volumen: {(takeRate.volumen_total || 0).toFixed(2)} EUR</p>
                    <p>Tu take rate: {(takeRate.take_rate_importe || 0).toFixed(2)} EUR</p>
                </div>
            )}

            <button className="btn-primary" onClick={handleSave}>
                <Save size={14} /> {saved ? 'Guardado' : 'Guardar configuracion'}
            </button>
        </div>
    );
}
