/**
 * Cuenta → Cerrar cuenta — doble confirmacion + backup datos.
 *
 * Documentado en wiki art. 10 (GDPR). Aqui mostramos el flujo previo
 * para que el usuario sepa que pasa con sus datos al cerrar.
 */

import { useState } from 'react';

export function CuentaCerrarPage() {
    const [step, setStep] = useState<'info' | 'confirm'>('info');
    const [text, setText] = useState('');
    const expected = 'CERRAR MI CUENTA';

    if (step === 'info') {
        return (
            <div className="db-card db-card--danger-border">
                <div className="db-card-title">Cerrar tu cuenta</div>
                <div className="db-card-sub">Antes de continuar, conoce qué pasa con tus datos.</div>

                <ul className="db-bullets">
                    <li>Se eliminarán <strong>tus locales, cartas, productos, mesas y zonas</strong> de forma permanente tras 30 días.</li>
                    <li>Las facturas emitidas se conservan por obligación legal (5 años, art. 30 CCom).</li>
                    <li>Puedes descargar todos tus datos en JSON antes de cerrar (RGPD art. 20).</li>
                    <li>Tu suscripción se cancela automáticamente; mantienes acceso hasta la próxima renovación.</li>
                </ul>

                <div className="db-btn-group" style={{ marginTop: 18 }}>
                    <button className="db-btn db-btn--ghost" disabled title="Próximamente">
                        Descargar mis datos (JSON)
                    </button>
                    <button className="db-btn db-btn--danger" onClick={() => setStep('confirm')}>
                        Continuar
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="db-card db-card--danger-border">
            <div className="db-card-title">Confirma el cierre</div>
            <div className="db-card-sub">Escribe <code>{expected}</code> para confirmar.</div>

            <input
                type="text"
                className="db-form-input"
                value={text}
                onChange={e => setText(e.target.value)}
                placeholder={expected}
                autoFocus
            />
            <div className="db-btn-group" style={{ marginTop: 14 }}>
                <button className="db-btn db-btn--ghost" onClick={() => setStep('info')}>Atrás</button>
                <button
                    className="db-btn db-btn--danger"
                    disabled={text !== expected}
                    onClick={() => alert('Cierre de cuenta: disponible próximamente. Se requiere endpoint close_account.')}
                >Cerrar cuenta definitivamente</button>
            </div>
        </div>
    );
}
